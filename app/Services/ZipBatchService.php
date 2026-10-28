<?php

namespace App\Services;

use App\Models\Document;
use App\Services\ActivityService;
use App\Services\BlockchainService;
use App\Services\OcrService;
use App\Services\BarcodeWatermarkService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ZipBatchService
{
    // ─── File filter constants ────────────────────────────────────────────────
    const VALID_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];

    /** Patterns in a file path that signal system/junk files to skip. */
    const SKIP_PATTERNS = ['__MACOSX', '.DS_Store', '.gitkeep', 'Thumbs.db', '.gitignore'];

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Main entry point. Accepts the uploaded ZIP file and processes every
     * valid image/PDF inside it sequentially.
     *
     * Returns a structured report array:
     * [
     *   'total'   => int,
     *   'success' => int,
     *   'failed'  => int,
     *   'items'   => [ ...success|failed detail objects ],
     * ]
     */
    public static function processBatch(UploadedFile $zipFile, string $uploaderEmail): array
    {
        $report = [
            'total'   => 0,
            'success' => 0,
            'failed'  => 0,
            'items'   => [],
        ];

        // 1. Create isolated temp directory inside storage/app
        $batchId  = Str::uuid()->toString();
        $tempDir  = storage_path("app/zip_temp/{$batchId}");

        try {
            // 2. Extract ZIP → temp dir
            $extracted = self::extractZip($zipFile->getRealPath(), $tempDir);

            if (empty($extracted)) {
                throw new \RuntimeException('ZIP tidak mengandung file gambar/PDF yang valid.');
            }

            $report['total'] = count($extracted);

            // 3. Loop every valid file
            foreach ($extracted as $fileInfo) {
                try {
                    $item = self::processOneFile($fileInfo, $uploaderEmail);
                    $report['items'][]  = $item;
                    $report['success'] += 1;
                } catch (\Throwable $e) {
                    Log::error("[ZipBatch] Gagal proses file: {$fileInfo['original_name']} — {$e->getMessage()}");
                    $report['items'][] = [
                        'status'    => 'failed',
                        'file_name' => $fileInfo['original_name'],
                        'error'     => $e->getMessage(),
                    ];
                    $report['failed'] += 1;
                }
            }
        } catch (\Throwable $e) {
            Log::error("[ZipBatch] Fatal error: {$e->getMessage()}");
            throw $e; // Re-throw so controller can return a proper 422/500
        } finally {
            // 4. Always clean up temp directory
            self::cleanupDir($tempDir);
        }

        return $report;
    }

    // ─── Step 1: Extract ZIP ──────────────────────────────────────────────────

    /**
     * Opens the ZIP archive and extracts only valid image/PDF files.
     * Returns array of ['original_name' => string, 'temp_path' => string, 'size' => int].
     *
     * @throws \RuntimeException if the ZIP cannot be opened.
     */
    private static function extractZip(string $zipPath, string $destDir): array
    {
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new \RuntimeException("Gagal membuka file ZIP (kode error: {$result}). File mungkin rusak atau bukan format ZIP.");
        }

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        // Collect metadata BEFORE extracting (ZipArchive stat is fast)
        $candidateMeta = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat      = $zip->statIndex($i);
            $nameInZip = $stat['name'];

            // Skip directories
            if (str_ends_with($nameInZip, '/') || str_ends_with($nameInZip, '\\')) {
                continue;
            }
            // Skip system/junk files
            if (self::isJunkFile($nameInZip)) {
                Log::info("[ZipBatch] Abaikan file sampah: {$nameInZip}");
                continue;
            }
            // Only allow valid extensions
            $ext = strtolower(pathinfo($nameInZip, PATHINFO_EXTENSION));
            if (!in_array($ext, self::VALID_EXTENSIONS, true)) {
                Log::info("[ZipBatch] Abaikan ekstensi tidak didukung: {$nameInZip}");
                continue;
            }

            $candidateMeta[$i] = [
                'name_in_zip' => $nameInZip,
                'base_name'   => basename($nameInZip),
                'size'        => $stat['size'],
                'extension'   => $ext,
            ];
        }

        // Extract ALL at once into the temp dir (preserves internal structure)
        $zip->extractTo($destDir);
        $zip->close();

        // Now resolve the actual paths on disk
        $validFiles = [];
        foreach ($candidateMeta as $meta) {
            // Path as laid out by ZipArchive (may include subdirs from inside the ZIP)
            $extractedPath = $destDir . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $meta['name_in_zip']);

            if (!file_exists($extractedPath)) {
                Log::warning("[ZipBatch] File diekstrak tapi tidak ditemukan di disk: {$meta['name_in_zip']}");
                continue;
            }

            $validFiles[] = [
                'original_name' => $meta['base_name'],
                'temp_path'     => $extractedPath,
                'size'          => $meta['size'],
                'extension'     => $meta['extension'],
            ];
        }

        Log::info("[ZipBatch] Ekstraksi selesai. File valid ditemukan: " . count($validFiles));
        return $validFiles;
    }

    // ─── Step 2: Process one file (OCR → DB → Blockchain) ────────────────────

    /**
     * Processes a single extracted file through the full pipeline:
     *   A. OCR via OcrService (Groq/EasyOCR)
     *   B. Compute file hash & build metadata
     *   C. Save Document to database
     *   D. Register on Hyperledger Fabric blockchain
     *
     * @throws \Throwable on any unrecoverable error for this file.
     */
    private static function processOneFile(array $fileInfo, string $uploaderEmail): array
    {
        $origName = $fileInfo['original_name'];
        $tempPath = $fileInfo['temp_path'];
        $ext      = $fileInfo['extension'];

        Log::info("[ZipBatch] Memproses: {$origName}");

        // ── A. Read file & prepare Base64 ─────────────────────────────────────
        $rawContent = file_get_contents($tempPath);
        if ($rawContent === false) {
            throw new \RuntimeException("Tidak dapat membaca file dari disk: {$tempPath}");
        }

        $base64   = base64_encode($rawContent);
        $mimeType = self::extToMime($ext);

        // ── B. Compute file hash (SHA-256) ────────────────────────────────────
        $fileHash = '0x' . hash('sha256', $rawContent);

        // ── C. Run OCR ────────────────────────────────────────────────────────
        Log::info("[ZipBatch] Menjalankan OCR untuk: {$origName}");
        $ocrResult = OcrService::analyze($base64, $origName, $mimeType);

        $metadata   = $ocrResult['metadata']  ?? [];
        $rawOcrText = $ocrResult['rawText']   ?? ($ocrResult['logs'] ? implode("\n", $ocrResult['logs']) : '');

        // ── D. Build metadata hash (file hash + OCR result hash) ──────────────
        $metaPayload = json_encode([
            'file_name'   => $origName,
            'file_size'   => $fileInfo['size'],
            'upload_date' => now()->toISOString(),
            'file_hash'   => $fileHash,
            'ocr_text'    => $rawOcrText,
            'metadata'    => $metadata,
        ], JSON_UNESCAPED_UNICODE);

        $metadataHash = '0x' . hash('sha256', $metaPayload);

        // ── E. Persist Document to DB ─────────────────────────────────────────
        // Store the actual file in permanent storage
        $storedPath = self::storeFilePermanently($tempPath, $origName, $ext);

        $doc = Document::create([
            'document_type'    => $metadata['documentType']   ?? 'Dokumen Lainnya',
            'owner_name'       => $metadata['ownerName']       ?? '-',
            'document_number'  => $metadata['documentNumber']  ?? ('DOC-' . date('Y') . '-' . strtoupper(Str::random(6))),
            'nisn'             => $metadata['nisn']             ?? '',
            'year'             => $metadata['year']             ?? date('Y'),
            'description'      => ($metadata['description']    ?? '') . " | Batch Upload | OCR Hash: {$metadataHash}",
            'hash'             => $fileHash,
            'file_name'        => $origName,
            'file_mime'        => $mimeType,
            'file_path'        => $storedPath,
            'uploaded_by'      => $uploaderEmail,
            'blockchain_tx_id' => '',
            'status'           => 'Menunggu Verifikasi',
        ]);

        // ── F. Register to Blockchain ─────────────────────────────────────────
        $blockchainEntry = null;
        $txId = '';

        try {
            $blockchainEntry = BlockchainService::register($doc);
            $txId            = $blockchainEntry->tx_id;
            $doc->update(['blockchain_tx_id' => $txId]);
            Log::info("[ZipBatch] Berhasil didaftarkan ke Blockchain. TX: {$txId}");
        } catch (\Throwable $e) {
            // Blockchain failure is non-fatal — document is already in DB
            Log::warning("[ZipBatch] Blockchain register gagal untuk {$origName}: {$e->getMessage()}");
            $txId = 'BLOCKCHAIN_ERROR';
        }

        // ── G. Stempel Barcode / QR Code Verifikasi Otomatis ──────────────────
        if ($doc->file_path && Storage::exists($doc->file_path)) {
            try {
                $fullDiskPath = Storage::path($doc->file_path);
                $verifyUrl = route('verify.hash', ['hash' => $doc->blockchain_tx_id ?: $doc->hash]);
                BarcodeWatermarkService::stampDocument($fullDiskPath, $doc->file_mime, $doc, $verifyUrl);
                $finalHash = '0x' . hash_file('sha256', $fullDiskPath);
                $doc->update(['hash' => $finalHash]);
                $fileHash = $finalHash;
                Log::info("[ZipBatch] Barcode berhasil dibubuhkan untuk: {$origName}");
            } catch (\Throwable $e) {
                Log::warning("[ZipBatch] Gagal membubuhkan barcode: " . $e->getMessage());
            }
        }

        // Log activity
        ActivityService::log(
            "Batch Upload: {$doc->owner_name}",
            "{$doc->document_type} — {$doc->document_number}",
            'upload'
        );

        return [
            'status'        => 'success',
            'file_name'     => $origName,
            'document_id'   => $doc->id,
            'owner_name'    => $doc->owner_name,
            'document_type' => $doc->document_type,
            'document_number' => $doc->document_number,
            'file_hash'     => $fileHash,
            'metadata_hash' => $metadataHash,
            'tx_id'         => $txId,
            'blockchain_status' => ($txId && $txId !== 'BLOCKCHAIN_ERROR') ? 'Registered' : 'Pending',
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Copy the temp file into permanent Laravel storage and return the stored path. */
    private static function storeFilePermanently(string $tempPath, string $origName, string $ext): string
    {
        $safeBase = Str::slug(pathinfo($origName, PATHINFO_FILENAME));
        $destName = $safeBase . '_' . Str::random(8) . '.' . $ext;
        $destPath = 'documents/' . $destName;
        Storage::put($destPath, file_get_contents($tempPath));
        return $destPath;
    }

    /** Returns true if the file path contains any junk pattern. */
    private static function isJunkFile(string $nameInZip): bool
    {
        foreach (self::SKIP_PATTERNS as $pattern) {
            if (str_contains($nameInZip, $pattern)) {
                return true;
            }
        }
        // Also skip files starting with a dot (hidden files)
        if (str_starts_with(basename($nameInZip), '.')) {
            return true;
        }
        return false;
    }

    /** Map file extension to MIME type string for the OCR API. */
    private static function extToMime(string $ext): string
    {
        return match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'pdf'         => 'application/pdf',
            default       => 'application/octet-stream',
        };
    }

    /** Recursively delete a directory and all its contents. */
    private static function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($dir);
            Log::info("[ZipBatch] Temp dir berhasil dibersihkan: {$dir}");
        } catch (\Throwable $e) {
            Log::warning("[ZipBatch] Gagal membersihkan temp dir {$dir}: {$e->getMessage()}");
        }
    }
}

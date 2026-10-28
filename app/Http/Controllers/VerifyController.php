<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\SystemSetting;
use App\Services\BlockchainService;
use App\Services\OcrService;
use App\Services\ActivityService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerifyController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::getAll();
        return view('verify.index', compact('settings'));
    }

    public function process(Request $request)
    {
        $request->validate([
            'document' => 'required|file|max:20480|mimes:pdf,jpg,jpeg,png',
        ]);

        $file     = $request->file('document');
        $hash     = BlockchainService::hashFile($file);
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        // Simpan file sementara agar bisa dipratinjau di hasil verifikasi
        $tempId   = 'temp_' . uniqid();
        $ext      = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $tempPath = 'temp_verify/' . $tempId . '.' . $ext;
        try {
            Storage::put($tempPath, file_get_contents($file->getRealPath()));
        } catch (\Throwable $e) {}

        // Read base64 for OCR
        $base64 = base64_encode(file_get_contents($file->getRealPath()));

        // Run OCR (Fast Local OCR)
        $ocrResult = OcrService::analyze($base64, $fileName, $mimeType);

        // Check hash on blockchain
        $matchedDoc = BlockchainService::verifyHash($hash);

        // Jika tidak ketemu via hash mentah, cari di database
        if (!$matchedDoc) {
            $matchedDoc = Document::where('hash', $hash)
                ->orWhere('document_number', $hash)
                ->orWhere('blockchain_tx_id', $hash)
                ->first();
        }

        $isValid = false;
        $isPending = false;
        $statusNote = '';

        if ($matchedDoc) {
            if ($matchedDoc->status === 'Terverifikasi') {
                $isValid = true;
                $statusNote = 'DOKUMEN ASLI & TERVERIFIKASI';
            } elseif ($matchedDoc->status === 'Menunggu Verifikasi') {
                $isPending = true;
                $statusNote = 'DOKUMEN TERDAFTAR (MENUNGGU PERSETUJUAN RESMI SEKOLAH)';
            } else {
                $statusNote = 'DOKUMEN DITOLAK OLEH SEKOLAH';
            }
        } else {
            $statusNote = 'DOKUMEN PALSU / TIDAK TERDAFTAR';
        }

        // Save to verify history
        $historyItem = [
            'id'           => 'VRF-' . time(),
            'file_name'    => $fileName,
            'hash'         => $hash,
            'result'       => $isValid ? 'Valid' : ($isPending ? 'Menunggu Persetujuan' : 'Tidak Valid'),
            'owner_name'   => $matchedDoc?->owner_name ?? ($ocrResult['metadata']['ownerName'] ?? ''),
            'doc_type'     => $matchedDoc?->document_type ?? ($ocrResult['metadata']['documentType'] ?? ''),
            'timestamp'    => now()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
        ];

        $history = session('verify_history', []);
        array_unshift($history, $historyItem);
        session(['verify_history' => array_slice($history, 0, 50)]);

        ActivityService::log(
            'Verifikasi Dokumen: ' . $fileName,
            $fileName,
            'verify',
            null
        );

        // Store result in session and redirect
        session([
            'verify_result' => [
                'hash'         => $hash,
                'file_name'    => $fileName,
                'ocr'          => $ocrResult,
                'matched_doc'  => $matchedDoc?->toArray(),
                'is_valid'     => $isValid,
                'is_pending'   => $isPending,
                'status_note'  => $statusNote,
                'temp_preview' => [
                    'id'   => $tempId,
                    'path' => $tempPath,
                    'name' => $fileName,
                    'mime' => $mimeType,
                ],
            ],
        ]);

        if ($isValid) {
            return redirect()->route('verify.result', ['status' => 'valid']);
        } elseif ($isPending) {
            return redirect()->route('verify.result', ['status' => 'pending']);
        }
        return redirect()->route('verify.result', ['status' => 'invalid']);
    }

    public function result(Request $request)
    {
        $verifyResult = session('verify_result');
        if (!$verifyResult) {
            return redirect()->route('verify.index');
        }
        $isValid     = $verifyResult['is_valid'] ?? false;
        $isPending   = $verifyResult['is_pending'] ?? false;
        $statusNote  = $verifyResult['status_note'] ?? '';
        $matchedDoc  = $verifyResult['matched_doc'] ?? null;
        $hash        = $verifyResult['hash'];
        $fileName    = $verifyResult['file_name'];
        $ocr         = $verifyResult['ocr'];
        $tempPreview = $verifyResult['temp_preview'] ?? null;

        $previewData = $this->resolvePreviewData($matchedDoc, $tempPreview, $fileName);

        return view('verify.result', array_merge(
            compact('isValid', 'isPending', 'statusNote', 'matchedDoc', 'hash', 'fileName', 'ocr'),
            $previewData
        ));
    }

    public function history()
    {
        $history = session('verify_history', []);
        return view('verify.history', compact('history'));
    }

    /**
     * Verifikasi instan dari pemindaian Barcode / QR Code.
     * Langsung menentukan apakah dokumen ASLI atau PALSU.
     */
    public function verifyByHash(Request $request, string $hash)
    {
        $cleanHash = trim($hash);

        // Cari dokumen berdasarkan hash (Fabric Blockchain / Database)
        $matchedDoc = BlockchainService::verifyHash($cleanHash);

        // Jika tidak ketemu via hash mentah, coba cari berdasarkan nomor dokumen atau id
        if (!$matchedDoc) {
            $matchedDoc = Document::where('hash', $cleanHash)
                ->orWhere('document_number', $cleanHash)
                ->orWhere('blockchain_tx_id', $cleanHash)
                ->first();
        }

        $isValid = false;
        $isPending = false;
        $statusNote = '';

        if ($matchedDoc) {
            // Dokumen ditemukan di Blockchain / Database
            if ($matchedDoc->status === 'Terverifikasi') {
                $isValid = true;
                $statusNote = 'DOKUMEN ASLI & TERVERIFIKASI';
            } elseif ($matchedDoc->status === 'Menunggu Verifikasi') {
                $isPending = true;
                $statusNote = 'DOKUMEN TERDAFTAR (MENUNGGU PERSETUJUAN RESMI SEKOLAH)';
            } else {
                $statusNote = 'DOKUMEN DITOLAK OLEH SEKOLAH';
            }

            ActivityService::log(
                "Scan Barcode: Dokumen {$matchedDoc->owner_name} ({$statusNote})",
                "No. Dokumen: {$matchedDoc->document_number} — TX: " . substr($matchedDoc->blockchain_tx_id, 0, 16),
                'verify',
                null
            );
        } else {
            // Dokumen TIDAK DITEMUKAN = DOKUMEN PALSU / TIDAK VALID
            $statusNote = 'DOKUMEN PALSU / TIDAK TERDAFTAR';

            ActivityService::log(
                "Scan Barcode: Gagal / Palsu",
                "Percobaan scan hash tidak terdaftar: {$cleanHash}",
                'verify',
                null
            );
        }

        // Simpan ke riwayat sesi verifikasi
        $historyItem = [
            'id'           => 'SCAN-' . time(),
            'file_name'    => $matchedDoc ? ($matchedDoc->file_name ?: $matchedDoc->document_number) : 'Pemindaian Barcode QR',
            'hash'         => $matchedDoc ? $matchedDoc->hash : $cleanHash,
            'result'       => $isValid ? 'Valid' : ($isPending ? 'Menunggu Persetujuan' : 'Tidak Valid'),
            'owner_name'   => $matchedDoc?->owner_name ?? 'Tidak Dikenal',
            'doc_type'     => $matchedDoc?->document_type ?? 'Tidak Terdaftar',
            'timestamp'    => now()->timezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
            'source'       => 'QR Barcode Scan',
        ];

        $history = session('verify_history', []);
        array_unshift($history, $historyItem);
        session(['verify_history' => array_slice($history, 0, 50)]);

        // Siapkan session verify_result agar konsisten
        $verifyResultData = [
            'hash'        => $matchedDoc ? $matchedDoc->hash : $cleanHash,
            'file_name'   => $matchedDoc ? ($matchedDoc->file_name ?: $matchedDoc->document_number) : 'Berkas Fisik (Scan Barcode)',
            'ocr'         => null,
            'matched_doc' => $matchedDoc?->toArray(),
            'is_valid'    => $isValid,
            'is_pending'  => $isPending,
            'is_scan'     => true,
            'status_note' => $statusNote,
        ];

        session(['verify_result' => $verifyResultData]);

        $fileName   = $verifyResultData['file_name'];
        $hash       = $verifyResultData['hash'];
        $matchedDoc = $verifyResultData['matched_doc'];
        $ocr        = null;
        $isScan     = true;

        $previewData = $this->resolvePreviewData($matchedDoc, null, $fileName);

        return view('verify.result', array_merge(
            compact('isValid', 'isPending', 'matchedDoc', 'hash', 'fileName', 'ocr', 'isScan', 'statusNote'),
            $previewData
        ));
    }

    /**
     * Stream pratinjau file dokumen publik (PDF atau Gambar) untuk halaman verifikasi.
     */
    public function previewFile(string $id)
    {
        // 1. Cek jika file sementara dari sesi verifikasi upload
        if (str_starts_with($id, 'temp_')) {
            $tempData = session('verify_result.temp_preview');
            if ($tempData && $tempData['id'] === $id && Storage::exists($tempData['path'])) {
                $path = Storage::path($tempData['path']);
                $mime = $tempData['mime'] ?: (mime_content_type($path) ?: 'application/pdf');
                $ext  = pathinfo($tempData['name'], PATHINFO_EXTENSION) ?: 'pdf';
                return response()->file($path, [
                    'Content-Type'        => $mime,
                    'Content-Disposition' => 'inline; filename="' . ($tempData['name'] ?: 'preview.' . $ext) . '"',
                    'Cache-Control'       => 'no-store, no-cache, must-revalidate',
                ]);
            }
        }

        // 2. Cari dokumen resmi di database
        $document = Document::find($id);
        if (!$document) {
            $document = Document::where('hash', $id)
                ->orWhere('blockchain_tx_id', $id)
                ->orWhere('document_number', $id)
                ->first();
        }

        if (!$document) {
            abort(404, 'Dokumen tidak ditemukan.');
        }

        $noCacheHeaders = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ];

        $exts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $fileNameExt = strtolower(pathinfo($document->file_name ?: '', PATHINFO_EXTENSION));
        if ($fileNameExt) {
            $exts = array_unique(array_merge([$fileNameExt], $exts));
        }

        // Prioritas 1: File berstempel barcode resmi di storage lokal
        foreach ($exts as $e) {
            $stampedPath = 'documents/' . $document->id . '_stamped.' . $e;
            if (Storage::exists($stampedPath)) {
                $path = Storage::path($stampedPath);
                $mime = mime_content_type($path) ?: ($document->file_mime ?: 'application/pdf');
                return response()->file($path, array_merge($noCacheHeaders, [
                    'Content-Type'        => $mime,
                    'Content-Disposition' => 'inline; filename="' . ($document->file_name ?: 'dokumen.' . $e) . '"',
                ]));
            }
        }

        // Prioritas 2: File di Google Drive
        if ($document->isGdriveFile()) {
            $fileId = $document->getGdriveFileId();
            if ($fileId) {
                $gdrive = new GoogleDriveService();
                if ($gdrive->isReady()) {
                    $content = $gdrive->readFileContent($fileId);
                    if ($content !== null) {
                        $mime     = $document->file_mime ?: 'application/pdf';
                        $fileName = $document->file_name ?: 'dokumen.pdf';
                        return response($content, 200, array_merge($noCacheHeaders, [
                            'Content-Type'        => $mime,
                            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                        ]));
                    }
                }
            }
        }

        // Prioritas 3: File path di local storage
        if ($document->file_path && Storage::exists($document->file_path)) {
            $path     = Storage::path($document->file_path);
            $mime     = mime_content_type($path) ?: ($document->file_mime ?: 'application/pdf');
            $fileName = $document->file_name ?: basename($path);
            return response()->file($path, array_merge($noCacheHeaders, [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]));
        }

        // Prioritas 4: File original di local storage
        foreach ($exts as $e) {
            $originalPath = 'documents/' . $document->id . '_original.' . $e;
            if (Storage::exists($originalPath)) {
                $path     = Storage::path($originalPath);
                $mime     = mime_content_type($path) ?: ($document->file_mime ?: 'application/pdf');
                $fileName = $document->file_name ?: ('dokumen.' . $e);
                return response()->file($path, array_merge($noCacheHeaders, [
                    'Content-Type'        => $mime,
                    'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                ]));
            }
        }

        abort(404, 'File dokumen fisik tidak ditemukan di server arsip.');
    }

    /**
     * Resolusi data file pratinjau dokumen untuk halaman hasil verifikasi.
     */
    protected function resolvePreviewData($matchedDoc, $tempPreview = null, $fallbackName = 'Dokumen')
    {
        $hasPreviewFile  = false;
        $previewUrl      = null;
        $previewType     = 'none'; // 'pdf', 'image', 'none'
        $previewFileName = $fallbackName;
        $previewDocId    = null;

        // 1. Cek dari matchedDoc terdaftar
        if ($matchedDoc && !empty($matchedDoc['id'])) {
            $doc = Document::find($matchedDoc['id']);
            if ($doc) {
                $exts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
                $fileNameExt = strtolower(pathinfo($doc->file_name ?: '', PATHINFO_EXTENSION));
                if ($fileNameExt) {
                    $exts = array_unique(array_merge([$fileNameExt], $exts));
                }

                $foundPath = null;
                $foundExt  = null;

                // Cek stamped file
                foreach ($exts as $e) {
                    $testPath = 'documents/' . $doc->id . '_stamped.' . $e;
                    if (Storage::exists($testPath)) {
                        $foundPath = $testPath;
                        $foundExt  = $e;
                        break;
                    }
                }

                // Cek file_path di database
                if (!$foundPath && $doc->file_path && Storage::exists($doc->file_path)) {
                    $foundPath = $doc->file_path;
                    $foundExt  = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                }

                // Cek original file
                if (!$foundPath) {
                    foreach ($exts as $e) {
                        $testPath = 'documents/' . $doc->id . '_original.' . $e;
                        if (Storage::exists($testPath)) {
                            $foundPath = $testPath;
                            $foundExt  = $e;
                            break;
                        }
                    }
                }

                if ($foundPath || $doc->isGdriveFile()) {
                    $hasPreviewFile  = true;
                    $previewDocId    = $doc->id;
                    $previewFileName = $doc->file_name ?: $fallbackName;
                    $previewUrl      = route('verify.preview', ['id' => $doc->id]);

                    $detectedExt = $foundExt ?: ($fileNameExt ?: 'pdf');
                    $isPdf = $detectedExt === 'pdf' || str_contains(strtolower($doc->file_mime ?? ''), 'pdf');
                    $previewType = $isPdf ? 'pdf' : 'image';
                }
            }
        }

        // 2. Fallback dari temp upload jika ada
        if (!$hasPreviewFile && $tempPreview && !empty($tempPreview['path']) && Storage::exists($tempPreview['path'])) {
            $ext = strtolower(pathinfo($tempPreview['name'], PATHINFO_EXTENSION) ?: 'pdf');
            $hasPreviewFile  = true;
            $previewDocId    = $tempPreview['id'];
            $previewFileName = $tempPreview['name'];
            $previewUrl      = route('verify.preview', ['id' => $tempPreview['id']]);
            $isPdf           = $ext === 'pdf' || str_contains(strtolower($tempPreview['mime'] ?? ''), 'pdf');
            $previewType     = $isPdf ? 'pdf' : 'image';
        }

        return compact('hasPreviewFile', 'previewUrl', 'previewType', 'previewFileName', 'previewDocId');
    }
}

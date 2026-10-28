<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class BarcodeWatermarkService
{
    /**
     * Stempel QR Code dan Badge Verifikasi Blockchain ke berkas dokumen (PDF atau Gambar).
     *
     * @param string $absoluteFilePath Path absolut ke file di disk
     * @param string $mimeType Tipe MIME file (cth: application/pdf, image/jpeg, image/png)
     * @param Document $document Model dokumen terkait
     * @param string $verifyUrl URL tujuan pemindaian QR code
     * @param float|null $customXRatio Koordinat horizontal pilihan pengguna (0.0 - 1.0)
     * @param float|null $customYRatio Koordinat vertikal pilihan pengguna (0.0 - 1.0)
     * @param int|null $customPage Nomor halaman target untuk PDF
     * @return bool Berhasil atau tidak
     */
    public static function stampDocument(
        string $absoluteFilePath,
        string $mimeType,
        Document $document,
        string $verifyUrl,
        ?float $customXRatio = null,
        ?float $customYRatio = null,
        ?int $customPage = null
    ): bool {
        if (!file_exists($absoluteFilePath)) {
            Log::warning("[BarcodeWatermark] File tidak ditemukan di path: {$absoluteFilePath}");
            return false;
        }

        $mime = strtolower($mimeType);

        try {
            if (str_contains($mime, 'pdf')) {
                return self::stampPdf($absoluteFilePath, $document, $verifyUrl, $customXRatio, $customYRatio, $customPage);
            } elseif (str_contains($mime, 'image') || str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || str_contains($mime, 'png')) {
                return self::stampImage($absoluteFilePath, $document, $verifyUrl, $customXRatio, $customYRatio);
            }
        } catch (\Throwable $e) {
            Log::error("[BarcodeWatermark] Gagal membubuhkan barcode: " . $e->getMessage(), [
                'doc_id' => $document->id,
                'trace'  => $e->getTraceAsString(),
            ]);
        }

        return false;
    }

    /**
     * Membubuhkan stempel QR code dan identitas verifikasi ke berkas PDF menggunakan FPDI.
     */
    public static function stampPdf(
        string $pdfPath,
        Document $document,
        string $verifyUrl,
        ?float $customXRatio = null,
        ?float $customYRatio = null,
        ?int $customPage = null
    ): bool {
        // Pastikan pustaka FPDF & FPDI ter-load
        self::ensurePdfLibraries();

        if (!class_exists(\setasign\Fpdi\Fpdi::class)) {
            Log::error("[BarcodeWatermark] Kelas FPDI tidak tersedia.");
            return false;
        }

        // Generate QR code PNG
        $qrPngBinary = QrCodeService::generatePng($verifyUrl, 300);
        $tempQrFile  = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tempQrFile, $qrPngBinary);

        $tempOutFile = tempnam(sys_get_temp_dir(), 'stamped_') . '.pdf';

        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);

            // Tentukan posisi stempel: jika pengguna menentukan koordinat manual, gunakan langsung
            $sigPos = null;
            if ($customXRatio !== null && $customYRatio !== null) {
                $sigPos = [
                    'x_ratio' => (float)$customXRatio,
                    'y_ratio' => (float)$customYRatio,
                    'method'  => 'manual_user_placement',
                    'found'   => true,
                ];
            }

            // Halaman target: jika ditentukan oleh pengguna, gunakan halaman tersebut; default halaman terakhir
            $targetPage = ($customPage && $customPage <= $pageCount && $customPage >= 1) ? $customPage : $pageCount;

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $pdf->importPage($pageNo);
                $size   = $pdf->getTemplateSize($tplIdx);

                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplIdx);

                // Bubuhkan barcode di halaman target
                if ($pageNo === $targetPage) {
                    if ($sigPos === null) {
                        $sigPos = self::detectSignatureSlot($pdfPath, 'application/pdf', (float)$size['width'], (float)$size['height']);
                    }
                    self::drawPdfStampBadge($pdf, $size['width'], $size['height'], $tempQrFile, $document, $sigPos);
                }
            }

            $pdf->Output('F', $tempOutFile);

            // Gantikan file asli dengan file yang sudah distamp
            if (file_exists($tempOutFile) && filesize($tempOutFile) > 0) {
                copy($tempOutFile, $pdfPath);
                @unlink($tempOutFile);
                @unlink($tempQrFile);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error("[BarcodeWatermark] Gagal proses PDF: " . $e->getMessage());
            @unlink($tempOutFile);
            @unlink($tempQrFile);
            return false;
        }

        @unlink($tempQrFile);
        return false;
    }

    /**
     * Menggambar HANYA QR Code murni pada dokumen PDF di area tanda tangan pejabat hasil deteksi AI.
     */
    private static function drawPdfStampBadge(\setasign\Fpdi\Fpdi $pdf, float $pageWidth, float $pageHeight, string $qrImagePath, Document $document, ?array $sigPos = null): void
    {
        $qrSize = 22; // mm (ukuran standar TTE digital Indonesia)
        $padding = 1.0; // mm margin putih tipis di sekeliling QR

        if ($sigPos && isset($sigPos['x_ratio'], $sigPos['y_ratio'])) {
            $x = ($pageWidth * $sigPos['x_ratio']) - ($qrSize / 2);
            $y = ($pageHeight * $sigPos['y_ratio']) - ($qrSize / 2);
        } else {
            if ($pageWidth < $pageHeight) {
                $x = ($pageWidth * 0.635) - ($qrSize / 2);
                $y = ($pageHeight * 0.740) - ($qrSize / 2);
            } else {
                $x = ($pageWidth * 0.720) - ($qrSize / 2);
                $y = ($pageHeight * 0.700) - ($qrSize / 2);
            }
        }

        // Batasi agar tidak terpotong tepi halaman
        $x = max(10, min($pageWidth - $qrSize - 10, $x));
        $y = max(10, min($pageHeight - $qrSize - 10, $y));

        // Cetak HANYA modul barcode transparan tanpa background
        $pdf->Image($qrImagePath, $x, $y, $qrSize, $qrSize);
    }

    /**
     * Membubuhkan QR Code ke berkas gambar (JPG/PNG) di area tanda tangan pejabat hasil deteksi AI atau koordinat manual.
     */
    public static function stampImage(
        string $imagePath,
        Document $document,
        string $verifyUrl,
        ?float $customXRatio = null,
        ?float $customYRatio = null
    ): bool {
        $info = @getimagesize($imagePath);
        if (!$info) {
            return false;
        }

        $origWidth  = $info[0];
        $origHeight = $info[1];
        $mime       = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $img = @imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $img = @imagecreatefrompng($imagePath);
                break;
            default:
                return false;
        }

        if (!$img) {
            return false;
        }

        // Tentukan posisi stempel: jika pengguna menentukan koordinat manual, gunakan langsung
        $sigPos = null;
        if ($customXRatio !== null && $customYRatio !== null) {
            $sigPos = [
                'x_ratio' => (float)$customXRatio,
                'y_ratio' => (float)$customYRatio,
                'method'  => 'manual_user_placement',
                'found'   => true,
            ];
        } else {
            // Deteksi posisi tanda tangan dengan AI (PyMuPDF / OpenCV / Smart Fallback)
            $sigPos = self::detectSignatureSlot($imagePath, $mime, (float)$origWidth, (float)$origHeight);
        }

        // Generate QR code binary murni dengan background transparan (tanpa latar belakang)
        $qrPngBinary = QrCodeService::generatePng($verifyUrl, 320);
        $qrImg = @imagecreatefromstring($qrPngBinary);
        if (!$qrImg) {
            imagedestroy($img);
            return false;
        }

        // Ukuran QR Code proporsional (sekitar 13.5% dari lebar gambar)
        $qrSize = (int)($origWidth * 0.135);
        $qrSize = max(110, min(240, $qrSize));

        if ($sigPos && isset($sigPos['x_ratio'], $sigPos['y_ratio'])) {
            $x = (int)(($origWidth * $sigPos['x_ratio']) - ($qrSize / 2));
            $y = (int)(($origHeight * $sigPos['y_ratio']) - ($qrSize / 2));
        } else {
            if ($origWidth < $origHeight) {
                $x = (int)(($origWidth * 0.635) - ($qrSize / 2));
                $y = (int)(($origHeight * 0.740) - ($qrSize / 2));
            } else {
                $x = (int)(($origWidth * 0.720) - ($qrSize / 2));
                $y = (int)(($origHeight * 0.700) - ($qrSize / 2));
            }
        }

        // Pastikan tidak keluar kanvas gambar
        $x = max(10, min($origWidth - $qrSize - 10, $x));
        $y = max(10, min($origHeight - $qrSize - 10, $y));

        // Aktifkan alpha blending agar modul barcode transparan tanpa background menyatu langsung ke dokumen
        imagealphablending($img, true);

        // Tempelkan HANYA modul QR Code transparan (tanpa kotak background)
        imagecopyresampled(
            $img,
            $qrImg,
            $x,
            $y,
            0,
            0,
            $qrSize,
            $qrSize,
            imagesx($qrImg),
            imagesy($qrImg)
        );

        // Simpan kembali gambar
        if ($mime === 'image/png') {
            imagepng($img, $imagePath);
        } else {
            imagejpeg($img, $imagePath, 95);
        }

        imagedestroy($img);
        imagedestroy($qrImg);
        return true;
    }

    /**
     * Memanggil microservice AI untuk mendeteksi posisi tanda tangan / stempel secara akurat.
     * Fallback ke posisi standar tanda tangan surat resmi Indonesia jika AI offline.
     */
    public static function detectSignatureSlot(string $filePath, string $mimeType, float $pageWidth, float $pageHeight): array
    {
        $isPortrait = ($pageWidth < $pageHeight);
        $defaultXRatio = $isPortrait ? 0.635 : 0.720;
        $defaultYRatio = $isPortrait ? 0.740 : 0.700;

        try {
            $ocrUrl = env('OCR_SERVICE_URL', 'http://127.0.0.1:5000');
            $response = \Illuminate\Support\Facades\Http::timeout(4)->post("{$ocrUrl}/api/detect-signature", [
                'file_path' => $filePath,
                'mime_type' => $mimeType,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['success']) && isset($data['x_ratio'], $data['y_ratio'])) {
                    Log::info("[BarcodeWatermark] AI mendeteksi tanda tangan", [
                        'method'  => $data['method'] ?? 'unknown',
                        'found'   => $data['found'] ?? false,
                        'x_ratio' => $data['x_ratio'],
                        'y_ratio' => $data['y_ratio'],
                    ]);
                    return [
                        'x_ratio' => (float)$data['x_ratio'],
                        'y_ratio' => (float)$data['y_ratio'],
                        'method'  => $data['method'] ?? 'ai',
                        'found'   => (bool)($data['found'] ?? false),
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[BarcodeWatermark] AI signature detection error: " . $e->getMessage());
        }

        return [
            'x_ratio' => $defaultXRatio,
            'y_ratio' => $defaultYRatio,
            'method'  => 'smart_default_signature_slot',
            'found'   => false,
        ];
    }

    /**
     * Memastikan class FPDF & FPDI dapat digunakan secara aman.
     */
    public static function ensurePdfLibraries(): void
    {
        if (!class_exists(\FPDF::class)) {
            $fpdfPath = base_path('vendor/setasign/fpdf/fpdf.php');
            if (file_exists($fpdfPath)) {
                require_once $fpdfPath;
            }
        }

        if (!class_exists(\setasign\Fpdi\Fpdi::class)) {
            $fpdiAutoload = base_path('vendor/setasign/fpdi/src/autoload.php');
            if (file_exists($fpdiAutoload)) {
                require_once $fpdiAutoload;
            }
        }
    }
}

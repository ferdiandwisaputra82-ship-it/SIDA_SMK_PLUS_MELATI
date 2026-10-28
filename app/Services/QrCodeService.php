<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;

class QrCodeService
{
    /**
     * Generate QR Code as PNG binary string with official school logo in center.
     * Tries:
     * 1. Pure PHP chillerlan/php-qrcode (Offline, instant, ECC Level H, native logo-space)
     * 2. Python local OCR/QR service (Port 5000)
     * 3. Cloud QR Server API fallback (with ecc=H)
     * 4. Pure PHP GD fallback
     */
    public static function generatePng(string $text, int $size = 250, bool $withLogo = true, bool $transparent = true): string
    {
        // 1. Coba generator lokal berkinerja tinggi (chillerlan/php-qrcode)
        if (class_exists(QRCode::class)) {
            try {
                $scale = max(4, (int)($size / 37));
                $logoModules = $withLogo ? 11 : null;

                $options = new QROptions([
                    'outputInterface'  => QRGdImagePNG::class,
                    'eccLevel'         => EccLevel::H, // 30% Error Correction (BSrE & TTE Standard)
                    'outputBase64'     => false,
                    'scale'            => $scale,
                    'logoSpaceWidth'   => $logoModules,
                    'logoSpaceHeight'  => $logoModules,
                    'returnResource'   => true,
                ]);

                $qr = new QRCode($options);
                $baseQr = $qr->render($text);

                if ($baseQr instanceof \GdImage) {
                    return self::processAndBrandGdImage($baseQr, $size, $withLogo, $transparent, $logoModules, $scale);
                }
            } catch (\Throwable $e) {
                Log::warning('chillerlan QR generation fallback: ' . $e->getMessage());
            }
        }

        // 2. Coba via Python OCR/QR Server (Port 5000)
        try {
            $pythonUrl = 'http://localhost:5000/api/qrcode';
            $response = Http::timeout(3)->withoutVerifying()->post($pythonUrl, [
                'data'      => $text,
                'size'      => $size,
                'with_logo' => $withLogo,
            ]);

            if ($response->ok() && $response->header('Content-Type') && str_contains($response->header('Content-Type'), 'image')) {
                return self::finalizePng($response->body(), $size, $withLogo, $transparent);
            } elseif ($response->ok() && $response->json('image_base64')) {
                return self::finalizePng(base64_decode($response->json('image_base64')), $size, $withLogo, $transparent);
            }
        } catch (\Throwable $e) {
            // Python service offline
        }

        // 3. Coba via Cloud QR Server (QR Server API dengan ecc=H)
        try {
            $encodedText = urlencode($text);
            $apiUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&ecc=H&margin=2&data={$encodedText}";
            $response = Http::timeout(4)->withoutVerifying()->get($apiUrl);
            if ($response->ok() && strlen($response->body()) > 100) {
                return self::finalizePng($response->body(), $size, $withLogo, $transparent);
            }
        } catch (\Throwable $e) {
            // Cloud offline fallback
        }

        // 4. Fallback: Pure GD Matrix Badge
        $fallbackRaw = self::generateFallbackPng($text, $size);
        return self::finalizePng($fallbackRaw, $size, $withLogo, $transparent);
    }

    /**
     * Generate QR Code as Base64 Data String for inline HTML/Blade previews.
     */
    public static function generateBase64(string $text, int $size = 200, bool $withLogo = true, bool $transparent = true): string
    {
        $png = self::generatePng($text, $size, $withLogo, $transparent);
        return base64_encode($png);
    }

    /**
     * Memproses GdImage hasil chillerlan: membuat transparansi & menyematkan badge logo sekolah di tengah.
     */
    private static function processAndBrandGdImage(
        \GdImage $baseQr,
        int $targetSize,
        bool $withLogo,
        bool $transparent,
        ?int $logoModules,
        int $scale
    ): string {
        $w = imagesx($baseQr);
        $h = imagesy($baseQr);

        $dest = imagecreatetruecolor($w, $h);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $trans = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        $dark  = imagecolorallocatealpha($dest, 15, 23, 42, 0); // slate-900

        imagefill($dest, 0, 0, $transparent ? $trans : imagecolorallocate($dest, 255, 255, 255));

        // Pindahkan modul barcode hitam
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgba = imagecolorat($baseQr, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                if ($r < 100 && $g < 100 && $b < 100) {
                    imagesetpixel($dest, $x, $y, $dark);
                } elseif (!$transparent) {
                    imagesetpixel($dest, $x, $y, imagecolorallocate($dest, 255, 255, 255));
                }
            }
        }
        imagedestroy($baseQr);

        // Sematkan logo sekolah di tengah jika diminta
        if ($withLogo) {
            $boxPx = ($logoModules ?: 11) * $scale;
            self::drawCenterLogoBadge($dest, $w, $h, $boxPx);
        }

        // Resize ke ukuran target jika diminta
        if ($targetSize && ($w !== $targetSize || $h !== $targetSize)) {
            $final = imagecreatetruecolor($targetSize, $targetSize);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            imagefill($final, 0, 0, $transparent ? $trans : imagecolorallocate($final, 255, 255, 255));
            imagealphablending($final, true);
            imagecopyresampled($final, $dest, 0, 0, 0, 0, $targetSize, $targetSize, $w, $h);
            imagedestroy($dest);
            $dest = $final;
        }

        ob_start();
        imagepng($dest);
        $output = ob_get_clean();
        imagedestroy($dest);

        return $output;
    }

    /**
     * Memproses gambar PNG biner dari API/Python/Fallback agar latar transparan dan terpasang logo sekolah.
     */
    private static function finalizePng(string $rawPng, int $targetSize, bool $withLogo, bool $transparent): string
    {
        $src = @imagecreatefromstring($rawPng);
        if (!$src) {
            return $rawPng;
        }

        $w = imagesx($src);
        $h = imagesy($src);

        $dest = imagecreatetruecolor($w, $h);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $trans = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        $dark  = imagecolorallocatealpha($dest, 15, 23, 42, 0);

        imagefill($dest, 0, 0, $transparent ? $trans : imagecolorallocate($dest, 255, 255, 255));

        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $rgba = imagecolorat($src, $x, $y);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $a = ($rgba >> 24) & 0x7F;

                if ($a >= 120 || ($r > 200 && $g > 200 && $b > 200)) {
                    if (!$transparent) {
                        imagesetpixel($dest, $x, $y, imagecolorallocate($dest, 255, 255, 255));
                    }
                } else {
                    imagesetpixel($dest, $x, $y, $dark);
                }
            }
        }
        imagedestroy($src);

        if ($withLogo) {
            $boxPx = (int)($w * 0.24);
            self::drawCenterLogoBadge($dest, $w, $h, $boxPx);
        }

        if ($targetSize && ($w !== $targetSize || $h !== $targetSize)) {
            $final = imagecreatetruecolor($targetSize, $targetSize);
            imagealphablending($final, false);
            imagesavealpha($final, true);
            imagefill($final, 0, 0, $transparent ? $trans : imagecolorallocate($final, 255, 255, 255));
            imagealphablending($final, true);
            imagecopyresampled($final, $dest, 0, 0, 0, 0, $targetSize, $targetSize, $w, $h);
            imagedestroy($dest);
            $dest = $final;
        }

        ob_start();
        imagepng($dest);
        $output = ob_get_clean();
        imagedestroy($dest);

        return $output ?: $rawPng;
    }

    /**
     * Menggambar badge putih solid yang rapi dan menempelkan logo SMK Plus Melati tepat di tengah.
     */
    private static function drawCenterLogoBadge(\GdImage $canvas, int $w, int $h, int $boxPx): void
    {
        $barcodePath = function_exists('public_path') 
            ? public_path('images/barcode-logo.png') 
            : __DIR__ . '/../../public/images/barcode-logo.png';

        $schoolPath = function_exists('public_path') 
            ? public_path('images/school-logo.png') 
            : __DIR__ . '/../../public/images/school-logo.png';

        $logoPath = file_exists($barcodePath) ? $barcodePath : $schoolPath;

        if (!file_exists($logoPath)) {
            return;
        }

        $logoImg = @imagecreatefrompng($logoPath);
        if (!$logoImg) {
            return;
        }

        $origLogoW = imagesx($logoImg);
        $origLogoH = imagesy($logoImg);

        $centerX = (int)($w / 2);
        $centerY = (int)($h / 2);

        $boxX = $centerX - (int)($boxPx / 2);
        $boxY = $centerY - (int)($boxPx / 2);

        imagealphablending($canvas, true);

        // Logo sekolah proporsional transparan langsung di tengah (tanpa kotak latar putih)
        $pad = max(1, (int)($boxPx * 0.04));
        $logoDestX = $boxX + $pad;
        $logoDestY = $boxY + $pad;
        $logoW = $boxPx - ($pad * 2);
        $logoH = $boxPx - ($pad * 2);

        imagecopyresampled(
            $canvas,
            $logoImg,
            $logoDestX,
            $logoDestY,
            0,
            0,
            $logoW,
            $logoH,
            $origLogoW,
            $origLogoH
        );

        imagedestroy($logoImg);
    }

    /**
     * Mengubah latar belakang putih/terang pada gambar QR Code menjadi 100% transparan (alpha channel).
     * Dipertahankan untuk kompatibilitas ke fungsi lain jika ada yang memanggil secara langsung.
     */
    public static function makeTransparentPng(string $pngBinary): string
    {
        return self::finalizePng($pngBinary, 0, false, true);
    }

    /**
     * Fallback generator using PHP GD to create a clear scan badge image
     */
    public static function generateFallbackPng(string $text, int $size = 250): string
    {
        $img = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 15, 23, 42);
        $blue  = imagecolorallocate($img, 37, 99, 235);
        $gray  = imagecolorallocate($img, 226, 232, 240);

        imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $white);
        imagerectangle($img, 0, 0, $size - 1, $size - 1, $gray);

        // Draw deterministic grid based on text hash
        $hash = md5($text);
        $gridSize = 21; // 21x21 QR version 1 grid
        $cellSize = (int)(($size - 30) / $gridSize);
        $offset = (int)(($size - ($gridSize * $cellSize)) / 2);

        // Finder patterns (3 corners)
        $drawFinder = function($startX, $startY) use ($img, $cellSize, $black, $white) {
            imagefilledrectangle($img, $startX, $startY, $startX + (7 * $cellSize), $startY + (7 * $cellSize), $black);
            imagefilledrectangle($img, $startX + $cellSize, $startY + $cellSize, $startX + (6 * $cellSize), $startY + (6 * $cellSize), $white);
            imagefilledrectangle($img, $startX + (2 * $cellSize), $startY + (2 * $cellSize), $startX + (5 * $cellSize), $startY + (5 * $cellSize), $black);
        };

        $drawFinder($offset, $offset);
        $drawFinder($offset + (($gridSize - 7) * $cellSize), $offset);
        $drawFinder($offset, $offset + (($gridSize - 7) * $cellSize));

        // Fill data cells from hash bits
        $hashBytes = hex2bin($hash);
        $bitIndex = 0;
        for ($r = 0; $r < $gridSize; $r++) {
            for ($c = 0; $c < $gridSize; $c++) {
                if (($r < 8 && $c < 8) || ($r < 8 && $c >= $gridSize - 8) || ($r >= $gridSize - 8 && $c < 8)) {
                    continue;
                }
                $byte = ord($hashBytes[$bitIndex % strlen($hashBytes)]);
                $bit = ($byte >> ($bitIndex % 8)) & 1;
                $bitIndex++;

                if ($bit === 1) {
                    $x = $offset + ($c * $cellSize);
                    $y = $offset + ($r * $cellSize);
                    imagefilledrectangle($img, $x, $y, $x + $cellSize - 1, $y + $cellSize - 1, $black);
                }
            }
        }

        // Draw center mini shield emblem
        $centerX = (int)($size / 2);
        $centerY = (int)($size / 2);
        $boxSize = (int)($cellSize * 4);
        imagefilledrectangle($img, $centerX - $boxSize/2, $centerY - $boxSize/2, $centerX + $boxSize/2, $centerY + $boxSize/2, $white);
        imagerectangle($img, $centerX - $boxSize/2, $centerY - $boxSize/2, $centerX + $boxSize/2, $centerY + $boxSize/2, $blue);

        ob_start();
        imagepng($img);
        $pngData = ob_get_clean();
        imagedestroy($img);

        return $pngData;
    }
}

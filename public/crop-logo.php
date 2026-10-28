<?php
$source = "C:/Users/Advan/.gemini/antigravity-ide/brain/49b090fd-0a14-423e-95b2-b7fec2c269f9/media__1784714769356.png";
$targetFile = __DIR__ . '/images/school-logo.png';

$im = imagecreatefrompng($source);
$w = imagesx($im);
$h = imagesy($im);

// The shield is centered in the image horizontally.
// Let's find the bounding box of non-white pixels only in the center region (e.g. x between 0.25*w and 0.75*w)
$minX = $w; $maxX = 0; $minY = $h; $maxY = 0;

for ($x = 0; $x < $w; $x++) {
    for ($y = 0; $y < $h; $y++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = ($rgb & 0xFF);

        // Ignore white or near white pixels
        if ($r < 240 || $g < 240 || $b < 240) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

// Create transparent output
$transparentIm = imagecreatetruecolor($w, $h);
imagealphablending($transparentIm, false);
imagesavealpha($transparentIm, true);
$transparentColor = imagecolorallocatealpha($transparentIm, 0, 0, 0, 127);
imagefill($transparentIm, 0, 0, $transparentColor);

for ($x = $minX; $x <= $maxX; $x++) {
    for ($y = $minY; $y <= $maxY; $y++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = ($rgb & 0xFF);
        if ($r < 240 || $g < 240 || $b < 240) {
            imagesetpixel($transparentIm, $x, $y, $rgb);
        }
    }
}

$cropW = $maxX - $minX + 1;
$cropH = $maxY - $minY + 1;

$cropped = imagecrop($transparentIm, ['x' => $minX, 'y' => $minY, 'width' => $cropW, 'height' => $cropH]);
if ($cropped !== false) {
    imagesavealpha($cropped, true);
    imagepng($cropped, $targetFile);
    imagedestroy($cropped);
    echo "EXACT_SHIELD_CROP (X: $minX..$maxX, Y: $minY..$maxY, W: $cropW, H: $cropH)";
}

imagedestroy($im);
imagedestroy($transparentIm);

<?php

declare(strict_types=1);

namespace App\Core;

/**
 * GD helpers: every uploaded/downloaded image is re-encoded (strips EXIF,
 * scripts-in-metadata and format tricks) and optionally resized.
 */
final class ImageTool
{
    /**
     * Re-encode arbitrary image bytes, fitting within $max pixels on the
     * longest side. Returns the new binary, or null if the input is not a
     * decodable image.
     */
    public static function resizeEncode(string $binary, int $max = 256, string $format = 'jpeg'): ?string
    {
        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1.0, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($format === 'png' || $format === 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        } else {
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        ob_start();
        $ok = match ($format) {
            'png' => imagepng($dst, null, 6),
            'webp' => function_exists('imagewebp') ? imagewebp($dst, null, 85) : imagejpeg($dst, null, 85),
            default => imagejpeg($dst, null, 85),
        };
        $out = (string) ob_get_clean();
        imagedestroy($dst);
        return ($ok && $out !== '') ? $out : null;
    }

    /**
     * Validate + re-encode an uploaded image file; returns the stored
     * relative path (under storage/uploads) or null on failure.
     */
    public static function storeUpload(string $tmpPath, string $subdir, int $max = 256, string $format = 'jpeg'): ?string
    {
        $binary = @file_get_contents($tmpPath);
        if ($binary === false) {
            return null;
        }
        $encoded = self::resizeEncode($binary, $max, $format);
        if ($encoded === null) {
            return null;
        }
        $ext = $format === 'jpeg' ? 'jpg' : $format;
        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = BASE_PATH . '/storage/uploads/' . trim($subdir, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (file_put_contents($dir . '/' . $name, $encoded, LOCK_EX) === false) {
            return null;
        }
        return trim($subdir, '/') . '/' . $name;
    }
}

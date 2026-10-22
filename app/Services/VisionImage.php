<?php

namespace App\Services;

class VisionImage
{
    /**
     * Rasmni OpenAI'ga yuborish uchun tayyorlaydi: eng uzun tomoni $maxSide dan katta bo'lsa,
     * proporsiyani saqlagan holda kichraytiriladi va JPEG ko'rinishida qaytariladi.
     * GD kengaytmasi bo'lmasa yoki rasmni o'qib bo'lmasa, asl fayl o'zgarishsiz yuboriladi.
     *
     * @return array{mime: string, data: string} data — base64 ko'rinishida
     */
    public static function encode(string $path, string $mimeType, int $maxSide): array
    {
        $original = ['mime' => $mimeType, 'data' => base64_encode(file_get_contents($path))];

        if (!function_exists('imagecreatefromstring') || !function_exists('imagejpeg')) {
            return $original;
        }

        $size = @getimagesize($path);

        if (!$size || max($size[0], $size[1]) <= $maxSide) {
            return $original;
        }

        $source = @imagecreatefromstring(file_get_contents($path));

        if (!$source) {
            return $original;
        }

        [$width, $height] = [$size[0], $size[1]];
        $ratio = $maxSide / max($width, $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($newWidth, $newHeight);

        // Shaffof PNG/WebP fon qora bo'lib qolmasligi uchun oq fon
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $target = self::applyExifOrientation($target, $path, $mimeType);

        ob_start();
        $ok = imagejpeg($target, null, 85);
        $jpeg = ob_get_clean();

        if (!$ok || $jpeg === false || $jpeg === '') {
            return $original;
        }

        return ['mime' => 'image/jpeg', 'data' => base64_encode($jpeg)];
    }

    /**
     * Telefon rasmlari ko'pincha EXIF orqali buriladi; qayta saqlashda bu ma'lumot yo'qoladi,
     * shuning uchun burilish piksellarning o'ziga qo'llanadi.
     */
    private static function applyExifOrientation($image, string $path, string $mimeType)
    {
        if ($mimeType !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        return imagerotate($image, $angle, 0) ?: $image;
    }
}

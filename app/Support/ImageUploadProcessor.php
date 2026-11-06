<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImageUploadProcessor
{
    public static function store(UploadedFile $file, string $directory, int $maxWidth = 1600, int $maxHeight = 1600, int $quality = 82): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            throw new RuntimeException('GD with WebP support is required to process uploaded images.');
        }

        $dimensions = @getimagesize($file->getRealPath());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 16_000_000) {
            throw ValidationException::withMessages(['image' => __('This image is too large to process. Choose an image under 16 megapixels.')]);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw ValidationException::withMessages(['image' => __('The uploaded image could not be processed.')]);
        }

        $source = self::orientJpeg($source, $file->getRealPath(), $dimensions[2]);
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, $maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $output = imagecreatetruecolor($width, $height);
        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefilledrectangle($output, 0, 0, $width, $height, $transparent);
        imagecopyresampled($output, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'athar-webp-');
        if ($temporaryPath === false) {
            imagedestroy($source);
            imagedestroy($output);
            throw new RuntimeException('Could not create a temporary image file.');
        }

        try {
            if (! imagewebp($output, $temporaryPath, max(50, min(90, $quality)))) {
                throw new RuntimeException('Could not encode the uploaded image as WebP.');
            }

            $path = trim($directory, '/').'/'.Str::uuid().'.webp';
            if (! Storage::disk('public')->put($path, file_get_contents($temporaryPath))) {
                throw new RuntimeException('Could not store the processed image.');
            }

            return $path;
        } finally {
            @unlink($temporaryPath);
            imagedestroy($source);
            imagedestroy($output);
        }
    }

    private static function orientJpeg(\GdImage $image, string $path, int $imageType): \GdImage
    {
        if ($imageType !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = @exif_read_data($path)['Orientation'] ?? 1;
        $rotation = match ($orientation) {
            3 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };
        $flip = match ($orientation) {
            2, 5, 7 => IMG_FLIP_HORIZONTAL,
            4 => IMG_FLIP_VERTICAL,
            default => null,
        };
        if ($flip !== null) {
            imageflip($image, $flip);
        }
        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, imagecolorallocatealpha($image, 0, 0, 0, 127));
            if ($rotated) {
                imagedestroy($image);
                $image = $rotated;
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }
        }

        return $image;
    }
}

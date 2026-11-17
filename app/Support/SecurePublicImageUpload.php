<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Store only raster images with a server-detected extension.
 * Client-provided filenames and extensions are never trusted.
 */
class SecurePublicImageUpload
{
    private const ALLOWED_DIRECTORIES = ['images', 'uploads'];

    private const MIME_EXTENSIONS = [
        'image/jpeg' => ['extension' => 'jpg', 'type' => IMAGETYPE_JPEG],
        'image/png' => ['extension' => 'png', 'type' => IMAGETYPE_PNG],
        'image/gif' => ['extension' => 'gif', 'type' => IMAGETYPE_GIF],
        'image/webp' => ['extension' => 'webp', 'type' => IMAGETYPE_WEBP],
    ];

    public static function store(UploadedFile $file, string $directory, string $field = 'image'): string
    {
        if (! in_array($directory, self::ALLOWED_DIRECTORIES, true)) {
            throw new InvalidArgumentException('Unsupported image upload directory.');
        }

        if (! $file->isValid() || ! is_file($file->getPathname())) {
            throw ValidationException::withMessages([
                $field => 'The uploaded image could not be read. Please choose another image.',
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file->getPathname());
        $imageInfo = @getimagesize($file->getPathname());
        $allowed = self::MIME_EXTENSIONS[$detectedMime] ?? null;

        if (! $allowed || ! is_array($imageInfo) || ($imageInfo['mime'] ?? null) !== $detectedMime
            || ($imageInfo[2] ?? null) !== $allowed['type']) {
            throw ValidationException::withMessages([
                $field => 'Only genuine JPEG, PNG, GIF, or WEBP image files are accepted.',
            ]);
        }

        $width = (int) ($imageInfo[0] ?? 0);
        $height = (int) ($imageInfo[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 6000 || $height > 6000 || ($width * $height) > 20000000) {
            throw ValidationException::withMessages([
                $field => 'The image dimensions are too large. Use an image no larger than 6000 × 6000 pixels.',
            ]);
        }

        $targetDirectory = public_path($directory);
        if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0755, true) && ! is_dir($targetDirectory)) {
            throw new \RuntimeException('The image upload directory could not be created.');
        }

        $filename = bin2hex(random_bytes(24)).'.'.$allowed['extension'];
        $file->move($targetDirectory, $filename);

        $storedPath = $targetDirectory.DIRECTORY_SEPARATOR.$filename;
        if (is_file($storedPath)) {
            @chmod($storedPath, 0644);
        }

        return $filename;
    }

    public static function delete(string $directory, ?string $filename): void
    {
        if (! in_array($directory, self::ALLOWED_DIRECTORIES, true)
            || ! is_string($filename)
            || $filename === ''
            || basename($filename) !== $filename) {
            return;
        }

        $path = public_path($directory.DIRECTORY_SEPARATOR.$filename);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class BookCoverService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const COVER_WIDTH = 800;

    private const COVER_HEIGHT = 1200;

    private const THUMBNAIL_SIZE = 300;

    private const COMPRESSION_QUALITY = 85;

    public function uploadPath(): string
    {
        return storage_path('app/public/book_cover');
    }

    public function process(UploadedFile $file, string $bookId): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload failed'];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return ['error' => 'File size must be less than 10MB'];
        }

        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return ['error' => 'Only JPG, PNG, GIF, and WebP images are allowed'];
        }

        $webpFilename = $bookId . '_' . time() . '.webp';

        $image = $this->loadImage($file->getPathname(), $mimeType);

        if (! $image) {
            return ['error' => 'Failed to process image'];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $width / $height;
        $newWidth = self::COVER_WIDTH;
        $newHeight = self::COVER_HEIGHT;

        if ($ratio > 0.75) {
            $newHeight = $newWidth / $ratio;
        } else {
            $newWidth = $newHeight * $ratio;
        }

        $resized = imagecreatetruecolor((int) $newWidth, (int) $newHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
            imagefilledrectangle($resized, 0, 0, (int) $newWidth, (int) $newHeight, $transparent);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);

        $thumb = imagecreatetruecolor(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;

        imagecopyresampled(
            $thumb,
            $image,
            0,
            0,
            (int) $x,
            (int) $y,
            self::THUMBNAIL_SIZE,
            self::THUMBNAIL_SIZE,
            (int) $size,
            (int) $size
        );

        $tempResizedPath = tempnam(sys_get_temp_dir(), 'cover_');
        $tempThumbPath = tempnam(sys_get_temp_dir(), 'cover_thumb_');

        $successResized = imagewebp($resized, $tempResizedPath, self::COMPRESSION_QUALITY);
        $successThumb = imagewebp($thumb, $tempThumbPath, self::COMPRESSION_QUALITY);

        imagedestroy($image);
        imagedestroy($resized);
        imagedestroy($thumb);

        if (! $successResized || ! $successThumb) {
            @unlink($tempResizedPath);
            @unlink($tempThumbPath);
            return ['error' => 'Failed to save processed image'];
        }

        try {
            $disk = config('filesystems.default', 'local');

            $resizedStream = fopen($tempResizedPath, 'r+');
            \Illuminate\Support\Facades\Storage::disk($disk)->put(
                'book_cover/' . $webpFilename,
                $resizedStream,
                'public'
            );
            if (is_resource($resizedStream)) {
                fclose($resizedStream);
            }

            $thumbStream = fopen($tempThumbPath, 'r+');
            \Illuminate\Support\Facades\Storage::disk($disk)->put(
                'book_cover/thumb_' . $webpFilename,
                $thumbStream,
                'public'
            );
            if (is_resource($thumbStream)) {
                fclose($thumbStream);
            }

            @unlink($tempResizedPath);
            @unlink($tempThumbPath);
        } catch (\Throwable $e) {
            @unlink($tempResizedPath);
            @unlink($tempThumbPath);
            return ['error' => 'Failed to upload book cover: ' . $e->getMessage()];
        }

        return ['success' => true, 'filename' => $webpFilename];
    }

    public function delete(string $filename): void
    {
        $disk = config('filesystems.default', 'local');
        $fullPath = 'book_cover/' . $filename;
        $thumbPath = 'book_cover/thumb_' . $filename;

        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($fullPath)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($fullPath);
        }

        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($thumbPath)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($thumbPath);
        }
    }

    private function loadImage(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/gif' => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default => null,
        };
    }
}

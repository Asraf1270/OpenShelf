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
        return public_path('uploads/book_cover');
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

        $uploadPath = $this->uploadPath();

        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $webpFilename = $bookId . '_' . time() . '.webp';
        $webpPath = $uploadPath . DIRECTORY_SEPARATOR . $webpFilename;

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

        imagewebp($resized, $webpPath, self::COMPRESSION_QUALITY);
        imagewebp($thumb, $uploadPath . DIRECTORY_SEPARATOR . 'thumb_' . $webpFilename, self::COMPRESSION_QUALITY);

        imagedestroy($image);
        imagedestroy($resized);
        imagedestroy($thumb);

        return ['success' => true, 'filename' => $webpFilename];
    }

    public function delete(string $filename): void
    {
        $uploadPath = $this->uploadPath();
        $fullPath = $uploadPath . DIRECTORY_SEPARATOR . $filename;
        $thumbPath = $uploadPath . DIRECTORY_SEPARATOR . 'thumb_' . $filename;

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        if (file_exists($thumbPath)) {
            unlink($thumbPath);
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

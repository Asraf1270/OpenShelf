<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ProfileImageService
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const THUMBNAIL_SIZE = 300;

    private const COMPRESSION_QUALITY = 80;

    public function uploadPath(): string
    {
        return storage_path('app/public/profile');
    }

    public function process(UploadedFile $file, string $userId): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return ['error' => 'File upload failed'];
        }

        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return ['error' => 'File size must be less than 5MB'];
        }

        $mimeType = $file->getMimeType();

        if (! in_array($mimeType, self::ALLOWED_TYPES, true)) {
            return ['error' => 'Only JPG, PNG, GIF, and WebP images are allowed'];
        }

        $webpFilename = $userId . '_' . time() . '.webp';
        $image = $this->loadImage($file->getPathname(), $mimeType);

        if (! $image) {
            return ['error' => 'Failed to process image'];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $size = min($width, $height);
        $x = ($width - $size) / 2;
        $y = ($height - $size) / 2;
        $thumb = imagecreatetruecolor(self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, self::THUMBNAIL_SIZE, self::THUMBNAIL_SIZE, $transparent);
        }

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

        $tempPath = tempnam(sys_get_temp_dir(), 'profile_');
        $success = imagewebp($thumb, $tempPath, self::COMPRESSION_QUALITY);

        imagedestroy($image);
        imagedestroy($thumb);

        if (! $success) {
            @unlink($tempPath);
            return ['error' => 'Failed to save processed image'];
        }

        try {
            $disk = config('filesystems.default', 'local');
            $stream = fopen($tempPath, 'r+');
            \Illuminate\Support\Facades\Storage::disk($disk)->put(
                'profile/' . $webpFilename,
                $stream,
                'public'
            );
            if (is_resource($stream)) {
                fclose($stream);
            }
            @unlink($tempPath);
        } catch (\Throwable $e) {
            @unlink($tempPath);
            return ['error' => 'Failed to upload image: ' . $e->getMessage()];
        }

        return ['success' => true, 'filename' => $webpFilename];
    }

    public function delete(string $filename): void
    {
        if ($filename === '' || $filename === 'default-avatar.jpg') {
            return;
        }

        $disk = config('filesystems.default', 'local');
        $path = 'profile/' . $filename;

        if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
            \Illuminate\Support\Facades\Storage::disk($disk)->delete($path);
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

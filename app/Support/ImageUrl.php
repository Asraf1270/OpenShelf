<?php

namespace App\Support;

/**
 * Centralised image URL resolver.
 *
 * Strategy:
 *  1. If the stored value is already an absolute URL → return as-is.
 *  2. If FILESYSTEM_DISK === 'r2'  → build a public CDN URL from CLOUDFLARE_R2_URL
 *     without touching the AWS/Flysystem SDK (avoids SDK issues on shared hosting).
 *  3. For local/public disks → check the public/storage path on disk.
 *  4. On any failure → return the default fallback image.
 */
class ImageUrl
{
    // ──────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────

    public static function cover(?string $coverImage): string
    {
        $fallback = asset('images/default-book-cover.jpg');

        if (empty($coverImage)) {
            return $fallback;
        }

        $value = trim($coverImage);

        // Already a full URL (uploaded earlier or stored as full path)
        if (self::isAbsolute($value)) {
            return $value;
        }

        $filename = basename(ltrim(parse_url($value, PHP_URL_PATH) ?: $value, '/'));

        if (empty($filename)) {
            return $fallback;
        }

        $diskName = config('filesystems.default', 'local');

        // ── R2 / S3: build URL from env variable directly ──────────
        if ($diskName === 'r2' || $diskName === 's3') {
            return self::buildRemoteUrl($diskName, 'book_cover/' . $filename, $fallback);
        }

        // ── Local / Public: resolve real path on disk ───────────────
        foreach ([
            'storage/book_cover/thumb_' . $filename,
            'storage/book_cover/' . $filename,
            'storage/uploads/book_cover/thumb_' . $filename,
            'storage/uploads/book_cover/' . $filename,
        ] as $path) {
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        return $fallback;
    }

    public static function avatar(?string $profilePic): string
    {
        $fallback = asset('images/avatars/default.jpg');

        if (empty($profilePic) || in_array($profilePic, ['default-avatar.jpg', 'default.jpg'], true)) {
            return $fallback;
        }

        $value = trim($profilePic);

        if (self::isAbsolute($value)) {
            return $value;
        }

        $filename = basename(ltrim(parse_url($value, PHP_URL_PATH) ?: $value, '/'));

        if (empty($filename)) {
            return $fallback;
        }

        $diskName = config('filesystems.default', 'local');

        if ($diskName === 'r2' || $diskName === 's3') {
            return self::buildRemoteUrl($diskName, 'profile/' . $filename, $fallback);
        }

        foreach ([
            'storage/profile/' . $filename,
            'storage/uploads/profile/' . $filename,
        ] as $path) {
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        return $fallback;
    }

    // ──────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────

    /**
     * Build a CDN URL for remote disks without using Storage::disk()->url(),
     * which can fail on shared hosting if the AWS SDK isn't fully loaded.
     */
    private static function buildRemoteUrl(string $diskName, string $relativePath, string $fallback): string
    {
        // Prefer the disk's configured public 'url' key (e.g. CLOUDFLARE_R2_URL)
        $baseUrl = config("filesystems.disks.{$diskName}.url");

        if (!empty($baseUrl)) {
            return rtrim($baseUrl, '/') . '/' . ltrim($relativePath, '/');
        }

        // Fallback: try Laravel's Storage facade (may fail on shared hosting)
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = \Illuminate\Support\Facades\Storage::disk($diskName);
            return $disk->url($relativePath);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private static function isAbsolute(string $value): bool
    {
        return (bool) preg_match('#^(https?:)?//#i', $value);
    }
}

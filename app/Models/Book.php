<?php

namespace App\Models;

use App\Support\ImageUrl;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'rating' => 'decimal:2',
        'views' => 'integer',
        'times_borrowed' => 'integer',
        'rating_count' => 'integer',
        'tags' => 'array',
        'reviews' => 'array',
        'comments' => 'array',
    ];

    public static function generateUniqueId(): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';

        do {
            $bookId = '';
            for ($i = 0; $i < 10; $i++) {
                $bookId .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (static::query()->where('id', $bookId)->exists());

        return $bookId;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function borrowRequests()
    {
        return $this->hasMany(BorrowRequest::class, 'book_id');
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class, 'book_id');
    }

    public function getDisplayStatusAttribute(): string
    {
        return strtolower($this->status ?? 'available');
    }

    public function getCoverUrlAttribute(): string
    {
        return ImageUrl::cover($this->cover_image);
    }

    public function getDetailCoverUrlAttribute(): string
    {
        // Detail page shows the full image, not the thumbnail; resolve the full filename.
        if (empty($this->cover_image)) {
            return asset('images/default-book-cover.jpg');
        }

        $filename = basename(ltrim($this->cover_image, '/'));
        $diskName = config('filesystems.default', 'local');

        // For R2/S3, we serve the full image (not thumb)
        if ($diskName === 'r2' || $diskName === 's3') {
            $baseUrl = config("filesystems.disks.{$diskName}.url");
            if (!empty($baseUrl)) {
                return rtrim($baseUrl, '/') . '/book_cover/' . $filename;
            }
            return ImageUrl::cover($this->cover_image);
        }

        // Local: try full image first, then thumb
        foreach ([
            'storage/book_cover/' . $filename,
            'storage/book_cover/thumb_' . $filename,
            'storage/uploads/book_cover/' . $filename,
            'storage/uploads/book_cover/thumb_' . $filename,
        ] as $path) {
            if (file_exists(public_path($path))) {
                return asset($path);
            }
        }

        return asset('images/default-book-cover.jpg');
    }

    public function getOwnerAvatarUrlAttribute(): string
    {
        return ImageUrl::avatar($this->owner_avatar);
    }

    public function getHallNameAttribute(): string
    {
        $halls = [
            '1' => 'Amar Ekushey Hall',
            '2' => 'Dr. Muhammad Shahidullah Hall',
            '3' => 'Fazlul Huq Muslim Hall',
            '4' => 'Salimullah Muslim Hall',
            '5' => 'Shahid Sergeant Zahurul Haq Hall',
            '6' => 'Haji Muhammad Mohsin Hall',
            '7' => 'Sir A.F. Rahman Hall',
            '8' => 'Masterda Surja Sen Hall',
            '9' => 'Kobi Jashimuddin Hall',
            '10' => 'Muktijoddha Ziaur Rahman Hall',
            '11' => 'Shaheed Sharif Osman Hadi Hall',
            '12' => 'Bijoy Ekattor Hall',
            '13' => 'Jagannath Hall',
            '14' => 'Ruqayyah Hall',
            '15' => 'Shamsun Nahar Hall',
            '16' => 'Bangladesh-Kuwait Maitree Hall',
            '17' => 'Begum Fazilatunnesa Mujib Hall',
            '18' => 'Kobi Sufiya Kamal Hall',
        ];

        return $halls[$this->hall] ?? 'N/A';
    }
}

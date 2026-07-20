<?php

namespace App\Models;

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
        return $this->resolveStoredCoverPath(false);
    }

    public function getDetailCoverUrlAttribute(): string
    {
        return $this->resolveStoredCoverPath(true);
    }

    private function resolveStoredCoverPath(bool $preferFullImage): string
    {
        if (empty($this->cover_image)) {
            return '/images/default-book-cover.jpg';
        }

        $filename = basename(ltrim($this->cover_image, '/'));
        $fullRelative = 'storage/uploads/book_cover/' . $filename;
        $thumbRelative = 'storage/uploads/book_cover/thumb_' . $filename;

        if ($preferFullImage) {
            if (file_exists(public_path($fullRelative))) {
                return '/' . $fullRelative;
            }

            if (file_exists(public_path($thumbRelative))) {
                return '/' . $thumbRelative;
            }
        } else {
            if (file_exists(public_path($fullRelative))) {
                return '/' . $fullRelative;
            }

            if (file_exists(public_path($thumbRelative))) {
                return '/' . $thumbRelative;
            }
        }

        $relative = 'storage/uploads/book_cover/' . ltrim($this->cover_image, '/');

        return file_exists(public_path($relative))
            ? '/' . $relative
            : '/images/default-book-cover.jpg';
    }

    public function getOwnerAvatarUrlAttribute(): string
    {
        if (! empty($this->owner_avatar) && $this->owner_avatar !== 'default-avatar.jpg') {
            $relative = 'storage/uploads/profile/' . ltrim($this->owner_avatar, '/');
            $publicPath = public_path($relative);

            if (file_exists($publicPath)) {
                return '/' . $relative;
            }
        }

        return '/images/avatars/default.jpg';
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

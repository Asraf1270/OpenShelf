<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $guarded = [];

    protected $casts = [
        'verified' => 'boolean',
        'otp_expiry' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function books()
    {
        return $this->hasMany(Book::class, 'owner_id');
    }

    public function getProfileImageUrlAttribute(): string
    {
        $name = $this->profile_pic ?: 'default-avatar.jpg';

        if (empty($name) || in_array($name, ['default-avatar.jpg', 'default.jpg'], true)) {
            return asset('images/avatars/default.jpg');
        }

        $relative = 'uploads/profile/' . ltrim($name, '/');

        return file_exists(public_path($relative))
            ? asset($relative)
            : asset('images/avatars/default.jpg');
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

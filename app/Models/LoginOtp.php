<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'verified' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}

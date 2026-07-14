<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowRequest extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'request_date' => 'datetime',
        'expected_return_date' => 'datetime',
        'returned_at' => 'datetime',
        'actual_return_date' => 'datetime',
        'return_confirmation_sent_at' => 'datetime',
        'return_confirmed_at' => 'datetime',
        'return_rejected_at' => 'datetime',
        'rejected_at' => 'datetime',
        'approved_at' => 'datetime',
        'history' => 'array',
        'rating' => 'integer',
    ];

    public function borrower()
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}

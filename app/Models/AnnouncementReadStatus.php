<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnouncementReadStatus extends Model
{
    public $timestamps = false;

    protected $table = 'announcement_read_status';

    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function announcement()
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'sent_via' => 'array',
        'stats' => 'array',
    ];

    public function readStatuses()
    {
        return $this->hasMany(AnnouncementReadStatus::class, 'announcement_id');
    }

    public function scopeActive($query)
    {
        return $query
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('scheduled_for')
                    ->orWhere('scheduled_for', '<=', now());
            });
    }

    public function getPriorityBadgeAttribute(): string
    {
        return match ($this->priority) {
            'danger' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            default => 'primary',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return strtoupper($this->priority ?? 'INFO');
    }
}

<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\AnnouncementReadStatus;
use Illuminate\Support\Collection;

class AnnouncementService
{
    public function getActiveAnnouncements(): Collection
    {
        return Announcement::query()
            ->active()
            ->orderByDesc('created_at')
            ->get();
    }

    public function getReadAnnouncementIds(string $userId): array
    {
        return AnnouncementReadStatus::query()
            ->where('user_id', $userId)
            ->pluck('announcement_id')
            ->all();
    }

    public function findAnnouncement(string $id): ?Announcement
    {
        return Announcement::find($id);
    }

    public function markAsRead(string $announcementId, string $userId): void
    {
        $alreadyRead = AnnouncementReadStatus::query()
            ->where('announcement_id', $announcementId)
            ->where('user_id', $userId)
            ->exists();

        if ($alreadyRead) {
            return;
        }

        AnnouncementReadStatus::create([
            'announcement_id' => $announcementId,
            'user_id' => $userId,
            'read_at' => now(),
        ]);

        $announcement = Announcement::find($announcementId);

        if (! $announcement) {
            return;
        }

        $stats = $announcement->stats ?? [];
        $stats['read'] = ($stats['read'] ?? 0) + 1;

        $announcement->update(['stats' => $stats]);
    }

    public function isRead(string $announcementId, array $readIds): bool
    {
        return in_array($announcementId, $readIds, true);
    }
}

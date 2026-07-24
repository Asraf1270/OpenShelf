<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    public function unreadCount(string $userId): int
    {
        return $this->baseQuery($userId)
            ->where('is_read', false)
            ->count();
    }

    public function getForUser(string $userId, int $limit = 50, bool $includeRead = true): Collection
    {
        $query = $this->baseQuery($userId)->orderByDesc('created_at');

        if (! $includeRead) {
            $query->where('is_read', false);
        }

        return $query->limit($limit)->get();
    }

    public function paginateForUser(string $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($userId)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function markAsRead(string $notificationId, string $userId): bool
    {
        return $this->baseQuery($userId)
            ->where('id', $notificationId)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]) > 0;
    }

    public function markAllAsRead(string $userId): bool
    {
        $this->baseQuery($userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return true;
    }

    public function delete(string $notificationId, string $userId): bool
    {
        return $this->baseQuery($userId)
            ->where('id', $notificationId)
            ->delete() > 0;
    }

    public function create(
        string $userId,
        string $type,
        string $title,
        string $message,
        string $link,
        ?\DateTimeInterface $expiresAt = null,
    ): Notification {
        return Notification::create([
            'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false,
            'created_at' => now(),
            'expires_at' => $expiresAt ?? now()->addDays(30),
        ]);
    }

    public function formatForApi(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'user_id' => $notification->user_id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'is_read' => (bool) $notification->is_read,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'expires_at' => $notification->expires_at?->toDateTimeString(),
            'time_ago' => $this->timeAgo($notification->created_at),
            'icon' => $this->iconFor($notification->type),
            'color' => $this->colorFor($notification->type),
        ];
    }

    public function iconFor(string $type): string
    {
        return match ($type) {
            'borrow_request' => 'fa-hand-holding-heart',
            'request_approved' => 'fa-check-circle',
            'request_rejected' => 'fa-times-circle',
            'return_reminder', 'return_pending' => 'fa-clock',
            'book_due_soon' => 'fa-exclamation-triangle',
            'book_overdue' => 'fa-exclamation-circle',
            'book_returned', 'return_confirmed' => 'fa-undo-alt',
            'return_rejected' => 'fa-times-circle',
            'new_review' => 'fa-star',
            'new_comment' => 'fa-comment',
            'account_approved' => 'fa-user-check',
            'account_rejected' => 'fa-user-times',
            'announcement' => 'fa-bullhorn',
            default => 'fa-bell',
        };
    }

    public function colorFor(string $type): string
    {
        return match ($type) {
            'borrow_request', 'new_comment', 'announcement' => '#2C3E50',
            'request_approved', 'book_returned', 'return_confirmed', 'account_approved' => '#4C9F8A',
            'request_rejected', 'book_overdue', 'return_rejected', 'account_rejected' => '#ef4444',
            'return_reminder', 'return_pending', 'book_due_soon', 'new_review' => '#f59e0b',
            default => '#5A6C7D',
        };
    }

    public function timeAgo($datetime): string
    {
        if (! $datetime) {
            return '';
        }

        $time = $datetime instanceof \DateTimeInterface
            ? $datetime->getTimestamp()
            : strtotime((string) $datetime);

        $diff = time() - $time;

        if ($diff < 60) {
            return 'just now';
        }

        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);

            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        }

        if ($diff < 604800) {
            $days = (int) floor($diff / 86400);

            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        }

        return date('M j, Y', $time);
    }

    private function baseQuery(string $userId)
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}

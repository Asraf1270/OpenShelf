<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use App\Models\AnnouncementReadStatus;
use App\Models\Notification;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAnnouncementsController extends AdminController
{
    public function __construct(private MailerService $mailerService)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin);
        }

        $announcements = Announcement::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Announcement $announcement) {
                $announcement->sent_via = $announcement->sent_via ?? ['email' => false, 'notification' => false];
                $announcement->stats = $announcement->stats ?? ['views' => 0, 'read' => 0];

                return $announcement;
            });

        $editingAnnouncement = null;

        if ($request->filled('edit')) {
            $editingAnnouncement = $announcements->firstWhere('id', $request->query('edit'));
        }

        return view('admin.announcements', [
            'admin' => $admin,
            'announcements' => $announcements,
            'editingAnnouncement' => $editingAnnouncement,
            'totalAnnouncements' => $announcements->count(),
            'activeAnnouncements' => $announcements->filter(fn (Announcement $a) => ! $a->expires_at || $a->expires_at->isFuture())->count(),
            'scheduledAnnouncements' => $announcements->filter(fn (Announcement $a) => $a->scheduled_for && $a->scheduled_for->isFuture())->count(),
            'expiredAnnouncements' => $announcements->filter(fn (Announcement $a) => $a->expires_at && $a->expires_at->isPast())->count(),
        ]);
    }

    private function handleAction(Request $request, $admin): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'create') {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:200'],
                'content' => ['required', 'string'],
                'priority' => ['required', 'in:info,success,warning,danger'],
                'target' => ['required', 'in:all,active'],
                'schedule_date' => ['nullable', 'date'],
                'expiry_date' => ['nullable', 'date'],
            ]);

            $sendEmail = $request->boolean('send_email');
            $sendNotification = $request->boolean('send_notification');
            $scheduleDate = $validated['schedule_date'] ?? null;

            $announcement = Announcement::query()->create([
                'id' => 'ann_' . uniqid() . '_' . bin2hex(random_bytes(4)),
                'title' => $validated['title'],
                'content' => $validated['content'],
                'priority' => $validated['priority'],
                'target' => $validated['target'],
                'created_by' => $admin->id,
                'created_by_name' => $admin->name,
                'created_at' => now(),
                'scheduled_for' => $scheduleDate ? now()->parse($scheduleDate) : null,
                'expires_at' => ! empty($validated['expiry_date'])
                    ? now()->parse($validated['expiry_date'])
                    : now()->addDays(30),
                'sent_via' => ['email' => $sendEmail, 'notification' => $sendNotification],
                'stats' => ['views' => 0, 'read' => 0],
            ]);

            $message = 'Announcement created successfully!';

            if (! $scheduleDate || now()->parse($scheduleDate)->lte(now())) {
                $sentCount = $this->sendAnnouncement($announcement, $sendEmail, $sendNotification);
                $message .= " Sent to {$sentCount['email']} users via email and {$sentCount['notification']} via notification.";
            } else {
                $message .= ' Scheduled for ' . now()->parse($scheduleDate)->format('M j, Y g:i A') . '.';
            }

            return back()->with('success', $message);
        }

        if ($action === 'update') {
            $validated = $request->validate([
                'announcement_id' => ['required', 'string', 'exists:announcements,id'],
                'title' => ['required', 'string', 'max:200'],
                'content' => ['required', 'string'],
                'priority' => ['required', 'in:info,success,warning,danger'],
                'expiry_date' => ['nullable', 'date'],
            ]);

            Announcement::query()
                ->whereKey($validated['announcement_id'])
                ->update([
                    'title' => $validated['title'],
                    'content' => $validated['content'],
                    'priority' => $validated['priority'],
                    'expires_at' => ! empty($validated['expiry_date'])
                        ? now()->parse($validated['expiry_date'])
                        : null,
                ]);

            return back()->with('success', 'Announcement updated successfully!');
        }

        if ($action === 'delete') {
            $validated = $request->validate([
                'announcement_id' => ['required', 'string', 'exists:announcements,id'],
            ]);

            AnnouncementReadStatus::query()
                ->where('announcement_id', $validated['announcement_id'])
                ->delete();

            Announcement::query()->whereKey($validated['announcement_id'])->delete();

            return back()->with('success', 'Announcement deleted successfully!');
        }

        if ($action === 'send_now') {
            $validated = $request->validate([
                'announcement_id' => ['required', 'string', 'exists:announcements,id'],
            ]);

            $announcement = Announcement::query()->findOrFail($validated['announcement_id']);
            $announcement->update(['scheduled_for' => null]);

            $sentVia = $announcement->sent_via ?? ['email' => false, 'notification' => false];
            $sentCount = $this->sendAnnouncement(
                $announcement,
                (bool) ($sentVia['email'] ?? false),
                (bool) ($sentVia['notification'] ?? false),
            );

            return back()->with('success', "Announcement sent! Delivered to {$sentCount['email']} emails and {$sentCount['notification']} notifications.");
        }

        return back()->with('error', 'Unsupported action.');
    }

    private function sendAnnouncement(Announcement $announcement, bool $sendEmail, bool $sendNotification): array
    {
        $users = User::query()->where('status', 'active')->get();
        $sentCount = ['email' => 0, 'notification' => 0];

        foreach ($users as $user) {
            if ($sendNotification) {
                Notification::query()->create([
                    'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
                    'user_id' => $user->id,
                    'type' => 'announcement',
                    'title' => $announcement->title,
                    'message' => Str::limit($announcement->content, 100),
                    'link' => '/announcements?id=' . $announcement->id,
                    'is_read' => false,
                    'created_at' => now(),
                    'expires_at' => $announcement->expires_at ?? now()->addDays(30),
                ]);
                $sentCount['notification']++;
            }

            if ($sendEmail && $user->email) {
                $sent = $this->mailerService->sendTemplate(
                    $user->email,
                    $user->name,
                    'announcement',
                    [
                        'subject' => '[OpenShelf] ' . $announcement->title,
                        'user_name' => $user->name,
                        'announcement_title' => $announcement->title,
                        'announcement_content' => $announcement->content,
                        'announcement_priority' => $announcement->priority,
                        'announcement_link' => route('announcements.index', ['id' => $announcement->id]),
                        'base_url' => config('app.url'),
                    ],
                    $user->id,
                );

                if ($sent) {
                    $sentCount['email']++;
                }
            }
        }

        return $sentCount;
    }
}

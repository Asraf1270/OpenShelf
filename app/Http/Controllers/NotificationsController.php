<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/notifications');

            return redirect()->route('login');
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $userId);
        }

        $notifications = $this->notificationService->paginateForUser($userId);
        $unreadCount = $this->notificationService->unreadCount($userId);

        return view('notifications', [
            'seoTitle' => 'Notifications - OpenShelf',
            'seoDesc' => 'Manage your borrow requests, returns, and wishlist notifications on OpenShelf.',
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'total' => $notifications->total(),
            'message' => session('success'),
            'error' => session('error'),
            'notificationService' => $this->notificationService,
        ]);
    }

    private function handleAction(Request $request, string $userId)
    {
        $action = $request->input('action');

        if ($action === 'mark_all_read') {
            if ($this->notificationService->markAllAsRead($userId)) {
                return back()->with('success', 'All notifications marked as read');
            }

            return back()->with('error', 'Failed to mark notifications as read');
        }

        if ($action === 'delete') {
            $notificationId = $request->input('notification_id');

            if ($this->notificationService->delete($notificationId, $userId)) {
                return back()->with('success', 'Notification deleted');
            }

            return back()->with('error', 'Failed to delete notification');
        }

        if ($action === 'mark_read') {
            $notificationId = $request->input('notification_id');

            if ($this->notificationService->markAsRead($notificationId, $userId)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'unread_count' => $this->notificationService->unreadCount($userId),
                    ]);
                }
            }

            return back();
        }

        return back();
    }
}

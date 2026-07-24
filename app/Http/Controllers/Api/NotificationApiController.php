<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function __construct(private NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
            ], 401);
        }

        if ($request->isMethod('post')) {
            return $this->handlePost($request, $userId);
        }

        $action = $request->query('action', 'list');

        if ($action === 'count') {
            return response()->json([
                'success' => true,
                'unread_count' => $this->notificationService->unreadCount($userId),
            ]);
        }

        $limit = max(1, min(100, (int) $request->query('limit', 20)));
        $includeRead = $request->query('include_read') === 'true';

        $notifications = $this->notificationService
            ->getForUser($userId, $limit, $includeRead)
            ->map(fn ($notification) => $this->notificationService->formatForApi($notification))
            ->values();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->unreadCount($userId),
            'total' => $notifications->count(),
        ]);
    }

    private function handlePost(Request $request, string $userId)
    {
        $input = $request->json()->all();
        $action = $input['action'] ?? $request->input('action');

        if ($action === 'mark_read') {
            $notificationId = $input['notification_id'] ?? $request->input('notification_id');

            if (empty($notificationId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification ID required',
                ]);
            }

            if ($this->notificationService->markAsRead($notificationId, $userId)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'unread_count' => $this->notificationService->unreadCount($userId),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
            ]);
        }

        if ($action === 'mark_all_read') {
            $this->notificationService->markAllAsRead($userId);

            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0,
            ]);
        }

        if ($action === 'delete') {
            $notificationId = $input['notification_id'] ?? $request->input('notification_id');

            if (empty($notificationId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification ID required',
                ]);
            }

            if ($this->notificationService->delete($notificationId, $userId)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification deleted',
                    'unread_count' => $this->notificationService->unreadCount($userId),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid action.',
        ]);
    }
}

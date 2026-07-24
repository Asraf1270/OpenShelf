<?php

namespace App\Http\Controllers;

use App\Services\AnnouncementService;
use Illuminate\Http\Request;

class AnnouncementsController extends Controller
{
    public function __construct(private AnnouncementService $announcementService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/announcements');

            return redirect()->route('login');
        }

        $selectedId = $request->query('id');
        $readIds = $this->announcementService->getReadAnnouncementIds($userId);
        $selectedAnnouncement = null;

        if ($selectedId) {
            $selectedAnnouncement = $this->announcementService->findAnnouncement($selectedId);

            if ($selectedAnnouncement) {
                $this->announcementService->markAsRead($selectedId, $userId);
                $readIds = $this->announcementService->getReadAnnouncementIds($userId);
            }
        }

        $activeAnnouncements = $this->announcementService->getActiveAnnouncements();

        return view('announcements', [
            'seoTitle' => 'Announcements - OpenShelf',
            'seoDesc' => 'Important updates and news from the OpenShelf team.',
            'activeAnnouncements' => $activeAnnouncements,
            'selectedAnnouncement' => $selectedAnnouncement,
            'readIds' => $readIds,
        ]);
    }
}

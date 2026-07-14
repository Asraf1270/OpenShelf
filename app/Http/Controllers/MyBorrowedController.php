<?php

namespace App\Http\Controllers;

use App\Services\BorrowRequestService;
use Illuminate\Http\Request;

class MyBorrowedController extends Controller
{
    public function __construct(private BorrowRequestService $borrowRequestService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/my-borrowed');

            return redirect()->route('login');
        }

        $activeBorrows = $this->borrowRequestService->getActiveBorrowsForUser($userId);
        $pastBorrows = $this->borrowRequestService->getPastBorrowsForUser($userId);

        return view('my-borrowed', [
            'seoTitle' => 'My Borrowed Books - OpenShelf',
            'seoDesc' => 'Manage your active reads and track return deadlines on OpenShelf.',
            'activeBorrows' => $activeBorrows,
            'pastBorrows' => $pastBorrows,
            'totalActive' => $activeBorrows->count(),
            'thumbCover' => fn (?string $cover) => $this->borrowRequestService->thumbCoverUrl($cover),
        ]);
    }
}

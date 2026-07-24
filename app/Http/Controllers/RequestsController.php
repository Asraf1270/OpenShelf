<?php

namespace App\Http\Controllers;

use App\Models\BorrowRequest;
use App\Services\BorrowRequestService;
use Illuminate\Http\Request;

class RequestsController extends Controller
{
    public function __construct(private BorrowRequestService $borrowRequestService)
    {
    }

    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/requests');

            return redirect()->route('login');
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $userId);
        }

        $receivedRequests = $this->borrowRequestService->getReceivedRequestsForUser($userId);
        $sentRequests = $this->borrowRequestService->getSentRequestsForUser($userId);

        return view('requests', [
            'seoTitle' => 'My Requests - OpenShelf',
            'seoDesc' => 'Manage your book borrowing requests on OpenShelf.',
            'receivedRequests' => $receivedRequests,
            'sentRequests' => $sentRequests,
            'stats' => $this->borrowRequestService->getReceivedStats($receivedRequests),
            'message' => session('success'),
            'error' => session('error'),
        ]);
    }

    private function handleAction(Request $request, string $userId)
    {
        $action = $request->input('action');
        $requestId = $request->input('request_id');

        $borrowRequest = BorrowRequest::find($requestId);

        if (! $borrowRequest) {
            return redirect()->route('requests.index')->with('error', 'Request not found');
        }

        if ($borrowRequest->owner_id !== $userId) {
            return redirect()->route('requests.index')->with('error', 'You do not have permission to modify this request');
        }

        if ($action === 'approve') {
            $ok = $this->borrowRequestService->approveRequest($borrowRequest, $userId);

            return redirect()->route('requests.index')->with(
                $ok ? 'success' : 'error',
                $ok ? 'Request approved successfully' : 'Failed to approve request',
            );
        }

        if ($action === 'reject') {
            $reason = trim($request->input('rejection_reason', 'No reason provided'));
            $ok = $this->borrowRequestService->rejectRequest($borrowRequest, $userId, $reason);

            return redirect()->route('requests.index')->with(
                $ok ? 'success' : 'error',
                $ok ? 'Request rejected successfully' : 'Failed to reject request',
            );
        }

        return redirect()->route('requests.index')->with('error', 'Unknown action');
    }
}

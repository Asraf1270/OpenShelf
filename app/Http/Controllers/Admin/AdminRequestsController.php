<?php

namespace App\Http\Controllers\Admin;

use App\Models\BorrowRequest;
use App\Services\AdminBorrowRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminRequestsController extends AdminController
{
    public function __construct(private AdminBorrowRequestService $adminBorrowRequestService)
    {
    }

    public function index(Request $request): View|RedirectResponse|Response
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->query('export')) {
            return $this->export($request);
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin->id, $admin->name);
        }

        $paginator = $this->adminBorrowRequestService->paginate($request);
        $requests = $this->adminBorrowRequestService->enrichOverdueStatus($paginator->getCollection());
        $paginator->setCollection($requests);

        return view('admin.requests', [
            'admin' => $admin,
            'requests' => $paginator,
            'stats' => $this->adminBorrowRequestService->stats(),
            'status' => $request->query('status', 'all'),
            'search' => $request->query('search', ''),
            'fromDate' => $request->query('from', ''),
            'toDate' => $request->query('to', ''),
            'message' => session('success'),
            'error' => session('error'),
        ]);
    }

    private function handleAction(Request $request, string $adminId, string $adminName): RedirectResponse
    {
        $action = $request->input('action');

        if ($action === 'approve') {
            $validated = $request->validate([
                'request_id' => ['required', 'string', 'exists:borrow_requests,id'],
            ]);

            $borrowRequest = BorrowRequest::query()->findOrFail($validated['request_id']);
            $this->adminBorrowRequestService->updateStatus($borrowRequest, 'approved', $adminId, $adminName);

            return back()->with('success', 'Request approved successfully.');
        }

        if ($action === 'reject') {
            $validated = $request->validate([
                'request_id' => ['required', 'string', 'exists:borrow_requests,id'],
                'rejection_reason' => ['required', 'string'],
            ]);

            $borrowRequest = BorrowRequest::query()->findOrFail($validated['request_id']);
            $reason = trim($validated['rejection_reason']);
            $this->adminBorrowRequestService->updateStatus($borrowRequest, 'rejected', $adminId, $adminName, ['reason' => $reason]);

            return back()->with('success', 'Request rejected successfully.');
        }

        if ($action === 'close') {
            $validated = $request->validate([
                'request_id' => ['required', 'string', 'exists:borrow_requests,id'],
                'close_notes' => ['nullable', 'string'],
            ]);

            $borrowRequest = BorrowRequest::query()->findOrFail($validated['request_id']);
            $notes = trim($validated['close_notes'] ?? '');
            $this->adminBorrowRequestService->updateStatus($borrowRequest, 'closed', $adminId, $adminName, ['notes' => $notes]);

            return back()->with('success', 'Request closed successfully.');
        }

        if ($action === 'extend') {
            $validated = $request->validate([
                'request_id' => ['required', 'string', 'exists:borrow_requests,id'],
                'extend_days' => ['required', 'integer', 'min:1', 'max:90'],
                'extend_reason' => ['nullable', 'string'],
            ]);

            $borrowRequest = BorrowRequest::query()->findOrFail($validated['request_id']);
            $ok = $this->adminBorrowRequestService->extendReturnDate(
                $borrowRequest,
                (int) $validated['extend_days'],
                $adminId,
                trim($validated['extend_reason'] ?? ''),
            );

            return back()->with(
                $ok ? 'success' : 'error',
                $ok ? 'Return date extended by ' . $validated['extend_days'] . ' days.' : 'Failed to extend return date.',
            );
        }

        if ($action === 'bulk_approve') {
            $validated = $request->validate([
                'request_ids' => ['required', 'array'],
                'request_ids.*' => ['string', 'exists:borrow_requests,id'],
            ]);

            $count = 0;

            foreach ($validated['request_ids'] as $requestId) {
                $borrowRequest = BorrowRequest::query()->find($requestId);

                if ($borrowRequest) {
                    $this->adminBorrowRequestService->updateStatus($borrowRequest, 'approved', $adminId, $adminName);
                    $count++;
                }
            }

            return back()->with('success', "Approved {$count} requests successfully.");
        }

        if ($action === 'bulk_reject') {
            $validated = $request->validate([
                'request_ids' => ['required', 'array'],
                'request_ids.*' => ['string', 'exists:borrow_requests,id'],
                'bulk_rejection_reason' => ['required', 'string'],
            ]);

            $reason = trim($validated['bulk_rejection_reason']);
            $count = 0;

            foreach ($validated['request_ids'] as $requestId) {
                $borrowRequest = BorrowRequest::query()->find($requestId);

                if ($borrowRequest) {
                    $this->adminBorrowRequestService->updateStatus($borrowRequest, 'rejected', $adminId, $adminName, ['reason' => $reason]);
                    $count++;
                }
            }

            return back()->with('success', "Rejected {$count} requests successfully.");
        }

        return back()->with('error', 'Unknown action.');
    }

    private function export(Request $request): Response
    {
        $csv = $this->adminBorrowRequestService->exportCsv($request);
        $filename = 'borrow_requests_' . now()->format('Y-m-d') . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}

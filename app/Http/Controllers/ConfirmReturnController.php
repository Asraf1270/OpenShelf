<?php

namespace App\Http\Controllers;

use App\Models\BorrowRequest;
use App\Services\BorrowRequestService;
use Illuminate\Http\Request;

class ConfirmReturnController extends Controller
{
    public function __construct(private BorrowRequestService $borrowRequestService)
    {
    }

    public function show(Request $request)
    {
        $token = trim($request->query('token', ''));

        if ($token === '' || strlen($token) !== 64) {
            return view('confirm-return', [
                'seoTitle' => 'Confirm Book Return | OpenShelf',
                'pageError' => 'Invalid or missing confirmation token.',
            ]);
        }

        $borrowRequest = BorrowRequest::query()
            ->where('return_confirmation_token', $token)
            ->first();

        if (! $borrowRequest) {
            return view('confirm-return', [
                'seoTitle' => 'Confirm Book Return | OpenShelf',
                'pageError' => 'Token not found or already used.',
            ]);
        }

        if ($borrowRequest->return_confirmation_status !== 'pending_owner') {
            return view('confirm-return', [
                'seoTitle' => 'Confirm Book Return | OpenShelf',
                'alreadyDone' => $borrowRequest->return_confirmation_status,
            ]);
        }

        $intendedAction = trim($request->query('action', 'confirm'));
        $actionResult = null;

        if ($request->isMethod('post')) {
            $postedAction = trim($request->input('action', 'confirm'));
            $rejectReason = trim($request->input('reject_reason', ''));

            $actionResult = $this->borrowRequestService->confirmReturn(
                $borrowRequest->fresh(),
                $postedAction === 'confirm' ? 'confirm' : 'reject',
                $rejectReason,
            );
        }

        return view('confirm-return', [
            'seoTitle' => 'Confirm Book Return | OpenShelf',
            'borrowRequest' => $borrowRequest,
            'token' => $token,
            'intendedAction' => $intendedAction,
            'actionResult' => $actionResult,
        ]);
    }
}

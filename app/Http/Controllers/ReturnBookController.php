<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Services\BorrowRequestService;
use Illuminate\Http\Request;

class ReturnBookController extends Controller
{
    public function __construct(private BorrowRequestService $borrowRequestService)
    {
    }

    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userName = $request->session()->get('user_name', 'Unknown');

        if (! $userId) {
            $request->session()->put('redirect_after_login', $request->getRequestUri());

            return redirect()->route('login');
        }

        $requestId = $request->query('id', '');

        if ($requestId === '') {
            return redirect('/requests')->with('error', 'No request specified');
        }

        $borrowRequest = BorrowRequest::find($requestId);

        if (! $borrowRequest) {
            return redirect('/requests')->with('error', 'Request not found');
        }

        $isBorrower = $userId === $borrowRequest->borrower_id;
        $isOwner = $userId === $borrowRequest->owner_id;

        if (! $isBorrower && ! $isOwner) {
            return redirect('/requests')->with('error', 'You are not authorized to return this book');
        }

        if (! in_array($borrowRequest->status, ['approved', 'borrowed'], true)) {
            return redirect('/requests')->with('error', 'This request cannot be returned at this time');
        }

        $book = Book::find($borrowRequest->book_id);

        if (! $book) {
            return redirect('/requests')->with('error', 'Book not found');
        }

        $error = '';

        if ($request->isMethod('post')) {
            $notes = trim($request->input('notes', ''));
            $condition = trim($request->input('condition', 'same'));
            $rating = (int) $request->input('rating', 0);
            $damageDescription = trim($request->input('damage_description', ''));

            if ($condition === 'damaged' && $damageDescription === '') {
                $error = 'Please describe the damage';
            } else {
                $payload = [
                    'notes' => $notes,
                    'return_condition' => $condition,
                    'rating' => $rating,
                ];

                if ($condition === 'damaged') {
                    $payload['notes'] = trim($notes . "\n\nDamage: " . $damageDescription);
                }

                $this->borrowRequestService->fileReturn($borrowRequest, $userId, $userName, $payload);

                return redirect('/requests')->with(
                    'success',
                    'Return filed! The book owner has been notified and must confirm physical receipt before it is marked as returned.',
                );
            }
        }

        $isPastDue = false;
        $overdueDays = 0;

        if ($borrowRequest->expected_return_date) {
            $dueDate = $borrowRequest->expected_return_date->startOfDay();
            $today = now()->startOfDay();

            if ($dueDate->lt($today)) {
                $isPastDue = true;
                $overdueDays = $dueDate->diffInDays($today);
            }
        }

        return view('return-book', [
            'seoTitle' => 'Return Book - ' . $borrowRequest->book_title . ' | OpenShelf',
            'borrowRequest' => $borrowRequest,
            'book' => $book,
            'coverImage' => $this->borrowRequestService->thumbCoverUrl($book->cover_image),
            'error' => $error,
            'isPastDue' => $isPastDue,
            'overdueDays' => $overdueDays,
        ]);
    }
}

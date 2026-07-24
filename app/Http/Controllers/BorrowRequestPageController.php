<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Services\BorrowRequestService;
use Illuminate\Http\Request;

class BorrowRequestPageController extends Controller
{
    public function __construct(private BorrowRequestService $borrowRequestService)
    {
    }

    public function show(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', $request->getRequestUri());

            return redirect()->route('login');
        }

        $bookId = $request->query('book_id', '');

        if ($bookId === '') {
            return redirect()->route('books');
        }

        $book = Book::find($bookId);

        if (! $book) {
            return redirect()->route('books');
        }

        if ($book->owner_id === $userId) {
            return redirect()->route('book.show', ['id' => $bookId])
                ->with('borrow_error', 'You cannot borrow your own book');
        }

        if ($book->status !== 'available') {
            return redirect()->route('book.show', ['id' => $bookId])
                ->with('borrow_error', 'Book is not available');
        }

        if ($this->borrowRequestService->hasPendingRequest($bookId, $userId)) {
            return redirect()->route('book.show', ['id' => $bookId])
                ->with('borrow_error', 'You already have a pending request');
        }

        $owner = User::find($book->owner_id);
        $borrower = User::find($userId);
        $error = '';

        if ($request->isMethod('post')) {
            $duration = (int) $request->input('duration', 14);
            $message = trim($request->input('message', ''));

            if ($borrower && $owner) {
                $this->borrowRequestService->createRequest(
                    $book,
                    $borrower,
                    $owner,
                    $request->session()->get('user_name', 'Unknown'),
                    $duration,
                    $message,
                );

                return redirect('/requests')
                    ->with('success', 'Request sent successfully! The owner has been notified.');
            }

            $error = 'Failed to create borrow request';
        }

        return view('borrow-request', [
            'seoTitle' => 'Borrow Request - OpenShelf',
            'book' => $book,
            'owner' => $owner,
            'coverImage' => $this->borrowRequestService->thumbCoverUrl($book->cover_image),
            'error' => $error,
        ]);
    }
}

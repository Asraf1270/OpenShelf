<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\BookQueryService;
use App\Services\NotificationService;
use App\Support\RelativeTime;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function __construct(
        private BookQueryService $bookQueryService,
        private NotificationService $notificationService,
    ) {
    }

    public function show(Request $request)
    {
        if ($request->isMethod('post')) {
            return $this->handlePost($request);
        }

        $bookId = $request->query('id', '');

        if ($bookId === '') {
            return redirect('/books');
        }

        $book = Book::with('owner')->find($bookId);

        if (! $book) {
            return redirect('/books');
        }

        $book->increment('views');

        $owner = $book->owner;
        $borrowRequests = BorrowRequest::where('book_id', $bookId)->get();
        $relatedBooks = $this->bookQueryService->getRelatedBooks($book->category ?? '', $bookId);

        $currentUserId = $request->session()->get('user_id');
        $isLoggedIn = ! empty($currentUserId);
        $currentUserName = $request->session()->get('user_name', 'Unknown');
        $isOwner = $isLoggedIn && $currentUserId === $book->owner_id;
        $hasRequested = $isLoggedIn && BorrowRequest::query()
            ->where('book_id', $bookId)
            ->where('borrower_id', $currentUserId)
            ->where('status', 'pending')
            ->exists();
        $canBorrow = $book->status === 'available' && $isLoggedIn && ! $isOwner && ! $hasRequested;
        $isUnavailable = $book->status !== 'available';
        $isWishlisted = $isLoggedIn && ! $isOwner && $isUnavailable && Wishlist::query()
            ->where('book_id', $bookId)
            ->where('user_id', $currentUserId)
            ->exists();
        $wishlistCount = Wishlist::where('book_id', $bookId)->count();

        $reviews = collect($book->reviews ?? []);
        $comments = collect($book->comments ?? []);
        $participantIds = $reviews->pluck('user_id')
            ->merge($comments->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();
        $participants = User::whereIn('id', $participantIds)->get()->keyBy('id');

        $whatsappLink = '';
        if ($isLoggedIn && ! $isOwner && $owner && ! empty($owner->phone)) {
            $phone = preg_replace('/[^0-9]/', '', $owner->phone);
            if (strlen($phone) === 11) {
                $phone = '88' . $phone;
            }
            $message = 'Hello ' . rawurlencode($owner->name) . '%0A%0A';
            $message .= 'I am ' . rawurlencode($currentUserName) . '%0A';
            $message .= 'I am interested in borrowing your book:%0A';
            $message .= '*' . rawurlencode($book->title) . '* by ' . rawurlencode($book->author) . '%0A%0A';
            $message .= 'Is it still available?%0A%0AThanks!';
            $whatsappLink = "https://wa.me/{$phone}?text={$message}";
        }

        return view('book', [
            'seoTitle' => ($book->title ?? 'Book Detail') . ' - OpenShelf',
            'seoDesc' => 'Borrow ' . ($book->title ?? 'this book') . ' on OpenShelf.',
            'book' => $book,
            'owner' => $owner,
            'borrowRequests' => $borrowRequests,
            'relatedBooks' => $relatedBooks,
            'reviews' => $reviews,
            'comments' => $comments,
            'participants' => $participants,
            'isLoggedIn' => $isLoggedIn,
            'currentUserId' => $currentUserId,
            'currentUserName' => $currentUserName,
            'isOwner' => $isOwner,
            'hasRequested' => $hasRequested,
            'canBorrow' => $canBorrow,
            'isUnavailable' => $isUnavailable,
            'isWishlisted' => $isWishlisted,
            'wishlistCount' => $wishlistCount,
            'avgRating' => number_format((float) ($book->rating ?? 0), 1),
            'ratingCount' => $book->rating_count ?? 0,
            'coverImage' => $book->detail_cover_url,
            'whatsappLink' => $whatsappLink,
            'borrowMessage' => $request->session()->get('borrow_message'),
            'borrowError' => $request->session()->get('borrow_error'),
            'formatDate' => fn (?string $date) => RelativeTime::format($date),
        ]);
    }

    private function handlePost(Request $request)
    {
        $bookId = $request->query('id', '');

        if ($bookId === '') {
            return redirect('/books');
        }

        $book = Book::find($bookId);

        if (! $book) {
            return redirect('/books');
        }

        if ($request->input('ajax_action')) {
            return $this->handleAjax($request, $book);
        }

        if ($request->input('action') === 'borrow') {
            return $this->handleBorrow($request, $book);
        }

        return redirect()->route('book.show', ['id' => $bookId]);
    }

    private function handleBorrow(Request $request, Book $book)
    {
        $currentUserId = $request->session()->get('user_id');
        $currentUserName = $request->session()->get('user_name', 'Unknown');

        if (! $currentUserId) {
            return redirect()->route('login');
        }

        $hasRequested = BorrowRequest::query()
            ->where('book_id', $book->id)
            ->where('borrower_id', $currentUserId)
            ->where('status', 'pending')
            ->exists();

        $canBorrow = $book->status === 'available'
            && $currentUserId !== $book->owner_id
            && ! $hasRequested;

        if (! $canBorrow) {
            return redirect()->route('book.show', ['id' => $book->id])
                ->with('borrow_error', 'Failed to send request');
        }

        $borrower = User::find($currentUserId);
        $owner = User::find($book->owner_id);
        $requestId = 'REQ' . time() . bin2hex(random_bytes(4));
        $duration = (int) $request->input('duration', 14);
        $message = trim($request->input('message', ''));

        BorrowRequest::create([
            'id' => $requestId,
            'book_id' => $book->id,
            'book_title' => $book->title,
            'book_author' => $book->author,
            'book_cover' => $book->cover_image,
            'owner_id' => $book->owner_id,
            'owner_name' => $owner?->name ?? 'Unknown',
            'owner_email' => $owner?->email,
            'borrower_id' => $currentUserId,
            'borrower_name' => $currentUserName,
            'borrower_email' => $borrower?->email,
            'status' => 'pending',
            'request_date' => now(),
            'expected_return_date' => now()->addDays($duration),
            'duration_days' => $duration,
            'message' => $message,
            'updated_at' => now(),
        ]);

        $book->update([
            'status' => 'reserved',
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $book->owner_id,
            'borrow_request',
            'New Borrow Request',
            $currentUserName . ' wants to borrow "' . $book->title . '"',
            '/requests/?id=' . $requestId,
        );

        return redirect()->route('book.show', ['id' => $book->id])
            ->with('borrow_message', 'Request sent successfully!');
    }

    private function handleAjax(Request $request, Book $book)
    {
        $action = $request->input('ajax_action');
        $currentUserId = $request->session()->get('user_id');
        $currentUserName = $request->session()->get('user_name', 'Unknown');
        $isLoggedIn = ! empty($currentUserId);
        $isOwner = $isLoggedIn && $currentUserId === $book->owner_id;

        return match ($action) {
            'add_review' => $this->addReview($request, $book, $currentUserId, $currentUserName, $isLoggedIn, $isOwner),
            'add_comment' => $this->addComment($request, $book, $currentUserId, $currentUserName, $isLoggedIn),
            'toggle_wishlist' => $this->toggleWishlist($request, $book, $currentUserId, $isLoggedIn, $isOwner),
            'like_comment' => $this->likeComment($request, $book, $currentUserId, $isLoggedIn),
            default => response()->json(['success' => false, 'message' => 'Unknown action'], 400),
        };
    }

    private function addReview(Request $request, Book $book, ?string $userId, string $userName, bool $isLoggedIn, bool $isOwner)
    {
        if (! $isLoggedIn || $isOwner) {
            return response()->json(['success' => false, 'message' => 'Please login to review']);
        }

        $rating = (int) $request->input('rating', 0);
        $reviewText = trim($request->input('review_text', ''));

        if ($rating < 1 || $rating > 5) {
            return response()->json(['success' => false, 'message' => 'Invalid rating']);
        }

        if (strlen($reviewText) < 10) {
            return response()->json(['success' => false, 'message' => 'Review must be at least 10 characters']);
        }

        $reviews = $book->reviews ?? [];

        foreach ($reviews as $review) {
            if (($review['user_id'] ?? null) === $userId) {
                return response()->json(['success' => false, 'message' => 'You have already reviewed this book']);
            }
        }

        $newReview = [
            'id' => 'rev_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'user_name' => $userName,
            'rating' => $rating,
            'review_text' => $reviewText,
            'likes' => [],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $reviews[] = $newReview;
        $reviewCount = count($reviews);
        $totalRating = array_sum(array_column($reviews, 'rating'));
        $newAvgRating = round($totalRating / $reviewCount, 2);

        $book->update([
            'reviews' => $reviews,
            'rating' => $newAvgRating,
            'rating_count' => $reviewCount,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'review' => $newReview,
            'new_rating' => $newAvgRating,
            'new_count' => $reviewCount,
        ]);
    }

    private function addComment(Request $request, Book $book, ?string $userId, string $userName, bool $isLoggedIn)
    {
        if (! $isLoggedIn) {
            return response()->json(['success' => false, 'message' => 'Please login to comment']);
        }

        $commentText = trim($request->input('comment_text', ''));

        if (strlen($commentText) < 2) {
            return response()->json(['success' => false, 'message' => 'Comment must be at least 2 characters']);
        }

        $comments = $book->comments ?? [];
        $newComment = [
            'id' => 'com_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'user_name' => $userName,
            'comment_text' => $commentText,
            'likes' => [],
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $comments[] = $newComment;

        $book->update([
            'comments' => $comments,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'comment' => $newComment]);
    }

    private function toggleWishlist(Request $request, Book $book, ?string $userId, bool $isLoggedIn, bool $isOwner)
    {
        if (! $isLoggedIn) {
            return response()->json(['success' => false, 'message' => 'Please login to use wishlist']);
        }

        if ($book->status === 'available') {
            return response()->json(['success' => false, 'message' => 'Book is already available — request to borrow instead!']);
        }

        if ($isOwner) {
            return response()->json(['success' => false, 'message' => 'You cannot wishlist your own book']);
        }

        $existing = Wishlist::query()
            ->where('book_id', $book->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $added = false;
        } else {
            Wishlist::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'book_title' => $book->title,
                'notified' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $added = true;
        }

        $count = Wishlist::where('book_id', $book->id)->count();

        return response()->json(['success' => true, 'added' => $added, 'count' => $count]);
    }

    private function likeComment(Request $request, Book $book, ?string $userId, bool $isLoggedIn)
    {
        if (! $isLoggedIn) {
            return response()->json(['success' => false, 'message' => 'Please login to like']);
        }

        $commentId = $request->input('comment_id', '');
        $comments = $book->comments ?? [];
        $commentFound = false;
        $likeCount = 0;
        $liked = false;

        foreach ($comments as &$comment) {
            if (($comment['id'] ?? null) !== $commentId) {
                continue;
            }

            $likes = $comment['likes'] ?? [];

            if (in_array($userId, $likes, true)) {
                $likes = array_values(array_diff($likes, [$userId]));
                $liked = false;
            } else {
                $likes[] = $userId;
                $liked = true;
            }

            $comment['likes'] = $likes;
            $likeCount = count($likes);
            $commentFound = true;
            break;
        }
        unset($comment);

        if (! $commentFound) {
            return response()->json(['success' => false, 'message' => 'Comment not found']);
        }

        $book->update(['comments' => $comments]);

        return response()->json(['success' => true, 'likes' => $likeCount, 'liked' => $liked]);
    }
}

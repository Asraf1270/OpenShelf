<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $viewUserId = $request->query('id', $request->session()->get('user_id'));

        if (! $viewUserId) {
            return redirect()->route('login');
        }

        $user = User::find($viewUserId);

        if (! $user) {
            return redirect('/books');
        }

        $ownedBooks = Book::where('owner_id', $viewUserId)->get();
        $borrowProfileData = $this->getBorrowProfileData($viewUserId);

        $isOwnProfile = $request->session()->get('user_id') === $viewUserId;

        return view('profile', [
            'seoTitle' => $user->name . ' - OpenShelf Profile',
            'seoDesc' => 'View ' . $user->name . '\'s book collection on OpenShelf.',
            'user' => $user,
            'ownedBooks' => $ownedBooks,
            'borrowedBooks' => $borrowProfileData['borrowed'],
            'lentBooks' => $borrowProfileData['lent'],
            'stats' => [
                'owned' => $ownedBooks->count(),
                'borrowed' => count($borrowProfileData['borrowed']),
                'lent' => count($borrowProfileData['lent']),
            ],
            'isOwnProfile' => $isOwnProfile,
            'showSensitiveInfo' => $isOwnProfile,
            'memberSince' => $user->created_at?->format('M Y') ?? now()->format('M Y'),
        ]);
    }

    private function getBorrowProfileData(string $userId): array
    {
        $requests = BorrowRequest::query()
            ->where(function ($query) use ($userId) {
                $query->where('borrower_id', $userId)
                    ->orWhere('owner_id', $userId);
            })
            ->whereIn('status', ['approved', 'returned'])
            ->get();

        $borrowedBooks = [];
        $lentBooks = [];

        foreach ($requests as $borrowRequest) {
            if ($borrowRequest->borrower_id === $userId) {
                $borrowedBooks[] = [
                    'id' => $borrowRequest->book_id,
                    'title' => $borrowRequest->book_title,
                    'author' => $borrowRequest->book_author,
                    'cover_image' => $borrowRequest->book_cover,
                    'status' => $borrowRequest->status,
                    'owner_name' => $borrowRequest->owner_name,
                    'owner_hall' => 'N/A',
                ];
            }

            if ($borrowRequest->owner_id === $userId) {
                $lentBooks[] = [
                    'id' => $borrowRequest->book_id,
                    'title' => $borrowRequest->book_title,
                    'author' => $borrowRequest->book_author,
                    'cover_image' => $borrowRequest->book_cover,
                    'status' => $borrowRequest->status,
                    'borrower_name' => $borrowRequest->borrower_name,
                    'owner_hall' => 'My Hall',
                ];
            }
        }

        return [
            'borrowed' => $borrowedBooks,
            'lent' => $lentBooks,
        ];
    }
}

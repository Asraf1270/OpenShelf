<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\BookCoverService;
use App\Services\BookQueryService;
use App\Support\Markdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookApiController extends Controller
{
    public function __construct(
        private BookQueryService $bookQueryService,
        private BookCoverService $bookCoverService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->booksResponse($request);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $book = Book::with('owner')->find($id);

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found.',
            ], 404);
        }

        $book->increment('views');

        $currentUserId = $request->session()->get('user_id');
        $isLoggedIn = ! empty($currentUserId);
        $isOwner = $isLoggedIn && (string) $currentUserId === (string) $book->owner_id;
        $hasRequested = $isLoggedIn && BorrowRequest::query()
            ->where('book_id', $book->id)
            ->where('borrower_id', $currentUserId)
            ->where('status', 'pending')
            ->exists();
        $canBorrow = $book->status === 'available' && $isLoggedIn && ! $isOwner && ! $hasRequested;
        $isUnavailable = $book->status !== 'available';
        $isWishlisted = $isLoggedIn && ! $isOwner && $isUnavailable && Wishlist::query()
            ->where('book_id', $book->id)
            ->where('user_id', $currentUserId)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'book' => $this->bookPayload($book),
                'owner' => $this->ownerPayload($book->owner),
                'related_books' => $this->bookQueryService->getRelatedBooks($book->category ?? '', $book->id)
                    ->map(fn ($relatedBook) => $this->bookQueryService->formatBookForApi($relatedBook))
                    ->values(),
                'reviews' => collect($book->reviews ?? [])->values(),
                'comments' => collect($book->comments ?? [])->values(),
                'description_html' => Markdown::toHtml($book->description ?? ''),
                'avg_rating' => number_format((float) ($book->rating ?? 0), 1),
                'rating_count' => (int) ($book->rating_count ?? 0),
                'wishlist_count' => Wishlist::where('book_id', $book->id)->count(),
                'is_logged_in' => $isLoggedIn,
                'current_user_id' => $currentUserId,
                'is_owner' => $isOwner,
                'has_requested' => $hasRequested,
                'can_borrow' => $canBorrow,
                'is_unavailable' => $isUnavailable,
                'is_wishlisted' => $isWishlisted,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to add a book.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'author' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'min:20', 'max:5000'],
            'category' => ['required', 'string', 'in:' . implode(',', $this->bookCategories())],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys($this->bookConditions()))],
            'cover_image' => ['required', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publication_year' => ['nullable', 'string', 'max:10'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:50'],
        ], [
            'title.required' => 'Book title is required',
            'title.min' => 'Title must be at least 2 characters',
            'title.max' => 'Title must be less than 200 characters',
            'author.required' => 'Author name is required',
            'author.min' => 'Author name must be at least 2 characters',
            'author.max' => 'Author name must be less than 100 characters',
            'description.min' => 'Description must be at least 20 characters',
            'description.max' => 'Description must be less than 5000 characters',
            'category.required' => 'Please select a category',
            'condition.required' => 'Please select a condition',
            'cover_image.required' => 'Book cover image is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bookId = Book::generateUniqueId();
        $uploadResult = $this->bookCoverService->process($request->file('cover_image'), $bookId);

        if (isset($uploadResult['error'])) {
            return response()->json([
                'success' => false,
                'message' => $uploadResult['error'],
                'errors' => ['cover_image' => [$uploadResult['error']]],
            ], 422);
        }

        try {
            $book = Book::create([
                'id' => $bookId,
                'title' => trim((string) $request->input('title')),
                'author' => trim((string) $request->input('author')),
                'description' => trim((string) $request->input('description', '')),
                'category' => $request->input('category'),
                'condition' => $request->input('condition'),
                'cover_image' => $uploadResult['filename'],
                'owner_id' => $user->id,
                'owner_name' => $user->name,
                'hall' => $request->session()->get('user_hall', $user->hall),
                'status' => 'available',
                'views' => 0,
                'times_borrowed' => 0,
                'isbn' => $request->filled('isbn') ? trim((string) $request->input('isbn')) : null,
                'publication_year' => $request->filled('publication_year') ? trim((string) $request->input('publication_year')) : null,
                'publisher' => $request->filled('publisher') ? trim((string) $request->input('publisher')) : null,
                'pages' => $request->filled('pages') ? (string) $request->input('pages') : null,
                'language' => trim((string) $request->input('language', 'English')),
                'reviews' => [],
                'comments' => [],
                'tags' => [],
            ]);
        } catch (\Throwable) {
            $this->bookCoverService->delete($uploadResult['filename']);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save book.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book created successfully.',
            'data' => $this->bookPayload($book),
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to update a book.',
            ], 401);
        }

        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found.',
            ], 404);
        }

        if ((string) $book->owner_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to edit this book.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'author' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'min:20', 'max:5000'],
            'category' => ['required', 'string', 'in:' . implode(',', $this->bookCategories())],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys($this->bookConditions()))],
            'cover_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,gif,webp', 'max:10240'],
            'isbn' => ['nullable', 'string', 'max:20'],
            'publication_year' => ['nullable', 'string', 'max:10'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:1'],
            'language' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:available,borrowed,reserved,returned'],
        ], [
            'title.required' => 'Book title is required',
            'title.min' => 'Title must be at least 2 characters',
            'title.max' => 'Title must be less than 200 characters',
            'author.required' => 'Author name is required',
            'author.min' => 'Author name must be at least 2 characters',
            'author.max' => 'Author name must be less than 100 characters',
            'description.min' => 'Description must be at least 20 characters',
            'description.max' => 'Description must be less than 5000 characters',
            'category.required' => 'Please select a category',
            'condition.required' => 'Please select a condition',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $previousCoverImage = (string) ($book->cover_image ?? '');
        $newCoverImage = null;

        if ($request->hasFile('cover_image')) {
            $uploadResult = $this->bookCoverService->process($request->file('cover_image'), $book->id);

            if (isset($uploadResult['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $uploadResult['error'],
                    'errors' => ['cover_image' => [$uploadResult['error']]],
                ], 422);
            }

            $newCoverImage = $uploadResult['filename'];
        }

        try {
            $book->update([
                'title' => trim((string) $request->input('title')),
                'author' => trim((string) $request->input('author')),
                'description' => trim((string) $request->input('description', '')),
                'category' => $request->input('category'),
                'condition' => $request->input('condition'),
                'cover_image' => $newCoverImage ?? $book->cover_image,
                'isbn' => $request->filled('isbn') ? trim((string) $request->input('isbn')) : $book->isbn,
                'publication_year' => $request->filled('publication_year') ? trim((string) $request->input('publication_year')) : $book->publication_year,
                'publisher' => $request->filled('publisher') ? trim((string) $request->input('publisher')) : $book->publisher,
                'pages' => $request->filled('pages') ? (string) $request->input('pages') : $book->pages,
                'language' => $request->filled('language') ? trim((string) $request->input('language')) : $book->language,
                'status' => $request->input('status', $book->status),
            ]);
        } catch (\Throwable) {
            if ($newCoverImage) {
                $this->bookCoverService->delete($newCoverImage);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update book.',
            ], 500);
        }

        if ($newCoverImage && $previousCoverImage !== '') {
            $this->bookCoverService->delete($previousCoverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book updated successfully.',
            'data' => $this->bookPayload($book->fresh()),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to delete a book.',
            ], 401);
        }

        $book = Book::find($id);

        if (! $book) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found.',
            ], 404);
        }

        if ((string) $book->owner_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this book.',
            ], 403);
        }

        $coverImage = (string) ($book->cover_image ?? '');

        Wishlist::where('book_id', $book->id)->delete();
        $book->delete();

        if ($coverImage !== '') {
            $this->bookCoverService->delete($coverImage);
        }

        return response()->json([
            'success' => true,
            'message' => 'Book deleted successfully.',
        ]);
    }

    public function my(Request $request): JsonResponse
    {
        $user = $this->currentUser($request);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to view your books.',
            ], 401);
        }

        $books = Book::query()
            ->where('owner_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Book $book) => $this->bookQueryService->formatBookForApi($book))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $books,
            'total' => $books->count(),
        ]);
    }

    public function newest(Request $request): JsonResponse
    {
        return $this->booksResponse($request, ['sort' => 'newest']);
    }

    public function available(Request $request): JsonResponse
    {
        return $this->booksResponse($request, ['availability' => 'available']);
    }

    public function categoriesList(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->bookQueryService->getCategories(),
        ]);
    }

    private function booksResponse(Request $request, array $forcedFilters = []): JsonResponse
    {
        $cursorDate = $request->input('cursor_date');
        $cursorId = $request->input('cursor_id');
        $cursorDate = in_array($cursorDate, [null, '', 'null'], true) ? null : $cursorDate;
        $cursorId = in_array($cursorId, [null, '', 'null'], true) ? null : $cursorId;

        $limit = min(100, (int) $request->input('limit', 25));
        $search = $request->input('search', '');
        $selectedCategories = (array) $request->input('categories', []);
        $availability = $forcedFilters['availability'] ?? $request->input('availability', '');
        $hall = $request->input('hall', '');
        $sort = $forcedFilters['sort'] ?? $request->input('sort', 'newest');

        try {
            $books = $this->bookQueryService->getBooks(
                $search,
                $selectedCategories,
                $availability,
                $hall,
                $limit,
                $cursorDate,
                $cursorId,
                $sort,
            );

            $data = $books->map(fn ($book) => $this->bookQueryService->formatBookForApi($book))->values();
            $lastBook = $books->last();

            return response()->json([
                'success' => true,
                'data' => $data,
                'cursor' => [
                    'date' => $lastBook?->created_at,
                    'id' => $lastBook?->id,
                ],
                'has_more' => $books->count() === $limit,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function currentUser(Request $request): ?User
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    private function bookPayload(Book $book): array
    {
        $data = $this->bookQueryService->formatBookForApi($book);
        $data['description_html'] = Markdown::toHtml($book->description ?? '');
        $data['detail_cover_url'] = $book->detail_cover_url;
        $data['display_status'] = $book->display_status;

        return $data;
    }

    private function ownerPayload(?User $owner): ?array
    {
        if (! $owner) {
            return null;
        }

        return [
            'id' => $owner->id,
            'name' => $owner->name,
            'department' => $owner->department,
            'session' => $owner->session,
            'hall' => (string) $owner->hall,
            'hall_name' => $owner->hall_name,
            'profile_pic' => $owner->profile_pic,
            'profile_image_url' => $owner->profile_image_url,
        ];
    }

    private function bookCategories(): array
    {
        return [
            'Fiction',
            'Self Development',
            'Science',
            'Religion',
            'Islamic',
            'Technology',
            'Business',
            'Health',
            'Arts',
            'Education',
            'History',
            'Biography',
            'Law',
        ];
    }

    private function bookConditions(): array
    {
        return [
            'New' => 'Brand new, never read',
            'Like New' => 'Perfect condition, no wear',
            'Very Good' => 'Minor wear, clean copy',
            'Good' => 'Normal wear, may have markings',
            'Acceptable' => 'Well-read, usable condition',
            'Poor' => 'Damaged, but readable',
        ];
    }
}

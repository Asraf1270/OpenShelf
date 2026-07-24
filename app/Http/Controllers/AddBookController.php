<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use App\Services\BookCoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddBookController extends Controller
{
    public function __construct(private BookCoverService $bookCoverService)
    {
    }

    public function create(Request $request)
    {
        $userId = $request->session()->get('user_id');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/add-book');

            return redirect()->route('login');
        }

        $this->ensureUserHallInSession($request, $userId);

        return view('add-book', [
            'seoTitle' => 'Add Book - OpenShelf',
            'seoDesc' => 'Add a new book to your OpenShelf library and share it with the community.',
            'categories' => $this->categories(),
            'conditions' => $this->conditions(),
            'success' => session('success'),
            'addedBookId' => session('addedBookId'),
        ]);
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('user_id');
        $userName = $request->session()->get('user_name', 'Unknown');

        if (! $userId) {
            $request->session()->put('redirect_after_login', '/add-book');

            return redirect()->route('login');
        }

        $this->ensureUserHallInSession($request, $userId);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'author' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'min:20', 'max:5000'],
            'category' => ['required', 'string', 'in:' . implode(',', $this->categories())],
            'condition' => ['required', 'string', 'in:' . implode(',', array_keys($this->conditions()))],
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
            return back()->withInput()->withErrors($validator);
        }

        $bookId = Book::generateUniqueId();
        $uploadResult = $this->bookCoverService->process($request->file('cover_image'), $bookId);

        if (isset($uploadResult['error'])) {
            return back()->withInput()->withErrors(['cover_image' => $uploadResult['error']]);
        }

        $coverImage = $uploadResult['filename'];

        try {
            Book::create([
                'id' => $bookId,
                'title' => trim($request->input('title')),
                'author' => trim($request->input('author')),
                'description' => trim($request->input('description', '')),
                'category' => $request->input('category'),
                'condition' => $request->input('condition'),
                'cover_image' => $coverImage,
                'owner_id' => $userId,
                'owner_name' => $userName,
                'hall' => $request->session()->get('user_hall'),
                'status' => 'available',
                'views' => 0,
                'times_borrowed' => 0,
                'isbn' => $request->filled('isbn') ? trim($request->input('isbn')) : null,
                'publication_year' => $request->filled('publication_year') ? trim($request->input('publication_year')) : null,
                'publisher' => $request->filled('publisher') ? trim($request->input('publisher')) : null,
                'pages' => $request->filled('pages') ? (string) $request->input('pages') : null,
                'language' => trim($request->input('language', 'English')),
                'reviews' => [],
                'comments' => [],
                'tags' => [],
            ]);
        } catch (\Throwable) {
            $this->bookCoverService->delete($coverImage);

            return back()->withInput()->withErrors([
                'general' => 'Failed to save book to database. Please try again.',
            ]);
        }

        return redirect()->route('books.create')->with([
            'success' => true,
            'addedBookId' => $bookId,
        ]);
    }

    private function ensureUserHallInSession(Request $request, string $userId): void
    {
        if ($request->session()->has('user_hall')) {
            return;
        }

        $user = User::query()->select('hall')->find($userId);

        if ($user) {
            $request->session()->put('user_hall', $user->hall);
        }
    }

    private function categories(): array
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

    private function conditions(): array
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

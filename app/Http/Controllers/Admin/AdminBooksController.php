<?php

namespace App\Http\Controllers\Admin;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminBooksController extends AdminController
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        if ($request->isMethod('post')) {
            return $this->handleAction($request, $admin->id);
        }

        return view('admin.books', $this->viewData($request, $admin));
    }

    private function handleAction(Request $request, string $adminId): RedirectResponse
    {
        $action = $request->input('action');
        $allowedStatuses = ['available', 'borrowed', 'reserved', 'unavailable'];

        if ($action === 'update_status') {
            $validated = $request->validate([
                'book_id' => ['required', 'string', 'exists:books,id'],
                'status' => ['required', Rule::in($allowedStatuses)],
            ]);

            Book::query()
                ->whereKey($validated['book_id'])
                ->update([
                    'status' => $validated['status'],
                    'status_updated_by' => $adminId,
                    'updated_at' => now(),
                ]);

            return back()->with('success', 'Book status updated successfully.');
        }

        if ($action === 'delete_book') {
            $validated = $request->validate([
                'book_id' => ['required', 'string', 'exists:books,id'],
            ]);

            $book = Book::query()->findOrFail($validated['book_id']);
            $this->deleteBookAssets($book);
            $book->delete();

            return back()->with('success', 'Book deleted successfully.');
        }

        if ($action === 'bulk_delete') {
            $validated = $request->validate([
                'book_ids' => ['required', 'array'],
                'book_ids.*' => ['string', 'exists:books,id'],
            ]);

            $books = Book::query()->whereIn('id', $validated['book_ids'])->get();

            foreach ($books as $book) {
                $this->deleteBookAssets($book);
                $book->delete();
            }

            return back()->with('success', 'Deleted ' . $books->count() . ' books successfully.');
        }

        if ($action === 'bulk_update_status') {
            $validated = $request->validate([
                'book_ids' => ['required', 'array'],
                'book_ids.*' => ['string', 'exists:books,id'],
                'bulk_status' => ['required', Rule::in($allowedStatuses)],
            ]);

            $count = Book::query()
                ->whereIn('id', $validated['book_ids'])
                ->update([
                    'status' => $validated['bulk_status'],
                    'status_updated_by' => $adminId,
                    'updated_at' => now(),
                ]);

            return back()->with('success', 'Updated status for ' . $count . ' books successfully.');
        }

        return back()->with('error', 'Unsupported action.');
    }

    private function viewData(Request $request, $admin): array
    {
        $status = $request->string('status')->toString() ?: 'all';
        $category = $request->string('category')->toString() ?: 'all';
        $search = trim($request->string('search')->toString());

        $query = Book::query()->with('owner:id,name');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category !== 'all') {
            $query->where('category', $category);
        }

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('author', 'like', '%' . $search . '%');
            });
        }

        /** @var LengthAwarePaginator $books */
        $books = $query
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return [
            'admin' => $admin,
            'status' => $status,
            'category' => $category,
            'search' => $search,
            'books' => $books,
            'categories' => Book::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->orderBy('category')
                ->distinct()
                ->pluck('category'),
            'totalBooks' => Book::query()->count(),
            'availableBooks' => Book::query()->where('status', 'available')->count(),
            'borrowedBooks' => Book::query()->where('status', 'borrowed')->count(),
            'unavailableBooks' => Book::query()->where('status', 'unavailable')->count(),
        ];
    }

    private function deleteBookAssets(Book $book): void
    {
        if (! $book->cover_image) {
            return;
        }

        $filename = basename($book->cover_image);
        $paths = [
            public_path('storage/uploads/book_cover/' . $filename),
            public_path('storage/uploads/book_cover/thumb_' . $filename),
            public_path('uploads/book_cover/' . $filename),
            public_path('uploads/book_cover/thumb_' . $filename),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}

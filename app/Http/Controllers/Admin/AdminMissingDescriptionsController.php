<?php

namespace App\Http\Controllers\Admin;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMissingDescriptionsController extends AdminController
{
    public function index(Request $request): View|RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        // Fetch all books where description is null, empty string, or whitespace only
        $missingQuery = Book::query()
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', '')
                    ->orWhereRaw("TRIM(description) = ''");
            });

        $totalMissing = (clone $missingQuery)->count();
        $pendingBooks = (clone $missingQuery)->orderBy('created_at', 'desc')->get();

        $selectedBookId = $request->query('book_id');
        $currentBook = null;

        if ($selectedBookId) {
            $currentBook = $pendingBooks->firstWhere('id', $selectedBookId);
        }

        if (!$currentBook && $pendingBooks->isNotEmpty()) {
            $currentBook = $pendingBooks->first();
        }

        // Calculate progress total books count
        $totalBooks = Book::query()->count();
        $completedCount = $totalBooks - $totalMissing;
        $progressPercentage = $totalBooks > 0 ? round(($completedCount / $totalBooks) * 100) : 100;

        return view('admin.missing-descriptions', [
            'admin' => $admin,
            'currentBook' => $currentBook,
            'pendingBooks' => $pendingBooks,
            'totalMissing' => $totalMissing,
            'totalBooks' => $totalBooks,
            'completedCount' => $completedCount,
            'progressPercentage' => $progressPercentage,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $validated = $request->validate([
            'book_id' => ['required', 'string', 'exists:books,id'],
            'description' => ['required', 'string', 'min:10'],
        ]);

        $book = Book::query()->findOrFail($validated['book_id']);
        $book->description = trim($validated['description']);
        $book->status_updated_by = $admin->id;
        $book->save();

        // Find next pending book ID (excluding the one just saved)
        $nextBook = Book::query()
            ->where('id', '!=', $book->id)
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', '')
                    ->orWhereRaw("TRIM(description) = ''");
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $message = "Description updated successfully for \"{$book->title}\".";

        if ($nextBook) {
            return redirect()
                ->route('admin.missing-descriptions.index', ['book_id' => $nextBook->id])
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.missing-descriptions.index')
            ->with('success', $message . " All books now have descriptions!");
    }

    public function skip(Request $request): RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof RedirectResponse) {
            return $admin;
        }

        $currentBookId = $request->input('book_id');

        $pendingQuery = Book::query()
            ->where(function ($query) {
                $query->whereNull('description')
                    ->orWhere('description', '')
                    ->orWhereRaw("TRIM(description) = ''");
            })
            ->orderBy('created_at', 'desc');

        if ($currentBookId) {
            $pendingQuery->where('id', '!=', $currentBookId);
        }

        $nextBook = $pendingQuery->first();

        if ($nextBook) {
            return redirect()->route('admin.missing-descriptions.index', ['book_id' => $nextBook->id]);
        }

        return redirect()->route('admin.missing-descriptions.index');
    }
}

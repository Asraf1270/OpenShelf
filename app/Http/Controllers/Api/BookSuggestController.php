<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Support\ImageUrl;
use Illuminate\Http\Request;

/**
 * Lightweight search-suggestions endpoint.
 *
 * Returns a small set of books (title, author, cover, id) that match
 * the query so the frontend can display a typeahead dropdown.
 */
class BookSuggestController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = trim($request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        try {
            // Use Scout for relevance-ranked results
            $ids = Book::search($query)->keys()->take(8)->all();

            if (empty($ids)) {
                // Fallback to a simple LIKE query
                $books = Book::query()
                    ->select(['id', 'title', 'author', 'category', 'cover_image', 'status'])
                    ->where(function ($q) use ($query) {
                        $q->where('title', 'like', "%{$query}%")
                          ->orWhere('author', 'like', "%{$query}%");
                    })
                    ->limit(6)
                    ->get();
            } else {
                $books = Book::query()
                    ->select(['id', 'title', 'author', 'category', 'cover_image', 'status'])
                    ->whereIn('id', $ids)
                    ->get()
                    // Preserve Scout relevance order
                    ->sortBy(fn ($book) => array_search($book->id, $ids))
                    ->values();
            }

            $suggestions = $books->map(fn (Book $book) => [
                'id'        => $book->id,
                'title'     => $book->title,
                'author'    => $book->author,
                'category'  => $book->category,
                'cover_url' => ImageUrl::cover($book->cover_image),
                'status'    => strtolower($book->status ?? 'available'),
            ])->values();

            return response()->json(['suggestions' => $suggestions]);
        } catch (\Throwable $e) {
            return response()->json(['suggestions' => []], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BookQueryService;
use Illuminate\Http\Request;

class BookApiController extends Controller
{
    public function __construct(private BookQueryService $bookQueryService)
    {
    }

    public function index(Request $request)
    {
        $cursorDate = $request->input('cursor_date');
        $cursorId = $request->input('cursor_id');
        $cursorDate = in_array($cursorDate, [null, '', 'null'], true) ? null : $cursorDate;
        $cursorId = in_array($cursorId, [null, '', 'null'], true) ? null : $cursorId;

        $limit = min(100, (int) $request->input('limit', 25));
        $search = $request->input('search', '');
        $selectedCategories = (array) $request->input('categories', []);
        $availability = $request->input('availability', '');
        $hall = $request->input('hall', '');
        $sort = $request->input('sort', 'newest');

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
}

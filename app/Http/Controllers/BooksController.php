<?php

namespace App\Http\Controllers;

use App\Services\BookQueryService;
use Illuminate\Http\Request;

class BooksController extends Controller
{
    public function __construct(private BookQueryService $bookQueryService)
    {
    }

    public function index(Request $request)
    {
        $search = $request->input('search', $request->input('q', ''));
        $selectedCategories = (array) $request->input('categories', []);
        $availability = $request->input('availability', '');
        $hallFilter = $request->input('hall', '');
        $sortParam = $request->input('sort', 'newest');
        $limit = 25;

        $filteredBooks = $this->bookQueryService->getBooks(
            $search,
            $selectedCategories,
            $availability,
            $hallFilter,
            $limit,
            null,
            null,
            $sortParam,
        );

        $categories = $this->bookQueryService->getCategories();
        $lastBook = $filteredBooks->last();

        return view('books', [
            'seoTitle' => 'Browse Books - OpenShelf',
            'seoDesc' => 'Discover and borrow books shared by students on OpenShelf.',
            'filteredBooks' => $filteredBooks,
            'categories' => $categories,
            'search' => $search,
            'selectedCategories' => $selectedCategories,
            'availability' => $availability,
            'hallFilter' => $hallFilter,
            'sortParam' => $sortParam,
            'limit' => $limit,
            'initialCursor' => [
                'date' => $lastBook?->created_at,
                'id' => $lastBook?->id,
            ],
            'userHall' => $request->session()->get('user_hall'),
        ]);
    }
}

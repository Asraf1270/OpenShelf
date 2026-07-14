<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookQueryService
{
    public function getBooks(
        string $search = '',
        array $selectedCategories = [],
        string $availability = '',
        string $hall = '',
        int $limit = 25,
        ?string $cursorDate = null,
        ?string $cursorId = null,
        string $sort = 'newest',
    ): Collection {
        $query = $this->buildBooksQuery($search, $selectedCategories, $availability, $hall);

        if ($cursorDate && $cursorId) {
            if ($sort === 'oldest') {
                $query->where(function (Builder $builder) use ($cursorDate, $cursorId) {
                    $builder->where('b.created_at', '>', $cursorDate)
                        ->orWhere(function (Builder $nested) use ($cursorDate, $cursorId) {
                            $nested->where('b.created_at', '=', $cursorDate)
                                ->where('b.id', '>', $cursorId);
                        });
                });
            } else {
                $query->where(function (Builder $builder) use ($cursorDate, $cursorId) {
                    $builder->where('b.created_at', '<', $cursorDate)
                        ->orWhere(function (Builder $nested) use ($cursorDate, $cursorId) {
                            $nested->where('b.created_at', '=', $cursorDate)
                                ->where('b.id', '<', $cursorId);
                        });
                });
            }
        }

        if ($sort === 'oldest') {
            $query->orderBy('b.created_at')->orderBy('b.id');
        } else {
            $query->orderByDesc('b.created_at')->orderByDesc('b.id');
        }

        $books = $query->limit($limit)->get();

        if (! empty($search) && $books->count() < 4 && ! $cursorDate) {
            $related = $this->getRelatedBooksForSearch(
                $search,
                $books->pluck('id')->all(),
                8 - $books->count(),
            );

            return $books->concat($related)->take($limit);
        }

        return $books;
    }

    public function buildBooksQuery(
        string $search = '',
        array $selectedCategories = [],
        string $availability = '',
        string $hall = '',
    ): Builder {
        $query = Book::query()
            ->from('books as b')
            ->leftJoin('users as u', 'b.owner_id', '=', 'u.id')
            ->select([
                'b.id',
                'b.title',
                'b.author',
                'b.category',
                'b.status',
                'b.created_at',
                'b.cover_image',
                'b.rating',
                'b.rating_count',
                'b.owner_id',
                'b.hall',
                DB::raw('u.name as owner_name'),
                DB::raw('u.profile_pic as owner_avatar'),
                DB::raw('u.hall as owner_hall'),
            ]);

        if ($availability !== '') {
            $query->where('b.status', $availability);
        }

        if ($hall !== '') {
            $query->where('b.hall', $hall);
        }

        if (! empty($selectedCategories)) {
            $query->whereIn('b.category', $selectedCategories);
        }

        if (trim($search) !== '') {
            $searchVal = '%' . trim($search) . '%';
            $query->where(function (Builder $builder) use ($searchVal) {
                $builder->where('b.title', 'like', $searchVal)
                    ->orWhere('b.author', 'like', $searchVal)
                    ->orWhere('b.publisher', 'like', $searchVal)
                    ->orWhere('u.name', 'like', $searchVal);
            });
        }

        return $query;
    }

    public function getCategories(): array
    {
        return Book::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    public function getRelatedBooks(string $category, string $excludeId, int $limit = 4): Collection
    {
        if ($category === '') {
            return collect();
        }

        return Book::query()
            ->from('books as b')
            ->leftJoin('users as u', 'b.owner_id', '=', 'u.id')
            ->select([
                'b.id',
                'b.title',
                'b.author',
                'b.category',
                'b.status',
                'b.created_at',
                'b.cover_image',
                'b.rating',
                'b.rating_count',
                'b.owner_id',
                'b.hall',
                DB::raw('u.name as owner_name'),
                DB::raw('u.profile_pic as owner_avatar'),
                DB::raw('u.hall as owner_hall'),
            ])
            ->where('b.category', $category)
            ->where('b.id', '!=', $excludeId)
            ->where('b.status', 'available')
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getRelatedBooksForSearch(string $search, array $excludeIds = [], int $limit = 6): Collection
    {
        if ($search === '' || $limit <= 0) {
            return collect();
        }

        $related = collect();

        $ownerIds = Book::query()
            ->from('books as b')
            ->leftJoin('users as u', 'b.owner_id', '=', 'u.id')
            ->where(function (Builder $builder) use ($search) {
                $builder->where('u.name', 'like', "%{$search}%")
                    ->orWhere('b.title', 'like', "%{$search}%");
            })
            ->distinct()
            ->limit(2)
            ->pluck('b.owner_id');

        if ($ownerIds->isNotEmpty()) {
            $related = $related->concat(
                $this->relatedQuery($excludeIds)
                    ->whereIn('b.owner_id', $ownerIds)
                    ->limit(3)
                    ->get()
            );
        }

        $categories = Book::query()
            ->where('category', 'like', "%{$search}%")
            ->orWhere('title', 'like', "%{$search}%")
            ->distinct()
            ->limit(2)
            ->pluck('category');

        $allCategories = $categories->flatMap(function ($category) {
            return array_merge([$category], $this->getRelatedCategories($category));
        })->unique()->filter()->values();

        if ($allCategories->isNotEmpty() && $related->count() < $limit) {
            $excludeNow = array_merge($excludeIds, $related->pluck('id')->all());
            $related = $related->concat(
                $this->relatedQuery($excludeNow)
                    ->whereIn('b.category', $allCategories)
                    ->orderByDesc('b.views')
                    ->limit($limit - $related->count())
                    ->get()
            );
        }

        if ($related->count() < $limit) {
            $excludeNow = array_merge($excludeIds, $related->pluck('id')->all());
            $related = $related->concat(
                $this->relatedQuery($excludeNow)
                    ->where('b.publisher', 'like', "%{$search}%")
                    ->orderByDesc('b.views')
                    ->limit($limit - $related->count())
                    ->get()
            );
        }

        if ($related->count() < 2) {
            $excludeNow = array_merge($excludeIds, $related->pluck('id')->all());
            $related = $related->concat(
                $this->relatedQuery($excludeNow)
                    ->orderByDesc('b.views')
                    ->inRandomOrder()
                    ->limit($limit - $related->count())
                    ->get()
            );
        }

        return $related->take($limit);
    }

    public function formatBookForApi(Book $book): array
    {
        $data = $book->toArray();
        $data['owner_avatar'] = $this->resolveOwnerAvatarPath($book->owner_avatar ?? '');
        $data['cover_image'] = $this->resolveCoverPath($book->cover_image ?? '');

        return $data;
    }

    public function resolveCoverPath(?string $coverImage): string
    {
        if (empty($coverImage)) {
            return asset('images/default-book-cover.jpg');
        }

        $filename = basename(ltrim($coverImage, '/'));
        $fullRelative = 'uploads/book_cover/' . $filename;
        $thumbRelative = 'uploads/book_cover/thumb_' . $filename;

        if (file_exists(public_path($fullRelative))) {
            return asset($fullRelative);
        }

        if (file_exists(public_path($thumbRelative))) {
            return asset($thumbRelative);
        }

        $relative = ltrim($coverImage, '/');

        return file_exists(public_path($relative))
            ? asset($relative)
            : asset('images/default-book-cover.jpg');
    }

    public function resolveOwnerAvatarPath(?string $ownerAvatar): string
    {
        if (! empty($ownerAvatar) && $ownerAvatar !== 'default-avatar.jpg') {
            $relative = 'uploads/profile/' . ltrim($ownerAvatar, '/');

            if (file_exists(public_path($relative))) {
                return asset($relative);
            }
        }

        return asset('images/avatars/default.jpg');
    }

    private function relatedQuery(array $excludeIds): Builder
    {
        $query = Book::query()
            ->from('books as b')
            ->leftJoin('users as u', 'b.owner_id', '=', 'u.id')
            ->select([
                'b.id',
                'b.title',
                'b.author',
                'b.category',
                'b.status',
                'b.created_at',
                'b.cover_image',
                'b.rating',
                'b.rating_count',
                'b.owner_id',
                'b.hall',
                DB::raw('u.name as owner_name'),
                DB::raw('u.profile_pic as owner_avatar'),
                DB::raw('u.hall as owner_hall'),
            ])
            ->where('b.status', 'available');

        if (! empty($excludeIds)) {
            $query->whereNotIn('b.id', $excludeIds);
        }

        return $query;
    }

    private function getRelatedCategories(string $category): array
    {
        $path = config_path('category_relations.json');

        if (! file_exists($path)) {
            return [];
        }

        $mapping = json_decode(file_get_contents($path), true);

        return $mapping[$category] ?? [];
    }
}

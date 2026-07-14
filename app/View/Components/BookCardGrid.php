<?php

namespace App\View\Components;

use App\Models\Book;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BookCardGrid extends Component
{
    public array $books;
    public string $id;
    public string $gridClass;
    public bool $showOwner;
    public ?string $extraInfoKey;
    public string $extraInfoLabel;
    public bool $skeleton;
    public int $count;

    public function __construct(
        array $books = [],
        string $id = '',
        string $gridClass = 'book-grid',
        bool $showOwner = true,
        ?string $extraInfoKey = null,
        string $extraInfoLabel = '',
        bool $skeleton = false,
        int $count = 4,
    ) {
        $this->books = $this->normalizeBooks($books);
        $this->id = $id;
        $this->gridClass = $gridClass;
        $this->showOwner = $showOwner;
        $this->extraInfoKey = $extraInfoKey;
        $this->extraInfoLabel = $extraInfoLabel;
        $this->skeleton = $skeleton;
        $this->count = $count;
    }

    public function render(): View|Closure|string
    {
        return view('components.book-card-grid');
    }

    private function normalizeBooks(array $books): array
    {
        if ($this->skeleton) {
            return array_fill(0, $this->count, []);
        }

        return array_map(fn ($book) => $this->normalizeBook($book), $books);
    }

    private function normalizeBook(mixed $book): array
    {
        if ($book instanceof Book) {
            $data = $book->toArray();
        } elseif (is_array($book)) {
            $data = $book;
        } else {
            return [];
        }

        $data['id'] = $data['id'] ?? ($data['book_id'] ?? '');
        $data['title'] = $data['title'] ?? 'Untitled';
        $data['author'] = $data['author'] ?? 'Unknown Author';
        $data['category'] = $data['category'] ?? 'General';
        $data['status'] = strtolower($data['status'] ?? 'available');
        $data['created_at'] = $data['created_at'] ?? '';
        $data['rating'] = (float) ($data['rating'] ?? 0);
        $data['rating_count'] = (int) ($data['rating_count'] ?? 0);
        $data['owner_name'] = $data['owner_name'] ?? 'Unknown Owner';
        $data['owner_avatar'] = $data['owner_avatar'] ?? '';
        $data['cover_image'] = $data['cover_image'] ?? '';
        $data['cover_url'] = $this->resolveCoverUrl($data['cover_image'] ?? '');
        $data['owner_avatar_url'] = $this->resolveAvatarUrl($data['owner_avatar'] ?? '');

        return $data;
    }

    private function resolveCoverUrl(string $coverImage): string
    {
        if (empty($coverImage)) {
            return asset('images/default-book-cover.jpg');
        }

        $relative = ltrim($coverImage, '/');
        $publicPath = public_path($relative);

        return file_exists($publicPath) ? asset($relative) : asset('images/default-book-cover.jpg');
    }

    private function resolveAvatarUrl(string $ownerAvatar): string
    {
        if (! empty($ownerAvatar) && $ownerAvatar !== 'default-avatar.jpg') {
            $relative = 'uploads/profile/' . ltrim($ownerAvatar, '/');
            $publicPath = public_path($relative);

            if (file_exists($publicPath)) {
                return asset($relative);
            }
        }

        return asset('images/avatars/default.jpg');
    }
}

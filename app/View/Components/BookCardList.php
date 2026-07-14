<?php

namespace App\View\Components;

use App\Models\Book;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BookCardList extends Component
{
    public array $books;
    public string $id;
    public string $listClass;
    public bool $showOwner;
    public ?string $extraInfoKey;
    public string $extraInfoLabel;
    public bool $skeleton;
    public int $count;

    public function __construct(
        array $books = [],
        string $id = '',
        string $listClass = 'book-list',
        bool $showOwner = true,
        ?string $extraInfoKey = null,
        string $extraInfoLabel = '',
        bool $skeleton = false,
        int $count = 3,
    ) {
        $this->books = $this->normalizeBooks($books);
        $this->id = $id;
        $this->listClass = $listClass;
        $this->showOwner = $showOwner;
        $this->extraInfoKey = $extraInfoKey;
        $this->extraInfoLabel = $extraInfoLabel;
        $this->skeleton = $skeleton;
        $this->count = $count;
    }

    public function render(): View|Closure|string
    {
        return view('components.book-card-list');
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
        $data['rating'] = (float) ($data['rating'] ?? 0);
        $data['rating_count'] = (int) ($data['rating_count'] ?? 0);
        $data['owner_id'] = $data['owner_id'] ?? '';
        $data['owner_name'] = $data['owner_name'] ?? ($data['owner']['name'] ?? 'Owner');
        $data['owner_avatar'] = $data['owner_avatar'] ?? '';
        $data['owner_hall'] = $data['owner_hall'] ?? ($data['hall'] ?? '');
        $data['cover_image'] = $data['cover_image'] ?? '';
        $data['cover_url'] = $this->resolveCoverUrl($data['cover_image'] ?? '');
        $data['owner_avatar_url'] = $this->resolveAvatarUrl($data['owner_avatar'] ?? '');
        $data['display_hall'] = $this->resolveHallName($data['owner_hall'] ?? '');

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

    private function resolveHallName(string $hallId): string
    {
        $halls = [
            '1' => 'Amar Ekushey Hall',
            '2' => 'Dr. Muhammad Shahidullah Hall',
            '3' => 'Fazlul Huq Muslim Hall',
            '4' => 'Salimullah Muslim Hall',
            '5' => 'Shahid Sergeant Zahurul Haq Hall',
            '6' => 'Haji Muhammad Mohsin Hall',
            '7' => 'Sir A.F. Rahman Hall',
            '8' => 'Masterda Surja Sen Hall',
            '9' => 'Kobi Jashimuddin Hall',
            '10' => 'Muktijoddha Ziaur Rahman Hall',
            '11' => 'Shaheed Sharif Osman Hadi Hall',
            '12' => 'Bijoy Ekattor Hall',
            '13' => 'Jagannath Hall',
            '14' => 'Ruqayyah Hall',
            '15' => 'Shamsun Nahar Hall',
            '16' => 'Bangladesh-Kuwait Maitree Hall',
            '17' => 'Begum Fazilatunnesa Mujib Hall',
            '18' => 'Kobi Sufiya Kamal Hall',
        ];

        return $halls[$hallId] ?? 'N/A';
    }
}

<?php

namespace Tests\Unit;

use App\View\Components\BookCardGrid;
use App\View\Components\BookCardList;
use PHPUnit\Framework\TestCase;

class BookCardComponentTest extends TestCase
{
    public function test_grid_component_keeps_full_asset_urls_for_covers_and_avatars(): void
    {
        $component = new BookCardGrid([
            [
                'id' => 1,
                'title' => 'Test Book',
                'author' => 'Author',
                'category' => 'General',
                'status' => 'available',
                'cover_image' => 'https://example.com/storage/uploads/book_cover/sample.jpg',
                'owner_avatar' => 'https://example.com/storage/uploads/profile/avatar.png',
            ],
        ]);

        $this->assertSame('https://example.com/storage/uploads/book_cover/sample.jpg', $component->books[0]['cover_url']);
        $this->assertSame('https://example.com/storage/uploads/profile/avatar.png', $component->books[0]['owner_avatar_url']);
    }

    public function test_list_component_keeps_full_asset_urls_for_covers_and_avatars(): void
    {
        $component = new BookCardList([
            [
                'id' => 1,
                'title' => 'Test Book',
                'author' => 'Author',
                'category' => 'General',
                'status' => 'available',
                'cover_image' => 'https://example.com/storage/uploads/book_cover/sample.jpg',
                'owner_avatar' => 'https://example.com/storage/uploads/profile/avatar.png',
            ],
        ]);

        $this->assertSame('https://example.com/storage/uploads/book_cover/sample.jpg', $component->books[0]['cover_url']);
        $this->assertSame('https://example.com/storage/uploads/profile/avatar.png', $component->books[0]['owner_avatar_url']);
    }
}

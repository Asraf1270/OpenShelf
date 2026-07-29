<?php

namespace Tests\Feature;

use App\Models\BorrowRequest;
use App\Models\User;
use App\View\Components\BookCardGrid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_card_grid_converts_hall_ids_to_names(): void
    {
        $component = new BookCardGrid([
            ['owner_hall' => '2', 'owner_name' => 'Owner User'],
        ]);

        $method = new \ReflectionMethod($component, 'normalizeBook');
        $method->setAccessible(true);

        $normalizedBook = $method->invoke($component, ['owner_hall' => '2', 'owner_name' => 'Owner User']);

        $this->assertSame('Dr. Muhammad Shahidullah Hall', $normalizedBook['display_hall']);
    }

    public function test_profile_shows_borrower_and_lent_user_details(): void
    {
        $owner = User::create([
            'id' => Str::random(16),
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'phone' => '1111111111',
            'password_hash' => Hash::make('password'),
            'hall' => '2',
        ]);

        $borrower = User::create([
            'id' => Str::random(16),
            'name' => 'Borrower User',
            'email' => 'borrower@example.com',
            'phone' => '2222222222',
            'password_hash' => Hash::make('password'),
            'hall' => '3',
        ]);

        BorrowRequest::create([
            'id' => Str::random(16),
            'book_id' => Str::random(16),
            'borrower_id' => $borrower->id,
            'owner_id' => $owner->id,
            'status' => 'approved',
            'book_title' => 'Test Book',
            'book_author' => 'Jane Doe',
            'book_cover' => '',
            'owner_name' => $owner->name,
            'borrower_name' => $borrower->name,
        ]);

        $response = $this->withSession(['user_id' => $owner->id])
            ->get('/profile?id=' . $owner->id);

        $response->assertOk();
        $response->assertSee('Borrower User');
        $response->assertSee('Dr. Muhammad Shahidullah Hall');
        $response->assertSee('Fazlul Huq Muslim Hall');
    }
}

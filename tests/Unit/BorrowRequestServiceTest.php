<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use App\Services\BookQueryService;
use App\Services\BorrowRequestService;
use App\Services\MailerService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BorrowRequestServiceTest extends TestCase
{
    use RefreshDatabase;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function test_create_request_sends_notification_without_email(): void
    {
        $bookQueryService = Mockery::mock(BookQueryService::class);
        $mailerService = Mockery::mock(MailerService::class);
        $notificationService = Mockery::mock(NotificationService::class);

        $mailerService->shouldNotReceive('sendTemplate');
        $notificationService->shouldReceive('create')
            ->once()
            ->withArgs(function (string $userId, string $type, string $title, string $message, string $link): bool {
                return $userId === 'owner-001'
                    && $type === 'borrow_request'
                    && $title === 'New Borrow Request'
                    && str_contains($message, 'Alex Borrower')
                    && str_starts_with($link, '/requests/?id=REQ');
            });

        $service = new BorrowRequestService(
            $bookQueryService,
            $notificationService,
            $mailerService,
        );

        $owner = User::create([
            'id' => 'owner-001',
            'name' => 'Owner One',
            'email' => 'owner@example.com',
            'phone' => '0123456789',
            'password_hash' => 'secret',
        ]);

        $borrower = User::create([
            'id' => 'borrower-1',
            'name' => 'Alex Borrower',
            'email' => 'alex@example.com',
            'phone' => '0987654321',
            'department' => 'CSE',
            'password_hash' => 'secret',
        ]);

        $book = Book::create([
            'id' => 'BOOK000001',
            'title' => 'Clean Code',
            'author' => 'Robert C. Martin',
            'owner_id' => $owner->id,
            'status' => 'available',
        ]);

        $borrowRequest = $service->createRequest(
            $book,
            $borrower,
            $owner,
            'Alex Borrower',
            14,
            'Please lend it to me.',
        );

        $this->assertSame($book->id, $borrowRequest->book_id);
        $this->assertSame('pending', $borrowRequest->status);
        $this->assertSame('reserved', $book->fresh()->status);
        $this->assertDatabaseHas('borrow_requests', [
            'id' => $borrowRequest->id,
            'owner_id' => $owner->id,
            'borrower_id' => $borrower->id,
            'status' => 'pending',
        ]);
    }
}
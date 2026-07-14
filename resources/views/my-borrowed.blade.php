@extends('layouts.app')

@section('content')
<main class="page-container">
    <header class="page-header">
        <h1 class="fade-in">My Borrowed Books</h1>
        <p class="fade-in" style="animation-delay: 0.1s">Manage your active reads and track return deadlines.</p>
    </header>

    <div class="stats-bar fade-in" style="animation-delay: 0.2s">
        <div class="stat-item">
            <span class="value">{{ $totalActive }}</span>
            <span class="label">Currently Reading</span>
        </div>
        <div class="stat-item">
            <span class="value">{{ $pastBorrows->count() }}</span>
            <span class="label">Books Returned</span>
        </div>
        <div class="stat-item">
            <span class="value">{{ now()->format('M j') }}</span>
            <span class="label">Today's Date</span>
        </div>
    </div>

    <section>
        <h2 class="section-title fade-in" style="animation-delay: 0.3s">
            <i class="fas fa-bookmark"></i> Active Borrows
        </h2>

        @if ($activeBorrows->isEmpty())
            <div class="empty-state fade-in" style="animation-delay: 0.4s">
                <i class="fas fa-layer-group"></i>
                <h2>Your shelf is empty</h2>
                <p>Start your reading journey by requesting a book from the library.</p>
                <a href="{{ route('books') }}" class="btn btn-primary" style="padding: 12px 24px; border-radius: 12px;">
                    <i class="fas fa-search"></i> Discover Books
                </a>
            </div>
        @else
            <div class="borrow-grid">
                @foreach ($activeBorrows as $index => $borrow)
                    @php
                        $dueDate = $borrow->expected_return_date;
                        $diff = $dueDate ? now()->startOfDay()->diffInDays($dueDate->startOfDay(), false) : 0;
                        $isOverdue = $dueDate && $dueDate->startOfDay()->lt(now()->startOfDay());
                        $totalDays = $borrow->duration_days ?? 14;
                        $remainingPercent = $dueDate ? max(0, min(100, ($diff / max($totalDays, 1)) * 100)) : 100;
                        $progressWidth = 100 - $remainingPercent;
                    @endphp
                    <div class="borrow-card fade-in" style="animation-delay: {{ 0.4 + ($index * 0.1) }}s">
                        <img src="{{ $thumbCover($borrow->cover_image) }}" alt="{{ $borrow->book_title }}" class="book-thumb">
                        <div class="card-content">
                            <div class="book-info">
                                <div class="meta-tags">
                                    <span class="tag tag-owner">{{ $borrow->owner_name }}</span>
                                    @if ($dueDate)
                                        <span class="tag {{ $isOverdue ? 'tag-due' : 'tag-days' }}">
                                            {{ $isOverdue ? 'Overdue' : abs($diff) . ' days left' }}
                                        </span>
                                    @endif
                                </div>
                                <h3>{{ $borrow->book_title }}</h3>
                                <p class="author">by {{ $borrow->book_author }}</p>

                                @if ($dueDate)
                                    <div class="due-progress">
                                        <div class="progress-bar" style="width: {{ $progressWidth }}%; {{ $isOverdue ? 'background: #f5365c;' : '' }}"></div>
                                    </div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted); display: flex; justify-content: space-between; font-weight: 600;">
                                        <span>Starts: {{ $borrow->request_date?->format('M j') }}</span>
                                        <span style="{{ $isOverdue ? 'color: var(--danger);' : '' }}">Due: {{ $dueDate->format('M j') }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="actions">
                                <a href="{{ route('return-book', ['id' => $borrow->id]) }}" class="btn-return">
                                    <i class="fas fa-undo"></i> Return Book
                                </a>
                                <a href="{{ route('book.show', ['id' => $borrow->book_id]) }}" class="btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($pastBorrows->isNotEmpty())
        <section style="margin-top: var(--space-xxl); margin-bottom: 4rem;">
            <h2 class="section-title fade-in" style="animation-delay: 0.6s">
                <i class="fas fa-history"></i> Just Finished
            </h2>
            <div class="recent-list fade-in" style="animation-delay: 0.7s">
                @foreach ($pastBorrows as $past)
                    <div class="recent-card">
                        <img src="{{ $thumbCover($past->cover_image) }}" alt="{{ $past->book_title }}" class="recent-thumb">
                        <div class="card-content">
                            <h4 style="font-size: 1rem; margin: 0; font-weight: 700;">{{ $past->book_title }}</h4>
                            <p style="font-size: 0.75rem; color: var(--text-muted); margin: 4px 0 0; font-weight: 500;">
                                <i class="fas fa-check-circle" style="color: var(--success); font-size: 0.7rem;"></i>
                                Returned on {{ $past->returned_at?->format('M j, Y') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection

@extends('layouts.app')

@section('content')
<div class="borrow-container">
    <div class="borrow-card">
        <h1 style="margin-bottom: 1rem;">📖 Request to Borrow</h1>

        @if ($error)
            <div class="alert alert-danger" style="background:rgba(239,68,68,0.1); color:#ef4444; padding:0.75rem; border-radius:0.5rem; margin-bottom:1rem;">
                {{ $error }}
            </div>
        @endif

        <div class="info-box">
            <i class="fas fa-envelope" style="color: var(--primary);"></i>
            <span>The owner will be notified about your request.</span>
        </div>

        <div class="book-summary">
            <img src="{{ $coverImage }}" alt="{{ $book->title }}">
            <div>
                <h3>{{ $book->title }}</h3>
                <p>by {{ $book->author }}</p>
                <p style="color: #64748b; font-size: 0.85rem;">Owner: {{ $owner?->name ?? 'Unknown' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('borrow-request', ['book_id' => $book->id]) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">📅 Borrow Duration</label>
                <select name="duration" class="form-select">
                    <option value="7">7 days</option>
                    <option value="14" selected>14 days</option>
                    <option value="21">21 days</option>
                    <option value="30">30 days</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">💬 Message to Owner (Optional)</label>
                <textarea name="message" class="form-control" rows="4"
                          placeholder="Introduce yourself and explain why you'd like to borrow this book..."></textarea>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-paper-plane"></i> Send Request
            </button>

            <a href="{{ route('book.show', ['id' => $book->id]) }}" class="btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection

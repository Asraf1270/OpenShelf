@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/borrow-request.css') }}?v={{ file_exists(public_path('css/borrow-request.css')) ? filemtime(public_path('css/borrow-request.css')) : '1' }}">
@endpush

@section('content')
<div class="borrow-container">
    <div class="borrow-card">
        <h1>📖 Request to Borrow</h1>

        @if ($error)
            <div class="alert alert-danger borrow-error">
                {{ $error }}
            </div>
        @endif

        <div class="info-box">
            <i class="fas fa-envelope"></i>
            <span>The owner will be notified about your request.</span>
        </div>

        <div class="book-summary">
            <img src="{{ $coverImage }}" alt="{{ $book->title }}">
            <div class="book-summary-meta">
                <h3>{{ $book->title }}</h3>
                <p>by {{ $book->author }}</p>
                <p class="book-owner">Owner: {{ $owner?->name ?? 'Unknown' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('borrow-request', ['book_id' => $book->id]) }}" class="borrow-form">
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

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Request
                </button>

                <a href="{{ route('book.show', ['id' => $book->id]) }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

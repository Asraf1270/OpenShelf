@extends('emails.layout', ['type' => 'success'])

@section('content')
@php
    $bookLink = $base_url . '/book?id=' . urlencode($book_id ?? '');
@endphp
<div class="greeting">Hello, {{ $user_name ?? 'Reader' }} 👋</div>

<p style="text-align: center; color: #475569; margin-bottom: 0;">
    Great news! A book on your wishlist is now available to borrow.
</p>

<div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 16px; padding: 28px; margin: 28px 0; text-align: center;">
    <div style="font-size: 36px; margin-bottom: 12px;">📗</div>
    <div style="font-size: 22px; font-weight: 800; color: #15803d; margin-bottom: 6px;">
        "{{ $book_title ?? 'Untitled' }}"
    </div>
    <div style="color: #166534; font-size: 14px; font-weight: 500;">
        by {{ $book_author ?? 'Unknown Author' }}
    </div>
</div>

@if (!empty($queue_position) && $queue_position == 1)
<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px 20px; margin: 0 0 24px;">
    <span style="font-size: 24px;">🥇</span>
    <div>
        <div style="font-weight: 700; color: #92400e; font-size: 15px;">You're first in line!</div>
        <div style="font-size: 13px; color: #b45309; margin-top: 2px;">
            Be quick — request to borrow before someone else does.
        </div>
    </div>
</div>
@else
<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 20px; margin: 0 0 24px; font-size: 14px; color: #475569;">
    You added this book to your wishlist. Head over and request it now before it's gone!
</div>
@endif

<div style="text-align: center; margin-top: 10px;">
    <a href="{{ $bookLink }}" class="button" style="background-color: #16a34a; padding: 14px 40px; font-size: 16px;">
        Borrow This Book &rarr;
    </a>
</div>

<p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 28px;">
    You received this because you wishlisted this book on OpenShelf.<br>
    This notification will not be sent again for the same availability window.
</p>
@endsection

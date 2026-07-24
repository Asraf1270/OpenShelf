@extends('emails.layout', ['type' => 'neutral'])

@section('content')
@php
    $displayName = $user_name ?? $borrower_name ?? 'Reader';
@endphp
<div class="greeting">Hello, {{ $displayName }}</div>
<p style="text-align: center;">We wanted to let you know that your request for <strong>"{{ $book_title ?? 'the book' }}"</strong> has been declined by the owner.</p>

@if (!empty($rejection_reason))
<div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 16px; padding: 25px; margin: 30px 0;">
    <p style="font-weight: 700; color: #991b1b; margin: 0 0 10px 0;">Reason provided:</p>
    <p style="color: #b91c1c; margin: 0;">{!! nl2br(e($rejection_reason)) !!}</p>
</div>
@endif

<p style="text-align: center; font-size: 14px; color: #64748b;">Don't worry! There are plenty of other books available. You can try requesting a different book or browse the library for new arrivals.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ $base_url }}/books" class="button">Browse More Books</a>
</div>
@endsection

@extends('emails.layout', ['type' => 'success'])

@section('content')
@php
    $displayName = $borrower_name ?? $user_name ?? 'Reader';
@endphp
<div class="greeting">Thank you, {{ $displayName }}!</div>
<p style="text-align: center;">We've successfully processed the return of the book you borrowed. We hope it was an enlightening read!</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <div style="font-size: 40px; margin-bottom: 15px;">📚</div>
    <div style="font-size: 20px; font-weight: 800; color: #065f46; margin-bottom: 5px;">"{{ $book_title ?? 'the book' }}"</div>
    <div style="color: #059669; font-size: 14px; font-weight: 600;">Returned on {{ (isset($return_date) && strtotime($return_date) > 0) ? date('F j, Y', strtotime($return_date)) : date('F j, Y') }}</div>
</div>

<p style="text-align: center;">What's next? Browse our collection and find your next favorite book today.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ $base_url }}/books" class="button">Find My Next Book</a>
</div>
@endsection

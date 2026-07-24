@extends('emails.layout', ['type' => ($days_remaining ?? 0) < 0 ? 'danger' : 'warning'])

@section('content')
@php
    $days = $days_remaining ?? 0;
    $isOverdue = $days < 0;
    $overdueDays = abs($days);
    $themeColor = $isOverdue ? '#ef4444' : '#f59e0b';
    $themeBg = $isOverdue ? '#fef2f2' : '#fffbeb';
    $themeBorder = $isOverdue ? '#fecaca' : '#fde68a';
    $displayName = $borrower_name ?? $user_name ?? 'Reader';
@endphp
<div class="greeting">Hello, {{ $displayName }}</div>
<p style="text-align: center;">{{ $isOverdue ? 'This is a reminder that the book you borrowed is past its due date.' : 'This is a friendly reminder that a book you borrowed is due soon.' }}</p>

<div style="background-color: {{ $themeBg }}; border: 1px solid {{ $themeBorder }}; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <p style="font-weight: 700; color: {{ $themeColor }}; margin: 0;">{{ $isOverdue ? 'Overdue Status:' : 'Time Remaining:' }}</p>
    <div style="font-size: 48px; font-weight: 900; color: {{ $themeColor }}; margin: 10px 0;">{{ $isOverdue ? $overdueDays : $days }} Days</div>
    <p style="color: {{ $themeColor }}; font-weight: 600; margin: 0;">{{ $isOverdue ? 'Past Return Date' : 'Until Return Date' }}</p>
</div>

<table style="width: 100%; border-collapse: collapse; margin: 25px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Book:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><strong>"{{ $book_title ?? 'Untitled' }}"</strong></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Owner:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;">{{ $owner_name ?? 'Owner' }}</td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Due Date:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;">{{ (isset($due_date) && strtotime($due_date) > 0) ? date('F j, Y', strtotime($due_date)) : 'N/A' }}</td></tr>
</table>

<p style="text-align: center; font-size: 14px; color: #64748b;">Please plan to return the book by the due date to ensure others can enjoy it too.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ $base_url }}/requests" class="button">View My Borrowed Books</a>
</div>
@endsection

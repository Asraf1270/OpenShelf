@extends('emails.layout', ['type' => 'success'])

@section('content')
@php
    $displayName = $user_name ?? $borrower_name ?? 'Reader';
    $displayDate = $due_date ?? $expected_return ?? 'soon';
@endphp
<div class="greeting">🎉 Request Approved, {{ $displayName }}!</div>
<p style="text-align: center;">Great news! <strong>{{ $owner_name ?? 'The owner' }}</strong> has approved your request to borrow their book.</p>

<div style="font-weight: 700; font-size: 16px; color: #1e293b; margin-top: 30px; margin-bottom: 10px;">Pickup Details:</div>
<table style="width: 100%; border-collapse: collapse; margin: 15px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Book:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><strong>"{{ $book_title ?? 'Untitled' }}"</strong></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Owner:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;">{{ $owner_name ?? 'Owner' }}</td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Room:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;">{{ $owner_room ?? 'N/A' }}</td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Return By:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;">{{ strtotime($displayDate) > 0 ? date('F j, Y', strtotime($displayDate)) : $displayDate }}</td></tr>
</table>

<p style="text-align: center; font-size: 14px; color: #64748b;">Please coordinate with the owner to pick up the book. Happy reading!</p>

<div style="text-align: center; margin-top: 30px;">
    @if (!empty($owner_phone))
    <a href="https://wa.me/88{{ preg_replace('/[^0-9]/', '', $owner_phone) }}" style="display: inline-block; padding: 14px 35px; background-color: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-bottom: 10px;">Contact on WhatsApp</a><br>
    @endif
    <a href="{{ $base_url }}/requests" class="button">View My Requests</a>
</div>
@endsection

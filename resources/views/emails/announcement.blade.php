@extends('emails.layout', ['type' => ($announcement_priority ?? 'info') === 'danger' ? 'danger' : (($announcement_priority ?? 'info') === 'warning' ? 'warning' : 'info')])

@section('content')
@php
    $priorityColors = [
        'info' => '#3b82f6',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
    ];
    $priority = $announcement_priority ?? 'info';
    $priorityColor = $priorityColors[$priority] ?? '#3b82f6';
@endphp
<div class="greeting">Hello, {{ $user_name ?? 'Reader' }}</div>
<p style="text-align: center;">An important update has been posted to the OpenShelf community:</p>

<div style="background-color: #f8fafc; border-left: 4px solid {{ $priorityColor }}; border-radius: 0 16px 16px 0; padding: 25px; margin: 30px 0;">
    <div style="display: inline-block; padding: 4px 12px; background-color: {{ $priorityColor }}; color: #ffffff; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px;">{{ strtoupper($announcement_priority ?? 'INFO') }}</div>
    <div style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 15px;">{{ $announcement_title ?? 'New Announcement' }}</div>
    <div style="line-height: 1.7; color: #374151;">
        {!! nl2br(e($announcement_content ?? '')) !!}
    </div>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ $announcement_link ?? ($base_url . '/announcements') }}" class="button">View Announcement</a>
</div>
@endsection

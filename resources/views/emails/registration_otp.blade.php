@extends('emails.layout', ['type' => 'info'])

@section('content')
@php
    $displayName = $user_name ?? 'Friend';
@endphp
<div class="greeting">Welcome to the community, {{ $displayName }}!</div>
<p style="text-align: center;">Thank you for joining OpenShelf. To complete your registration and verify your email address, please use the verification code below:</p>

<div style="background: #f1f5f9; border: 2px solid #e2e8f0; padding: 25px; text-align: center; font-size: 42px; font-weight: 800; letter-spacing: 10px; color: #4f46e5; border-radius: 16px; margin: 30px 0;">
    {{ $otp ?? '000000' }}
</div>

<div style="text-align: center; font-size: 14px; color: #64748b; margin-top: -10px; margin-bottom: 30px;">
    This code will expire in <strong>{{ $expiry_minutes ?? 15 }} minutes</strong>.
</div>

<p style="text-align: center; font-size: 14px; color: #64748b;">If you did not create an account on OpenShelf, you can safely ignore this email.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ $base_url }}/register/verify" class="button">Verify My Account</a>
</div>

<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8; text-align: center;">
    Requester IP: {{ $ip_address ?? 'N/A' }}
</div>
@endsection

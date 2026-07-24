@extends('emails.layout', ['type' => 'info'])

@section('content')
<div class="greeting">Security Verification</div>
<p style="text-align: center;">Hello {{ $user_name ?? 'Admin' }}, please use the following code to verify your administrative access:</p>

<div style="background: #f1f5f9; border: 2px solid #e2e8f0; padding: 25px; text-align: center; font-size: 42px; font-weight: 800; letter-spacing: 10px; color: #4f46e5; border-radius: 16px; margin: 30px 0;">
    {{ $otp ?? '000000' }}
</div>

<div style="text-align: center; font-size: 14px; color: #64748b; margin-top: -10px; margin-bottom: 30px;">
    Valid for <strong>{{ $expiry_minutes ?? 15 }} minutes</strong>.
</div>

<div style="background-color: #f8fafc; border-radius: 12px; padding: 15px; font-size: 13px; color: #64748b; text-align: center;">
    <strong>Requested from:</strong><br>
    IP: {{ $ip_address ?? 'N/A' }}
</div>
@endsection

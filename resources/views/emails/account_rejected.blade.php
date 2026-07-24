@extends('emails.layout')

@section('content')
    <div class="greeting">Hi {{ $user_name }},</div>

    <p style="margin: 0 0 16px;">We reviewed your OpenShelf registration, but we could not approve your account at this time.</p>

    <div style="padding: 16px; border-radius: 12px; background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; margin: 20px 0;">
        <strong>Reason:</strong> {{ $rejection_reason }}
    </div>

    <p style="margin: 0 0 16px;">If you believe this was a mistake or need help updating your information, contact us at <a href="mailto:{{ $support_email }}">{{ $support_email }}</a>.</p>

    <p style="margin: 24px 0 0;">OpenShelf Support Team</p>
@endsection

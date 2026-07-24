@extends('emails.layout')

@section('content')
    <div class="greeting">Hi {{ $user_name }},</div>

    <p style="margin: 0 0 16px;">Your OpenShelf account has been approved by the admin team.</p>
    <p style="margin: 0 0 16px;">You can now sign in and start browsing, sharing, and borrowing books on the platform.</p>

    <p style="text-align: center;">
        <a href="{{ $login_url }}" class="button">Login to OpenShelf</a>
    </p>

    <p style="margin: 24px 0 0;">Thanks for joining OpenShelf.</p>
@endsection

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Registration - OpenShelf</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { margin: 0; font-family: 'Outfit', sans-serif; background: #0f172a; color: #f8fafc; display: grid; place-items: center; min-height: 100vh; }
        .card { background: #111827; border: 1px solid #334155; border-radius: 20px; padding: 2rem; max-width: 480px; text-align: center; box-shadow: 0 20px 45px rgba(0,0,0,0.3); }
        .icon { font-size: 2.5rem; color: #4C9F8A; margin-bottom: 1rem; }
        a { color: #4C9F8A; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><i class="fas fa-envelope-circle-check"></i></div>
        <h1>Almost there!</h1>

        @if (session('success'))
            <div style="margin: 1rem 0; padding: 1rem; border-radius: 12px; background: rgba(34,197,94,0.12); color: #bbf7d0; border: 1px solid rgba(34,197,94,0.25);">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div style="margin: 1rem 0; padding: 1rem; border-radius: 12px; background: rgba(248,113,113,0.12); color: #fecaca; border: 1px solid rgba(248,113,113,0.25);">
                {{ session('error') }}
            </div>
        @endif

        <p>Your account has been created successfully. A verification code has been sent to <strong>{{ $email }}</strong>.</p>
        <p>Enter the 6-digit verification code below to complete your registration.</p>

        <form method="POST" action="{{ route('register.verify.handle') }}" style="margin-top: 1.5rem; text-align: left;">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label for="otp" style="display: block; margin-bottom: 0.5rem; color: #cbd5e1; font-weight: 600;">Verification Code</label>
                <input id="otp" name="otp" type="text" value="{{ old('otp') }}" maxlength="6" pattern="\d{6}" inputmode="numeric" placeholder="123456" required style="width: 100%; padding: 0.95rem 1rem; border-radius: 12px; border: 1px solid #334155; background: #0f172a; color: #f8fafc;">
                @error('otp')
                    <div style="margin-top: 0.75rem; color: #fecaca; font-size: 0.95rem;">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" style="width: 100%; padding: 1rem; border-radius: 14px; border: none; background: #4c9f8a; color: #fff; font-size: 1rem; font-weight: 700; cursor: pointer;">Verify Account</button>
        </form>

        <p style="margin-top: 1.5rem; color: #94a3b8; font-size: 0.95rem;">If you did not receive a code, check your spam folder or try registering again.</p>
        <p style="margin-top: 0.5rem;"><a href="{{ route('register') }}">Back to registration</a> · <a href="{{ route('login') }}">Sign in</a></p>
    </div>
</body>
</html>

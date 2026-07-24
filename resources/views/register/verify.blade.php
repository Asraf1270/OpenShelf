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
        <p>Your account has been created successfully. A verification code has been prepared for <strong>{{ $email }}</strong>.</p>
        <p>Please continue with the email verification flow in the next step.</p>
        <p><a href="{{ route('register') }}">Back to registration</a></p>
    </div>
</body>
</html>

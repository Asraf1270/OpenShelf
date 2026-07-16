<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - OpenShelf</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --primary-hover: #4C9F8A;
            --bg: #f8fafc;
            --text: #1e293b;
            --text-muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background: linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%);
        }
        .login-box {
            width: 100%;
            max-width: 400px;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
        }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h2 { margin: 0; font-size: 24px; font-weight: 700; }
        .login-header p { margin: 10px 0 0; color: var(--text-muted); font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #475569; font-weight: 500; font-size: 14px; }
        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            background: #fff;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.2);
        }
        button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover { background: var(--primary-hover); }
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
        }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .footer-links { margin-top: 25px; text-align: center; font-size: 14px; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <h2>Admin Portal</h2>
            <p>Welcome back! Please login to continue.</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" placeholder="admin@duopenshelf.top" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit">Sign In</button>
        </form>

        <div class="footer-links">
            <a href="{{ route('home') }}">&larr; Back to OpenShelf</a>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-icon.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --primary-light: #4C9F8A;
            --bg-dark: #0F172A;
            --border-color: #334155;
            --text-main: #F8F9FA;
            --text-muted: #94a3b8;
            --error: #C65D5D;
            --focus-ring: rgba(76, 159, 138, 0.5);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', system-ui, sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-main); min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        .ambient-bg { position: fixed; inset: 0; z-index: -1; overflow: hidden; background: radial-gradient(circle at 10% 20%, rgba(44, 62, 80, 0.25) 0%, transparent 40%), radial-gradient(circle at 90% 80%, rgba(76, 159, 138, 0.2) 0%, transparent 40%), radial-gradient(circle at 50% 50%, rgba(44, 62, 80, 0.1) 0%, transparent 60%), #0f172a; }
        .ambient-bg::after { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)'/%3E%3C/svg%3E"); opacity: 0.03; pointer-events: none; }
        .login-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; width: 100%; }
        .login-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 28px; width: 100%; max-width: 440px; padding: 3.5rem 2.5rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) inset; animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1); position: relative; z-index: 10; }
        @keyframes cardEntrance { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .login-header { text-align: center; margin-bottom: 2rem; }
        .brand-logo { width: 64px; height: 64px; border-radius: 16px; margin-bottom: 1rem; display: inline-block; box-shadow: 0 4px 20px rgba(44, 62, 80, 0.3); }
        .login-header h1 { font-size: 1.75rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; letter-spacing: -0.02em; }
        .login-header p { color: var(--text-muted); font-size: 0.95rem; }
        .alert { padding: 0.875rem 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem; font-weight: 500; }
        .alert-error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.2); color: #99ffcc; }
        .form-group { margin-bottom: 1.25rem; }
        .input-group { position: relative; display: flex; align-items: center; }
        .input-group > i:first-child { position: absolute; left: 1.25rem; color: var(--text-muted); font-size: 1rem; transition: color 0.3s ease; pointer-events: none; }
        .input-group input { width: 100%; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: 12px; padding: 0.875rem 1.25rem 0.875rem 3rem; color: #fff; font-size: 1rem; transition: all 0.2s ease; }
        .input-group input:focus { outline: none; border-color: var(--primary-light); background: var(--bg-dark); box-shadow: 0 0 0 3px var(--focus-ring); }
        .input-group input:focus ~ i:first-child { color: var(--primary-light); }
        .input-group input[type="password"], .input-group input[type="text"] { padding-right: 3rem; }
        .form-options { display: flex; justify-content: space-between; align-items: center; margin: 1.25rem 0; font-size: 0.9rem; }
        .remember-me { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: var(--text-muted); user-select: none; }
        .remember-me input { display: none; }
        .custom-checkbox { width: 18px; height: 18px; border: 1.5px solid var(--border-color); border-radius: 5px; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; background: rgba(15, 23, 42, 0.5); }
        .remember-me input:checked + .custom-checkbox { background: var(--primary-light); border-color: var(--primary-light); }
        .custom-checkbox i { color: #fff; font-size: 0.65rem; opacity: 0; transform: scale(0.5); transition: all 0.2s ease; }
        .remember-me input:checked + .custom-checkbox i { opacity: 1; transform: scale(1); }
        .forgot-password { color: var(--text-muted); text-decoration: none; font-weight: 500; transition: color 0.2s ease; }
        .forgot-password:hover { color: var(--text-main); }
        .btn-login { width: 100%; background: var(--primary); color: #fff; border: none; border-radius: 12px; padding: 1rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
        .btn-login:hover { background: var(--primary-light); transform: translateY(-1px); }
        .btn-login:active { transform: translateY(1px); }
        .register-link { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.95rem; }
        .register-link a { color: var(--primary-light); text-decoration: none; font-weight: 600; margin-left: 0.25rem; transition: color 0.2s ease; }
        .register-link a:hover { color: var(--primary); text-decoration: underline; }
        .security-info { margin-top: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        @media (min-width: 640px) { .login-card { padding: 3rem 2.5rem; } .login-header h1 { font-size: 2rem; } }
    </style>
</head>
<body>
    <div class="ambient-bg"></div>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="{{ asset('images/logo-icon.svg') }}" alt="OpenShelf" class="brand-logo">
                <h1>OpenShelf</h1>
                <p>Login to your portal</p>
            </div>

            @if (session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-phone-flip"></i>
                        <input type="tel" name="phone" id="phone" placeholder="Phone Number (01XXXXXXXXX)" value="{{ old('phone') }}" required pattern="01[3-9]\d{8}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <span class="custom-checkbox"><i class="fas fa-check"></i></span>
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.forgot') }}" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="{{ route('register') }}">Join OpenShelf</a>
            </div>

            <div class="security-info">
                <i class="fas fa-shield-halved"></i>
                <span>Enterprise grade security protected</span>
            </div>
        </div>
    </div>
</body>
</html>

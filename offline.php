<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Offline - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #6366f1;
            --primary-light: #8b5cf6;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated background */
        .bg-gradient {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
            z-index: 0;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.12;
            animation: float 20s infinite alternate ease-in-out;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            top: -100px;
            left: -100px;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, #818cf8, #c084fc);
            bottom: -80px;
            right: -80px;
            animation-delay: -7s;
        }

        @keyframes float {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(60px, 40px) scale(1.15); }
        }

        /* Content card */
        .offline-container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
            max-width: 520px;
            width: 100%;
        }

        .offline-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            padding: 3.5rem 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(15px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Logo */
        .offline-logo {
            width: 80px;
            height: 80px;
            border-radius: 22px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 4px 20px rgba(99, 102, 241, 0.4));
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { filter: drop-shadow(0 4px 20px rgba(99, 102, 241, 0.4)); }
            50% { filter: drop-shadow(0 4px 30px rgba(99, 102, 241, 0.6)); }
        }

        /* Offline icon */
        .offline-icon {
            width: 100px;
            height: 100px;
            margin: 1.5rem auto;
            position: relative;
        }

        .offline-icon svg {
            width: 100%;
            height: 100%;
        }

        /* Text */
        .offline-title {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #fff 30%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .offline-message {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.7;
            margin-bottom: 2.5rem;
        }

        /* Retry button */
        .btn-retry {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            border-radius: 100px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            text-decoration: none;
        }

        .btn-retry:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(99, 102, 241, 0.45);
            filter: brightness(1.1);
        }

        .btn-retry:active {
            transform: translateY(0);
        }

        .btn-retry svg {
            width: 20px;
            height: 20px;
            transition: transform 0.4s ease;
        }

        .btn-retry:hover svg {
            transform: rotate(180deg);
        }

        /* Status indicator */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 100px;
            font-size: 0.85rem;
            color: #fca5a5;
            margin-top: 2rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* Online detection */
        .online-toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 1rem 2rem;
            border-radius: 100px;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 100;
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(10px);
        }

        .online-toast.show {
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 480px) {
            .offline-card {
                padding: 2.5rem 1.5rem;
                border-radius: 22px;
            }
            .offline-title {
                font-size: 1.6rem;
            }
            .offline-logo {
                width: 64px;
                height: 64px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-gradient">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
    </div>

    <div class="offline-container">
        <div class="offline-card">
            <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="offline-logo">

            <!-- Offline cloud icon -->
            <div class="offline-icon">
                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M75 45C75 45 78 28 60 22C42 16 38 32 38 32C38 32 22 30 20 45C18 60 32 62 32 62H70C70 62 82 60 75 45Z" 
                          stroke="#64748b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>
                    <line x1="35" y1="72" x2="65" y2="72" stroke="#ef4444" stroke-width="3" stroke-linecap="round" opacity="0.8"/>
                    <line x1="40" y1="78" x2="60" y2="78" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" opacity="0.5"/>
                    <line x1="45" y1="84" x2="55" y2="84" stroke="#ef4444" stroke-width="2" stroke-linecap="round" opacity="0.3"/>
                </svg>
            </div>

            <h1 class="offline-title">You're Offline</h1>
            <p class="offline-message">
                It looks like you've lost your internet connection. 
                Don't worry — previously visited pages may still be available. 
                Check your connection and try again.
            </p>

            <button class="btn-retry" onclick="window.location.reload()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"></polyline>
                    <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
                </svg>
                Try Again
            </button>

            <div class="status-indicator">
                <span class="status-dot"></span>
                No internet connection
            </div>
        </div>
    </div>

    <!-- Online detection toast -->
    <div class="online-toast" id="onlineToast">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Back online! Redirecting...
    </div>

    <script>
        // Auto-redirect when back online
        window.addEventListener('online', () => {
            const toast = document.getElementById('onlineToast');
            toast.classList.add('show');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Offline - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2C3E50">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #2C3E50;
            --secondary: #4C9F8A;
            --bg-dark: #0F172A;
            --surface: #1E293B;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #F8F9FA;
            --text-muted: #94A3B8;
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
                radial-gradient(circle at 20% 20%, rgba(44, 62, 80, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(76, 159, 138, 0.2) 0%, transparent 50%);
            z-index: 0;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.15;
            animation: float 20s infinite alternate ease-in-out;
        }

        .blob-1 {
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            top: -100px;
            left: -100px;
        }

        .blob-2 {
            width: 350px;
            height: 350px;
            background: linear-gradient(135deg, var(--secondary), #3A7B6B);
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
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 4rem 3rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Logo */
        .offline-logo {
            width: 80px;
            height: 80px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 8px 25px rgba(76, 159, 138, 0.3));
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); filter: drop-shadow(0 8px 25px rgba(76, 159, 138, 0.3)); }
            50% { transform: scale(1.05); filter: drop-shadow(0 12px 35px rgba(76, 159, 138, 0.5)); }
        }

        /* Offline icon */
        .offline-icon {
            width: 100px;
            height: 100px;
            margin: 1.5rem auto;
            position: relative;
        }

        /* Text */
        .offline-title {
            font-size: 2.25rem;
            font-weight: 850;
            letter-spacing: -0.04em;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .offline-message {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.75;
            margin-bottom: 3rem;
            font-weight: 500;
        }

        /* Retry button */
        .btn-retry {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1.15rem 3rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 100px;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
            text-decoration: none;
            width: 100%;
        }

        .btn-retry:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(76, 159, 138, 0.4);
            filter: brightness(1.1);
        }

        .btn-retry:active {
            transform: translateY(-1px);
        }

        .btn-retry svg {
            width: 22px;
            height: 22px;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-retry:hover svg {
            transform: rotate(180deg);
        }

        /* Status indicator */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.6rem 1.5rem;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 100px;
            font-size: 0.9rem;
            color: #fca5a5;
            margin-top: 2.5rem;
            font-weight: 700;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #ef4444;
            border-radius: 50%;
            animation: blink 2s ease-in-out infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.8); }
        }

        /* Online detection */
        .online-toast {
            position: fixed;
            bottom: 3rem;
            left: 50%;
            transform: translateX(-50%) translateY(120px);
            background: rgba(76, 159, 138, 0.2);
            border: 1px solid rgba(76, 159, 138, 0.4);
            color: #6ee7b7;
            padding: 1.25rem 2.5rem;
            border-radius: 100px;
            font-weight: 800;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 100;
            transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .online-toast.show {
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 480px) {
            .offline-card {
                padding: 3rem 1.5rem;
                border-radius: 28px;
            }
            .offline-title {
                font-size: 1.8rem;
            }
            .offline-logo {
                width: 64px;
                height: 64px;
            }
        }
    </style>
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

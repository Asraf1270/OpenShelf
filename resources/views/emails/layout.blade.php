@php
    $themeType = $type ?? 'info';
    $theme = config("openshelf-mail.themes.{$themeType}", config('openshelf-mail.themes.info'));
    $year = date('Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f8fafc; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f8fafc; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: {{ $theme['bg'] }}; padding: 32px 20px 28px; text-align: center; }
        .brand-wordmark { display: block; margin: 0 auto; max-width: 220px; width: 100%; height: auto; }
        .content { padding: 40px 35px; color: #1e293b; }
        .button { display: inline-block; padding: 14px 35px; background-color: {{ $theme['btn'] }}; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-top: 20px; }
        .greeting { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 12px; text-align: center; }
        .footer { padding: 0; background-color: #f8fafc; border-top: 1px solid #f1f5f9; }
        .footer-inner { padding: 28px 24px 20px; text-align: center; }
        .footer-wordmark { max-width: 180px; margin-bottom: 14px; }
        .social-row { display: inline-flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 12px; }
        .social-link { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: #e2e8f0; color: #1e293b; text-decoration: none; }
        .social-link svg { width: 14px; height: 14px; display: block; fill: currentColor; }
        .footer-address { margin: 0 0 6px; font-size: 12px; line-height: 1.5; color: #64748b; }
        .footer-meta { margin: 0 0 12px; font-size: 12px; line-height: 1.5; color: #64748b; }
        .footer-meta a { color: #4C9F8A; text-decoration: none; }
        .footer-copy { margin: 0; font-size: 12px; color: #94a3b8; }
        .footer-note { margin: 10px 0 0; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="{{ asset('images/logo-wordmark.svg') }}" alt="OpenShelf" class="brand-wordmark" style="filter: brightness(0) invert(1);">
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <div class="footer-inner">
                    <img src="{{ asset('images/logo-wordmark.svg') }}" alt="OpenShelf" class="brand-wordmark footer-wordmark">

                    <div class="social-row">
                        <a href="https://www.facebook.com/profile.php?id=61590695101230" class="social-link" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.3c0-.9.3-1.6 1.6-1.6H16V2.9c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H7v3.1h2.8v8h3.7z"/></svg>
                        </a>
                        <a href="https://www.x.com/duopenshelf" class="social-link" aria-label="X" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 2h3.4l-7.4 8.5L22.8 22h-6.7l-5.2-7.5L5.1 22H1.7l7.9-9.1L1.2 2h6.9l4.7 6.8L18.9 2zm-1.2 18h1.8L7.5 3.9H5.6L17.7 20z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/duopenshelf" class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5A5.5 5.5 0 1 1 6.5 13 5.5 5.5 0 0 1 12 7.5zm0 2A3.5 3.5 0 1 0 15.5 13 3.5 3.5 0 0 0 12 9.5zm5.5-3.2a1.3 1.3 0 1 1-1.3-1.3 1.3 1.3 0 0 1 1.3 1.3z"/></svg>
                        </a>
                        <a href="https://wa.me/8801987971270" class="social-link" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12.1 2a9.9 9.9 0 0 0-8.6 15.1L2 22l5.1-1.5A9.9 9.9 0 1 0 12.1 2zm5.7 14.1c-.2.6-1.2 1.1-1.7 1.2-.4.1-1 .1-3.2-.7-2.7-1-4.5-3.6-4.7-3.8-.2-.2-1.7-2.2-1.7-4.2 0-2 1-3 1.4-3.5.3-.3.8-.4 1-.4h.7c.2 0 .5 0 .8.6.3.7 1 2.3 1.1 2.5.1.2.1.4 0 .7-.1.2-.1.4-.3.6-.2.2-.3.4-.5.7-.2.2-.4.5-.1.9.3.4.9 1.8 2 2.9 1.4 1.3 2.7 1.7 3.2 1.9.5.2.8.2 1-.1.3-.3.9-1 .9-1.3.1-.3.1-.6.1-.8-.1-.2-.3-.3-.5-.5z"/></svg>
                        </a>
                    </div>

                    <p class="footer-address">University of Dhaka, Bangladesh</p>
                    <p class="footer-meta">
                        <a href="mailto:support@duopenshelf.top">support@duopenshelf.top</a>
                        &nbsp;·&nbsp;
                        <a href="tel:+8801987971270">+880 1987 971270</a>
                    </p>
                    <p class="footer-copy">&copy; {{ $year }} OpenShelf. All rights reserved.</p>
                    <p class="footer-note">This is an automated message, please do not reply.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

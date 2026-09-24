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
        .container { max-width: 720px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: {{ $theme['bg'] }}; padding: 32px 20px 28px; text-align: center; }
        .brand-wordmark { display: block; margin: 0 auto; max-width: 260px; width: 100%; height: auto; }
        .content { padding: 48px 40px; color: #1e293b; }
        .button { display: inline-block; padding: 14px 35px; background-color: {{ $theme['btn'] }}; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-top: 20px; }
        .greeting { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 12px; text-align: center; }
        .footer { padding: 0; background-color: #f8fafc; border-top: 1px solid #f1f5f9; }
        .footer-inner { padding: 32px 28px 24px; text-align: center; }
        .footer-wordmark { max-width: 220px; margin-bottom: 14px; }
        .social-row { 
            text-align: center; 
            margin-bottom: 12px; 
        }
        .social-link {
            display: inline-block;
            width: 32px;
            height: 32px;
            line-height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            text-decoration: none;
            text-align: center;
            margin: 0 4px;
            vertical-align: middle;
        }
        .social-link img {
            display: inline-block;
            width: 16px;
            height: 16px;
            vertical-align: middle;
            border: 0;
            margin-top: 8px; /* Centers the 16px image inside a 32px height container */
        }
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
                            <img src="{{ asset('images/social/facebook.png') }}" alt="Facebook" width="16" height="16">
                        </a>
                        <a href="https://www.x.com/duopenshelf" class="social-link" aria-label="X" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('images/social/twitter.png') }}" alt="X" width="16" height="16">
                        </a>
                        <a href="https://www.instagram.com/duopenshelf" class="social-link" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('images/social/instagram.png') }}" alt="Instagram" width="16" height="16">
                        </a>
                        <a href="https://wa.me/8801987971270" class="social-link" aria-label="WhatsApp" target="_blank" rel="noopener noreferrer">
                            <img src="{{ asset('images/social/whatsapp.png') }}" alt="WhatsApp" width="16" height="16">
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

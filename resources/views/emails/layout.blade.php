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
        .header { background: {{ $theme['bg'] }}; padding: 40px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 26px; font-weight: 700; }
        .content { padding: 40px 35px; color: #1e293b; }
        .footer { padding: 25px; text-align: center; background-color: #f8fafc; border-top: 1px solid #f1f5f9; font-size: 13px; color: #94a3b8; }
        .button { display: inline-block; padding: 14px 35px; background-color: {{ $theme['btn'] }}; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-top: 20px; }
        .greeting { font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 12px; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1 style="color: #ffffff;">OpenShelf</h1>
            </div>
            <div class="content">
                @yield('content')
            </div>
            <div class="footer">
                <p>&copy; {{ $year }} OpenShelf. All rights reserved.</p>
                <p style="font-size: 11px; margin-top: 10px;">This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
require_once __DIR__ . '/lib/Mailer.php';
$mailer = new Mailer();
echo "Testing SMTP connection...\n";
$result = $mailer->testConnection();
print_r($result);

if ($result['success']) {
    echo "Sending test email...\n";
    $sent = $mailer->sendTemplate(
        'test@example.com',
        'Test User',
        'welcome',
        [
            'subject' => 'Test Email Subject',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'login_url' => 'http://localhost/login',
            'base_url' => 'http://localhost'
        ]
    );
    echo $sent ? "Email sent!\n" : "Email failed!\n";
} else {
    echo "Connection failed!\n";
}

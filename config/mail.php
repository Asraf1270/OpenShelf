<?php
// Try to load from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
} else {
    $env = [];
}

// Return configuration array
return [
    // SMTP settings
    'smtp' => [
        'host' => $env['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com',
        'port' => $env['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: 587,
        'secure' => $env['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: 'tls',
        'auth' => true,
        'username' => $env['SMTP_USERNAME'] ?? getenv('SMTP_USERNAME') ?: '',
        'password' => $env['SMTP_PASSWORD'] ?? getenv('SMTP_PASSWORD') ?: '',
        'timeout' => 30,
        'debug' => 0
    ],
    
    // Email settings
    'email' => [
        'from' => [
            'address' => $env['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@openshelf.org',
            'name' => $env['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'OpenShelf'
        ],
        'reply_to' => [
            'address' => $env['MAIL_REPLY_TO'] ?? getenv('MAIL_REPLY_TO') ?: 'support@openshelf.org',
            'name' => $env['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'OpenShelf Support'
        ],
        'admin_email' => $env['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@openshelf.org',
        'charset' => 'UTF-8',
        'encoding' => 'base64',
        'wordwrap' => 50
    ],
    
    // Email templates directory
    'templates' => __DIR__ . '/../emails/',
    
    // Rate limiting (prevent spam)
    'rate_limit' => [
        'enabled' => true,
        'max_per_hour' => 5,                        // Max emails per user per hour
        'max_per_day' => 20                          // Max emails per user per day
    ],
    
    // Queue settings (for high volume)
    'queue' => [
        'enabled' => false,                          // Use database queue for bulk emails
        'table' => 'email_queue',
        'retry_limit' => 3,
        'retry_delay' => 300                         // 5 minutes between retries
    ],
    
    // Logging
    'log' => [
        'enabled' => true,
        'file' => __DIR__ . '/../logs/mail.log',
        'level' => 'info'                             // info, error, debug
    ]
];
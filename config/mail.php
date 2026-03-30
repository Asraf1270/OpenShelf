<?php
/**
 * OpenShelf Mail Configuration
 * 
 * Configuration file for PHPMailer settings
 */

// Return configuration array
return [
    // SMTP settings
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp-relay.brevo.com',
        'port' => getenv('SMTP_PORT') ?: 587,
        'secure' => getenv('SMTP_SECURE') ?: 'tls',
        'auth' => true,
        'username' => getenv('SMTP_USERNAME'),
        'password' => getenv('SMTP_PASSWORD'),
        'timeout' => 30,
        'debug' => 0
    ],
    
    // Email settings
    'email' => [
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@openshelf.org',
            'name' => getenv('MAIL_FROM_NAME') ?: 'OpenShelf'
        ],
        'reply_to' => [
            'address' => getenv('MAIL_REPLY_TO') ?: 'support@openshelf.org',
            'name' => getenv('MAIL_FROM_NAME') ?: 'OpenShelf Support'
        ],
        'admin_email' => getenv('ADMIN_EMAIL') ?: 'admin@openshelf.org',
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
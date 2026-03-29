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
        'host' => 'smtp-relay.brevo.com',              // SMTP server (use your provider)
        'port' => 587,                            // TLS port
        'secure' => 'tls',                         // tls or ssl
        'auth' => true,                            // Enable SMTP authentication
        'username' => getenv('SMTP_USERNAME') ?: 'a15ef4001@smtp-brevo.com',     // SMTP username
        'password' => getenv('SMTP_PASSWORD') ?: '',         // SMTP password (use environment variable)
        'timeout' => 30,                            // Connection timeout
        'debug' => 0                                 // Debug level (0 = off, 1 = client, 2 = client & server)
    ],
    
    // Email settings
    'email' => [
        'from' => [
            'address' => 'mdasrafulislam70@yahoo.com',
            'name' => 'OpenShelf'
        ],
        'reply_to' => [
            'address' => 'mdasrafulislam70@yahoo.com',
            'name' => 'OpenShelf Support'
        ],
        'admin_email' => 'mdasrafulislam1270@gmail.com',     // Admin email for system notifications
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
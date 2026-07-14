<?php

return [

    'reply_to' => [
        'address' => env('MAIL_REPLY_TO', 'support@duopenshelf.top'),
        'name' => env('MAIL_REPLY_TO_NAME', 'OpenShelf Support'),
    ],

    'admin_email' => env('MAIL_ADMIN_EMAIL', 'admin@duopenshelf.top'),

    'rate_limit' => [
        'enabled' => env('MAIL_RATE_LIMIT_ENABLED', true),
        'max_per_hour' => (int) env('MAIL_RATE_LIMIT_HOUR', 5),
        'max_per_day' => (int) env('MAIL_RATE_LIMIT_DAY', 20),
        'storage_path' => storage_path('app/mail_rate_limit.json'),
    ],

    'log' => [
        'enabled' => env('MAIL_LOG_ENABLED', true),
        'file' => storage_path('logs/mail.log'),
    ],

    'themes' => [
        'info' => [
            'bg' => 'linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%)',
            'btn' => '#4C9F8A',
        ],
        'success' => [
            'bg' => 'linear-gradient(135deg, #2E8B57 0%, #267347 100%)',
            'btn' => '#2E8B57',
        ],
        'warning' => [
            'bg' => 'linear-gradient(135deg, #D97706 0%, #B45309 100%)',
            'btn' => '#D97706',
        ],
        'danger' => [
            'bg' => 'linear-gradient(135deg, #C65D5D 0%, #A84F4F 100%)',
            'btn' => '#C65D5D',
        ],
        'neutral' => [
            'bg' => 'linear-gradient(135deg, #1E293B 0%, #0F172A 100%)',
            'btn' => '#1E293B',
        ],
    ],

    'default_subjects' => [
        'registration_otp' => 'Verify Your OpenShelf Account',
        'welcome' => 'Welcome to OpenShelf',
        'forget_password' => 'Password Reset Verification - OpenShelf',
        'borrow_request' => 'New Borrow Request - OpenShelf',
        'request_approved' => 'Your Borrow Request Has Been Approved!',
        'request_rejected' => 'Update on Your Borrow Request',
        'wishlist_available' => 'A Wishlisted Book Is Available - OpenShelf',
        'book_returned' => 'Book Return Processed - OpenShelf',
        'book_returned_owner' => 'Confirm Book Return - OpenShelf',
        'return_confirmed_borrower' => 'Return Confirmed - OpenShelf',
        'return_rejected_borrower' => 'Return Not Confirmed - OpenShelf',
        'return_reminder' => 'Book Return Reminder - OpenShelf',
        'overdue' => 'Overdue Book Reminder - OpenShelf',
        'announcement' => 'OpenShelf Announcement',
        'otp' => 'Admin Verification Code - OpenShelf',
    ],

];

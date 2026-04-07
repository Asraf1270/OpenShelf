<?php
/**
 * OpenShelf Database Configuration
 * 
 * Returns database connection settings.
 * In a production environment, these should be loaded from environment variables.
 */

// Try to load from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
} else {
    $env = [];
}

return [
    'host' => $env['DB_HOST'] ?? 'localhost',
    'dbname' => $env['DB_NAME'] ?? 'openshelf_db',
    'password' => $env['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'debug' => (isset($env['DEBUG']) && $env['DEBUG'] === 'true'),
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

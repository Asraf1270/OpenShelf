<?php
/**
 * OpenShelf Database Connection Helper
 * 
 * Provides a singleton PDO instance for database operations across the application.
 */

// Function to get the PDO instance
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        $config = include __DIR__ . '/../config/database.php';
        
        // Define DEBUG if not defined
        if (!defined('DEBUG')) {
            define('DEBUG', isset($config['debug']) ? $config['debug'] : false);
        }

        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            
            $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            
        } catch (\PDOException $e) {
             // For debugging - in production you should log this and show a generic error
             error_log("Database Connection Error: " . $e->getMessage());
             
             // If debugging is enabled via some flag, show more info
             if (defined('DEBUG') && DEBUG) {
                 die("Connection failed: " . $e->getMessage());
             } else {
                 die("A database connection error occurred. Please try again later.");
             }
        }
    }

    return $pdo;
}

// Global variable for easier access (optional, but convenient for procedural code)
if (!isset($db)) {
    $db = getDB();
}

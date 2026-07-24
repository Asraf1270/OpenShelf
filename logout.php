<?php
/**
 * OpenShelf Logout System
 * Handles user logout, session cleanup, and remember me token removal
 */

session_start();

// Configuration
define('DATA_PATH', __DIR__ . '/data/');
define('USERS_PATH', __DIR__ . '/users/');

// Include database connection
require_once __DIR__ . '/includes/db.php';

/**
 * Clear remember me token from database
 * 
 * @param string $userId User ID
 * @param string $token Token to remove
 * @return bool Success status
 */
function clearRememberToken($userId, $token) {
    try {
        $db = getDB();
        $hashedToken = hash('sha256', $token);
        $stmt = $db->prepare("DELETE FROM `remember_tokens` WHERE `user_id` = ? AND `token` = ?");
        return $stmt->execute([$userId, $hashedToken]);
    } catch (Exception $e) {
        error_log("Error clearing remember token in logout: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear all remember me tokens for a user
 * 
 * @param string $userId User ID
 * @return bool Success status
 */
function clearAllRememberTokens($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM `remember_tokens` WHERE `user_id` = ?");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Error clearing all remember tokens in logout: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user activity
 * 
 * @param string $userId User ID
 * @param string $action Action performed
 */
function logUserActivity($userId, $action) {
    $logFile = DATA_PATH . 'user_activity.log';
    $logEntry = date('Y-m-d H:i:s') . " | User: {$userId} | Action: {$action} | IP: " . 
                ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | UA: " . 
                ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Store user info before destroying session for logging
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Unknown';
$isAdmin = isset($_SESSION['admin_id']);

// Handle remember me cookie
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Extract user ID from token
    if (strpos($token, ':') !== false) {
        list($tokenUserId, $tokenValue) = explode(':', $token, 2);
        
        // Clear this specific token from user's profile
        if ($tokenUserId) {
            clearRememberToken($tokenUserId, $tokenValue);
        }
    }
    
    // Delete the cookie
    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );
}

// Destroy the session
session_destroy();

// Log the logout activity
if ($userId) {
    logUserActivity($userId, 'logout');
    
    // Also log to admin audit if it's an admin logout
    if ($isAdmin) {
        $adminLogFile = DATA_PATH . 'admin_audit.log';
        $logEntry = date('Y-m-d H:i:s') . " | Admin: {$userName} | Action: logout | IP: " . 
                    ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL;
        file_put_contents($adminLogFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

// Determine where to redirect based on user type
$redirectUrl = '/';
$message = 'You have been successfully logged out.';

// Check if there's a custom redirect parameter
if (isset($_GET['redirect'])) {
    $redirectUrl = $_GET['redirect'];
}

// Store logout message in session for next page (if we start a new session)
session_start();
$_SESSION['logout_message'] = $message;

// Redirect to home or login page
header('Location: ' . $redirectUrl);
exit;
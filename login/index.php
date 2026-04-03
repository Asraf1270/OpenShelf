<?php
/**
 * OpenShelf User Login System
 * 
 * Handles user authentication with remember me functionality,
 * session management, and admin approval verification.
 */

// Start session for user login state
session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('REMEMBER_ME_DAYS', 30);
define('COOKIE_SECURE', false); // Set to true in production with HTTPS
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

/**
 * Load users from JSON file
 * 
 * @return array Array of users
 */
function loadUsers()
{
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) {
        return [];
    }
    return json_decode(file_get_contents($usersFile), true) ?? [];
}

/**
 * Find user by phone number
 * 
 * @param string $phone Phone number to search for
 * @param array $users Array of users
 * @return array|null User data or null if not found
 */
function findUserByPhone($phone, $users)
{
    foreach ($users as $user) {
        if ($user['phone'] === $phone) {
            return $user;
        }
    }
    return null;
}

/**
 * Generate a secure remember me token
 * 
 * @return string Secure random token
 */
function generateRememberToken()
{
    return bin2hex(random_bytes(32));
}

/**
 * Save remember me token to user's profile
 * 
 * @param string $userId User ID
 * @param string $token Remember me token
 * @param int $expiry Timestamp when token expires
 * @return bool Success status
 */
function saveRememberToken($userId, $token, $expiry)
{
    $userFile = USERS_PATH . $userId . '.json';

    if (!file_exists($userFile)) {
        return false;
    }

    $userData = json_decode(file_get_contents($userFile), true);

    // Initialize remember_tokens array if not exists
    if (!isset($userData['remember_tokens'])) {
        $userData['remember_tokens'] = [];
    }

    // Clean up expired tokens
    $userData['remember_tokens'] = array_filter($userData['remember_tokens'], function ($t) {
        return $t['expiry'] > time();
    });

    // Add new token
    $userData['remember_tokens'][] = [
        'token' => hash('sha256', $token), // Store hashed token for security
        'expiry' => $expiry,
        'created_at' => date('Y-m-d H:i:s'),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];

    // Keep only last 5 tokens to prevent unlimited growth
    $userData['remember_tokens'] = array_slice($userData['remember_tokens'], -5);

    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Validate remember me token from cookie
 * 
 * @param string $token Token from cookie
 * @return array|false User data if token valid, false otherwise
 */
function validateRememberToken($token)
{
    if (empty($token) || !strpos($token, ':')) {
        return false;
    }

    list($userId, $tokenValue) = explode(':', $token, 2);

    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) {
        return false;
    }

    $userData = json_decode(file_get_contents($userFile), true);

    if (!isset($userData['remember_tokens'])) {
        return false;
    }

    $hashedToken = hash('sha256', $tokenValue);

    foreach ($userData['remember_tokens'] as $storedToken) {
        if ($storedToken['token'] === $hashedToken) {
            // Check if token has expired
            if ($storedToken['expiry'] > time()) {
                // Optional: Verify user agent for additional security
                if ($storedToken['user_agent'] === ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')) {
                    return $userData;
                }
            }
            break;
        }
    }

    return false;
}

/**
 * Clear remember me tokens for a user
 * 
 * @param string $userId User ID
 * @return bool Success status
 */
function clearRememberTokens($userId)
{
    $userFile = USERS_PATH . $userId . '.json';

    if (!file_exists($userFile)) {
        return false;
    }

    $userData = json_decode(file_get_contents($userFile), true);
    $userData['remember_tokens'] = [];

    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Set secure cookie with proper flags
 * 
 * @param string $name Cookie name
 * @param string $value Cookie value
 * @param int $expiry Expiry timestamp
 */
function setSecureCookie($name, $value, $expiry)
{
    setcookie(
        $name,
        $value,
        [
            'expires' => $expiry,
            'path' => '/',
            'domain' => '', // Current domain only
            'secure' => COOKIE_SECURE,
            'httponly' => COOKIE_HTTPONLY,
            'samesite' => COOKIE_SAMESITE
        ]
    );
}

/**
 * Log user activity for security monitoring
 * 
 * @param string $userId User ID
 * @param string $action Action performed
 */
function logUserActivity($userId, $action)
{
    $logFile = DATA_PATH . 'user_activity.log';
    $logEntry = date('Y-m-d H:i:s') . " | User: {$userId} | Action: {$action} | IP: " .
        ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | UA: " .
        ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . PHP_EOL;

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Initialize variables
$phone = '';
$error = '';
$success = '';

// Check for existing remember me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $userData = validateRememberToken($_COOKIE['remember_token']);

    if ($userData) {
        // Auto login user
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['personal_info']['name'];
        $_SESSION['user_role'] = $userData['account_info']['role'];
        $_SESSION['login_time'] = time();

        logUserActivity($userData['id'], 'auto_login_via_remember_me');

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Redirect to dashboard
        header('Location: /');
        exit;
    } else {
        // Invalid token, clear cookie
        setSecureCookie('remember_token', '', time() - 3600);
    }
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize inputs
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) && $_POST['remember_me'] === 'on';

    // Basic validation
    if (empty($phone)) {
        $error = 'Phone number is required';
    } elseif (empty($password)) {
        $error = 'Password is required';
    } else {

        // Load users and find by phone
        $users = loadUsers();
        $user = findUserByPhone($phone, $users);

        if (!$user) {
            // Use generic message to prevent user enumeration
            $error = 'Invalid phone number or password';
            logUserActivity('unknown', "failed_login_attempt - phone: {$phone}");
        } else {

            // Verify password
            if (password_verify($password, $user['password_hash'])) {

                // Check if account is verified by admin
                if (!$user['verified']) {
                    $error = 'Your account is waiting for admin approval. Please check back later.';
                    logUserActivity($user['id'], 'login_attempt_unverified');
                } else {

                    // Check account status
                    if ($user['status'] !== 'active') {
                        $error = 'Your account is currently ' . $user['status'] . '. Please contact support.';
                        logUserActivity($user['id'], 'login_attempt_' . $user['status']);
                    } else {

                        // Successful login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['login_time'] = time();

                        logUserActivity($user['id'], 'login_successful');

                        // Handle Remember Me
                        if ($rememberMe) {
                            $token = generateRememberToken();
                            $expiry = time() + (REMEMBER_ME_DAYS * 24 * 60 * 60);

                            // Save token to user's profile
                            if (saveRememberToken($user['id'], $token, $expiry)) {
                                // Set cookie with user ID and token
                                $cookieValue = $user['id'] . ':' . $token;
                                setSecureCookie('remember_token', $cookieValue, $expiry);
                            }
                        }

                        // Update last login time in users.json
                        foreach ($users as &$u) {
                            if ($u['id'] === $user['id']) {
                                $u['last_login'] = date('Y-m-d H:i:s');
                                break;
                            }
                        }
                        file_put_contents(
                            DATA_PATH . 'users.json',
                            json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                        );

                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Redirect to homepage or requested page
                        $redirect = $_SESSION['redirect_after_login'] ?? '/';
                        unset($_SESSION['redirect_after_login']);
                        header('Location: ' . $redirect);
                        exit;
                    }
                }
            } else {
                $error = 'Invalid phone number or password';
                logUserActivity($user['id'], 'failed_login_incorrect_password');
            }
        }
    }
}

// Check for redirect after login (if user tried to access protected page)
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="apple-touch-icon" href="/assets/images/pwa/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="OpenShelf">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#6366f1">
    <meta name="msapplication-TileImage" content="/assets/images/pwa/icon-144x144.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6d28d9;
            --primary-light: #8b5cf6;
            --secondary: #0ea5e9;
            --bg-dark: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            min-height: 100vh;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            position: relative;
            background: radial-gradient(circle at 0% 0%, rgba(109, 40, 217, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 100% 100%, rgba(14, 165, 233, 0.15) 0%, transparent 50%);
        }

        /* Animated background blobs */
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.15;
            animation: move 20s infinite alternate;
        }

        .blob-1 {
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .blob-2 {
            bottom: -100px;
            right: -100px;
            animation-delay: -5s;
        }

        @keyframes move {
            from {
                transform: translate(0, 0) scale(1);
            }

            to {
                transform: translate(100px, 100px) scale(1.2);
            }
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            width: 100%;
            max-width: 460px;
            padding: 3rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            margin-bottom: 0.75rem;
            filter: drop-shadow(0 4px 15px rgba(99, 102, 241, 0.4));
        }

        .login-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 1.05rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .input-group {
            position: relative;
            transition: all 0.3s ease;
        }

        .input-group i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1.1rem 1.25rem 1.1rem 3.25rem;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .input-group input:focus+i {
            color: var(--primary-light);
            transform: translateY(-50%) scale(1.1);
        }

        .input-group .toggle-password {
            left: auto;
            right: 1.25rem;
            cursor: pointer;
            pointer-events: auto;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.5rem 0 2rem;
            font-size: 0.9rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            cursor: pointer;
            color: var(--text-muted);
            user-select: none;
        }

        .remember-me input {
            display: none;
        }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.02);
        }

        .remember-me input:checked+.custom-checkbox {
            background: var(--primary);
            border-color: var(--primary);
        }

        .custom-checkbox i {
            color: #fff;
            font-size: 0.7rem;
            display: none;
        }

        .remember-me input:checked+.custom-checkbox i {
            display: block;
        }

        .forgot-password {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: #fff;
        }

        /* Buttons */
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 1.1rem;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(109, 40, 217, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(109, 40, 217, 0.4);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            transition: transform 0.3s ease;
        }

        .btn-login:hover i {
            transform: translateX(4px);
        }

        /* Footer */
        .register-link {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .register-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.25rem;
            transition: color 0.2s ease;
        }

        .register-link a:hover {
            color: var(--secondary);
        }

        .security-info {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loading .btn-text,
        .loading .fa-arrow-right {
            display: none;
        }

        .loading .spinner {
            display: block;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2.5rem 1.5rem;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>

        <div class="login-card">
            <div class="login-header">
                <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="brand-logo">
                <h1>OpenShelf</h1>
                <p>Login to your portal</p>
            </div>

            <!-- Alerts Container -->
            <div class="alerts">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-circle-exclamation"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-circle-check"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Login Form -->
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-phone-flip"></i>
                        <input type="tel" name="phone" id="phone" placeholder="Phone Number (01XXXXXXXXX)"
                            value="<?php echo htmlspecialchars($phone); ?>" required pattern="01[3-9]\d{8}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <span class="custom-checkbox">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password/" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text">Sign In</span>
                    <span class="spinner"></span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="register-link">
                Don't have an account? <a href="/register/">Join OpenShelf</a>
            </div>

            <div class="security-info">
                <i class="fas fa-shield-halved"></i>
                <span>Enterprise grade security protected</span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Show loading state on form submit
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function () {
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });
        // Toggle icon
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
        });

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });

        // Form submission with loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', function (e) {
            // Basic client-side validation
            const phone = phoneInput.value.trim();
            const password = document.getElementById('password').value;

            if (!phone || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }

            // Validate phone format
            const phoneRegex = /^01[3-9]\d{8}$/;
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Bangladeshi phone number (11 digits starting with 01)');
                return;
            }

            // Add loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // Remember me checkbox styling
        const rememberMe = document.getElementById('remember_me');
        const rememberLabel = document.querySelector('.remember-me');

        rememberLabel.addEventListener('click', function (e) {
            if (e.target.type !== 'checkbox') {
                rememberMe.checked = !rememberMe.checked;
            }
        });

        // Auto-focus phone input on page load
        phoneInput.focus();

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Show password strength indicator on focus
        password.addEventListener('focus', function () {
            console.log('Password field focused');
        });

        // Add animation to input groups
        const inputGroups = document.querySelectorAll('.input-group');
        inputGroups.forEach(group => {
            const input = group.querySelector('input');
            const icon = group.querySelector('i:not(.toggle-password)');

            input.addEventListener('focus', () => {
                if (icon) icon.style.color = '#667eea';
            });

            input.addEventListener('blur', () => {
                if (icon) icon.style.color = '#8898aa';
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            // Ctrl+Shift+L to focus login button
            if (e.ctrlKey && e.shiftKey && e.key === 'L') {
                e.preventDefault();
                loginBtn.focus();
            }

            // Escape key to clear form
            if (e.key === 'Escape') {
                if (document.activeElement !== loginBtn) {
                    loginForm.reset();
                }
            }
        });

        // Display user's last login time (if available in session)
        <?php if (isset($_SESSION['last_login'])): ?>
            const lastLogin = <?php echo json_encode($_SESSION['last_login']); ?>;
            const deviceInfo = document.querySelector('.device-info');
            if (deviceInfo) {
                deviceInfo.innerHTML = '<i class="fas fa-clock"></i> Last login: ' + lastLogin +
                    ' <i class="fas fa-circle" style="font-size: 0.3rem; margin: 0 0.5rem;"></i> ' +
                    deviceInfo.innerHTML;
            }
            <?php unset($_SESSION['last_login']); ?>
        <?php endif; ?>
    </script>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then((reg) => console.log('[PWA] SW registered:', reg.scope))
                    .catch((err) => console.warn('[PWA] SW failed:', err));
            });
        }
    </script>
</body>

</html>
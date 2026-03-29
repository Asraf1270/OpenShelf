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
function loadUsers() {
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
function findUserByPhone($phone, $users) {
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
function generateRememberToken() {
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
function saveRememberToken($userId, $token, $expiry) {
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
    $userData['remember_tokens'] = array_filter($userData['remember_tokens'], function($t) {
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
function validateRememberToken($token) {
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
function clearRememberTokens($userId) {
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
function setSecureCookie($name, $value, $expiry) {
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
function logUserActivity($userId, $action) {
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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Login page specific styles */
        .login-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .login-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            position: relative;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
        }
        
        .login-body {
            padding: 2.5rem 2rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .alert-error {
            background: rgba(245, 54, 92, 0.1);
            border: 2px solid #f5365c;
            color: #f5365c;
        }
        
        .alert-success {
            background: rgba(45, 206, 137, 0.1);
            border: 2px solid #2dce89;
            color: #2dce89;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
        
        .alert a {
            color: inherit;
            font-weight: 600;
            text-decoration: underline;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8898aa;
            transition: all 0.3s ease;
            z-index: 1;
        }
        
        .input-group .toggle-password {
            left: auto;
            right: 1rem;
            cursor: pointer;
            color: #8898aa;
        }
        
        .input-group .toggle-password:hover {
            color: #667eea;
        }
        
        .input-group input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group input:focus + i {
            color: #667eea;
        }
        
        .input-group input::placeholder {
            color: #adb5bd;
        }
        
        /* Remember me and forgot password */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #8898aa;
            font-size: 0.9rem;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #667eea;
        }
        
        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .forgot-password:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        /* Login button */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .btn-login:hover i {
            transform: translateX(5px);
        }
        
        /* Register link */
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e9ecef;
            color: #8898aa;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        /* Loading spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        .btn-login.loading .spinner {
            display: inline-block;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Device info */
        .device-info {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #8898aa;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .device-info i {
            font-size: 0.9rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 480px) {
            .login-body {
                padding: 2rem 1.5rem;
            }
            
            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .login-header h1 {
                font-size: 2rem;
            }
        }
        
        /* Demo credentials (for testing only) */
        .demo-credentials {
            background: #f8f9fe;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border: 2px dashed #667eea;
        }
        
        .demo-credentials p {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .demo-credentials small {
            color: #8898aa;
            display: block;
            line-height: 1.6;
        }
        
        .demo-credentials i {
            color: #2dce89;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><i class="fas fa-book-open"></i> OpenShelf</h1>
                <p>Welcome back! Please login to your account</p>
            </div>
            
            <div class="login-body">
                <!-- Demo credentials for testing (remove in production) -->
                <div class="demo-credentials">
                    <p><i class="fas fa-info-circle"></i> Demo Credentials:</p>
                    <small><i class="fas fa-phone"></i> Phone: 01712345678</small>
                    <small><i class="fas fa-lock"></i> Password: Password123</small>
                </div>
                
                <!-- Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Success Alert (for registration, password reset, etc) -->
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="loginForm">
                    <!-- Phone Number -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" name="phone" id="phone" 
                                   placeholder="Phone Number (e.g., 01712345678)"
                                   value="<?php echo htmlspecialchars($phone); ?>" 
                                   required
                                   pattern="01[3-9]\d{8}"
                                   title="Please enter a valid Bangladeshi phone number">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" 
                                   placeholder="Password" required>
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <i class="far fa-square unchecked"></i>
                            <i class="fas fa-check-square checked"></i>
                            <span>Remember me for 30 days</span>
                        </label>
                        <a href="/forgot-password/" class="forgot-password">
                            <i class="fas fa-key"></i> Forgot Password?
                        </a>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" class="btn-login" id="loginBtn">
                        <span class="btn-text">Login to OpenShelf</span>
                        <span class="spinner"></span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <!-- Register Link -->
                <div class="register-link">
                    Don't have an account? 
                    <a href="/register/">
                        <i class="fas fa-user-plus"></i> Create Account
                    </a>
                </div>
                
                <!-- Device Info -->
                <div class="device-info">
                    <i class="fas fa-shield-alt"></i>
                    <span>Secure login with 256-bit encryption</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });
        
        // Form submission with loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        
        loginForm.addEventListener('submit', function(e) {
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
        
        rememberLabel.addEventListener('click', function(e) {
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
        password.addEventListener('focus', function() {
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
        document.addEventListener('keydown', function(e) {
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
</body>
</html>
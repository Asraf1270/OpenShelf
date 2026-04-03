<?php
/**
 * OpenShelf User Registration System
 * With Email Notifications
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

// Load mailer
require_once dirname(__DIR__) . '/vendor/autoload.php';
$mailer = new Mailer();

/**
 * Generate a secure 16-character user ID
 */
function generateUserId() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
    $userId = '';
    for ($i = 0; $i < 16; $i++) {
        $userId .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $userId;
}

/**
 * Validate email against university pattern
 */
function validateEmail($email) {
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z]+\.du\.ac\.bd$/';
    return preg_match($pattern, $email) === 1;
}

/**
 * Check if email exists
 */
function isEmailExists($email, $users) {
    foreach ($users as $user) {
        if ($user['email'] === $email) return true;
    }
    return false;
}

/**
 * Check if phone exists
 */
function isPhoneExists($phone, $users) {
    foreach ($users as $user) {
        if ($user['phone'] === $phone) return true;
    }
    return false;
}

// Initialize variables
$name = $email = $department = $session = $phone = $roomNumber = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $session = trim($_POST['session'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roomNumber = trim($_POST['roomNumber'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name)) $errors['name'] = 'Name is required';
    elseif (strlen($name) < 3) $errors['name'] = 'Name must be at least 3 characters';
    
    if (empty($email)) $errors['email'] = 'Email is required';
    elseif (!validateEmail($email)) $errors['email'] = 'Email must be a valid university email';
    
    if (empty($department)) $errors['department'] = 'Department is required';
    
    if (empty($session)) $errors['session'] = 'Session is required';
    elseif (!preg_match('/^\d{4}-\d{2}$/', $session)) $errors['session'] = 'Session must be in format YYYY-YY';
    
    if (empty($phone)) $errors['phone'] = 'Phone number is required';
    elseif (!preg_match('/^01[3-9]\d{8}$/', $phone)) $errors['phone'] = 'Please enter a valid Bangladeshi phone number';
    
    if (empty($roomNumber)) $errors['roomNumber'] = 'Room number is required';
    
    if (empty($password)) $errors['password'] = 'Password is required';
    elseif (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters';
    
    if ($password !== $confirmPassword) $errors['confirm_password'] = 'Passwords do not match';
    
    if (empty($errors)) {
        $usersFile = DATA_PATH . 'users.json';
        $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
        
        if (isEmailExists($email, $users)) {
            $errors['email'] = 'This email is already registered';
        }
        
        if (isPhoneExists($phone, $users)) {
            $errors['phone'] = 'This phone number is already registered';
        }
        
        if (empty($errors)) {
            // Generate unique ID
            do {
                $userId = generateUserId();
                $idExists = false;
                foreach ($users as $user) {
                    if ($user['id'] === $userId) {
                        $idExists = true;
                        break;
                    }
                }
            } while ($idExists);
            
            // Create user data
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $userData = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'department' => $department,
                'session' => $session,
                'phone' => $phone,
                'room_number' => $roomNumber,
                'password_hash' => $passwordHash,
                'verified' => false,
                'role' => 'user',
                'profile_pic' => 'default-avatar.jpg',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'last_login' => null,
                'status' => 'pending'
            ];
            
            $users[] = $userData;
            
            if (file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT))) {
                
                // Create individual profile
                $profileFile = USERS_PATH . $userId . '.json';
                file_put_contents($profileFile, json_encode([
                    'id' => $userId,
                    'personal_info' => [
                        'name' => $name,
                        'email' => $email,
                        'department' => $department,
                        'session' => $session,
                        'phone' => $phone,
                        'room_number' => $roomNumber,
                        'bio' => ''
                    ],
                    'account_info' => [
                        'verified' => false,
                        'role' => 'user',
                        'created_at' => date('Y-m-d H:i:s'),
                        'status' => 'pending'
                    ],
                    'stats' => [
                        'books_owned' => 0,
                        'books_borrowed' => 0,
                        'books_lent' => 0,
                        'reviews' => 0
                    ],
                    'preferences' => [
                        'notifications' => true,
                        'privacy' => 'public'
                    ]
                ], JSON_PRETTY_PRINT));
                
                // Send welcome email
                try {
                    $mailer->sendTemplate(
                        $email,
                        $name,
                        'welcome',
                        [
                            'user_name' => $name,
                            'user_email' => $email,
                            'login_url' => BASE_URL . '/login/',
                            'base_url' => BASE_URL
                        ]
                    );
                } catch (Exception $e) {
                    // Log email error but don't stop registration
                    error_log("Welcome email failed: " . $e->getMessage());
                }
                
                // Notify admin about new registration
                $adminsFile = DATA_PATH . 'admins.json';
                if (file_exists($adminsFile)) {
                    $admins = json_decode(file_get_contents($adminsFile), true);
                    foreach ($admins as $admin) {
                        try {
                            $mailer->sendTemplate(
                                $admin['email'],
                                $admin['name'],
                                'admin_notification',
                                [
                                    'admin_name' => $admin['name'],
                                    'notification_type' => 'new_registration',
                                    'user_name' => $name,
                                    'user_email' => $email,
                                    'user_department' => $department,
                                    'user_session' => $session,
                                    'admin_url' => BASE_URL . '/admin/users/',
                                    'base_url' => BASE_URL
                                ]
                            );
                        } catch (Exception $e) {
                            error_log("Admin notification failed: " . $e->getMessage());
                        }
                    }
                }
                
                $success = true;
                $name = $email = $department = $session = $phone = $roomNumber = '';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OpenShelf</title>
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
            --warning: #f59e0b;
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

        .registration-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
            position: relative;
            background: radial-gradient(circle at 100% 0%, rgba(109, 40, 217, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 0% 100%, rgba(14, 165, 233, 0.1) 0%, transparent 40%);
        }

        /* Animated background blobs */
        .blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            filter: blur(100px);
            border-radius: 50%;
            z-index: -1;
            opacity: 0.1;
            animation: move 25s infinite alternate;
        }

        .blob-1 { top: -150px; right: -150px; animation-delay: 0s; }
        .blob-2 { bottom: -150px; left: -150px; animation-delay: -7s; }

        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(-100px, 100px) scale(1.1); }
        }

        .registration-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            width: 100%;
            max-width: 850px;
            padding: 3.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.98) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .registration-header {
            text-align: center;
            margin-bottom: 3.5rem;
        }

        .brand-logo {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            margin-bottom: 1rem;
            filter: drop-shadow(0 4px 15px rgba(99, 102, 241, 0.4));
        }

        .registration-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            color: #fff;
        }

        .registration-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .full-width {
            grid-column: span 2;
        }

        .input-group {
            position: relative;
            transition: all 0.3s ease;
        }

        .input-group i:not(.toggle-password, .check-icon) {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 1.1rem 1.25rem 1.1rem 3.25rem;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--primary-light);
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.15);
        }

        .input-group input:focus + i {
            color: var(--primary-light);
            transform: translateY(-50%) scale(1.1);
        }

        /* Error Messages */
        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Alerts */
        .alert {
            padding: 1.25rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .alert-success .btn-login {
            background: var(--success);
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.9rem;
            text-decoration: none;
            color: white;
            font-weight: 600;
            margin-left: auto;
        }

        /* Password Strength */
        .password-strength-container {
            margin-top: 0.75rem;
        }

        .password-strength-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.4s ease;
        }

        .strength-weak { background: var(--error); width: 33%; }
        .strength-medium { background: var(--warning); width: 66%; }
        .strength-strong { background: var(--success); width: 100%; }

        .password-requirements {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .requirement.met {
            color: var(--success);
        }

        /* Terms */
        .terms-group {
            margin: 2rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .custom-checkbox {
            width: 22px;
            height: 22px;
            border: 1px solid var(--glass-border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(255, 255, 255, 0.02);
            position: relative;
        }

        .terms-group input { display: none; }

        .terms-group input:checked + .custom-checkbox {
            background: var(--primary);
            border-color: var(--primary);
        }

        .custom-checkbox i {
            color: #fff;
            font-size: 0.8rem;
            display: none;
        }

        .terms-group input:checked + .custom-checkbox i {
            display: block;
        }

        .terms-group a {
            color: var(--primary-light);
            text-decoration: none;
        }

        /* Buttons */
        .btn-register {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 1.25rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 10px 15px -3px rgba(109, 40, 217, 0.3);
        }

        .btn-register:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(109, 40, 217, 0.4);
            filter: brightness(1.1);
        }

        .btn-register:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Footer */
        .login-link {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid var(--glass-border);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .login-link a {
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.25rem;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: var(--secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .registration-card {
                padding: 2.5rem 1.5rem;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .full-width {
                grid-column: span 1;
            }
            .password-requirements {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        
        <div class="registration-card">
            <div class="registration-header">
                <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="brand-logo">
                <h1>Join OpenShelf</h1>
                <p>Create your account and start sharing</p>
            </div>
            
            <!-- Success/Error Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check fa-2x"></i>
                    <div>
                        <strong>Registration Successful!</strong>
                        <p>Your account is pending admin approval. We'll notify you soon.</p>
                    </div>
                    <a href="/login/" class="btn-login">Login Hub</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert error-alert">
                    <i class="fas fa-circle-exclamation fa-2x"></i>
                    <div>
                        <strong>Registration Failed!</strong>
                        <p><?php echo $errors['general']; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="registrationForm">
                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" id="name" placeholder="Full Name" 
                                   value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['name']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="University Email (@*.du.ac.bd)" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['email']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Department -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-building-columns"></i>
                            <input type="text" name="department" id="department" placeholder="Department" 
                                   value="<?php echo htmlspecialchars($department); ?>" required>
                        </div>
                        <?php if (isset($errors['department'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['department']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Session -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-calendar-days"></i>
                            <input type="text" name="session" id="session" placeholder="Session (YYYY-YY)" 
                                   value="<?php echo htmlspecialchars($session); ?>" required>
                        </div>
                        <?php if (isset($errors['session'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['session']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone" placeholder="Phone Number" 
                                   value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['phone']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Room Number -->
                    <div class="form-group">
                        <div class="input-group">
                            <i class="fas fa-door-open"></i>
                            <input type="text" name="roomNumber" id="roomNumber" placeholder="Room/Hostel Info" 
                                   value="<?php echo htmlspecialchars($roomNumber); ?>" required>
                        </div>
                        <?php if (isset($errors['roomNumber'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['roomNumber']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group full-width">
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="password" id="password" placeholder="Secure Password" required>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                        
                        <div class="password-strength-container">
                            <div class="password-strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="password-requirements">
                                <div class="requirement" id="req-length"><i class="fas fa-circle-dot"></i> 8+ Characters</div>
                                <div class="requirement" id="req-upper"><i class="fas fa-circle-dot"></i> Uppercase</div>
                                <div class="requirement" id="req-lower"><i class="fas fa-circle-dot"></i> Lowercase</div>
                                <div class="requirement" id="req-number"><i class="fas fa-circle-dot"></i> Number</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group full-width">
                        <div class="input-group">
                            <i class="fas fa-shield"></i>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error-message"><i class="fas fa-circle-exclamation"></i> <?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Terms -->
                <div class="terms-group">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms" class="custom-checkbox">
                        <i class="fas fa-check"></i>
                    </label>
                    <label for="terms">I accept the <a href="/terms.php">Terms</a> & <a href="/privacy.php">Privacy</a></label>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">
                    <span class="btn-text">Create Account</span>
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>
            
            <div class="login-link">
                Already part of the community? <a href="/login/">Sign In</a>
            </div>
        </div>
    </div>
                
                <div class="login-link">
                    Already have an account? <a href="/login/">Login here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const value = password.value;
            
            const hasLength = value.length >= 8;
            const hasUpper = /[A-Z]/.test(value);
            const hasLower = /[a-z]/.test(value);
            const hasNumber = /\d/.test(value);
            
            updateReq('req-length', hasLength);
            updateReq('req-upper', hasUpper);
            updateReq('req-lower', hasLower);
            updateReq('req-number', hasNumber);
            
            const met = [hasLength, hasUpper, hasLower, hasNumber].filter(Boolean).length;
            const fill = document.getElementById('strengthFill');
            
            fill.className = 'strength-fill';
            if (value.length > 0) {
                if (met <= 2) fill.classList.add('strength-weak');
                else if (met === 3) fill.classList.add('strength-medium');
                else fill.classList.add('strength-strong');
            } else {
                fill.style.width = '0';
            }
        }
        
        function updateReq(id, isMet) {
            const el = document.getElementById(id);
            const icon = el.querySelector('i');
            if (isMet) {
                el.classList.add('met');
                icon.className = 'fas fa-circle-check';
            } else {
                el.classList.remove('met');
                icon.className = 'fas fa-circle-dot';
            }
        }
        
        password.addEventListener('input', checkPasswordStrength);
        
        // Real-time password matching
        function checkPasswordMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value === confirmPassword.value) {
                    confirmPassword.style.borderColor = '#2dce89';
                } else {
                    confirmPassword.style.borderColor = '#f5365c';
                }
            } else {
                confirmPassword.style.borderColor = '#e9ecef';
            }
        }
        
        password.addEventListener('input', checkPasswordMatch);
        confirmPassword.addEventListener('input', checkPasswordMatch);
        
        // Prevent form submission if passwords don't match
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        
        // Auto-format session input
        document.getElementById('session').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.substr(0, 4) + '-' + value.substr(4, 2);
            }
            e.target.value = value;
        });
        
        // Auto-format phone number (Bangladesh format)
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });
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
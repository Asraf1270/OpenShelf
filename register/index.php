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
require_once dirname(__DIR__) . '/lib/Mailer.php';
$mailer = new Mailer();

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

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
 * Check if email exists in DB
 */
function isEmailExists($email) {
    if (empty($email)) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Check if phone exists in DB
 */
function isPhoneExists($phone) {
    if (empty($phone)) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT phone FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetch() !== false;
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
        if (isEmailExists($email)) {
            $errors['email'] = 'This email is already registered';
        }
        
        if (isPhoneExists($phone)) {
            $errors['phone'] = 'This phone number is already registered';
        }
        
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate unique ID
            $db = getDB();
            do {
                $userId = generateUserId();
                $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $idExists = $stmt->fetch() !== false;
            } while ($idExists);
            
            // Create user data
            // Save user to DB
            $sql = "INSERT INTO users (
                        id, name, email, department, session, phone, room_number, 
                        password_hash, verified, role, profile_pic, created_at, 
                        updated_at, status
                    ) VALUES (
                        :id, :name, :email, :department, :session, :phone, :room_number, 
                        :password_hash, :verified, :role, :profile_pic, :created_at, 
                        :updated_at, :status
                    )";
            
            $stmt = $db->prepare($sql);
            $success_db = $stmt->execute([
                ':id' => $userId,
                ':name' => $name,
                ':email' => $email,
                ':department' => $department,
                ':session' => $session,
                ':phone' => $phone,
                ':room_number' => $roomNumber,
                ':password_hash' => $passwordHash,
                ':verified' => 0,
                ':role' => 'user',
                ':profile_pic' => 'default-avatar.jpg',
                ':created_at' => date('Y-m-d H:i:s'),
                ':updated_at' => date('Y-m-d H:i:s'),
                ':status' => 'pending'
            ]);
            
            if ($success_db) {
                
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
                            'subject'    => 'Welcome to OpenShelf!',
                            'user_name'  => $name,
                            'user_email' => $email,
                            'login_url'  => BASE_URL . '/login/',
                            'base_url'   => BASE_URL
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
                                    'subject'           => 'New User Registration: ' . $name,
                                    'admin_name'        => $admin['name'],
                                    'notification_type' => 'new_registration',
                                    'user_name'         => $name,
                                    'user_email'        => $email,
                                    'user_department'   => $department,
                                    'user_session'      => $session,
                                    'admin_url'         => BASE_URL . '/admin/users/',
                                    'base_url'          => BASE_URL
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
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --secondary: #0ea5e9;
            --bg-dark: #0f172a;
            --surface: #1e293b;
            --surface-hover: #334155;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --focus-ring: rgba(99, 102, 241, 0.5);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', system-ui, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* Ambient Background */
        .ambient-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
            background: radial-gradient(circle at 15% 50%, rgba(79, 70, 229, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.08) 0%, transparent 40%);
        }

        .registration-container {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            padding: 1.5rem;
            width: 100%;
        }

        .registration-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            width: 100%;
            max-width: 850px;
            padding: 2.5rem 1.5rem;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
            animation: slideUp 0.5s ease-out forwards;
            position: relative;
            z-index: 10;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .registration-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: inline-block;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .registration-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .registration-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Form Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .full-width {
            grid-column: span 1;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group > i:first-child:not(.toggle-password, .check-icon) {
            position: absolute;
            left: 1.25rem;
            color: var(--text-muted);
            font-size: 1rem;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .input-group input,
        .input-group select {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.875rem 1.25rem 0.875rem 3rem;
            color: #fff;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: var(--primary-light);
            background: var(--bg-dark);
            box-shadow: 0 0 0 3px var(--focus-ring);
        }

        .input-group input:focus ~ i:first-child:not(.toggle-password, .check-icon) {
            color: var(--primary-light);
        }

        /* Password Input Extra Padding */
        .input-group input[type="password"],
        .input-group input[name="confirm_password"] {
            padding-right: 3rem;
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
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.95rem;
            animation: slideIn 0.5s ease;
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
            background: rgba(15, 23, 42, 0.6);
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
            grid-template-columns: 1fr;
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

        .requirement.met { color: var(--success); }

        /* Terms */
        .terms-group {
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .terms-group input { display: none; }

        .custom-checkbox {
            width: 20px;
            height: 20px;
            border: 1.5px solid var(--border-color);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: rgba(15, 23, 42, 0.5);
        }

        .terms-group input:checked + .custom-checkbox {
            background: var(--primary-light);
            border-color: var(--primary-light);
        }

        .custom-checkbox i {
            color: #fff;
            font-size: 0.7rem;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
        }

        .terms-group input:checked + .custom-checkbox i {
            opacity: 1;
            transform: scale(1);
        }

        .terms-group a {
            color: var(--primary-light);
            text-decoration: none;
            transition: color 0.2s;
        }

        .terms-group a:hover { color: var(--primary); }

        /* Buttons */
        .btn-register {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-register:hover:not(:disabled) {
            background: var(--primary-light);
            transform: translateY(-1px);
        }

        .btn-register:active:not(:disabled) {
            transform: translateY(1px);
        }

        .btn-register:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Footer */
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .login-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            margin-left: 0.25rem;
            transition: color 0.2s ease;
        }

        .login-link a:hover {
            color: var(--primary);
            text-decoration: underline;
        }

        /* Desktop enhancements */
        @media (min-width: 640px) {
            .registration-card {
                padding: 3rem;
            }
            .registration-header h1 {
                font-size: 2.25rem;
            }
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .full-width {
                grid-column: span 2;
            }
            .password-requirements {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="ambient-bg"></div>
    <div class="registration-container">
        
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
                    <label for="terms">I accept the <a href="/terms.php">Terms</a> and <a href="/privacy.php">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn-register" id="submitBtn">
                    <span class="btn-text">Create Account</span>
                    <i class="fas fa-user-plus"></i>
                </button>
            </form>
            
            <div class="login-link">
                Already part of the community? <a href="/login/">Sign in here</a>
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
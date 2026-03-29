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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Additional registration page specific styles */
        .registration-container {
            min-height: calc(100vh - 140px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        
        .registration-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        .registration-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .registration-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .registration-header p {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .registration-body {
            padding: 2rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8898aa;
        }
        
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus,
        .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group .error-message {
            color: #f5365c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            padding-left: 2.5rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }
        
        .strength-weak { width: 33.33%; background: #f5365c; }
        .strength-medium { width: 66.66%; background: #fb6340; }
        .strength-strong { width: 100%; background: #2dce89; }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #8898aa;
            margin-top: 0.5rem;
            padding-left: 2.5rem;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0;
        }
        
        .password-requirements li {
            margin-bottom: 0.25rem;
        }
        
        .password-requirements li.valid {
            color: #2dce89;
        }
        
        .password-requirements li i {
            width: 20px;
        }
        
        .success-alert {
            background: rgba(45, 206, 137, 0.1);
            border: 2px solid #2dce89;
            color: #2dce89;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .success-alert i {
            font-size: 2rem;
        }
        
        .success-alert .btn {
            margin-left: auto;
            background: #2dce89;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .success-alert .btn:hover {
            background: #28b97b;
            transform: translateY(-2px);
        }
        
        .error-alert {
            background: rgba(245, 54, 92, 0.1);
            border: 2px solid #f5365c;
            color: #f5365c;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .terms-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: auto;
        }
        
        .terms-checkbox label {
            color: #8898aa;
            font-size: 0.9rem;
        }
        
        .terms-checkbox a {
            color: #667eea;
            text-decoration: none;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        
        .btn-register {
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
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-register:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #8898aa;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .registration-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-card">
            <div class="registration-header">
                <h1><i class="fas fa-book-open"></i> Join OpenShelf</h1>
                <p>Create your account to start sharing and borrowing books</p>
            </div>
            
            <div class="registration-body">
                <?php if ($success): ?>
                    <div class="success-alert">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong>Registration Successful!</strong>
                            <p>Your account has been created and is pending admin approval. You will be able to login once your account is verified.</p>
                        </div>
                        <a href="/login/" class="btn">Go to Login</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Registration Failed!</strong>
                            <p><?php echo $errors['general']; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="registrationForm">
                    <div class="form-row">
                        <!-- Full Name -->
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" id="name" placeholder="Full Name" 
                                   value="<?php echo htmlspecialchars($name); ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="error-message"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Email -->
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" placeholder="University Email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="error-message"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <!-- Department -->
                        <div class="input-group">
                            <i class="fas fa-building"></i>
                            <input type="text" name="department" id="department" placeholder="Department" 
                                   value="<?php echo htmlspecialchars($department); ?>" required>
                            <?php if (isset($errors['department'])): ?>
                                <div class="error-message"><?php echo $errors['department']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Session -->
                        <div class="input-group">
                            <i class="fas fa-calendar"></i>
                            <input type="text" name="session" id="session" placeholder="Session (e.g., 2023-24)" 
                                   value="<?php echo htmlspecialchars($session); ?>" required>
                            <?php if (isset($errors['session'])): ?>
                                <div class="error-message"><?php echo $errors['session']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <!-- Phone -->
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="phone" id="phone" placeholder="Phone Number" 
                                   value="<?php echo htmlspecialchars($phone); ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="error-message"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Room Number -->
                        <div class="input-group">
                            <i class="fas fa-door-open"></i>
                            <input type="text" name="roomNumber" id="roomNumber" placeholder="Room Number" 
                                   value="<?php echo htmlspecialchars($roomNumber); ?>" required>
                            <?php if (isset($errors['roomNumber'])): ?>
                                <div class="error-message"><?php echo $errors['roomNumber']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Password" required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="error-message"><?php echo $errors['password']; ?></div>
                        <?php endif; ?>
                        
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrength"></div>
                        </div>
                        
                        <div class="password-requirements">
                            <ul id="passwordRequirements">
                                <li id="length"><i class="fas fa-circle"></i> At least 8 characters</li>
                                <li id="uppercase"><i class="fas fa-circle"></i> At least one uppercase letter</li>
                                <li id="lowercase"><i class="fas fa-circle"></i> At least one lowercase letter</li>
                                <li id="number"><i class="fas fa-circle"></i> At least one number</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="terms-checkbox">
                        <input type="checkbox" name="terms" id="terms" required>
                        <label for="terms">I agree to the <a href="/terms.php" target="_blank">Terms of Service</a> and <a href="/privacy.php" target="_blank">Privacy Policy</a></label>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-register" id="submitBtn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="/login/">Login here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Client-side password strength checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('passwordStrength');
        
        // Password requirement elements
        const lengthReq = document.getElementById('length');
        const uppercaseReq = document.getElementById('uppercase');
        const lowercaseReq = document.getElementById('lowercase');
        const numberReq = document.getElementById('number');
        
        function checkPasswordStrength() {
            const value = password.value;
            
            // Check requirements
            const hasLength = value.length >= 8;
            const hasUppercase = /[A-Z]/.test(value);
            const hasLowercase = /[a-z]/.test(value);
            const hasNumber = /\d/.test(value);
            
            // Update requirement indicators
            updateRequirement(lengthReq, hasLength);
            updateRequirement(uppercaseReq, hasUppercase);
            updateRequirement(lowercaseReq, hasLowercase);
            updateRequirement(numberReq, hasNumber);
            
            // Calculate strength
            const requirements = [hasLength, hasUppercase, hasLowercase, hasNumber];
            const metCount = requirements.filter(Boolean).length;
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (value.length === 0) {
                strengthBar.style.width = '0';
            } else if (metCount <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (metCount === 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        }
        
        function updateRequirement(element, isValid) {
            const icon = element.querySelector('i');
            if (isValid) {
                element.classList.add('valid');
                icon.className = 'fas fa-check-circle';
            } else {
                element.classList.remove('valid');
                icon.className = 'fas fa-circle';
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
</body>
</html>
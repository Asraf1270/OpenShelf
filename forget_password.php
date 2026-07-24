<?php
/**
 * OpenShelf Forgot Password System
 * 
 * Multi-step process to reset user password via email verification.
 */

session_start();

// Include necessary files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/lib/Mailer.php';

// Configuration
define('OTP_EXPIRY', 600); // 10 minutes
define('BASE_URL', 'https://duopenshelf.top'); // Adjust if needed

/**
 * Find user by phone and email
 */
function findUserByPhoneAndEmail($phone, $email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? AND email = ?");
    $stmt->execute([$phone, $email]);
    return $stmt->fetch() ?: null;
}

/**
 * Generate 6-digit OTP
 */
function generateOTP() {
    return sprintf("%06d", random_int(0, 999999));
}

/**
 * Save OTP to DB
 */
function saveOTP($email, $otp) {
    $db = getDB();
    
    // Clean expired and old OTPs for this email
    $stmt = $db->prepare("DELETE FROM login_otps WHERE email = ? OR expires_at < NOW()");
    $stmt->execute([$email]);
    
    $otpId = 'fpr_' . uniqid() . '_' . bin2hex(random_bytes(4));
    $stmt = $db->prepare("INSERT INTO login_otps (id, email, otp_hash, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $otpId,
        $email,
        password_hash($otp, PASSWORD_BCRYPT),
        date('Y-m-d H:i:s', time() + OTP_EXPIRY)
    ]);
    
    return $otpId;
}

/**
 * Verify OTP in DB
 */
function verifyOTP($otpId, $submittedOtp) {
    if (empty($otpId)) return false;
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM login_otps WHERE id = ?");
    $stmt->execute([$otpId]);
    $otpData = $stmt->fetch();
    
    if (!$otpData) return false;
    
    // Check expiry
    if (strtotime($otpData['expires_at']) < time()) {
        $db->prepare("DELETE FROM login_otps WHERE id = ?")->execute([$otpId]);
        return false;
    }
    
    // Check attempts
    if ($otpData['attempts'] >= 5) {
        $db->prepare("DELETE FROM login_otps WHERE id = ?")->execute([$otpId]);
        return false;
    }
    
    // Increment attempts
    $db->prepare("UPDATE login_otps SET attempts = attempts + 1 WHERE id = ?")->execute([$otpId]);
    
    // Verify OTP
    if (password_verify($submittedOtp, $otpData['otp_hash'])) {
        $db->prepare("UPDATE login_otps SET verified = 1 WHERE id = ?")->execute([$otpId]);
        return $otpData['email'];
    }
    
    return false;
}

// Initialize variables
$step = $_SESSION['forget_pwd_step'] ?? 'identify';
$error = '';
$success = '';
$phone = $_SESSION['forget_pwd_phone'] ?? '';
$email = $_SESSION['forget_pwd_email'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'identify') {
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($phone) || empty($email)) {
            $error = 'Both phone and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            $user = findUserByPhoneAndEmail($phone, $email);
            if ($user) {
                // User found, send OTP
                $otp = generateOTP();
                $otpId = saveOTP($email, $otp);
                
                $mailer = new Mailer();
                $sent = $mailer->sendTemplate(
                    $email,
                    $user['name'],
                    'forget_password',
                    [
                        'otp' => $otp,
                        'expiry_minutes' => 10,
                        'subject' => 'Password Reset Verification - OpenShelf'
                    ]
                );

                if ($sent) {
                    $_SESSION['forget_pwd_step'] = 'verify';
                    $_SESSION['forget_pwd_phone'] = $phone;
                    $_SESSION['forget_pwd_email'] = $email;
                    $_SESSION['forget_pwd_otp_id'] = $otpId;
                    $_SESSION['forget_pwd_user_id'] = $user['id'];
                    $success = 'Verification code sent to your email.';
                    header("Location: forget_password.php");
                    exit;
                } else {
                    $error = 'Failed to send verification email. Please try again.';
                }
            } else {
                $error = 'No account found with these details.';
            }
        }
    } elseif ($action === 'verify') {
        $submittedOtp = trim($_POST['otp'] ?? '');
        $otpId = $_SESSION['forget_pwd_otp_id'] ?? '';

        if (empty($submittedOtp)) {
            $error = 'Verification code is required.';
        } else {
            $verifiedEmail = verifyOTP($otpId, $submittedOtp);
            if ($verifiedEmail) {
                $_SESSION['forget_pwd_step'] = 'reset';
                $success = 'Account verified! Please set your new password.';
                header("Location: forget_password.php");
                exit;
            } else {
                $error = 'Invalid or expired verification code.';
            }
        }
    } elseif ($action === 'reset') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $userId = $_SESSION['forget_pwd_user_id'] ?? '';

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (empty($userId)) {
            $error = 'Session expired. Please start over.';
            $_SESSION['forget_pwd_step'] = 'identify';
        } else {
            // Update password
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([password_hash($password, PASSWORD_BCRYPT), $userId])) {
                // Clear reset session
                unset($_SESSION['forget_pwd_step']);
                unset($_SESSION['forget_pwd_phone']);
                unset($_SESSION['forget_pwd_email']);
                unset($_SESSION['forget_pwd_otp_id']);
                unset($_SESSION['forget_pwd_user_id']);
                
                $_SESSION['success_message'] = 'Password reset successful! You can now login.';
                header("Location: login/index.php");
                exit;
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}

// Reset if requested
if (isset($_GET['reset_session'])) {
    unset($_SESSION['forget_pwd_step']);
    unset($_SESSION['forget_pwd_phone']);
    unset($_SESSION['forget_pwd_email']);
    unset($_SESSION['forget_pwd_otp_id']);
    unset($_SESSION['forget_pwd_user_id']);
    header("Location: forget_password.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - OpenShelf</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2C3E50;
            --secondary: #4C9F8A;
            --bg-dark: #0F172A;
            --surface: #1E293B;
            --border-color: #334155;
            --text-main: #F8F9FA;
            --text-muted: #94A3B8;
            --error: #EF4444;
            --success: #10B981;
            --focus-ring: rgba(76, 159, 138, 0.4);
            --radius-lg: 24px;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .ambient-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: -1;
            background: radial-gradient(circle at 15% 50%, rgba(44, 62, 80, 0.2) 0%, transparent 40%),
                        radial-gradient(circle at 85% 30%, rgba(76, 159, 138, 0.15) 0%, transparent 40%);
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            padding: 3.5rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo {
            width: 72px; height: 72px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0 15px rgba(76, 159, 138, 0.3));
        }

        h1 { 
            font-size: 2rem; 
            font-weight: 850; 
            margin-bottom: 0.75rem; 
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #fff 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p { color: var(--text-muted); font-size: 1rem; font-weight: 500; }

        .form-group { margin-bottom: 1.5rem; }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group i:first-child {
            position: absolute;
            left: 1.25rem;
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .input-group input {
            width: 100%;
            background: rgba(15, 23, 42, 0.4);
            border: 1.5px solid var(--border-color);
            border-radius: 14px;
            padding: 1rem 1.25rem 1rem 3.25rem;
            color: #fff;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-family: inherit;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px var(--focus-ring);
            background: rgba(15, 23, 42, 0.6);
        }

        .btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary) 0%, #34495E 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 1.15rem;
            font-size: 1.1rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1.5rem;
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.3);
        }

        .btn:hover { 
            background: var(--secondary);
            transform: translateY(-2px); 
            box-shadow: 0 15px 25px -5px rgba(76, 159, 138, 0.4);
        }

        .alert {
            padding: 1.25rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
        }

        .alert-error { 
            background: rgba(239, 68, 68, 0.15); 
            color: #ff9999; 
            border: 1px solid rgba(239, 68, 68, 0.2); 
        }

        .alert-success { 
            background: rgba(16, 185, 129, 0.15); 
            color: #99ffcc; 
            border: 1px solid rgba(16, 185, 129, 0.2); 
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .step {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: var(--border-color);
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1rem;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 2;
            border: 3px solid var(--surface);
        }

        .step.active {
            background: var(--secondary);
            color: #fff;
            box-shadow: 0 0 20px rgba(76, 159, 138, 0.4);
            transform: scale(1.1);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .back-link:hover { color: var(--secondary); transform: translateX(-5px); }

        .otp-inputs {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-input {
            width: 52px; height: 64px;
            text-align: center;
            font-size: 1.75rem;
            font-weight: 850;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--border-color);
            border-radius: 14px;
            color: #fff;
            transition: all 0.3s ease;
        }

        .otp-input:focus {
            border-color: var(--secondary);
            outline: none;
            box-shadow: 0 0 0 4px var(--focus-ring);
            background: rgba(15, 23, 42, 0.8);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="ambient-bg"></div>

    <div class="card">
        <div class="header">
            <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="logo">
            <h1>Reset Password</h1>
            <p>Securely recover your account Access</p>
        </div>

        <div class="step-indicator">
            <div class="step <?php echo $step === 'identify' ? 'active' : ''; ?>">1</div>
            <div class="step <?php echo $step === 'verify' ? 'active' : ''; ?>">2</div>
            <div class="step <?php echo $step === 'reset' ? 'active' : ''; ?>">3</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if ($step === 'identify'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="identify">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($phone); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn">
                    <span>Send Verification Code</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        <?php elseif ($step === 'verify'): ?>
            <form method="POST" id="otpForm">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="otp" id="otpHidden">
                <div class="otp-inputs">
                    <?php for($i=0; $i<6; $i++): ?>
                        <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                    <?php endfor; ?>
                </div>
                <button type="submit" class="btn">
                    <span>Verify Code</span>
                    <i class="fas fa-shield-check"></i>
                </button>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="?reset_session=1" style="color: var(--text-muted); font-size: 0.85rem;">Didn't get code? Try again</a>
                </div>
            </form>
        <?php elseif ($step === 'reset'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="reset">
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="New Password" required minlength="8">
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <i class="fas fa-lock-keyhole"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8">
                    </div>
                </div>
                <button type="submit" class="btn">
                    <span>Change Password</span>
                    <i class="fas fa-key"></i>
                </button>
            </form>
        <?php endif; ?>

        <a href="/login/" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Login
        </a>
    </div>

    <script>
        // OTP Inputs behavior
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otpHidden');
        const otpForm = document.getElementById('otpForm');

        if (otpInputs.length > 0) {
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                    updateOTP();
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });

                // Paste support
                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const data = e.clipboardData.getData('text').slice(0, 6);
                    if (/^\d+$/.test(data)) {
                        data.split('').forEach((char, i) => {
                            if (otpInputs[i]) otpInputs[i].value = char;
                        });
                        updateOTP();
                        otpInputs[Math.min(data.length, 5)].focus();
                    }
                });
            });
        }

        function updateOTP() {
            let val = '';
            otpInputs.forEach(i => val += i.value);
            if (otpHidden) otpHidden.value = val;
        }
    </script>
</body>
</html>

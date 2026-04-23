<?php
// /admin/login/index.php
session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BASE_URL', 'https://openshelf.free.nf');
define('OTP_EXPIRY', 300); // 5 minutes

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';

// Load mailer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/lib/Mailer.php';
$mailer = new Mailer();

/**
 * Find admin by email in DB
 */
function findAdminByEmail($email) {
    if (empty($email)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

/**
 * Generate OTP
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
    
    $otpId = 'otp_' . uniqid() . '_' . bin2hex(random_bytes(4));
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
    if ($otpData['attempts'] >= 3) {
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
$step = $_SESSION['admin_login_step'] ?? 'email';
$email = $_SESSION['admin_email'] ?? '';
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';

// Clear session messages
unset($_SESSION['error']);
unset($_SESSION['success']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'request_otp') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Valid email is required';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $admin = findAdminByEmail($email);
        
        if (!$admin) {
            $_SESSION['error'] = 'No active admin account found';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $otp = generateOTP();
        $otpId = saveOTP($email, $otp);
        
        try {
            $mailer->sendTemplate(
                $email,
                $admin['name'],
                'otp',
                [
                    'subject'      => 'Your OpenShelf Admin Login OTP',
                    'otp'          => $otp,
                    'expiry_minutes'=> 5,
                    'ip_address'   => $_SERVER['REMOTE_ADDR'],
                    'user_agent'   => $_SERVER['HTTP_USER_AGENT'],
                    'base_url'     => BASE_URL
                ]
            );
            
            $_SESSION['admin_login_step'] = 'otp';
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_otp_id'] = $otpId;
            $_SESSION['success'] = "OTP sent to $email";
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to send OTP: ' . $e->getMessage();
        }
        
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    elseif (isset($_POST['action']) && $_POST['action'] === 'verify_otp') {
        $submittedOtp = trim($_POST['otp'] ?? '');
        $otpId = $_SESSION['admin_otp_id'] ?? '';
        
        if (!preg_match('/^\d{6}$/', $submittedOtp)) {
            $_SESSION['error'] = 'Please enter a valid 6-digit OTP';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        $email = verifyOTP($otpId, $submittedOtp);
        
        if ($email) {
            $admin = findAdminByEmail($email);
            
            if ($admin) {
                $db = getDB();
                $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
                
                // Set ALL session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_login_time'] = time();
                
                // Clear OTP session data
                unset($_SESSION['admin_login_step']);
                unset($_SESSION['admin_otp_id']);
                
                // Force session write and close
                session_write_close();
                
                // Redirect to dashboard
                header('Location: /admin/dashboard/');
                exit;
            }
        }
        
        $_SESSION['error'] = 'Invalid or expired OTP';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - OpenShelf</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); height: 100vh; display: flex; justify-content: center; align-items: center; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 350px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #666; font-weight: 600; }
        input { width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 16px; box-sizing: border-box; }
        input:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 5px; font-size: 16px; font-weight: 600; cursor: pointer; }
        button:hover { opacity: 0.9; }
        .alert { padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; }
        .step { width: 30px; height: 30px; border-radius: 50%; background: #e0e0e0; color: #666; display: flex; align-items: center; justify-content: center; margin: 0 10px; }
        .step.active { background: #667eea; color: white; }
        .otp-inputs { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; }
        .otp-input { width: 45px; height: 50px; text-align: center; font-size: 24px; border: 2px solid #e0e0e0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        
        <div class="step-indicator">
            <div class="step <?php echo $step === 'email' ? 'active' : ''; ?>">1</div>
            <div class="step <?php echo $step === 'otp' ? 'active' : ''; ?>">2</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step === 'email'): ?>
            <form method="POST">
                <input type="hidden" name="action" value="request_otp">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autofocus>
                </div>
                <button type="submit">Send OTP</button>
            </form>
            
        <?php else: ?>
            <form method="POST" id="otpForm">
                <input type="hidden" name="action" value="verify_otp">
                <input type="hidden" name="otp" id="otpHidden">
                
                <div class="form-group">
                    <label>Enter 6-Digit OTP</label>
                    <div class="otp-inputs">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric">
                        <?php endfor; ?>
                    </div>
                </div>
                
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    OTP sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
                </p>
                
                <button type="submit">Verify & Login</button>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const hidden = document.getElementById('otpHidden');
        
        if (inputs.length > 0) {
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    if (this.value.length === 1 && index < 5) {
                        inputs[index + 1].focus();
                    }
                    updateOTP();
                });
                
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && index > 0) {
                        inputs[index - 1].focus();
                    }
                });
            });
            
            inputs[0].focus();
        }
        
        function updateOTP() {
            let otp = '';
            inputs.forEach(i => otp += i.value);
            hidden.value = otp;
        }
    </script>
</body>
</html>
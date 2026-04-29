<?php
/**
 * OpenShelf OTP Verification Page
 */

session_start();
require_once dirname(__DIR__) . '/includes/db.php';

$email = $_SESSION['verify_email'] ?? '';

if (empty($email)) {
    header('Location: /login/');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $error = 'Please enter the verification code.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND otp_code = ?");
        $stmt->execute([$email, $otp]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check expiry
            if (strtotime($user['otp_expiry']) < time()) {
                $error = 'Verification code has expired. Please request a new one.';
            } else {
                // Success! Verify user
                $stmt = $db->prepare("UPDATE users SET verified = 1, status = 'active', otp_code = NULL, otp_expiry = NULL, verified_at = ? WHERE id = ?");
                $stmt->execute([date('Y-m-d H:i:s'), $user['id']]);
                
                // Update JSON profile
                $profileFile = dirname(__DIR__) . '/users/' . $user['id'] . '.json';
                if (file_exists($profileFile)) {
                    $profileData = json_decode(file_get_contents($profileFile), true);
                    $profileData['account_info']['verified'] = true;
                    $profileData['account_info']['status'] = 'active';
                    file_put_contents($profileFile, json_encode($profileData, JSON_PRETTY_PRINT));
                }
                
                $success = true;
                unset($_SESSION['verify_email']);
            }
        } else {
            $error = 'Invalid verification code. Please try again.';
        }
    }
}

// Resend OTP logic
if (isset($_GET['resend'])) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE email = ? AND status = 'unverified'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $otp = sprintf("%06d", random_int(0, 999999));
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
        $stmt->execute([$otp, $otp_expiry, $user['id']]);
        
        // Send email
        require_once dirname(__DIR__) . '/vendor/autoload.php';
        require_once dirname(__DIR__) . '/lib/Mailer.php';
        $mailer = new Mailer();
        
        try {
            $mailer->sendTemplate(
                $user['email'],
                $user['name'],
                'registration_otp',
                [
                    'subject'    => 'Your New OpenShelf Verification Code',
                    'otp'        => $otp,
                    'expiry_minutes' => 15,
                    'user_name'  => $user['name'],
                    'user_email' => $user['email'],
                    'base_url'   => 'https://openshelf.free.nf',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]
            );
            $resend_success = "A new code has been sent to your email.";
        } catch (Exception $e) {
            $error = "Failed to send email. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --bg-dark: #0f172a;
            --surface: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #10b981;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .ambient-bg {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: -1;
            background: radial-gradient(circle at 15% 50%, rgba(79, 70, 229, 0.1) 0%, transparent 40%),
                        radial-gradient(circle at 85% 30%, rgba(14, 165, 233, 0.08) 0%, transparent 40%);
        }

        .verify-card {
            background: var(--surface);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            width: 100%;
            max-width: 450px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 20px 50px -12px rgba(0,0,0,0.5);
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-light);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);
        }

        h1 { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; }
        p { color: var(--text-muted); margin-bottom: 2rem; line-height: 1.6; }

        .otp-input-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            transition: all 0.2s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: var(--primary-light);
            background: var(--bg-dark);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
        }

        .btn-verify {
            width: 100%;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 1.1rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .btn-verify:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .resend-link {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .resend-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error { background: rgba(239, 68, 68, 0.1); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.2); }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.2); }

        /* Success State */
        .success-box {
            text-align: center;
        }
        .success-box i { font-size: 4rem; color: var(--success); margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="ambient-bg"></div>

    <div class="verify-card">
        <?php if ($success): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <h1>Account Verified!</h1>
                <p>Your email has been successfully verified. You can now access your account.</p>
                <a href="/login/" class="btn-verify" style="display: block; text-decoration: none;">Continue to Login</a>
            </div>
        <?php else: ?>
            <div class="icon-box">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h1>Verify Your Email</h1>
            <p>We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong></p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($resend_success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $resend_success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="verifyForm">
                <div class="otp-input-group">
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="text" class="otp-input" maxlength="1" pattern="\d" required>
                    <input type="hidden" name="otp" id="full-otp">
                </div>

                <button type="submit" class="btn-verify">Verify Account</button>
            </form>

            <div class="resend-link">
                Didn't receive the code? <a href="?resend=1">Resend Code</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        const hiddenInput = document.getElementById('full-otp');
        const form = document.getElementById('verifyForm');

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length > 1) {
                    e.target.value = e.target.value.slice(0, 1);
                }
                if (e.target.value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenInput();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        function updateHiddenInput() {
            let otp = '';
            inputs.forEach(input => otp += input.value);
            hiddenInput.value = otp;
        }

        form.addEventListener('submit', (e) => {
            updateHiddenInput();
            if (hiddenInput.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits');
            }
        });
    </script>
</body>
</html>

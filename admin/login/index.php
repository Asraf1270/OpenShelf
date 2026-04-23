<?php
// /admin/login/index.php
session_start();

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';

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

// Initialize variables
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';

// Clear session messages
unset($_SESSION['error']);
unset($_SESSION['success']);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email and password are required';
    } else {
        $admin = findAdminByEmail($email);
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $db = getDB();
            $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);
            
            // Set ALL session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            
            // Force session write and close
            session_write_close();
            
            // Redirect to dashboard
            header('Location: /admin/dashboard/');
            exit;
        } else {
            $_SESSION['error'] = 'Invalid email or password';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - OpenShelf</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #f8fafc;
            --text: #1e293b;
            --text-muted: #64748b;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
            color: var(--text);
        }
        .login-box { 
            background: rgba(255, 255, 255, 0.95); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
            width: 100%;
            max-width: 400px; 
            backdrop-filter: blur(10px);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 { 
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            color: #1e293b;
        }
        .login-header p {
            margin: 10px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }
        .form-group { margin-bottom: 20px; }
        label { 
            display: block; 
            margin-bottom: 8px; 
            color: #475569; 
            font-weight: 500; 
            font-size: 14px;
        }
        input { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px; 
            font-size: 15px; 
            box-sizing: border-box; 
            transition: all 0.2s;
            background: #fff;
        }
        input:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }
        button { 
            width: 100%; 
            padding: 14px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-size: 16px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.2s;
            margin-top: 10px;
        }
        button:hover { 
            background: var(--primary-hover);
            transform: translateY(-1px);
        }
        button:active {
            transform: translateY(0);
        }
        .alert { 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            text-align: center; 
            font-size: 14px;
            font-weight: 500;
        }
        .alert-error { 
            background: #fef2f2; 
            color: #dc2626; 
            border: 1px solid #fee2e2; 
        }
        .alert-success { 
            background: #f0fdf4; 
            color: #16a34a; 
            border: 1px solid #dcfce7; 
        }
        .footer-links {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
        }
        .footer-links a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <h2>Admin Portal</h2>
            <p>Welcome back! Please login to continue.</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="admin@openshelf.com" required autofocus>
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit">Sign In</button>
        </form>

        <div class="footer-links">
            <a href="/">&larr; Back to OpenShelf</a>
        </div>
    </div>
</body>
</html>
<?php
/**
 * OpenShelf Admin Profile
 * Manage admin account settings
 */
session_start();

define('DATA_PATH', dirname(__DIR__) . '/data/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];

/**
 * Load admin data from DB
 */
function loadAdminData($adminId) {
    if (empty($adminId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    return $stmt->fetch() ?: null;
}

/**
 * Update admin data in DB
 */
function updateAdmin($adminId, $data) {
    $db = getDB();
    
    $sql = "UPDATE admins SET name = :name";
    $params = [
        ':name' => $data['name'],
        ':id' => $adminId
    ];
    
    if (!empty($data['password'])) {
        $sql .= ", password_hash = :password_hash";
        $params[':password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

$admin = loadAdminData($adminId);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name)) {
        $error = 'Name is required';
    } elseif (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $error = 'Current password is required to change password';
        } elseif (!password_verify($currentPassword, $admin['password_hash'])) {
            $error = 'Current password is incorrect';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Passwords do not match';
        }
    }
    
    if (empty($error)) {
        $updateData = ['name' => $name];
        if (!empty($newPassword)) {
            $updateData['password'] = $newPassword;
        }
        
        if (updateAdmin($adminId, $updateData)) {
            $_SESSION['admin_name'] = $name;
            $message = 'Profile updated successfully';
            $admin = loadAdminData($adminId);
        } else {
            $error = 'Failed to update profile';
        }
    }
}

$adminName = $admin['name'];
$adminEmail = $admin['email'];
$adminRole = $admin['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns: 1fr; }
        }
        .profile-card {
            background: var(--admin-card-bg, #ffffff);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
        }
        .profile-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .profile-header h2 { margin: 0; font-size: 1.5rem; font-weight: 700; }
        .profile-header p { margin: 0.5rem 0 0; opacity: 0.8; font-size: 0.9rem; }
        
        .profile-body { padding: 2rem; }
        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-main);
        }
        .section-title i { color: #6366f1; }
        
        .form-group { margin-bottom: 1.5rem; }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .form-control:disabled {
            background: #f1f5f9;
            cursor: not-allowed;
            color: #94a3b8;
        }
        
        .btn-save {
            width: 100%;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
    </style>
</head>
<body class="admin-page">
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <div class="profile-container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="profile-grid">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h2>Account Profile</h2>
                        <p><?php echo htmlspecialchars($adminRole); ?> Status</p>
                    </div>
                    <div class="profile-body">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($adminName); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($adminEmail); ?>" disabled title="Email cannot be changed">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admin Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($adminRole); ?>" disabled>
                        </div>
                    </div>
                </div>

                <!-- Security Card -->
                <div class="profile-card">
                    <div class="profile-body">
                        <div class="section-title">
                            <i class="fas fa-lock"></i>
                            Security Settings
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Required to change password">
                        </div>
                        
                        <hr style="margin: 2rem 0; border: none; border-top: 1px solid #e2e8f0;">
                        
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>
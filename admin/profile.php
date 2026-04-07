<?php
/**
 * OpenShelf Admin Profile
 * Manage admin account settings
 */

define('DATA_PATH', dirname(__DIR__) . '/data/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'];
$adminEmail = $_SESSION['admin_email'];
$adminRole = $_SESSION['admin_role'];

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
        .admin-profile {
            max-width: 600px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .profile-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .profile-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: #6366f1;
        }
        .profile-body {
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }
        .form-control:focus {
            outline: none;
            border-color: #6366f1;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="admin-profile">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($adminName); ?></h2>
                    <p><?php echo htmlspecialchars($adminRole); ?> • <?php echo htmlspecialchars($adminEmail); ?></p>
                </div>
                
                <div class="profile-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($adminName); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($adminEmail); ?>" disabled>
                            <small style="color: #64748b;">Email cannot be changed</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($adminRole); ?>" disabled>
                        </div>
                        
                        <hr style="margin: 1.5rem 0;">
                        
                        <h3>Change Password</h3>
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>
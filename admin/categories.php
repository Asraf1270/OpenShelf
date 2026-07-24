<?php
session_start();
/**
 * OpenShelf Admin Category Management
 * Manage book categories
 */

define('DATA_PATH', dirname(__DIR__) . '/data/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

function loadCategories() {
    $db = getDB();
    
    // Auto-sync: Add any categories used in books but missing in categories table
    try {
        $db->query("INSERT IGNORE INTO categories (name) 
                    SELECT DISTINCT category FROM books 
                    WHERE category IS NOT NULL AND category != ''");
    } catch (Exception $e) {
        // Silent fail if table doesn't support this yet
    }

    $sql = "SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category = c.name) as count 
            FROM categories c 
            ORDER BY c.name ASC";
    $stmt = $db->query($sql);
    $categories = $stmt->fetchAll();
    
    return $categories;
}

$categories = loadCategories();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $stmt = $db->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
            if ($stmt->execute([$name])) {
                $message = 'Category added successfully';
            } else {
                $error = 'Failed to add category';
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
        if ($stmt->execute([$name, $id])) {
            $message = 'Category updated';
        } else {
            $error = 'Failed to update category';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = 'Category deleted';
        } else {
            $error = 'Failed to delete category';
        }
    } elseif ($action === 'collect') {
        try {
            $stmt = $db->query("INSERT IGNORE INTO categories (name) 
                                SELECT DISTINCT category FROM books 
                                WHERE category IS NOT NULL AND category != ''");
            $count = $stmt->rowCount();
            $message = "Sync complete. Collected $count new categories.";
        } catch (Exception $e) {
            $error = 'Sync failed: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
    }

    body {
        background: var(--bg);
        color: var(--text-main);
        transition: background 0.3s ease;
    }

    .categories-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .add-form {
        background: var(--surface);
        padding: 2rem;
        border-radius: var(--radius-md);
        margin-bottom: 2.5rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .category-list {
        background: var(--surface);
        border-radius: var(--radius-md);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .category-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 2rem;
        border-bottom: 1px solid var(--border);
        transition: background 0.3s ease;
    }

    .category-item:last-child {
        border-bottom: none;
    }

    .category-item:hover {
        background: rgba(76, 159, 138, 0.05);
    }

    .category-name {
        font-weight: 750;
        font-size: 1.1rem;
        color: var(--text-main);
    }

    .category-count {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-left: 0.5rem;
        font-weight: 600;
    }

    .actions {
        display: flex;
        gap: 0.75rem;
    }

    .btn-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--bg);
        border: 1px solid var(--border);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        transition: all 0.3s ease;
    }

    .btn-icon:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .btn-icon.delete:hover {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="categories-page">
            <div class="flex justify-between items-center" style="margin-bottom: 2rem;">
                <h1 style="margin: 0; font-weight: 850; letter-spacing: -1.5px;">Book Categories</h1>
                <form method="POST">
                    <input type="hidden" name="action" value="collect">
                    <button type="submit" class="export-btn" style="background: var(--primary); font-size: 0.85rem;">
                        <i class="fas fa-sync"></i> Collect Categories
                    </button>
                </form>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="add-form">
                <h3>Add New Category</h3>
                <form method="POST" style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="name" class="form-control" style="flex: 1;" placeholder="Category name" required>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </form>
            </div>
            
            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <div>
                            <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                            <span class="category-count">(<?php echo $cat['count']; ?> books)</span>
                        </div>
                        <div class="actions">
                            <button class="btn-icon" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this category?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn-icon delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Category</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <input type="text" name="name" id="editName" class="form-control" style="width: 100%;">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editCategory(id, name) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
    
    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>
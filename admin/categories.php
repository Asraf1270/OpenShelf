<?php
/**
 * OpenShelf Admin Category Management
 * Manage book categories
 */

session_start();

define('DATA_PATH', dirname(__DIR__) . '/data/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

function loadCategories() {
    $categoriesFile = DATA_PATH . 'categories.json';
    if (file_exists($categoriesFile)) {
        return json_decode(file_get_contents($categoriesFile), true) ?? [];
    }
    return [
        ['id' => 1, 'name' => 'Fiction', 'count' => 0],
        ['id' => 2, 'name' => 'Non-Fiction', 'count' => 0],
        ['id' => 3, 'name' => 'Science Fiction', 'count' => 0],
        ['id' => 4, 'name' => 'Fantasy', 'count' => 0],
        ['id' => 5, 'name' => 'Mystery', 'count' => 0],
        ['id' => 6, 'name' => 'Biography', 'count' => 0],
        ['id' => 7, 'name' => 'History', 'count' => 0],
        ['id' => 8, 'name' => 'Programming', 'count' => 0],
        ['id' => 9, 'name' => 'Science', 'count' => 0],
        ['id' => 10, 'name' => 'Self-Help', 'count' => 0]
    ];
}

function saveCategories($categories) {
    $categoriesFile = DATA_PATH . 'categories.json';
    return file_put_contents($categoriesFile, json_encode($categories, JSON_PRETTY_PRINT));
}

$categories = loadCategories();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $error = 'Category name is required';
        } else {
            $newId = max(array_column($categories, 'id')) + 1;
            $categories[] = ['id' => $newId, 'name' => $name, 'count' => 0];
            if (saveCategories($categories)) {
                $message = 'Category added successfully';
            } else {
                $error = 'Failed to add category';
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        foreach ($categories as &$cat) {
            if ($cat['id'] === $id) {
                $cat['name'] = $name;
                break;
            }
        }
        if (saveCategories($categories)) {
            $message = 'Category updated';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $categories = array_filter($categories, fn($c) => $c['id'] !== $id);
        if (saveCategories(array_values($categories))) {
            $message = 'Category deleted';
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
        .categories-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .add-form {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        .category-list {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
        }
        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .category-name {
            font-weight: 500;
        }
        .category-count {
            color: #64748b;
            font-size: 0.8rem;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            color: #64748b;
        }
        .btn-icon:hover {
            color: #6366f1;
        }
        .btn-icon.delete:hover {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="categories-page">
            <h1 style="margin-bottom: 1.5rem;">Book Categories</h1>
            
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
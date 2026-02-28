<?php
require_once '../config.php';
require_once '../functions.php';

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$categories = getAllCategories();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $categoryName = sanitizeInput($_POST['category_name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');

        if (empty($categoryName)) {
            $error = 'Category name is required';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO categories (category_name, description)
                    VALUES (?, ?)
                ");
                if ($stmt->execute([$categoryName, $description])) {
                    $success = 'Category added successfully';
                    logActivity('admin', $_SESSION['admin_id'], 'CATEGORY_ADDED', 'New category: ' . $categoryName);
                    $categories = getAllCategories();
                } else {
                    $error = 'Failed to add category';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'delete_category') {
        $categoryId = intval($_POST['category_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$categoryId])) {
                $success = 'Category deleted successfully';
                logActivity('admin', $_SESSION['admin_id'], 'CATEGORY_DELETED', 'Category ID: ' . $categoryId);
                $categories = getAllCategories();
            } else {
                $error = 'Failed to delete category';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Welcome, <?php echo $_SESSION['admin_username']; ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php"><i class="fas fa-list"></i> All Grievances</a></li>
                <li><a href="staff-management.php"><i class="fas fa-users"></i> Staff Management</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-tags"></i> Categories Management</h1>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Category Form -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Add New Category</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="form-group" style="grid-column: 1;">
                        <label>Category Name *</label>
                        <input type="text" name="category_name" required>
                    </div>

                    <div class="form-group" style="grid-column: 2;">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="align-self: flex-end; grid-column: 2;">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </form>
            </div>

            <!-- Categories Table -->
            <div class="table-container">
                <div style="padding: 1.5rem; border-bottom: 1px solid #ddd;">
                    <h3>All Categories</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?php echo $cat['category_name']; ?></strong></td>
                            <td><?php echo $cat['description'] ?? 'N/A'; ?></td>
                            <td><?php echo date('d M Y', strtotime($cat['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
<?php
require_once '../config.php';
require_once '../functions.php';

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setting_key = sanitizeInput($_POST['setting_key'] ?? '');
    $setting_value = sanitizeInput($_POST['setting_value'] ?? '');

    if (empty($setting_key)) {
        $error = 'Setting key is required';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            if ($stmt->execute([$setting_key, $setting_value, $setting_value])) {
                $success = 'Setting updated successfully';
            } else {
                $error = 'Failed to update setting';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get all settings
$stmt = $pdo->prepare("SELECT * FROM settings ORDER BY setting_key ASC");
$stmt->execute();
$settings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
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
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-cog"></i> System Settings</h1>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Settings Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Setting</th>
                            <th>Value</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($settings as $setting): ?>
                        <tr>
                            <td><strong><?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($setting['setting_value']); ?></td>
                            <td><?php echo date('d M Y, H:i', strtotime($setting['updated_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editSetting(<?php echo htmlspecialchars(json_encode($setting)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add New Setting -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Add/Update Setting</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Setting Key *</label>
                        <input type="text" name="setting_key" placeholder="e.g., college_name" required>
                    </div>

                    <div class="form-group">
                        <label>Setting Value *</label>
                        <textarea name="setting_value" rows="3" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                        <i class="fas fa-save"></i> Save Setting
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function editSetting(setting) {
            document.querySelector('input[name="setting_key"]').value = setting.setting_key;
            document.querySelector('textarea[name="setting_value"]').value = setting.setting_value;
            document.querySelector('input[name="setting_key"]').disabled = true;
            window.scrollTo(0, document.body.scrollHeight);
        }
    </script>
</body>
</html>
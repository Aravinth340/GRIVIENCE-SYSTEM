<?php
require_once '../config.php';
require_once '../functions.php';

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$departments = getAllDepartments();
$allStaff = getAllStaff();
$error = '';
$success = '';

// Handle staff actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_staff') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $department_id = intval($_POST['department_id'] ?? 0);
        $role = sanitizeInput($_POST['role'] ?? 'staff');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($department_id) || empty($password)) {
            $error = 'All fields are required';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("
                    INSERT INTO staff (name, email, department_id, phone, password, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$name, $email, $department_id, $phone, $hashedPassword, $role])) {
                    $success = 'Staff member added successfully';
                    logActivity('admin', $_SESSION['admin_id'], 'STAFF_ADDED', 'New staff: ' . $name);
                    $allStaff = getAllStaff();
                } else {
                    $error = 'Failed to add staff member';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    } elseif ($action === 'update_staff') {
        $staff_id = intval($_POST['staff_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'staff');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("
                UPDATE staff SET name = ?, phone = ?, role = ?, is_active = ? WHERE id = ?
            ");
            if ($stmt->execute([$name, $phone, $role, $is_active, $staff_id])) {
                $success = 'Staff member updated successfully';
                logActivity('admin', $_SESSION['admin_id'], 'STAFF_UPDATED', 'Updated staff ID: ' . $staff_id);
                $allStaff = getAllStaff();
            } else {
                $error = 'Failed to update staff member';
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
    <title>Staff Management - Admin Panel</title>
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
                <li><a href="staff-management.php" class="active"><i class="fas fa-users"></i> Staff Management</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-users"></i> Staff Management</h1>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add Staff Form -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Add New Staff Member</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <input type="hidden" name="action" value="add_staff">
                    
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required>
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>

                    <div class="form-group">
                        <label>Department *</label>
                        <select name="department_id" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="staff">Staff</option>
                            <option value="hod">HOD</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                        <i class="fas fa-plus"></i> Add Staff
                    </button>
                </form>
            </div>

            <!-- Staff Table -->
            <div class="table-container">
                <div style="padding: 1.5rem; border-bottom: 1px solid #ddd;">
                    <h3>Staff Members</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStaff as $staff): ?>
                        <tr>
                            <td><strong><?php echo $staff['name']; ?></strong></td>
                            <td><?php echo $staff['email']; ?></td>
                            <td><?php echo $staff['department_name']; ?></td>
                            <td><?php echo $staff['phone'] ?? 'N/A'; ?></td>
                            <td><?php echo ucfirst($staff['role']); ?></td>
                            <td>
                                <span class="status-badge" style="background: <?php echo $staff['is_active'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $staff['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo $staff['last_login'] ? date('d M Y, H:i', strtotime($staff['last_login'])) : 'Never'; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editStaff(<?php echo htmlspecialchars(json_encode($staff)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function editStaff(staff) {
            // Implementation for editing staff
            alert('Edit functionality for: ' + staff.name);
        }
    </script>
</body>
</html>
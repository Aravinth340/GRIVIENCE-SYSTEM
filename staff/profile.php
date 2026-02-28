<?php
require_once '../config.php';
require_once '../functions.php';

if (!isStaffLoggedIn()) {
    redirectToLogin();
}

$staffId = $_SESSION['staff_id'];
$staff = getStaffById($staffId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!empty($phone)) {
        try {
            $stmt = $pdo->prepare("UPDATE staff SET phone = ? WHERE id = ?");
            if ($stmt->execute([$phone, $staffId])) {
                $success = 'Phone number updated successfully';
                $staff = getStaffById($staffId);
            } else {
                $error = 'Failed to update phone number';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    if (!empty($currentPassword) && !empty($newPassword)) {
        if ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters';
        } elseif (!password_verify($currentPassword, $staff['password'])) {
            $error = 'Current password is incorrect';
        } else {
            try {
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE staff SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $staffId])) {
                    $success = 'Password changed successfully';
                    $staff = getStaffById($staffId);
                } else {
                    $error = 'Failed to change password';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Staff Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-users"></i> Staff Panel</h3>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Welcome, <?php echo $_SESSION['staff_name']; ?></p>
                <p style="font-size: 0.85rem; color: #999; margin-top: 0.25rem;"><?php echo $_SESSION['department_name']; ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php"><i class="fas fa-list"></i> My Grievances</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-user"></i> My Profile</h1>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Profile Information -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Profile Information</h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; color: #666;">Name</label>
                        <p><?php echo $staff['name']; ?></p>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; color: #666;">Email</label>
                        <p><?php echo $staff['email']; ?></p>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; color: #666;">Department</label>
                        <p><?php echo $staff['department_name']; ?></p>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-weight: 600; color: #666;">Role</label>
                        <p><?php echo ucfirst($staff['role']); ?></p>
                    </div>
                    <div>
                        <label style="font-weight: 600; color: #666;">Status</label>
                        <p>
                            <span class="status-badge" style="background: <?php echo $staff['is_active'] ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $staff['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Update Profile -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1.5rem;">Update Phone</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo $staff['phone'] ?? ''; ?>" placeholder="10-digit mobile number">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Update Phone
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-top: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1.5rem;">Change Password</h3>
                <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Current Password *</label>
                        <input type="password" name="current_password" placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label>New Password *</label>
                        <input type="password" name="new_password" placeholder="Enter new password" minlength="6">
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password *</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password" minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary" style="align-self: flex-end;">
                        <i class="fas fa-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
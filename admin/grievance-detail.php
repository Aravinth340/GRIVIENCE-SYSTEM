<?php
require_once '../config.php';
require_once '../functions.php';

// Check authentication
if (!isAdminLoggedIn()) {
    redirectToLogin();
}

// Get grievance ID from URL
$grievanceId = intval($_GET['id'] ?? 0);

if ($grievanceId <= 0) {
    die("Invalid grievance ID");
}

// Fetch grievance details
$grievance = getGrievanceById($grievanceId);

if (!$grievance) {
    die("Grievance not found!");
}

// Get related data
$comments = getComments($grievanceId);
$staff = getStaffByDepartment($grievance['department_id']);

// Handle form submissions
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $newStatus = sanitizeInput($_POST['status'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');

        if (empty($newStatus)) {
            $error = 'Status is required';
        } elseif (updateGrievanceStatus($grievanceId, $newStatus, $remarks)) {
            // Send email notification if email exists
            if (!empty($grievance['email'])) {
                sendStatusUpdateEmail($grievance['email'], $grievance['student_name'], $grievance['grievance_id'], $newStatus, $remarks);
            }
            $success = 'Status updated successfully!';
            logActivity('admin', $_SESSION['admin_id'], 'STATUS_UPDATED', 'Updated to: ' . $newStatus, $grievanceId);
            // Refresh grievance data
            $grievance = getGrievanceById($grievanceId);
        } else {
            $error = 'Failed to update status';
        }
    }
    elseif ($action === 'assign_staff') {
        $staffId = intval($_POST['staff_id'] ?? 0);
        
        if ($staffId <= 0) {
            $error = 'Please select a staff member';
        } elseif (assignGrievanceToStaff($grievanceId, $staffId)) {
            $success = 'Grievance assigned successfully!';
            logActivity('admin', $_SESSION['admin_id'], 'GRIEVANCE_ASSIGNED', 'Assigned to staff: ' . $staffId, $grievanceId);
            $grievance = getGrievanceById($grievanceId);
        } else {
            $error = 'Failed to assign grievance';
        }
    }
    elseif ($action === 'close_grievance') {
        $closureReason = sanitizeInput($_POST['closure_reason'] ?? '');
        
        if (empty($closureReason)) {
            $error = 'Closure reason is required';
        } elseif (!canCloseGrievance($grievance['status'])) {
            $error = 'Grievance can only be closed if status is Resolved or Rejected';
        } elseif (closeGrievance($grievanceId, $closureReason)) {
            $success = 'Grievance closed successfully!';
            logActivity('admin', $_SESSION['admin_id'], 'GRIEVANCE_CLOSED', 'Closed: ' . $closureReason, $grievanceId);
            $grievance = getGrievanceById($grievanceId);
        } else {
            $error = 'Failed to close grievance';
        }
    }
    elseif ($action === 'add_comment') {
        $comment = sanitizeInput($_POST['comment'] ?? '');
        
        if (empty($comment)) {
            $error = 'Comment cannot be empty';
        } elseif (addComment($grievanceId, $_SESSION['admin_id'], 'admin', $comment, true)) {
            $success = 'Comment added successfully!';
            logActivity('admin', $_SESSION['admin_id'], 'COMMENT_ADDED', 'Added comment', $grievanceId);
            $comments = getComments($grievanceId);
        } else {
            $error = 'Failed to add comment';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grievance Details - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #f8f9fa; padding: 20px; border-right: 1px solid #ddd; }
        .main-content { flex: 1; padding: 30px; background: #f5f5f5; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin: 10px 0; }
        .sidebar-menu a { display: block; padding: 10px 15px; color: #333; text-decoration: none; border-radius: 5px; }
        .sidebar-menu a.active { background: #0066cc; color: white; }
        .sidebar-menu a:hover { background: #f0f0f0; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; }
        .btn { display: inline-block; padding: 10px 20px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
        .btn-block { width: 100%; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
        .status-pending { background: #ffc107; color: #000; }
        .status-in_review { background: #17a2b8; color: white; }
        .status-resolved { background: #28a745; color: white; }
        .status-closed { background: #6c757d; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .comment-item { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div>
                <h3 style="margin-bottom: 20px;"><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <p style="color: #666; font-size: 0.9rem;">Welcome, <?php echo $_SESSION['admin_username']; ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php" class="active"><i class="fas fa-list"></i> All Grievances</a></li>
                <li><a href="staff-management.php"><i class="fas fa-users"></i> Staff Management</a></li>
                <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="reports.php"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <a href="grievances.php" class="btn btn-secondary" style="margin-bottom: 20px;">
                <i class="fas fa-arrow-left"></i> Back to Grievances
            </a>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <div class="grid-2">
                <!-- Left Column: Grievance Details -->
                <div>
                    <!-- Grievance Header -->
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                            <div>
                                <h2><?php echo htmlspecialchars($grievance['grievance_id']); ?></h2>
                                <p style="color: #666;">By <?php echo htmlspecialchars($grievance['student_name']); ?></p>
                            </div>
                            <span class="status-badge status-<?php echo $grievance['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <strong>Student Name</strong><br>
                                <?php echo htmlspecialchars($grievance['student_name']); ?>
                            </div>
                            <div>
                                <strong>Register Number</strong><br>
                                <?php echo htmlspecialchars($grievance['register_number']); ?>
                            </div>
                            <div>
                                <strong>Email</strong><br>
                                <?php echo htmlspecialchars($grievance['email'] ?? 'N/A'); ?>
                            </div>
                            <div>
                                <strong>Phone</strong><br>
                                <?php echo htmlspecialchars($grievance['phone'] ?? 'N/A'); ?>
                            </div>
                            <div>
                                <strong>Department</strong><br>
                                <?php echo htmlspecialchars($grievance['department_name']); ?>
                            </div>
                            <div>
                                <strong>Category</strong><br>
                                <?php echo htmlspecialchars($grievance['category_name']); ?>
                            </div>
                            <div>
                                <strong>Submitted</strong><br>
                                <?php echo date('d M Y, H:i', strtotime($grievance['created_at'])); ?>
                            </div>
                            <div>
                                <strong>Priority</strong><br>
                                <?php echo ucfirst(htmlspecialchars($grievance['priority'])); ?>
                            </div>
                        </div>

                        <div style="border-top: 1px solid #ddd; padding-top: 15px;">
                            <strong>Description</strong><br>
                            <p style="margin-top: 10px; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($grievance['description'])); ?>
                            </p>
                        </div>

                        <?php if ($grievance['resolution_remarks']): ?>
                        <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
                            <strong>Resolution Remarks</strong><br>
                            <p style="margin-top: 10px; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($grievance['resolution_remarks'])); ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if ($grievance['file_path'] && file_exists('../' . $grievance['file_path'])): ?>
                        <div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px;">
                            <strong>Supporting Document</strong><br>
                            <a href="../<?php echo htmlspecialchars($grievance['file_path']); ?>" download class="btn" style="margin-top: 10px;">
                                <i class="fas fa-download"></i> Download (<?php echo htmlspecialchars($grievance['file_name']); ?>)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Comments Section -->
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Internal Comments</h3>

                        <form method="POST" style="margin-bottom: 20px;">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="form-group">
                                <label>Add Comment</label>
                                <textarea name="comment" rows="4" placeholder="Type your comment..." required></textarea>
                            </div>
                            <button type="submit" class="btn">
                                <i class="fas fa-comment"></i> Add Comment
                            </button>
                        </form>

                        <div>
                            <?php if (empty($comments)): ?>
                                <p style="color: #999; text-align: center;">No comments yet</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                        <strong><?php echo ucfirst(htmlspecialchars($comment['comment_type'])); ?></strong>
                                        <small style="color: #999;">
                                            <?php echo date('d M Y, H:i', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p style="margin: 0;">
                                        <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Actions -->
                <div>
                    <!-- Update Status -->
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Update Status</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            
                            <div class="form-group">
                                <label>New Status</label>
                                <select name="status" required>
                                    <option value="">-- Select Status --</option>
                                    <option value="pending" <?php echo $grievance['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_review" <?php echo $grievance['status'] == 'in_review' ? 'selected' : ''; ?>>In Review</option>
                                    <option value="escalated" <?php echo $grievance['status'] == 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                                    <option value="resolved" <?php echo $grievance['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="rejected" <?php echo $grievance['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="closed" <?php echo $grievance['status'] == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Remarks (Optional)</label>
                                <textarea name="remarks" rows="4" placeholder="Add resolution remarks..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-block">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>
                    </div>

                    <!-- Assign to Staff -->
                    <?php if (!empty($staff) && $grievance['status'] !== 'closed'): ?>
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Assign to Staff</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="assign_staff">
                            
                            <div class="form-group">
                                <label>Select Staff Member</label>
                                <select name="staff_id" required>
                                    <option value="">-- Select Staff --</option>
                                    <?php foreach ($staff as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" <?php echo $grievance['assigned_to'] == $s['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?> (<?php echo ucfirst($s['role']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-block">
                                <i class="fas fa-user-check"></i> Assign
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Close Grievance -->
                    <?php if (canCloseGrievance($grievance['status']) && $grievance['status'] !== 'closed'): ?>
                    <div class="card" style="border: 2px solid #dc3545;">
                        <h3 style="margin-bottom: 20px; color: #dc3545;">
                            <i class="fas fa-times-circle"></i> Close Grievance
                        </h3>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                            <strong>⚠️ Warning:</strong> Closing is permanent and cannot be undone.
                        </p>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to close this grievance? This action cannot be undone.');">
                            <input type="hidden" name="action" value="close_grievance">
                            
                            <div class="form-group">
                                <label>Closure Reason *</label>
                                <textarea name="closure_reason" rows="4" placeholder="Explain why this grievance is being closed..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-danger btn-block">
                                <i class="fas fa-times-circle"></i> Close Grievance
                            </button>
                        </form>
                    </div>
                    <?php elseif ($grievance['status'] === 'closed'): ?>
                    <div class="card" style="text-align: center; background: #f0f0f0;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                        <h3 style="color: #28a745; margin: 10px 0 5px;">Grievance Closed</h3>
                        <p style="color: #666;">This grievance has been closed.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Rating -->
                    <?php if ($grievance['rating']): ?>
                    <div class="card">
                        <h3 style="margin-bottom: 20px;">Student Rating</h3>
                        <div style="text-align: center;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star" style="color: <?php echo $i <= $grievance['rating'] ? '#ffc107' : '#ddd'; ?>; font-size: 1.5rem; margin: 0 5px;"></i>
                            <?php endfor; ?>
                            <p style="margin-top: 10px; font-weight: 600; font-size: 1.2rem;">
                                <?php echo $grievance['rating']; ?>/5
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
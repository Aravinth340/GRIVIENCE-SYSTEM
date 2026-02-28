<?php
require_once '../config.php';
require_once '../functions.php';

if (!isStaffLoggedIn()) {
    redirectToLogin();
}

$grievanceId = intval($_GET['id'] ?? 0);
$grievance = getGrievanceById($grievanceId);

if (!$grievance || $grievance['assigned_to'] != $_SESSION['staff_id']) {
    echo "Grievance not found or not assigned to you!";
    exit;
}

$comments = getComments($grievanceId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $newStatus = sanitizeInput($_POST['status'] ?? '');
        $remarks = sanitizeInput($_POST['remarks'] ?? '');

        if (updateGrievanceStatus($grievanceId, $newStatus, $remarks)) {
            if (!empty($grievance['email'])) {
                sendStatusUpdateEmail($grievance['email'], $grievance['student_name'], $grievance['grievance_id'], $newStatus, $remarks);
            }
            $success = 'Status updated successfully';
            $grievance = getGrievanceById($grievanceId);
        } else {
            $error = 'Failed to update status';
        }
    } elseif ($action === 'close_grievance') {
        $closureReason = sanitizeInput($_POST['closure_reason'] ?? '');
        
        if (empty($closureReason)) {
            $error = 'Closure reason is required';
        } elseif (!canCloseGrievance($grievance['status'])) {
            $error = 'Grievance can only be closed if status is Resolved or Rejected';
        } else {
            if (closeGrievance($grievanceId, $closureReason)) {
                $success = 'Grievance closed successfully';
                logActivity('staff', $_SESSION['staff_id'], 'GRIEVANCE_CLOSED', 'Grievance closed: ' . $closureReason, $grievanceId);
                $grievance = getGrievanceById($grievanceId);
            } else {
                $error = 'Failed to close grievance';
            }
        }
    } elseif ($action === 'add_comment') {
        $comment = sanitizeInput($_POST['comment'] ?? '');
        if (!empty($comment)) {
            if (addComment($grievanceId, $_SESSION['staff_id'], 'staff', $comment, true)) {
                $success = 'Comment added successfully';
                $comments = getComments($grievanceId);
            } else {
                $error = 'Failed to add comment';
            }
        } else {
            $error = 'Comment cannot be empty';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grievance Detail - Staff Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-users"></i> Staff Panel</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php" class="active"><i class="fas fa-list"></i> My Grievances</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <a href="grievances.php" class="btn btn-secondary btn-sm" style="margin-bottom: 1rem;">
                <i class="fas fa-arrow-left"></i> Back
            </a>

            <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Main Content -->
                <div>
                    <!-- Grievance Info -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                            <div>
                                <h2><?php echo $grievance['grievance_id']; ?></h2>
                                <p style="color: #666;">From <?php echo $grievance['student_name']; ?></p>
                            </div>
                            <span class="status-badge status-<?php echo $grievance['status']; ?>" style="font-size: 1.1rem; padding: 0.75rem 1rem;">
                                <?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?>
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                            <div>
                                <label style="font-weight: 600; color: #666;">Student Name</label>
                                <p><?php echo $grievance['student_name']; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #666;">Register Number</label>
                                <p><?php echo $grievance['register_number']; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #666;">Email</label>
                                <p><?php echo $grievance['email'] ?? 'N/A'; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #666;">Phone</label>
                                <p><?php echo $grievance['phone'] ?? 'N/A'; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #666;">Category</label>
                                <p><?php echo $grievance['category_name']; ?></p>
                            </div>
                            <div>
                                <label style="font-weight: 600; color: #666;">Submitted</label>
                                <p><?php echo date('d M Y, H:i', strtotime($grievance['created_at'])); ?></p>
                            </div>
                        </div>

                        <div>
                            <label style="font-weight: 600; color: #666;">Description</label>
                            <p style="white-space: pre-wrap; line-height: 1.6;"><?php echo $grievance['description']; ?></p>
                        </div>

                        <?php if ($grievance['file_path'] && file_exists('../' . $grievance['file_path'])): ?>
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                            <label style="font-weight: 600; color: #666;">Supporting Document</label>
                            <a href="../<?php echo $grievance['file_path']; ?>" download class="btn btn-secondary btn-sm">
                                <i class="fas fa-download"></i> Download (<?php echo $grievance['file_name']; ?>)
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Comments Section -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1.5rem;">Internal Comments</h3>

                        <!-- Add Comment Form -->
                        <form method="POST" style="margin-bottom: 2rem;">
                            <input type="hidden" name="action" value="add_comment">
                            <div class="form-group">
                                <textarea name="comment" placeholder="Add internal comment..." rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-comment"></i> Add Comment
                            </button>
                        </form>

                        <!-- Comments List -->
                        <div>
                            <?php if (empty($comments)): ?>
                                <p style="color: #999; text-align: center;">No comments yet</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                <div style="padding: 1rem; background: #f9f9f9; border-radius: 4px; margin-bottom: 1rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <strong style="color: var(--primary-color);">
                                            <?php echo ucfirst($comment['comment_type']); ?>
                                        </strong>
                                        <small style="color: #999;">
                                            <?php echo date('d M Y, H:i', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p><?php echo nl2br($comment['comment_text']); ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Actions -->
                <div>
                    <!-- Update Status -->
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem;">Update Status</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="pending" <?php echo $grievance['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_review" <?php echo $grievance['status'] == 'in_review' ? 'selected' : ''; ?>>In Review</option>
                                    <option value="escalated" <?php echo $grievance['status'] == 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                                    <option value="resolved" <?php echo $grievance['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Remarks</label>
                                <textarea name="remarks" placeholder="Add resolution remarks..." rows="4"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>
                    </div>

                    <!-- Close Grievance -->
                    <?php if (canCloseGrievance($grievance['status']) && $grievance['status'] !== 'closed'): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: 2px solid #dc3545;">
                        <h3 style="margin-bottom: 1rem; color: #dc3545;">
                            <i class="fas fa-times-circle"></i> Close Grievance
                        </h3>
                        <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                            <strong>Note:</strong> Closing a grievance is permanent.
                        </p>
                        <form method="POST">
                            <input type="hidden" name="action" value="close_grievance">
                            <div class="form-group">
                                <label>Closure Reason *</label>
                                <textarea name="closure_reason" placeholder="Explain why this grievance is being closed..." rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure? This action cannot be undone.');">
                                <i class="fas fa-times-circle"></i> Close Grievance
                            </button>
                        </form>
                    </div>
                    <?php elseif ($grievance['status'] === 'closed'): ?>
                    <div style="background: #f0f0f0; padding: 1.5rem; border-radius: 8px; text-align: center;">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                        <h3 style="color: #28a745; margin-top: 0.5rem;">Grievance Closed</h3>
                        <p style="color: #666;">This grievance has been closed.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Rating -->
                    <?php if ($grievance['rating']): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 1rem;">Student Rating</h3>
                        <div style="text-align: center;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color: <?php echo $i <= $grievance['rating'] ? '#ffc107' : '#ddd'; ?>; font-size: 1.5rem;"></i>
                            <?php endfor; ?>
                            <p style="margin-top: 0.5rem; font-weight: 600;"><?php echo $grievance['rating']; ?>/5</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
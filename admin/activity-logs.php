<?php
require_once '../config.php';
require_once '../functions.php';

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$user_type = $_GET['user_type'] ?? '';

$query = "SELECT * FROM activity_logs WHERE 1=1";
$params = [];

if (!empty($user_type)) {
    $query .= " AND user_type = ?";
    $params[] = $user_type;
}

$query .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = ($page - 1) * ITEMS_PER_PAGE;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get total count
$countQuery = "SELECT COUNT(*) as count FROM activity_logs WHERE 1=1";
if (!empty($user_type)) {
    $countQuery .= " AND user_type = ?";
}
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute(!empty($user_type) ? [$user_type] : []);
$totalCount = $countStmt->fetch()['count'];
$totalPages = ceil($totalCount / ITEMS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Panel</title>
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
                <li><a href="activity-logs.php" class="active"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-history"></i> Activity Logs</h1>

            <!-- Filters -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>User Type</label>
                        <select name="user_type">
                            <option value="">All Types</option>
                            <option value="admin" <?php echo $user_type == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <option value="staff" <?php echo $user_type == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="student" <?php echo $user_type == 'student' ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="activity-logs.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Activity Logs Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User Type</th>
                            <th>User ID</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p>No activity logs found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d M Y, H:i:s', strtotime($log['timestamp'])); ?></td>
                                <td>
                                    <span class="status-badge" style="background: 
                                        <?php echo $log['user_type'] == 'admin' ? '#0066cc' : ($log['user_type'] == 'staff' ? '#28a745' : '#ffc107'); ?>">
                                        <?php echo ucfirst($log['user_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo $log['user_id'] ?? 'N/A'; ?></td>
                                <td><strong><?php echo $log['action']; ?></strong></td>
                                <td><?php echo $log['description'] ?? '-'; ?></td>
                                <td><code><?php echo $log['ip_address'] ?? 'N/A'; ?></code></td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" onclick="showDetails('<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>')">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div style="margin-top: 2rem; text-align: center;">
                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i === $page) ? 'btn-primary' : 'btn-secondary';
                    $params = http_build_query(array_merge($_GET, ['page' => $i]));
                    echo '<a href="?'.$params.'" class="btn btn-sm '.$activeClass.'" style="margin: 0.25rem;">'.$i.'</a>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        function showDetails(userAgent) {
            alert('User Agent:\n' + userAgent);
        }
    </script>
</body>
</html>
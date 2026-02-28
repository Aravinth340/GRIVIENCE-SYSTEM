<?php
// MUST be in root: config.php and functions.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Debug: Check if functions exist
if (!function_exists('isAdminLoggedIn')) {
    die('Error: functions.php not loaded properly');
}

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$status = $_GET['status'] ?? '';
$department_id = $_GET['department_id'] ?? '';
$search = $_GET['search'] ?? '';

$filters = array_filter(compact('status', 'department_id', 'search'));
$grievances = getAllGrievances($filters, $page);
$totalCount = getGrievanceCount($filters);
$totalPages = ceil($totalCount / ITEMS_PER_PAGE);
$departments = getAllDepartments();
$categories = getAllCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grievances - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .main-content { margin-left: 250px; flex: 1; padding: 30px; }
        .sidebar-header { margin-bottom: 30px; }
        .sidebar-header h3 { font-size: 1.2rem; margin-bottom: 10px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin: 10px 0; }
        .sidebar-menu a { display: block; padding: 12px 15px; color: white; text-decoration: none; border-radius: 5px; transition: all 0.3s; }
        .sidebar-menu a:hover { background: #34495e; padding-left: 20px; }
        .sidebar-menu a.active { background: #0066cc; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #0066cc; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; }
        .btn-secondary { background: #6c757d; }
        .btn-sm { padding: 5px 10px; font-size: 0.9rem; }
        .btn-block { width: 100%; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        tr:hover { background: #f9f9f9; }
        .status-badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; }
        .status-pending { background: #ffc107; color: #000; }
        .status-in_review { background: #17a2b8; color: white; }
        .status-resolved { background: #28a745; color: white; }
        .status-closed { background: #6c757d; color: white; }
        .status-rejected { background: #dc3545; color: white; }
        .pagination { text-align: center; margin-top: 30px; }
        .pagination a { margin: 0 5px; padding: 8px 12px; background: #f8f9fa; color: #0066cc; text-decoration: none; border-radius: 5px; }
        .pagination a:hover { background: #0066cc; color: white; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin</h3>
                <small>Logged in</small>
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

        <main class="main-content">
            <h1><i class="fas fa-list"></i> All Grievances</h1>

            <!-- Filters -->
            <div class="card">
                <form method="GET">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" placeholder="Grievance ID, Student, Register #" value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="form-group">
                            <label>Department</label>
                            <select name="department_id">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_review" <?php echo $status == 'in_review' ? 'selected' : ''; ?>>In Review</option>
                                <option value="escalated" <?php echo $status == 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                                <option value="resolved" <?php echo $status == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="closed" <?php echo $status == 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="grievances.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </form>
            </div>

            <!-- Grievances Table -->
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Grievance ID</th>
                            <th>Student</th>
                            <th>Register #</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grievances)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p style="margin-top: 10px; color: #999;">No grievances found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($grievances as $g): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($g['grievance_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($g['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($g['register_number']); ?></td>
                                <td><?php echo htmlspecialchars($g['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($g['category_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $g['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $g['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($g['created_at'])); ?></td>
                                <td><?php echo $g['staff_name'] ?? 'Unassigned'; ?></td>
                                <td>
                                    <a href="grievance-detail.php?id=<?php echo $g['id']; ?>" class="btn btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php
                    $params = http_build_query(array_merge($_GET, ['page' => $i]));
                    $activeClass = ($i === $page) ? 'style="background: #0066cc; color: white;"' : '';
                    ?>
                    <a href="?<?php echo $params; ?>" <?php echo $activeClass; ?>><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </main>
    </div>
</body>
</html>
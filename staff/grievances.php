<?php
require_once '../config.php';
require_once '../functions.php';

if (!isStaffLoggedIn()) {
    redirectToLogin();
}

$staffId = $_SESSION['staff_id'];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$status = $_GET['status'] ?? '';

$filters = [];
if (!empty($status)) {
    $filters['status'] = $status;
}

$grievances = getStaffGrievances($staffId);

// Apply status filter
if (!empty($status)) {
    $grievances = array_filter($grievances, function($g) use ($status) {
        return $g['status'] === $status;
    });
}

// Pagination
$itemsPerPage = ITEMS_PER_PAGE;
$totalCount = count($grievances);
$totalPages = ceil($totalCount / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;
$paginatedGrievances = array_slice($grievances, $offset, $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grievances - Staff Panel</title>
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
                <li><a href="grievances.php" class="active"><i class="fas fa-list"></i> My Grievances</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h1><i class="fas fa-list"></i> My Grievances</h1>

            <!-- Filters -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_review" <?php echo $status == 'in_review' ? 'selected' : ''; ?>>In Review</option>
                            <option value="escalated" <?php echo $status == 'escalated' ? 'selected' : ''; ?>>Escalated</option>
                            <option value="resolved" <?php echo $status == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="grievances.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Grievances Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Grievance ID</th>
                            <th>Student Name</th>
                            <th>Register #</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($paginatedGrievances)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p>No grievances found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($paginatedGrievances as $g): ?>
                            <tr>
                                <td><strong><?php echo $g['grievance_id']; ?></strong></td>
                                <td><?php echo $g['student_name']; ?></td>
                                <td><?php echo $g['register_number']; ?></td>
                                <td><?php echo $g['category_name']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $g['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $g['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($g['created_at'])); ?></td>
                                <td>
                                    <a href="grievance-detail.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-primary">
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
            <div style="margin-top: 2rem; text-align: center;">
                <?php
                for ($i = 1; $i <= $totalPages; $i++) {
                    $activeClass = ($i === $page) ? 'btn-primary' : 'btn-secondary';
                    $statusParam = !empty($status) ? '&status=' . $status : '';
                    echo '<a href="?page='.$i.$statusParam.'" class="btn btn-sm '.$activeClass.'" style="margin: 0.25rem;">'.$i.'</a>';
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>
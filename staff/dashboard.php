<?php
require_once '../config.php';
require_once '../functions.php';

if (!isStaffLoggedIn()) {
    redirectToLogin();
}

$staffId = $_SESSION['staff_id'];
$staff = getStaffById($staffId);
$grievances = getStaffGrievances($staffId);
$pendingCount = 0;
$inReviewCount = 0;
$resolvedCount = 0;

foreach ($grievances as $g) {
    if ($g['status'] === 'pending') $pendingCount++;
    elseif ($g['status'] === 'in_review') $inReviewCount++;
    elseif ($g['status'] === 'resolved') $resolvedCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Grievance System</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-users"></i> Staff Panel</h3>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Welcome, <?php echo $_SESSION['staff_name']; ?></p>
                <p style="font-size: 0.85rem; color: #999; margin-top: 0.25rem;"><?php echo $_SESSION['department_name']; ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php"><i class="fas fa-list"></i> My Grievances</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header">
                <h1><i class="fas fa-chart-line"></i> Staff Dashboard</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Total Assigned</h3>
                    <div class="number"><?php echo count($grievances); ?></div>
                    <div class="subtitle">Grievances assigned to you</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #ffc107;">
                    <h3>Pending Review</h3>
                    <div class="number" style="color: #ffc107;"><?php echo $pendingCount; ?></div>
                    <div class="subtitle">Awaiting action</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #17a2b8;">
                    <h3>In Review</h3>
                    <div class="number" style="color: #17a2b8;"><?php echo $inReviewCount; ?></div>
                    <div class="subtitle">Being processed</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #28a745;">
                    <h3>Resolved</h3>
                    <div class="number" style="color: #28a745;"><?php echo $resolvedCount; ?></div>
                    <div class="subtitle">Completed</div>
                </div>
            </div>

            <!-- Recent Grievances Table -->
            <div class="table-container">
                <div style="padding: 1.5rem; border-bottom: 1px solid #ddd;">
                    <h3>My Assigned Grievances</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Grievance ID</th>
                            <th>Student Name</th>
                            <th>Register Number</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grievances)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem;">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p>No grievances assigned yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($grievances as $grievance): ?>
                            <tr>
                                <td><strong><?php echo $grievance['grievance_id']; ?></strong></td>
                                <td><?php echo $grievance['student_name']; ?></td>
                                <td><?php echo $grievance['register_number']; ?></td>
                                <td><?php echo $grievance['category_name']; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $grievance['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($grievance['created_at'])); ?></td>
                                <td>
                                    <a href="grievance-detail.php?id=<?php echo $grievance['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
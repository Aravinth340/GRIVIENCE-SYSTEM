<?php
require_once '../config.php';
require_once '../functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$stats = getDashboardStats();
$monthlyStats = getMonthlyGrievanceStats(6);
$recentGrievances = getAllGrievances([], 1, 5);
$totalGrievances = getGrievanceCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Grievance System</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Welcome, <?php echo $_SESSION['admin_username']; ?></p>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="grievances.php"><i class="fas fa-list"></i> All Grievances</a></li>
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
            <div class="dashboard-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <div>
                    <a href="reports.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-download"></i> Export Report
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>Total Grievances</h3>
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="subtitle">All submissions</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #ffc107;">
                    <h3>Pending</h3>
                    <div class="number" style="color: #ffc107;"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="subtitle">Awaiting review</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #17a2b8;">
                    <h3>In Review</h3>
                    <div class="number" style="color: #17a2b8;"><?php echo $stats['in_review'] ?? 0; ?></div>
                    <div class="subtitle">Being processed</div>
                </div>

                <div class="dashboard-card" style="border-top-color: #28a745;">
                    <h3>Resolved</h3>
                    <div class="number" style="color: #28a745;"><?php echo $stats['resolved'] ?? 0; ?></div>
                    <div class="subtitle">Completed</div>
                </div>

                <div class="dashboard-card">
                    <h3>Average Rating</h3>
                    <div class="number"><?php echo $stats['avg_rating'] ?? 0; ?>/5</div>
                    <div class="subtitle">Resolution satisfaction</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-section" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div class="chart-container" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1rem;">Monthly Trend</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>

                <div class="chart-container" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1rem;">Department-wise Distribution</h3>
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>

            <!-- Recent Grievances Table -->
            <div class="table-container">
                <div style="padding: 1.5rem; border-bottom: 1px solid #ddd;">
                    <h3>Recent Grievances</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Grievance ID</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentGrievances as $grievance): ?>
                        <tr>
                            <td><strong><?php echo $grievance['grievance_id']; ?></strong></td>
                            <td><?php echo $grievance['student_name']; ?></td>
                            <td><?php echo $grievance['department_name']; ?></td>
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
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($item) { 
                    return date('M', strtotime($item['month'] . '-01')); 
                }, $monthlyStats)); ?>,
                datasets: [{
                    label: 'Grievances',
                    data: <?php echo json_encode(array_column($monthlyStats, 'count')); ?>,
                    borderColor: '#0066cc',
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Department Chart
        const deptCtx = document.getElementById('departmentChart').getContext('2d');
        const deptChart = new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($stats['by_department'] ?? [], 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($stats['by_department'] ?? [], 'count')); ?>,
                    backgroundColor: [
                        '#0066cc',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
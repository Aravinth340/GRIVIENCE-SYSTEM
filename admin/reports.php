<?php
require_once '../config.php';
require_once '../functions.php';

if (!isAdminLoggedIn()) {
    redirectToLogin();
}

$stats = getDashboardStats();
$monthlyStats = getMonthlyGrievanceStats(12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="reports.php" class="active"><i class="fas fa-file-pdf"></i> Reports</a></li>
                <li><a href="activity-logs.php"><i class="fas fa-history"></i> Activity Logs</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-file-pdf"></i> Reports</h1>
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>

            <!-- Summary Statistics -->
            <div class="dashboard-grid" style="margin-bottom: 2rem;">
                <div class="dashboard-card">
                    <h3>Total Grievances</h3>
                    <div class="number"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="dashboard-card" style="border-top-color: #ffc107;">
                    <h3>Pending</h3>
                    <div class="number" style="color: #ffc107;"><?php echo $stats['pending'] ?? 0; ?></div>
                </div>
                <div class="dashboard-card" style="border-top-color: #28a745;">
                    <h3>Resolved</h3>
                    <div class="number" style="color: #28a745;"><?php echo $stats['resolved'] ?? 0; ?></div>
                </div>
                <div class="dashboard-card">
                    <h3>Avg Rating</h3>
                    <div class="number"><?php echo $stats['avg_rating'] ?? 0; ?>/5</div>
                </div>
            </div>

            <!-- Charts -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1rem;">12-Month Trend</h3>
                    <canvas id="yearlyChart"></canvas>
                </div>

                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 1rem;">Status Distribution</h3>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Category Report -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1rem;">Category-wise Distribution</h3>
                <table style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 0.75rem;">Category</th>
                            <th style="text-align: right; padding: 0.75rem;">Count</th>
                            <th style="text-align: right; padding: 0.75rem;">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalCategories = array_sum(array_column($stats['by_category'] ?? [], 'count'));
                        foreach ($stats['by_category'] ?? [] as $cat): 
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem;"><?php echo $cat['category_name']; ?></td>
                                <td style="text-align: right; padding: 0.75rem;"><?php echo $cat['count']; ?></td>
                                <td style="text-align: right; padding: 0.75rem;">
                                    <?php echo round(($cat['count'] / $totalCategories) * 100, 2); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Department Report -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h3 style="margin-bottom: 1rem;">Department-wise Distribution</h3>
                <table style="width: 100%;">
                    <thead>
                        <tr style="border-bottom: 2px solid #ddd;">
                            <th style="text-align: left; padding: 0.75rem;">Department</th>
                            <th style="text-align: right; padding: 0.75rem;">Count</th>
                            <th style="text-align: right; padding: 0.75rem;">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDepts = array_sum(array_column($stats['by_department'] ?? [], 'count'));
                        foreach ($stats['by_department'] ?? [] as $dept): 
                        ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem;"><?php echo $dept['name']; ?></td>
                                <td style="text-align: right; padding: 0.75rem;"><?php echo $dept['count']; ?></td>
                                <td style="text-align: right; padding: 0.75rem;">
                                    <?php echo round(($dept['count'] / $totalDepts) * 100, 2); ?>%
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Yearly Chart
        const yearlyCtx = document.getElementById('yearlyChart').getContext('2d');
        new Chart(yearlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($item) { 
                    return date('M Y', strtotime($item['month'] . '-01')); 
                }, $monthlyStats)); ?>,
                datasets: [{
                    label: 'Grievances',
                    data: <?php echo json_encode(array_column($monthlyStats, 'count')); ?>,
                    backgroundColor: '#0066cc',
                    borderColor: '#0052a3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Pending', 'In Review', 'Resolved', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending'] ?? 0; ?>,
                        <?php echo $stats['in_review'] ?? 0; ?>,
                        <?php echo $stats['resolved'] ?? 0; ?>,
                        <?php echo $stats['rejected'] ?? 0; ?>
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>
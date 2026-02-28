<?php
require_once 'config.php';
require_once 'functions.php';

$grievance = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grievanceId = sanitizeInput($_POST['grievance_id'] ?? '');
    $registerNumber = sanitizeInput($_POST['register_number'] ?? '');

    if (empty($grievanceId) || empty($registerNumber)) {
        $error = 'Please enter both Grievance ID and Register Number';
    } else {
        $grievance = getGrievanceByIdAndRegister($grievanceId, $registerNumber);
        if (!$grievance) {
            $error = 'Grievance not found. Please check your details.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Grievance - Chendhuran Polytechnic</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-building"></i>
                <span>Chendhuran Polytechnic</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html">Home</a></li>
                <li><a href="grievance.php">Submit Grievance</a></li>
                <li><a href="track.php" class="active">Track Grievance</a></li>
                <li><a href="login.php" class="btn btn-login">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="track-container">
            <h1><i class="fas fa-search"></i> Track Your Grievance</h1>
            <p class="form-subtitle">Enter your details to track your grievance</p>

            <form method="POST" class="track-form">
                <div class="form-group">
                    <label for="grievance_id">Grievance ID *</label>
                    <input type="text" id="grievance_id" name="grievance_id" placeholder="e.g., GRV-20260216-XXXX" required>
                </div>

                <div class="form-group">
                    <label for="register_number">Register Number *</label>
                    <input type="text" id="register_number" name="register_number" placeholder="e.g., 21CE001" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Track Now
                </button>
            </form>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($grievance): ?>
            <div class="grievance-details">
                <div class="detail-header">
                    <h2>Grievance Details</h2>
                    <span class="status-badge status-<?php echo $grievance['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?>
                    </span>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Grievance ID</label>
                        <p><?php echo $grievance['grievance_id']; ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Status</label>
                        <p><?php echo ucfirst(str_replace('_', ' ', $grievance['status'])); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Submitted Date</label>
                        <p><?php echo date('d M Y, H:i', strtotime($grievance['created_at'])); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Category</label>
                        <p><?php echo $grievance['category_name']; ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Department</label>
                        <p><?php echo $grievance['department_name']; ?></p>
                    </div>
                    <?php if ($grievance['assigned_to']): ?>
                    <div class="detail-item">
                        <label>Assigned To</label>
                        <p><?php echo $grievance['staff_name']; ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="detail-section">
                    <h3>Description</h3>
                    <p><?php echo nl2br($grievance['description']); ?></p>
                </div>

                <?php if ($grievance['resolution_remarks']): ?>
                <div class="detail-section">
                    <h3>Resolution Remarks</h3>
                    <p><?php echo nl2br($grievance['resolution_remarks']); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($grievance['file_path'] && file_exists($grievance['file_path'])): ?>
                <div class="detail-section">
                    <h3>Supporting Document</h3>
                    <a href="<?php echo $grievance['file_path']; ?>" download class="btn btn-secondary btn-sm">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
                <?php endif; ?>

                <!-- Status Timeline -->
                <div class="timeline">
                    <div class="timeline-item <?php echo $grievance['status'] !== 'pending' ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Submitted</h4>
                            <p><?php echo date('d M Y, H:i', strtotime($grievance['created_at'])); ?></p>
                        </div>
                    </div>

                    <div class="timeline-item <?php echo in_array($grievance['status'], ['in_review', 'escalated', 'resolved']) ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Under Review</h4>
                            <?php if ($grievance['assigned_to']): ?>
                                <p>Assigned to <?php echo $grievance['staff_name']; ?></p>
                            <?php else: ?>
                                <p>Awaiting assignment</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="timeline-item <?php echo in_array($grievance['status'], ['resolved', 'closed']) ? 'completed' : ''; ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>Resolved</h4>
                            <?php if ($grievance['resolution_date']): ?>
                                <p><?php echo date('d M Y, H:i', strtotime($grievance['resolution_date'])); ?></p>
                            <?php else: ?>
                                <p>Pending resolution</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($grievance['status'] === 'resolved' && !$grievance['rating']): ?>
                <div class="rating-section">
                    <h3>How was your experience?</h3>
                    <p>Please rate the resolution of your grievance</p>
                    <div class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star" data-rating="<?php echo $i; ?>">
                                <i class="fas fa-star"></i>
                            </span>
                        <?php endfor; ?>
                    </div>
                    <button class="btn btn-primary" id="submit-rating" style="display:none;">Submit Rating</button>
                </div>
                <?php elseif ($grievance['rating']): ?>
                <div class="rating-section">
                    <h3>Your Rating</h3>
                    <div class="star-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $grievance['rating'] ? 'filled' : ''; ?>"></i>
                        <?php endfor; ?>
                        <span>(<?php echo $grievance['rating']; ?>/5)</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 Chendhuran Polytechnic College. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/script.js"></script>
    <script>
        // Star rating functionality
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                document.querySelectorAll('.star').forEach(s => {
                    s.classList.toggle('filled', s.dataset.rating <= rating);
                });
                document.getElementById('submit-rating').style.display = 'block';
            });
        });

        document.getElementById('submit-rating')?.addEventListener('click', function() {
            const rating = document.querySelectorAll('.star.filled').length;
            // TODO: Implement AJAX to submit rating
            console.log('Submitted rating:', rating);
        });
    </script>
</body>
</html>
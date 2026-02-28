<?php
require_once 'config.php';
require_once 'functions.php';

$departments = getAllDepartments();
$categories = getAllCategories();
$csrf_token = generateCSRFToken();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token mismatch';
    } else {
        $studentName = sanitizeInput($_POST['student_name'] ?? '');
        $registerNumber = sanitizeInput($_POST['register_number'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $departmentId = intval($_POST['department_id'] ?? 0);
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $anonymous = isset($_POST['anonymous']) ? 1 : 0;

        // Validation
        if (empty($studentName) || empty($registerNumber) || empty($departmentId) || empty($categoryId) || empty($description)) {
            $error = 'All required fields must be filled';
        } elseif (strlen($description) < 20) {
            $error = 'Description must be at least 20 characters';
        } else {
            // Handle file upload
            $fileResult = ['file_path' => null, 'file_name' => null];
            if (isset($_FILES['proof_document']) && $_FILES['proof_document']['size'] > 0) {
                $fileResult = uploadGrievanceFile($_FILES['proof_document']);
                if (!$fileResult['success']) {
                    $error = $fileResult['message'];
                }
            }

            if (empty($error)) {
                $grievanceData = [
                    'student_name' => $studentName,
                    'register_number' => $registerNumber,
                    'email' => $email,
                    'phone' => $phone,
                    'department_id' => $departmentId,
                    'category_id' => $categoryId,
                    'description' => $description,
                    'file_path' => $fileResult['file_path'],
                    'file_name' => $fileResult['file_name'],
                    'anonymous' => $anonymous
                ];

                $result = submitGrievance($grievanceData);
                if ($result['success']) {
                    $success = 'Grievance submitted successfully! Your Grievance ID: ' . $result['grievance_id'];
                    sendGrievanceSubmittedEmail($email, $studentName, $result['grievance_id']);
                } else {
                    $error = $result['message'];
                }
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
    <title>Submit Grievance - Chendhuran Polytechnic</title>
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
                <li><a href="grievance.php" class="active">Submit Grievance</a></li>
                <li><a href="track.php">Track Grievance</a></li>
                <li><a href="login.php" class="btn btn-login">Login</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h1><i class="fas fa-file-alt"></i> Submit a Grievance</h1>
            <p class="form-subtitle">Help us improve by reporting issues and concerns</p>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <br><small>You can track your grievance using your Register Number and Grievance ID</small>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="grievance-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="form-section">
                    <h3>Personal Information</h3>

                    <div class="form-group">
                        <label for="student_name">Full Name *</label>
                        <input type="text" id="student_name" name="student_name" placeholder="Enter your full name" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="register_number">Register Number *</label>
                            <input type="text" id="register_number" name="register_number" placeholder="e.g., 21CE001" required>
                        </div>
                        <div class="form-group">
                            <label for="department_id">Department *</label>
                            <select id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" placeholder="your.email@example.com" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="10-digit mobile number">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Grievance Details</h3>

                    <div class="form-group">
                        <label for="category_id">Grievance Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" placeholder="Describe your grievance in detail (minimum 20 characters)" rows="6" required></textarea>
                        <small id="char-count">0 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="proof_document">Supporting Document (Optional)</label>
                        <div class="file-upload">
                            <input type="file" id="proof_document" name="proof_document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <label for="proof_document" class="file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                Click to upload or drag file here
                                <small>PDF, JPG, PNG, DOC, DOCX (Max 5MB)</small>
                            </label>
                        </div>
                        <div id="file-info"></div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="anonymous" id="anonymous">
                            <span>Submit this grievance anonymously</span>
                        </label>
                        <p class="checkbox-help">If checked, your name won't be displayed in reports</p>
                    </div>

                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="terms" id="terms" required>
                            <span>I agree that the information provided is true and correct *</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Clear Form
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Grievance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 Chendhuran Polytechnic College. All rights reserved.</p>
        </div>
    </footer>

    <script src="assets/script.js"></script>
    <script>
        // Character counter
        document.getElementById('description').addEventListener('input', function() {
            document.getElementById('char-count').textContent = this.value.length + ' characters';
        });

        // File upload handling
        const fileInput = document.getElementById('proof_document');
        const fileLabel = document.querySelector('.file-label');
        const fileInfo = document.getElementById('file-info');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.style.borderColor = '#0066cc';
                fileLabel.style.backgroundColor = '#f0f7ff';
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.style.borderColor = '#ddd';
                fileLabel.style.backgroundColor = '#f9f9f9';
            });
        });

        fileLabel.addEventListener('drop', (e) => {
            fileInput.files = e.dataTransfer.files;
            updateFileInfo();
        });

        fileInput.addEventListener('change', updateFileInfo);

        function updateFileInfo() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                fileInfo.innerHTML = `<small style="color: green;"><i class="fas fa-check-circle"></i> ${file.name} (${sizeMB} MB)</small>`;
            }
        }
    </script>
</body>
</html>
<?php
require_once 'config.php';
require_once 'functions.php';

$error = '';
$tab = 'admin'; // Default tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = $_POST['tab'] ?? 'admin';
    $csrf_ok = verifyCSRFToken($_POST['csrf_token'] ?? '');

    if (!$csrf_ok) {
        $error = 'Security token mismatch';
    } else {
        if ($tab === 'admin') {
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = 'Username and password are required';
            } elseif (adminLogin($username, $password)) {
                redirectTo('admin/dashboard.php');
            } else {
                $error = 'Invalid username or password';
            }
        } elseif ($tab === 'staff') {
            $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Email and password are required';
            } elseif (staffLogin($email, $password)) {
                redirectTo('staff/dashboard.php');
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chendhuran Polytechnic</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-lock"></i>
                <h1>Secure Login</h1>
                <p>Chendhuran Polytechnic College</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <div class="login-tabs">
                <button class="login-tab active" onclick="switchTab('admin')">
                    <i class="fas fa-user-tie"></i> Admin
                </button>
                <button class="login-tab" onclick="switchTab('staff')">
                    <i class="fas fa-chalkboard-user"></i> Staff
                </button>
            </div>

            <!-- Admin Login -->
            <form id="admin-form" method="POST" class="login-form active">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="tab" value="admin">

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                        <span class="input-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <!-- Staff Login -->
            <form id="staff-form" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="tab" value="staff">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        <input type="email" id="email" name="email" placeholder="Enter email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="staff-password">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" id="staff-password" name="password" placeholder="Enter password" required>
                        <span class="input-toggle" onclick="togglePassword('staff-password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-help">
                <a href="index.html"><i class="fas fa-home"></i> Back to Home</a>
            </div>

            <div class="login-info">
                <p><strong>Demo Credentials:</strong></p>
                <p><em>Admin:</em> username: <code>admin</code> | password: <code>admin123</code></p>
                <p><em>Staff:</em> email: <code>ramesh.ce@chendhuran.edu</code> | password: <code>admin123</code></p>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.login-tab').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.login-form').forEach(el => el.classList.remove('active'));

            event.target.closest('.login-tab').classList.add('active');
            document.getElementById(tab + '-form').classList.add('active');
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = event.target.closest('.input-toggle');
            if (input.type === 'password') {
                input.type = 'text';
                toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                toggle.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }
    </script>
</body>
</html>
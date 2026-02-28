<?php
/**
 * Configuration File
 * Grievance Redressal System - Chendhuran Polytechnic College
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'grievance_system');
define('DB_PORT', 3306);

// Application Settings
define('APP_NAME', 'Grievance Redressal System');
define('APP_URL', 'http://localhost/grievance-system');
define('COLLEGE_NAME', 'Chendhuran Polytechnic College');
define('COLLEGE_EMAIL', 'grievance@chendhuran.edu');
define('COLLEGE_PHONE', '+91-XXXXXX-XXXX');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']);

// Security Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('PASSWORD_RESET_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// Email Configuration (Optional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
define('SMTP_FROM_EMAIL', 'grievance@chendhuran.edu');
define('SMTP_FROM_NAME', COLLEGE_NAME);
define('ENABLE_EMAIL', false);

// SMS Configuration (Optional)
define('ENABLE_SMS', false);
define('SMS_API_KEY', 'your-sms-api-key');

// Security Headers
define('ENABLE_CSRF', true);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Auto Escalation
define('AUTO_ESCALATE_DAYS', 7);

// Enable/Disable Features
define('ENABLE_ANONYMOUS_GRIEVANCE', true);
define('ENABLE_FILE_UPLOAD', true);
define('ENABLE_RATING_SYSTEM', true);
define('ENABLE_ACTIVITY_LOG', true);

// Error Reporting
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session Configuration
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Set to 1 for HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create upload directory if not exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Database Connection using PDO
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    if (APP_DEBUG) {
        die("Database Connection Error: " . $e->getMessage());
    } else {
        die("Database Connection Error. Please try again later.");
    }
}

// Helper Functions
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
}

function logActivity($userType, $userId, $action, $description = null, $grievanceId = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_type, user_id, action, description, grievance_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userType,
            $userId,
            $action,
            $description,
            $grievanceId,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        if (APP_DEBUG) {
            error_log($e->getMessage());
        }
    }
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function generateGrievanceID() {
    return 'GRV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
}

function redirectToLogin() {
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

function redirectTo($page) {
    header('Location: ' . APP_URL . '/' . $page);
    exit;
}

?>
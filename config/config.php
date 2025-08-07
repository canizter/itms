<?php
// Database configuration for IT Management System
// Make sure to update these settings according to your XAMPP setup

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // Default XAMPP MySQL password is empty
define('DB_NAME', 'itms_db');

// Application settings
define('APP_NAME', 'IT Management System');
define('APP_VERSION', '1.5');
define('BASE_URL', 'http://localhost/itms/');

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// File upload settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('UPLOAD_PATH', 'uploads/');

// Pagination settings
define('RECORDS_PER_PAGE', 20);

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');

// Security settings
define('HASH_ALGO', PASSWORD_DEFAULT);
define('CSRF_TOKEN_NAME', '_token');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection function
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Security functions
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    $user_role = $_SESSION['role'] ?? 'user';
    $roles = ['user' => 1, 'manager' => 2, 'admin' => 3];
    return ($roles[$user_role] ?? 0) >= ($roles[$required_role] ?? 0);
}

// Returns true if the current user is an admin
function isAdmin() {
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

// Utility functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatDate($date, $format = DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

function redirectTo($url) {
    header("Location: $url");
    exit();
}

// Auto-logout function
function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_destroy();
            redirectTo('login.php?timeout=1');
        }
    }
    $_SESSION['last_activity'] = time();
}
?>

<?php
session_start();
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'employee_management');

// OTP Configuration (NEW)
define('OTP_EXPIRY_MINUTES', 10); // OTP validity in minutes
define('OTP_LENGTH', 6); // 6-digit OTP
define('DEBUG_MODE', true); // Enable console logging for development
define('RESET_TOKEN_EXPIRY_HOURS', 1); // Reset token expiry

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is employee
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../employee/dashboard.php");
        exit();
    }
}

// Redirect if not employee
function requireEmployee() {
    requireLogin();
    if (!isEmployee()) {
        header("Location: ../admin/dashboard.php");
        exit();
    }
}

// Sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Password verification
function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}
?>
<?php
// session_check.php - Include this at the top of all admin pages

session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Session timeout (30 minutes)
$inactive = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive)) {
    // Last request was more than 30 minutes ago
    session_unset();
    session_destroy();
    header("Location: login.php?logout=timeout");
    exit();
}
$_SESSION['last_activity'] = time(); // Update last activity time

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Optional: Verify user still exists in database
require_once 'config.php';
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role IN ('admin', 'teacher')");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // User no longer exists or lost privileges
    session_unset();
    session_destroy();
    header("Location: login.php?logout=privileges");
    exit();
}
?>
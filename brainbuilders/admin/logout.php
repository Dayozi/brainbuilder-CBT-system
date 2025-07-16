<?php
// logout.php - Admin Panel Logout Script

// Start session with secure settings
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => isset($_SERVER['HTTPS']), // Requires HTTPS if available
    'cookie_httponly' => true,    // Prevents JavaScript access
    'cookie_samesite' => 'Strict', // Prevents CSRF attacks
    'use_strict_mode' => true     // Prevents session fixation
]);

// Verify the request is coming from your application
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$base_url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

// Only process logout if coming from your application
if (strpos($referer, $base_url) === 0 || php_sapi_name() === 'cli') {
    // Unset all session variables
    $_SESSION = [];

    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Completely destroy the session
    session_destroy();

    // Prevent caching of the page
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Determine correct login page path
    $login_path = '/brainbuilders/admin/login.php'; // Adjust this path to match your structure
    
    // Redirect to login page with success message
    header("Location: $login_path?logout=success");
    exit();
} else {
    // If not coming from your app, show 403 error
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}
?>
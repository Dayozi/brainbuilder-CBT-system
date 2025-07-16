<?php
require_once '../includes/config.php';

// SECURITY WARNING: Remove this file after use!
$username = 'admin';
$new_password = 'your_secure_password';

$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $username);

if ($stmt->execute()) {
    echo "Password reset successfully for $username<br>";
    echo "New hash: $hashed_password";
} else {
    echo "Error: " . $conn->error;
}
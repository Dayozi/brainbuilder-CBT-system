<?php
session_start();
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Show logout message if present
if (isset($_GET['logout'])) {
    $messages = [
        'success' => 'You have been successfully logged out.',
        'timeout' => 'Your session has expired. Please login again.'
    ];
    if (isset($messages[$_GET['logout']])) {
        $logout_message = $messages[$_GET['logout']];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Prevent brute force by adding delay
    sleep(1);
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role IN ('admin', 'teacher')");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            header("Location: dashboard.php");
            exit();
        }
    }
    
    $error = "Invalid credentials or insufficient privileges";
}
?>

<?php include '../includes/header.php'; ?>

<div class="auth-container">
    <h2>Admin Portal Login</h2>
    
    <?php if (isset($logout_message)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($logout_message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" autocomplete="off">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
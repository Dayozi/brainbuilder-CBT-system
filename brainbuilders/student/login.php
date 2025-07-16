<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            header("Location: ../student/dashboard.php");
            exit();
        }
    }
    
    $error = "Invalid username or password";
}
?>

<?php include '../includes/header.php'; ?>

<div class="login-container">
    <h2>Student Login</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Login</button>
    </form>
    
    <div class="login-links">
        <a href="forgot-password.php">Forgot Password?</a>
        <span>|</span>
        <a href="register.php">Register New Account</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
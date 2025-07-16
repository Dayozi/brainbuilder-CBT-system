<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $class_id = intval($_POST['class_id']);
    
    // Check if username exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows > 0) {
        $error = "Username or email already exists";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, class_id) VALUES (?, ?, ?, 'student', ?)");
        $stmt->bind_param("sssi", $username, $email, $password, $class_id);
        
        if ($stmt->execute()) {
            $_SESSION['registration_success'] = true;
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Get classes for dropdown
$classes = $conn->query("SELECT id, name, section FROM classes ORDER BY section, name");
?>

<?php include '../includes/header.php'; ?>

<div class="register-container">
    <h2>Student Registration</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="class_id">Class</label>
            <select id="class_id" name="class_id" required>
                <option value="">Select Class</option>
                <?php while ($class = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $class['id']; ?>">
                        <?php echo $class['section'] . ' - ' . $class['name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
    
    <div class="register-links">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
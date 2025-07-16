<div class="sidebar">
    <div class="admin-profile">
        <h3><?php echo $_SESSION['admin_username']; ?></h3>
        <p><?php echo ucfirst($_SESSION['admin_role']); ?></p>
    </div>
    
    <nav>
        <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="manage_classes.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_classes.php' ? 'active' : '' ?>">Manage Classes</a>
        <a href="manage_exams.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_exams.php' ? 'active' : '' ?>">Manage Exams</a>
        <a href="manage_questions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_questions.php' ? 'active' : '' ?>">Manage Questions</a>
        <a href="results.php" class="<?= basename($_SERVER['PHP_SELF']) == 'results.php' ? 'active' : '' ?>">View Results</a>
        <a href="logout.php">Logout</a>
    </nav>
</div>
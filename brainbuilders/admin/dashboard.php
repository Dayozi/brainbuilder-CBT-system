<?php
// Start session with secure settings and check authentication
require_once '../includes/session_check.php';

// Initialize all variables with default values
$students = ['count' => 0];
$exams = ['count' => 0];
$classes = ['count' => 0];
$teachers = ['count' => 0];
$activities = [];
$error = null;

// Get stats with comprehensive error handling
try {
    // Get student count
    $studentResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if ($studentResult) {
        $students = $studentResult->fetch_assoc();
    } else {
        throw new Exception("Error fetching student count");
    }

    // Get active exams count
    $examResult = $conn->query("
        SELECT COUNT(*) as count FROM exams 
        WHERE available_from <= NOW() AND available_to >= NOW()
    ");
    if ($examResult) {
        $exams = $examResult->fetch_assoc();
    } else {
        throw new Exception("Error fetching active exams");
    }

    // Get classes count
    $classResult = $conn->query("SELECT COUNT(*) as count FROM classes");
    if ($classResult) {
        $classes = $classResult->fetch_assoc();
    } else {
        throw new Exception("Error fetching classes");
    }

    // Get teachers count
    $teacherResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
    if ($teacherResult) {
        $teachers = $teacherResult->fetch_assoc();
    } else {
        throw new Exception("Error fetching teachers");
    }

    // Get recent activities (last 5) - wrapped in try-catch as table might not exist
    try {
        $activityResult = $conn->query("
            SELECT a.*, u.username as user_name 
            FROM activities a
            LEFT JOIN users u ON a.user_id = u.id
            ORDER BY a.created_at DESC 
            LIMIT 5
        ");
        
        if ($activityResult) {
            $activities = $activityResult->fetch_all(MYSQLI_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Activities table might not exist: " . $e->getMessage());
        $activities = [];
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Unable to load some dashboard data. Showing partial information.";
}

// Helper function to display time ago
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "Just now";
    if ($diff < 3600) return floor($diff/60) . " mins ago";
    if ($diff < 86400) return floor($diff/3600) . " hours ago";
    if ($diff < 2592000) return floor($diff/86400) . " days ago";
    return date('M j, Y', $time);
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Admin Dashboard</h1>
            <small>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></small>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card bg-primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Students</h3>
                    <p><?php echo htmlspecialchars($students['count']); ?></p>
                </div>
            </div>
            
            <div class="stat-card bg-success">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Exams</h3>
                    <p><?php echo htmlspecialchars($exams['count']); ?></p>
                </div>
            </div>
            
            <div class="stat-card bg-info">
                <div class="stat-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-info">
                    <h3>Classes</h3>
                    <p><?php echo htmlspecialchars($classes['count']); ?></p>
                </div>
            </div>
            
            <div class="stat-card bg-warning">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3>Teachers</h3>
                    <p><?php echo htmlspecialchars($teachers['count']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-sections">
            <section class="recent-activity">
                <div class="section-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                    <?php if (!empty($activities)): ?>
                        <a href="activities.php" class="btn btn-sm">View All</a>
                    <?php endif; ?>
                </div>
                <div class="activity-list">
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php switch($activity['type']) {
                                    case 'exam': echo '<i class="fas fa-book"></i>'; break;
                                    case 'user': echo '<i class="fas fa-user"></i>'; break;
                                    case 'class': echo '<i class="fas fa-chalkboard"></i>'; break;
                                    default: echo '<i class="fas fa-info-circle"></i>';
                                } ?>
                            </div>
                            <div class="activity-details">
                                <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                <small>
                                    <?php 
                                    echo htmlspecialchars($activity['user_name'] ?? 'System'); 
                                    echo ' • ' . time_ago($activity['created_at']);
                                    ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-info-circle"></i>
                            <p>No recent activities found</p>
                            <small>Activities will appear here when users perform actions in the system</small>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            
            <section class="quick-actions">
                <div class="section-header">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                </div>
                <div class="action-buttons">
                    <a href="create_exam.php" class="action-btn">
                        <i class="fas fa-plus"></i>
                        <span>Create Exam</span>
                    </a>
                    <a href="add_question.php" class="action-btn">
                        <i class="fas fa-question"></i>
                        <span>Add Question</span>
                    </a>
                    <a href="register_user.php" class="action-btn">
                        <i class="fas fa-user-plus"></i>
                        <span>Register User</span>
                    </a>
                    <a href="manage_classes.php" class="action-btn">
                        <i class="fas fa-chalkboard"></i>
                        <span>Manage Classes</span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
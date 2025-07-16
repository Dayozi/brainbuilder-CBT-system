<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) {
    header("Location: login.php");
    exit();
}

// Get student information
$stmt = $conn->prepare("SELECT u.*, c.name as class_name, c.section 
                       FROM users u 
                       JOIN classes c ON u.class_id = c.id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get upcoming exams
$exams = $conn->query("SELECT e.*, s.name as subject_name 
                      FROM exams e 
                      JOIN subjects s ON e.subject_id = s.id 
                      WHERE e.class_id = {$student['class_id']} 
                      AND e.available_from <= NOW() 
                      AND e.available_to >= NOW()");
?>

<?php include '../includes/header.php'; ?>

<div class="dashboard-container">
    <div class="sidebar">
        <div class="profile">
            <h3><?php echo $student['username']; ?></h3>
            <p><?php echo $student['section'] . ' - ' . $student['class_name']; ?></p>
        </div>
        
        <nav>
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="exams.php">Available Exams</a>
            <a href="results.php">Exam Results</a>
            <a href="profile.php">My Profile</a>
            <a href="../logout.php">Logout</a>
        </nav>
    </div>
    
    <div class="main-content">
        <h1>Welcome, <?php echo $student['username']; ?></h1>
        
        <div class="dashboard-cards">
            <div class="card">
                <h3>Upcoming Exams</h3>
                <p><?php echo $exams->num_rows; ?></p>
            </div>
            
            <div class="card">
                <h3>Completed Exams</h3>
                <p>0</p>
            </div>
        </div>
        
        <section class="upcoming-exams">
            <h2>Available Exams</h2>
            
            <?php if ($exams->num_rows > 0): ?>
                <div class="exam-list">
                    <?php while ($exam = $exams->fetch_assoc()): ?>
                        <div class="exam-card">
                            <h3><?php echo $exam['subject_name']; ?></h3>
                            <p><?php echo $exam['title']; ?></p>
                            <p>Duration: <?php echo $exam['duration']; ?> minutes</p>
                            <p>Questions: <?php echo $exam['total_questions']; ?></p>
                            <a href="take-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary">Start Exam</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No exams available at this time.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
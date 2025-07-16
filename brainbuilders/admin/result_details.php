<?php
session_start();
require_once '../includes/config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get and validate result ID
$result_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

include '../includes/header.php';
?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <?php
        if ($result_id === 0) {
            echo '<div class="alert alert-danger">Invalid result ID provided</div>';
            include '../includes/footer.php';
            exit();
        }

        // Fetch result details using prepared statement
        $stmt = $conn->prepare("
            SELECT er.*, e.title as exam_title, e.pass_mark,
                   s.name as subject_name, u.username as student_name,
                   u.email as student_email, c.name as class_name
            FROM exam_results er
            JOIN exams e ON er.exam_id = e.id
            JOIN subjects s ON e.subject_id = s.id
            JOIN users u ON er.student_id = u.id
            JOIN classes c ON u.class_id = c.id
            WHERE er.id = ?
        ");
        
        if (!$stmt) {
            echo '<div class="alert alert-danger">Database error: '.$conn->error.'</div>';
            include '../includes/footer.php';
            exit();
        }
        
        $stmt->bind_param("i", $result_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Check if result exists
        if (!$result) {
            echo '<div class="alert alert-danger">Result not found. It may have been deleted or the ID is incorrect.</div>
                  <div class="actions">
                      <a href="results.php" class="btn">Back to Results</a>
                  </div>';
            include '../includes/footer.php';
            exit();
        }

        // Fetch questions with answers using prepared statement
        $stmt = $conn->prepare("
            SELECT q.*, ea.answer as student_answer
            FROM questions q
            LEFT JOIN exam_answers ea ON q.id = ea.question_id AND ea.exam_result_id = ?
            WHERE q.exam_id = ?
        ");
        
        if (!$stmt) {
            echo '<div class="alert alert-danger">Database error: '.$conn->error.'</div>';
            include '../includes/footer.php';
            exit();
        }
        
        $stmt->bind_param("ii", $result_id, $result['exam_id']);
        $stmt->execute();
        $questions = $stmt->get_result();
        
        if ($questions === false) {
            echo '<div class="alert alert-danger">Error loading questions: '.$conn->error.'</div>';
            $stmt->close();
            include '../includes/footer.php';
            exit();
        }
        ?>
        
        <h1>Exam Result Details</h1>
        
        <div class="result-header">
            <h2><?= htmlspecialchars($result['exam_title']) ?></h2>
            <p><strong>Student:</strong> <?= htmlspecialchars($result['student_name']) ?> (<?= htmlspecialchars($result['class_name']) ?>)</p>
            <p><strong>Submitted:</strong> <?= date('M j, Y g:i A', strtotime($result['submitted_at'])) ?></p>
            <div class="score <?= $result['percentage'] >= $result['pass_mark'] ? 'pass' : 'fail' ?>">
                Score: <?= $result['score'] ?>/<?= $result['total_questions'] ?> 
                (<?= $result['percentage'] ?>%)
            </div>
        </div>
        
        <div class="email-form">
            <form method="POST" action="send_result_email.php">
                <input type="hidden" name="result_id" value="<?= $result['id'] ?>">
                <div class="form-group">
                    <label>Send to Email:</label>
                    <input type="email" name="email" value="<?= $result['student_email'] ?>" placeholder="student@example.com">
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-envelope"></i> Email Results
                </button>
            </form>
        </div>
        
        <div class="question-review">
            <?php if ($questions->num_rows === 0): ?>
                <div class="alert alert-warning">No questions found for this exam</div>
            <?php else: ?>
                <?php $qnum = 1; ?>
                <?php while ($question = $questions->fetch_assoc()): ?>
                    <!-- Question display code remains the same -->
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="results.php" class="btn">Back to Results</a>
            <button onclick="window.print()" class="btn">Print Report</button>
            <a href="generate_pdf.php?id=<?= htmlspecialchars($result['id']) ?>" class="btn">
                <i class="fas fa-file-pdf"></i> Download PDF
            </a>
        </div>
    </div>
</div>

<?php 
$stmt->close();
include '../includes/footer.php'; 
?>
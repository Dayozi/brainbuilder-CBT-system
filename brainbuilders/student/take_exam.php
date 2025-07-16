<?php
session_start();
require_once '../includes/config.php';

// Redirect if not student
if ($_SESSION['role'] !== 'student') {
    header("Location: ../admin/login.php");
    exit();
}

// Get exam ID
$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch exam details
$exam = $conn->query("
    SELECT e.*, s.name as subject_name 
    FROM exams e 
    JOIN subjects s ON e.subject_id = s.id 
    WHERE e.id = $exam_id
")->fetch_assoc();

// Check if exam exists and is available
if (!$exam || strtotime($exam['available_to']) < time()) {
    die("Exam not available");
}

// Check if already attempted
$attempt = $conn->query("
    SELECT id FROM exam_results 
    WHERE exam_id = $exam_id AND student_id = {$_SESSION['user_id']}
")->num_rows;

if ($attempt > 0) {
    die("You've already taken this exam");
}

// Fetch questions (randomized)
$questions = $conn->query("
    SELECT * FROM questions 
    WHERE exam_id = $exam_id 
    ORDER BY RAND() 
    LIMIT {$exam['total_questions']}
");
?>

<?php include '../includes/header.php'; ?>

<div class="exam-container">
    <div class="exam-header">
        <h1><?= $exam['title'] ?></h1>
        <div class="exam-timer" id="examTimer" 
             data-duration="<?= $exam['duration'] * 60 ?>">
            Time Left: <?= $exam['duration'] ?>:00
        </div>
    </div>

    <form id="examForm" method="POST" action="submit_exam.php">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        
        <div class="question-navigation">
            <?php $qnum = 1; ?>
            <?php while ($question = $questions->fetch_assoc()): ?>
                <button type="button" class="nav-btn" 
                        onclick="showQuestion(<?= $qnum-1 ?>)">
                    <?= $qnum ?>
                </button>
                <?php $qnum++; ?>
            <?php endwhile; ?>
        </div>

        <div class="questions-wrapper">
            <?php 
            $questions->data_seek(0); // Reset pointer
            $qnum = 1; 
            ?>
            <?php while ($question = $questions->fetch_assoc()): ?>
                <div class="question-box" id="question-<?= $qnum ?>">
                    <h3>Question <?= $qnum ?></h3>
                    <p><?= nl2br($question['question_text']) ?></p>
                    
                    <input type="hidden" name="question_ids[]" 
                           value="<?= $question['id'] ?>">
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <?php $options = json_decode($question['options'], true); ?>
                        <?php foreach ($options as $key => $value): ?>
                            <label class="option-label">
                                <input type="radio" 
                                       name="answers[<?= $question['id'] ?>]" 
                                       value="<?= $key ?>">
                                <?= $key ?>. <?= $value ?>
                            </label>
                        <?php endforeach; ?>
                    
                    <?php elseif ($question['question_type'] === 'true_false'): ?>
                        <label class="option-label">
                            <input type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   value="True"> True
                        </label>
                        <label class="option-label">
                            <input type="radio" 
                                   name="answers[<?= $question['id'] ?>]" 
                                   value="False"> False
                        </label>
                    
                    <?php else: ?>
                        <input type="text" 
                               name="answers[<?= $question['id'] ?>]" 
                               placeholder="Your answer">
                    <?php endif; ?>
                </div>
                <?php $qnum++; ?>
            <?php endwhile; ?>
        </div>

        <div class="exam-controls">
            <button type="button" id="prevBtn" onclick="prevQuestion()">Previous</button>
            <button type="button" id="nextBtn" onclick="nextQuestion()">Next</button>
            <button type="submit" class="btn-submit">Submit Exam</button>
        </div>
    </form>
</div>

<script src="../assets/js/exam.js"></script>
<?php include '../includes/footer.php'; ?>
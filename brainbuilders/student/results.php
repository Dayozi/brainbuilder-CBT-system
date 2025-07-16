<?php
session_start();
require_once '../includes/config.php';

// Redirect if not student
if ($_SESSION['role'] !== 'student') {
    header("Location: ../admin/login.php");
    exit();
}

$exam_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch result and exam details
$result = $conn->query("
    SELECT er.*, e.title as exam_title, e.pass_mark, 
           s.name as subject_name, c.name as class_name
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects s ON e.subject_id = s.id
    JOIN users u ON er.student_id = u.id
    JOIN classes c ON u.class_id = c.id
    WHERE er.exam_id = $exam_id 
    AND er.student_id = {$_SESSION['user_id']}
")->fetch_assoc();

// Fetch questions with student answers
$questions = $conn->query("
    SELECT q.*, a.answer as student_answer
    FROM questions q
    LEFT JOIN (
        SELECT question_id, answer 
        FROM exam_answers 
        WHERE exam_result_id = {$result['id']}
    ) a ON q.id = a.question_id
    WHERE q.exam_id = $exam_id
");
?>

<?php include '../includes/header.php'; ?>

<div class="results-container">
    <h1>Exam Results: <?= $result['exam_title'] ?></h1>
    
    <div class="result-summary <?= $result['percentage'] >= $result['pass_mark'] ? 'pass' : 'fail' ?>">
        <div class="score">
            <h2>Your Score</h2>
            <div class="percentage"><?= $result['percentage'] ?>%</div>
            <div class="status">
                <?= $result['percentage'] >= $result['pass_mark'] ? 'PASSED' : 'FAILED' ?>
                (Required: <?= $result['pass_mark'] ?>%)
            </div>
        </div>
        
        <div class="details">
            <p><strong>Subject:</strong> <?= $result['subject_name'] ?></p>
            <p><strong>Class:</strong> <?= $result['class_name'] ?></p>
            <p><strong>Date Taken:</strong> <?= date('M j, Y g:i A', strtotime($result['submitted_at'])) ?></p>
            <p><strong>Questions:</strong> <?= $result['score'] ?> correct out of <?= $result['total_questions'] ?></p>
        </div>
    </div>

    <div class="question-review">
        <h2>Question Review</h2>
        
        <?php $qnum = 1; ?>
        <?php while ($question = $questions->fetch_assoc()): ?>
            <div class="question <?= $question['student_answer'] == $question['correct_answer'] ? 'correct' : 'incorrect' ?>">
                <h3>Question <?= $qnum ?></h3>
                <p><?= nl2br($question['question_text']) ?></p>
                
                <?php if ($question['question_type'] === 'multiple_choice'): ?>
                    <?php $options = json_decode($question['options'], true); ?>
                    <div class="options">
                        <?php foreach ($options as $key => $value): ?>
                            <div class="option <?= $key == $question['correct_answer'] ? 'correct-answer' : '' ?>
                                         <?= $key == $question['student_answer'] ? 'your-answer' : '' ?>">
                                <?= $key ?>. <?= $value ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                
                <?php elseif ($question['question_type'] === 'true_false'): ?>
                    <div class="options">
                        <div class="option <?= 'True' == $question['correct_answer'] ? 'correct-answer' : '' ?>
                                      <?= 'True' == $question['student_answer'] ? 'your-answer' : '' ?>">
                            True
                        </div>
                        <div class="option <?= 'False' == $question['correct_answer'] ? 'correct-answer' : '' ?>
                                      <?= 'False' == $question['student_answer'] ? 'your-answer' : '' ?>">
                            False
                        </div>
                    </div>
                
                <?php else: ?>
                    <div class="answers">
                        <div class="correct-answer">
                            <strong>Correct Answer:</strong> <?= $question['correct_answer'] ?>
                        </div>
                        <?php if (!empty($question['student_answer'])): ?>
                            <div class="your-answer">
                                <strong>Your Answer:</strong> <?= $question['student_answer'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($question['student_answer'] != $question['correct_answer']): ?>
                    <div class="explanation">
                        <strong>Explanation:</strong> 
                        <?= !empty($question['explanation']) ? $question['explanation'] : 'No explanation provided.' ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php $qnum++; ?>
        <?php endwhile; ?>
    </div>
    
    <div class="result-actions">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
        <button onclick="window.print()" class="btn">Print Results</button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
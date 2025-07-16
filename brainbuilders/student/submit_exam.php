<?php
session_start();
require_once '../includes/config.php';

if ($_SESSION['role'] !== 'student' || !isset($_POST['exam_id'])) {
    header("Location: ../index.php");
    exit();
}

$exam_id = intval($_POST['exam_id']);
$student_id = $_SESSION['user_id'];
$answers = $_POST['answers'] ?? [];
$question_ids = $_POST['question_ids'] ?? [];

// Calculate score
$score = 0;
$total_questions = count($question_ids);

foreach ($question_ids as $qid) {
    $qid = intval($qid);
    $question = $conn->query("SELECT correct_answer FROM questions WHERE id = $qid")->fetch_assoc();
    
    if (isset($answers[$qid]) {
        if ($answers[$qid] == $question['correct_answer']) {
            $score++;
        }
    }
}

// Save result
$conn->query("
    INSERT INTO exam_results 
    (student_id, exam_id, score, total_questions, percentage, started_at, submitted_at) 
    VALUES (
        $student_id, 
        $exam_id, 
        $score, 
        $total_questions, 
        " . round(($score/$total_questions)*100, 2) . ",
        NOW(), 
        NOW()
    )
");

// After saving the result, store individual answers
$result_id = $conn->insert_id;

foreach ($question_ids as $qid) {
    $qid = intval($qid);
    $answer = $conn->real_escape_string($answers[$qid] ?? '');
    
    $conn->query("
        INSERT INTO exam_answers 
        (exam_result_id, question_id, answer) 
        VALUES ($result_id, $qid, '$answer')
    ");
}

// Redirect to results
header("Location: results.php?id=$exam_id");
exit();
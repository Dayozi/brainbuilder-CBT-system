<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/email_template.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    die("Access denied");
}

$result_id = intval($_POST['result_id']);
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

// Fetch result data
$result = $conn->query("
    SELECT er.*, e.title as exam_title, e.pass_mark, 
           s.name as subject_name, u.username as student_name, 
           u.email as student_email, c.name as class_name
    FROM exam_results er
    JOIN exams e ON er.exam_id = e.id
    JOIN subjects s ON e.subject_id = s.id
    JOIN users u ON er.student_id = u.id
    JOIN classes c ON u.class_id = c.id
    WHERE er.id = $result_id
")->fetch_assoc();

// Fetch questions
$questions = $conn->query("
    SELECT q.*, ea.answer as student_answer
    FROM questions q
    LEFT JOIN exam_answers ea ON q.id = ea.question_id AND ea.exam_result_id = $result_id
    WHERE q.exam_id = {$result['exam_id']}
")->fetch_all(MYSQLI_ASSOC);

// Generate PDF URL (optional)
$pdf_url = "http://$_SERVER[HTTP_HOST]/brainbuilders/admin/generate_pdf.php?id=$result_id";

// Prepare email
$to = $email ?: $result['student_email'];
$subject = "Exam Results: " . $result['exam_title'];
$message = generate_result_email($result, $questions, $pdf_url);
$headers = [
    'From: BrainBuilders CBT <noreply@brainbuilders.com>',
    'Reply-To: admin@brainbuilders.com',
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=utf-8'
];

// Send email
$mail_sent = mail($to, $subject, $message, implode("\r\n", $headers));

if ($mail_sent) {
    $_SESSION['success'] = "Results emailed successfully to $to";
} else {
    $_SESSION['error'] = "Failed to send email. Check server mail configuration.";
}

header("Location: result_details.php?id=$result_id");
exit();
?>
<?php
// Start secure session
require_once '../includes/session_check.php';

// Verify admin/teacher role
if (!in_array($_SESSION['admin_role'], ['admin', 'teacher'])) {
    die("Access denied. You don't have permission to view this resource.");
}

// Validate and sanitize input
$result_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($result_id <= 0) {
    die("Invalid result ID specified.");
}

// Check if user has permission to view this result
try {
    // For teachers, verify they teach this subject/class
    if ($_SESSION['admin_role'] === 'teacher') {
        $permission_check = $conn->prepare("
            SELECT 1 FROM exam_results er
            JOIN exams e ON er.exam_id = e.id
            JOIN teacher_subjects ts ON e.subject_id = ts.subject_id
            WHERE er.id = ? AND ts.teacher_id = ?
        ");
        $permission_check->bind_param("ii", $result_id, $_SESSION['admin_id']);
        $permission_check->execute();
        
        if (!$permission_check->get_result()->num_rows) {
            die("You don't have permission to view this result.");
        }
    }

    // Fetch result data using prepared statement
    $result_stmt = $conn->prepare("
        SELECT er.*, e.title as exam_title, e.pass_mark, e.total_questions,
               s.name as subject_name, u.username as student_name, 
               c.name as class_name, c.section as class_section
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        JOIN subjects s ON e.subject_id = s.id
        JOIN users u ON er.student_id = u.id
        JOIN classes c ON u.class_id = c.id
        WHERE er.id = ?
    ");
    $result_stmt->bind_param("i", $result_id);
    $result_stmt->execute();
    $result = $result_stmt->get_result()->fetch_assoc();

    if (!$result) {
        die("Result not found.");
    }

    // Fetch questions with prepared statement
    $questions_stmt = $conn->prepare("
        SELECT q.*, ea.answer as student_answer 
        FROM questions q 
        LEFT JOIN exam_answers ea ON q.id = ea.question_id AND ea.exam_result_id = ?
        WHERE q.exam_id = ?
    ");
    $questions_stmt->bind_param("ii", $result_id, $result['exam_id']);
    $questions_stmt->execute();
    $questions = $questions_stmt->get_result();

    require_once '../vendor/tcpdf/tcpdf.php';

    // Create new PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document info
    $pdf->SetCreator('BrainBuilders CBT');
    $pdf->SetAuthor('BrainBuilders');
    $pdf->SetTitle('Exam Result - ' . $result['exam_title']);
    $pdf->SetSubject('Exam Results');
    $pdf->SetKeywords('Exam, Result, BrainBuilders');

    // Set default header data
    $pdf->SetHeaderData('', 0, 'BrainBuilders CBT', 'Exam Result Report');

    // Set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);

    // Add a page
    $pdf->AddPage();

    // School header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'BrainBuilders CBT', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Exam Result Report', 0, 1, 'C');
    $pdf->Ln(10);

    // Student info
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Student:');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, htmlspecialchars($result['student_name']) . ' (' . htmlspecialchars($result['class_section']) . ' ' . htmlspecialchars($result['class_name']) . ')', 0, 1);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Exam:');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, htmlspecialchars($result['exam_title']) . ' (' . htmlspecialchars($result['subject_name']) . ')', 0, 1);

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(40, 10, 'Date:');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('M j, Y g:i A', strtotime($result['submitted_at'])), 0, 1);

    // Score summary
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Score: ' . $result['score'] . '/' . $result['total_questions'] . ' (' . $result['percentage'] . '%)', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Status: ' . ($result['percentage'] >= $result['pass_mark'] ? 'PASSED' : 'FAILED'), 0, 1, 'C');
    $pdf->Ln(15);

    // Questions
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Question Review', 0, 1);
    $pdf->Ln(5);

    $qnum = 1;
    while ($question = $questions->fetch_assoc()) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Question ' . $qnum . ':', 0, 1);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 8, htmlspecialchars($question['question_text']), 0, 'L');
        $pdf->Ln(2);
        
        if ($question['question_type'] === 'multiple_choice') {
            $options = json_decode($question['options'], true);
            foreach ($options as $key => $value) {
                $style = '';
                if ($key == $question['correct_answer']) $style = 'B';
                if ($key == $question['student_answer']) $style .= 'U';
                
                $pdf->SetFont('helvetica', $style, 11);
                $pdf->Cell(5, 8, '');
                $pdf->Cell(0, 8, $key . '. ' . htmlspecialchars($value), 0, 1);
            }
        } 
        elseif ($question['question_type'] === 'true_false') {
            foreach (['True', 'False'] as $opt) {
                $style = '';
                if ($opt == $question['correct_answer']) $style = 'B';
                if ($opt == $question['student_answer']) $style .= 'U';
                
                $pdf->SetFont('helvetica', $style, 11);
                $pdf->Cell(5, 8, '');
                $pdf->Cell(0, 8, $opt, 0, 1);
            }
        } 
        else {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(5, 8, '');
            $pdf->Cell(0, 8, 'Correct Answer: ' . htmlspecialchars($question['correct_answer']), 0, 1);
            
            if (!empty($question['student_answer'])) {
                $pdf->SetFont('helvetica', 'U', 11);
                $pdf->Cell(5, 8, '');
                $pdf->Cell(0, 8, 'Student Answer: ' . htmlspecialchars($question['student_answer']), 0, 1);
            }
        }
        
        $pdf->Ln(8);
        $qnum++;
    }

    // Footer
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Generated by ' . htmlspecialchars($_SESSION['admin_username']) . ' on ' . date('Y-m-d H:i:s'), 0, 0, 'C');

    // Output PDF
    $pdf->Output('result_' . $result_id . '_' . date('Ymd') . '.pdf', 'D');

} catch (Exception $e) {
    error_log("PDF generation error: " . $e->getMessage());
    die("An error occurred while generating the PDF. Please try again later.");
}
<?php
function generate_result_email($result, $questions, $pdf_url = '') {
    $email_html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { background: #1e5799; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .score { font-size: 1.2em; font-weight: bold; margin: 15px 0; }
            .pass { color: #28a745; }
            .fail { color: #dc3545; }
            .question { margin-bottom: 15px; border-left: 3px solid #ddd; padding-left: 10px; }
            .correct { border-left-color: #28a745; }
            .incorrect { border-left-color: #dc3545; }
            .footer { margin-top: 20px; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>BrainBuilders CBT Exam Results</h1>
        </div>
        
        <div class="content">
            <h2>'.$result['exam_title'].' ('.$result['subject_name'].')</h2>
            <p><strong>Student:</strong> '.$result['student_name'].' - '.$result['class_name'].'</p>
            <p><strong>Date Taken:</strong> '.date('M j, Y g:i A', strtotime($result['submitted_at'])).'</p>
            
            <div class="score '.($result['percentage'] >= $result['pass_mark'] ? 'pass' : 'fail').'">
                Score: '.$result['score'].'/'.$result['total_questions'].' ('.$result['percentage'].'%) - 
                '.($result['percentage'] >= $result['pass_mark'] ? 'PASSED' : 'FAILED').'
            </div>';
            
            if ($pdf_url) {
                $email_html .= '<p><a href="'.$pdf_url.'">Download Detailed Result PDF</a></p>';
            }
            
            $email_html .= '
            <hr>
            <h3>Performance Summary</h3>';
            
            $qnum = 1;
            foreach ($questions as $question) {
                $email_html .= '
                <div class="question '.($question['student_answer'] == $question['correct_answer'] ? 'correct' : 'incorrect').'">
                    <p><strong>Question '.$qnum.':</strong> '.nl2br($question['question_text']).'</p>
                    <p>Your answer: <strong>'.htmlspecialchars($question['student_answer'] ?? 'Not attempted').'</strong></p>
                    <p>Correct answer: <strong>'.$question['correct_answer'].'</strong></p>
                </div>';
                $qnum++;
            }
            
            $email_html .= '
            <div class="footer">
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p>&copy; '.date('Y').' BrainBuilders CBT System</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $email_html;
}
?>
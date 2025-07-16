<?php
// Start session with secure settings and check authentication
require_once '../includes/session_check.php';

// Verify admin role
if ($_SESSION['admin_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$error = null;
$success = null;

// Fetch active exams with error handling
try {
    $exams = $conn->query("SELECT id, title FROM exams WHERE available_to >= NOW() ORDER BY title");
    if (!$exams) {
        throw new Exception("Error fetching exams: " . $conn->error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "Unable to load exam data. Please try again later.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize inputs
        $exam_id = intval($_POST['exam_id']);
        $question_type = $conn->real_escape_string($_POST['question_type']);
        $question_text = trim($conn->real_escape_string($_POST['question_text']));
        $marks = min(100, max(1, intval($_POST['marks']))); // Ensure marks between 1-100
        
        // Validate required fields
        if (empty($question_text) || empty($exam_id)) {
            throw new Exception("All required fields must be filled");
        }

        // Process based on question type
        switch ($question_type) {
            case 'multiple_choice':
                $options = [
                    'A' => trim($_POST['option_a']),
                    'B' => trim($_POST['option_b']),
                    'C' => trim($_POST['option_c']),
                    'D' => trim($_POST['option_d'])
                ];
                
                // Validate options
                foreach ($options as $key => $value) {
                    if (empty($value)) {
                        throw new Exception("All multiple choice options must be filled");
                    }
                }
                
                $options = json_encode($options);
                $correct_answer = $_POST['correct_option'];
                break;
                
            case 'true_false':
                $options = json_encode(['True', 'False']);
                $correct_answer = $_POST['true_false_answer'];
                break;
                
            default: // fill_blank or others
                $options = NULL;
                $correct_answer = trim($_POST['correct_answer']);
                if (empty($correct_answer)) {
                    throw new Exception("Correct answer must be provided");
                }
        }

        // Insert question
        $stmt = $conn->prepare("INSERT INTO questions 
            (exam_id, question_type, question_text, options, correct_answer, marks, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $admin_id = $_SESSION['admin_id'];
        $stmt->bind_param("issssii", $exam_id, $question_type, $question_text, $options, $correct_answer, $marks, $admin_id);
        
        if ($stmt->execute()) {
            // Log activity
            $activity_desc = "Added new $question_type question to exam ID $exam_id";
            $conn->query("INSERT INTO activities (user_id, type, description) VALUES ($admin_id, 'exam', '$activity_desc')");
            
            $_SESSION['success'] = "Question added successfully!";
            header("Location: manage_questions.php?success=added");
            exit();
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Add New Question</h1>
            <a href="manage_questions.php" class="btn btn-secondary">Back to Questions</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <form method="POST" id="questionForm" class="question-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="exam_id">Exam *</label>
                    <select name="exam_id" id="exam_id" required>
                        <option value="">-- Select Exam --</option>
                        <?php while ($exam = $exams->fetch_assoc()): ?>
                            <option value="<?php echo $exam['id']; ?>"
                                <?php if (isset($_POST['exam_id']) && $_POST['exam_id'] == $exam['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($exam['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="question_type">Question Type *</label>
                    <select name="question_type" id="questionType" required>
                        <option value="multiple_choice" <?php if (isset($_POST['question_type']) && $_POST['question_type'] == 'multiple_choice') echo 'selected'; ?>>Multiple Choice</option>
                        <option value="true_false" <?php if (isset($_POST['question_type']) && $_POST['question_type'] == 'true_false') echo 'selected'; ?>>True/False</option>
                        <option value="fill_blank" <?php if (isset($_POST['question_type']) && $_POST['question_type'] == 'fill_blank') echo 'selected'; ?>>Fill in the Blank</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="marks">Marks *</label>
                    <input type="number" id="marks" name="marks" min="1" max="100" 
                           value="<?php echo isset($_POST['marks']) ? htmlspecialchars($_POST['marks']) : '1'; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="question_text">Question Text *</label>
                <textarea id="question_text" name="question_text" rows="3" required><?php 
                    echo isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : ''; 
                ?></textarea>
            </div>
            
            <!-- Dynamic fields will be inserted here by JavaScript -->
            <div id="questionFields">
                <?php if (isset($_POST['question_type'])): ?>
                    <?php include 'partials/question_fields_' . $_POST['question_type'] . '.php'; ?>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Question
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/question_form.css">
<script src="../assets/js/question_form.js"></script>

<?php include '../includes/footer.php'; ?>
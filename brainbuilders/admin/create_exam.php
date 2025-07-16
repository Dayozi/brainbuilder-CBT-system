<?php
// Start session with secure settings and check authentication
require_once '../includes/session_check.php';

// Fetch classes and subjects for dropdowns
$classes = $conn->query("SELECT * FROM classes ORDER BY section, name");
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

// Check if default subjects exist, if not create them
$defaultSubjects = ['Mathematics', 'English Language', 'ICT'];
foreach ($defaultSubjects as $subject) {
    $check = $conn->query("SELECT id FROM subjects WHERE name = '$subject'");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO subjects (name) VALUES ('$subject')");
    }
}

// Re-fetch subjects after potential insert
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $title = trim($conn->real_escape_string($_POST['title']));
    $subject_id = intval($_POST['subject_id']);
    $class_id = intval($_POST['class_id']);
    $duration = max(1, intval($_POST['duration'])); // Minimum 1 minute
    $total_questions = max(1, intval($_POST['total_questions']));
    $pass_mark = min(100, max(1, intval($_POST['pass_mark']))); // Between 1-100%
    $available_from = $conn->real_escape_string($_POST['available_from']);
    $available_to = $conn->real_escape_string($_POST['available_to']);
    
    // Validate date range
    if (strtotime($available_from) >= strtotime($available_to)) {
        $error = "End date must be after start date";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO exams 
                (title, subject_id, class_id, duration, total_questions, pass_mark, available_from, available_to) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siiiiiss", $title, $subject_id, $class_id, $duration, 
                             $total_questions, $pass_mark, $available_from, $available_to);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Exam created successfully!";
                header("Location: manage_exams.php?success=created");
                exit();
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        } catch (Exception $e) {
            $error = "Error creating exam: " . $e->getMessage();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Create New Exam</h1>
            <a href="manage_exams.php" class="btn btn-secondary">Back to Exams</a>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="exam-form" id="examForm">
            <div class="form-group">
                <label for="title">Exam Title *</label>
                <input type="text" id="title" name="title" required 
                       placeholder="E.g. Mid-Term Mathematics Exam">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="subject_id">Subject *</label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">-- Select Subject --</option>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['id']; ?>"
                                <?php if (isset($_POST['subject_id']) && $_POST['subject_id'] == $subject['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($subject['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="class_id">Class *</label>
                    <select id="class_id" name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>"
                                <?php if (isset($_POST['class_id']) && $_POST['class_id'] == $class['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($class['section'] . ' - ' . $class['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="duration">Duration (minutes) *</label>
                    <input type="number" id="duration" name="duration" min="1" 
                           value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : '60'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="total_questions">Total Questions *</label>
                    <input type="number" id="total_questions" name="total_questions" min="1" 
                           value="<?php echo isset($_POST['total_questions']) ? htmlspecialchars($_POST['total_questions']) : '20'; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="pass_mark">Pass Mark (%) *</label>
                    <input type="number" id="pass_mark" name="pass_mark" min="1" max="100" 
                           value="<?php echo isset($_POST['pass_mark']) ? htmlspecialchars($_POST['pass_mark']) : '50'; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="available_from">Available From *</label>
                    <input type="datetime-local" id="available_from" name="available_from" 
                           value="<?php echo isset($_POST['available_from']) ? htmlspecialchars($_POST['available_from']) : date('Y-m-d\TH:i'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="available_to">Available To *</label>
                    <input type="datetime-local" id="available_to" name="available_to" 
                           value="<?php echo isset($_POST['available_to']) ? htmlspecialchars($_POST['available_to']) : date('Y-m-d\TH:i', strtotime('+1 week')); ?>" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Exam
                </button>
                <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('examForm').addEventListener('submit', function(e) {
    const from = new Date(document.getElementById('available_from').value);
    const to = new Date(document.getElementById('available_to').value);
    
    if (from >= to) {
        alert('End date must be after start date');
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

<?php include '../includes/footer.php'; ?>
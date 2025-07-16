<?php
// Start session with secure settings and check authentication
require_once '../includes/session_check.php';

// Fetch questions with exam/subject details
$questions = $conn->query("
    SELECT q.*, e.title as exam_title, s.name as subject_name 
    FROM questions q
    JOIN exams e ON q.exam_id = e.id
    JOIN subjects s ON e.subject_id = s.id
    ORDER BY q.id DESC
");

// Check for query execution error
if (!$questions) {
    die("Database error: " . $conn->error);
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Manage Questions</h1>
            <a href="add_question.php" class="btn btn-primary">+ Add Question</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'added' => 'Question added successfully!',
                    'updated' => 'Question updated successfully!',
                    'deleted' => 'Question deleted successfully!'
                ];
                echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully');
                ?>
            </div>
        <?php endif; ?>

        <div class="question-table">
            <?php if ($questions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Question</th>
                            <th>Exam</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($q = $questions->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($q['id']) ?></td>
                            <td><?= htmlspecialchars(substr(strip_tags($q['question_text']), 0, 50)) ?>...</td>
                            <td><?= htmlspecialchars($q['exam_title']) ?></td>
                            <td><?= htmlspecialchars($q['subject_name']) ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($q['question_type']))) ?></td>
                            <td>
                                <a href="edit_question.php?id=<?= $q['id'] ?>" class="btn btn-sm">Edit</a>
                                <a href="delete_question.php?id=<?= $q['id'] ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this question? This action cannot be undone.')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">No questions found. Add your first question using the button above.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
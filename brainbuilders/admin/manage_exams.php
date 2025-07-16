<?php
// Start session with secure settings and check authentication
require_once '../includes/session_check.php';

// Fetch existing exams with error handling
try {
    $exams = $conn->query("
        SELECT e.*, s.name as subject_name, c.name as class_name 
        FROM exams e
        JOIN subjects s ON e.subject_id = s.id
        JOIN classes c ON e.class_id = c.id
        ORDER BY e.available_from DESC
    ");
    
    if (!$exams) {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    die("Error fetching exams: " . htmlspecialchars($e->getMessage()));
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Manage Exams</h1>
            <a href="create_exam.php" class="btn btn-primary">Create New Exam</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                $messages = [
                    'created' => 'Exam created successfully!',
                    'updated' => 'Exam updated successfully!',
                    'deleted' => 'Exam deleted successfully!'
                ];
                echo htmlspecialchars($messages[$_GET['success']] ?? 'Operation completed successfully');
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                Error: <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="exam-table">
            <?php if ($exams->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Duration</th>
                            <th>Available</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($exam = $exams->fetch_assoc()): 
                            $current_time = time();
                            $available_from = strtotime($exam['available_from']);
                            $available_to = strtotime($exam['available_to']);
                            $status = '';
                            
                            if ($current_time < $available_from) {
                                $status = '<span class="badge badge-info">Upcoming</span>';
                            } elseif ($current_time > $available_to) {
                                $status = '<span class="badge badge-secondary">Expired</span>';
                            } else {
                                $status = '<span class="badge badge-success">Active</span>';
                            }
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exam['title']); ?></td>
                            <td><?php echo htmlspecialchars($exam['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($exam['class_name']); ?></td>
                            <td><?php echo (int)$exam['duration']; ?> mins</td>
                            <td>
                                <?php echo date('M j, Y', $available_from); ?> to<br>
                                <?php echo date('M j, Y', $available_to); ?>
                            </td>
                            <td><?php echo $status; ?></td>
                            <td>
                                <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" 
                                   class="btn btn-sm"
                                   title="Edit Exam">
                                   <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_exam.php?id=<?php echo $exam['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   title="Delete Exam"
                                   onclick="return confirm('Are you sure? This will permanently delete the exam and all associated questions.\n\nThis action cannot be undone!')">
                                   <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    No exams found. Create your first exam using the button above.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
// Start secure session
require_once '../includes/session_check.php';

// Verify admin/teacher role
if (!in_array($_SESSION['admin_role'], ['admin', 'teacher'])) {
    header("Location: unauthorized.php");
    exit();
}

// Initialize variables
$exam_id = 0;
$class_id = 0;
$error = null;
$results = [];

try {
    // Get filter parameters with validation
    $exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

    // Fetch available exams and classes for filters
    $exams = $conn->query("SELECT id, title FROM exams ORDER BY available_to DESC");
    $classes = $conn->query("SELECT id, name, section FROM classes ORDER BY section, name");

    if (!$exams || !$classes) {
        throw new Exception("Error loading filter options");
    }

    // Build SQL query with prepared statements for security
    $sql = "
        SELECT er.*, e.title as exam_title, s.name as subject_name,
               u.username as student_name, c.name as class_name,
               e.pass_mark, e.total_questions
        FROM exam_results er
        JOIN exams e ON er.exam_id = e.id
        JOIN subjects s ON e.subject_id = s.id
        JOIN users u ON er.student_id = u.id
        JOIN classes c ON u.class_id = c.id
        WHERE 1=1
    ";

    $params = [];
    $types = '';

    if ($exam_id > 0) {
        $sql .= " AND er.exam_id = ?";
        $params[] = $exam_id;
        $types .= 'i';
    }

    if ($class_id > 0) {
        $sql .= " AND u.class_id = ?";
        $params[] = $class_id;
        $types .= 'i';
    }

    $sql .= " ORDER BY er.submitted_at DESC";

    // Use prepared statement
    $stmt = $conn->prepare($sql);
    
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $results = $stmt->get_result();

} catch (Exception $e) {
    error_log("Results page error: " . $e->getMessage());
    $error = "Unable to load results. Please try again later.";
}
?>

<?php include '../includes/header.php'; ?>

<div class="admin-container">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Exam Results</h1>
            <?php if (in_array($_SESSION['admin_role'], ['admin'])): ?>
                <a href="export_results.php?exam_id=<?= $exam_id ?>&class_id=<?= $class_id ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Export Results
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters card">
            <div class="card-header">
                <h3>Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="exam-filter">Exam</label>
                            <select id="exam-filter" name="exam_id" class="form-control">
                                <option value="0">All Exams</option>
                                <?php while ($exam = $exams->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($exam['id']) ?>"
                                        <?= $exam_id == $exam['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($exam['title']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="class-filter">Class</label>
                            <select id="class-filter" name="class_id" class="form-control">
                                <option value="0">All Classes</option>
                                <?php while ($class = $classes->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($class['id']) ?>"
                                        <?= $class_id == $class['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($class['section']) ?> - <?= htmlspecialchars($class['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                            <a href="results.php" class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Table -->
        <div class="results-table card mt-4">
            <div class="card-body">
                <?php if ($results && $results->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Exam</th>
                                    <th>Subject</th>
                                    <th>Score</th>
                                    <th>Date Taken</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($result = $results->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($result['student_name']) ?></td>
                                        <td><?= htmlspecialchars($result['class_name']) ?></td>
                                        <td><?= htmlspecialchars($result['exam_title']) ?></td>
                                        <td><?= htmlspecialchars($result['subject_name']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $result['percentage'] >= $result['pass_mark'] ? 'success' : 'danger' ?>">
                                                <?= htmlspecialchars($result['score']) ?>/<?= htmlspecialchars($result['total_questions']) ?>
                                                (<?= htmlspecialchars($result['percentage']) ?>%)
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($result['submitted_at'])) ?></td>
                                        <td>
                                            <a href="result_details.php?id=<?= $result['id'] ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                                                <a href="delete_result.php?id=<?= $result['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this result?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        No results found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
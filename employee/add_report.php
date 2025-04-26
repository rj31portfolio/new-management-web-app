<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "Add Daily Report";
$employeeId = getUserId();

// Get task ID from query string
$taskId = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;

if ($taskId === 0) {
    redirect('tasks.php', 'Invalid task ID', 'danger');
}

// Verify task is assigned to this employee
$stmt = $pdo->prepare("SELECT id, title FROM tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$taskId, $employeeId]);
$task = $stmt->fetch();

if (!$task) {
    redirect('tasks.php', 'Task not found or not assigned to you', 'danger');
}

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportDate = sanitize($_POST['report_date']);
    $workDescription = sanitize($_POST['work_description']);
    $hoursWorked = (float)$_POST['hours_worked'];
    $problemsEncountered = sanitize($_POST['problems_encountered']);
    $status = sanitize($_POST['status']);
    
    // Check if report already exists for this date and task
    $stmt = $pdo->prepare("SELECT id FROM daily_reports WHERE task_id = ? AND report_date = ?");
    $stmt->execute([$taskId, $reportDate]);
    
    if ($stmt->fetch()) {
        redirect("add_report.php?task_id=$taskId", 'A report already exists for this date', 'danger');
    }
    
    // Insert new report
    $stmt = $pdo->prepare("
        INSERT INTO daily_reports 
        (employee_id, task_id, report_date, work_description, hours_worked, problems_encountered, status, submitted_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $submittedAt = $status === 'submitted' ? date('Y-m-d H:i:s') : null;
    
    if ($stmt->execute([
        $employeeId, 
        $taskId, 
        $reportDate, 
        $workDescription, 
        $hoursWorked, 
        $problemsEncountered, 
        $status,
        $submittedAt
    ])) {
        $message = $status === 'submitted' ? 
            'Report submitted successfully' : 
            'Report saved as draft successfully';
        redirect("task_details.php?id=$taskId", $message, 'success');
    } else {
        redirect("add_report.php?task_id=$taskId", 'Failed to save report', 'danger');
    }
}

// Default to today's date
$defaultDate = date('Y-m-d');
?>

<div class="card">
    <div class="card-header">
        <h4>Add Daily Report for: <?php echo $task['title']; ?></h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="report_date" class="form-label">Report Date</label>
                <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $defaultDate; ?>" required>
            </div>
            <div class="mb-3">
                <label for="hours_worked" class="form-label">Hours Worked</label>
                <input type="number" step="0.25" class="form-control" id="hours_worked" name="hours_worked" min="0.25" max="24" required>
            </div>
            <div class="mb-3">
                <label for="work_description" class="form-label">Work Description</label>
                <textarea class="form-control" id="work_description" name="work_description" rows="5" required></textarea>
            </div>
            <div class="mb-3">
                <label for="problems_encountered" class="form-label">Problems Encountered (Optional)</label>
                <textarea class="form-control" id="problems_encountered" name="problems_encountered" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="draft">Save as Draft</option>
                    <option value="submitted">Submit Report</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Report</button>
            <a href="task_details.php?id=<?php echo $taskId; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
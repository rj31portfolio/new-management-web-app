<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "Task Details";
$employeeId = getUserId();

// Get task ID from query string
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId === 0) {
    redirect('tasks.php', 'Invalid task ID', 'danger');
}

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*, a.username as assigned_by_name 
    FROM tasks t 
    JOIN admins a ON t.assigned_by = a.id 
    WHERE t.id = ? AND t.assigned_to = ?
");
$stmt->execute([$taskId, $employeeId]);
$task = $stmt->fetch();

if (!$task) {
    redirect('tasks.php', 'Task not found or access denied', 'danger');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    if ($stmt->execute([$newStatus, $taskId])) {
        redirect("task_details.php?id=$taskId", 'Task status updated successfully', 'success');
    } else {
        redirect("task_details.php?id=$taskId", 'Failed to update task status', 'danger');
    }
}

// Get reports for this task
$stmt = $pdo->prepare("
    SELECT * FROM daily_reports 
    WHERE task_id = ? AND employee_id = ? 
    ORDER BY report_date DESC
");
$stmt->execute([$taskId, $employeeId]);
$reports = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h4><?php echo $task['title']; ?></h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Task Information</h6>
                    <hr>
                    <p><strong>Assigned By:</strong> <?php echo $task['assigned_by_name']; ?></p>
                    <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($task['due_date'])); ?></p>
                    <p><strong>Priority:</strong> 
                        <span class="badge bg-<?php 
                            echo $task['priority'] === 'high' ? 'danger' : 
                                ($task['priority'] === 'medium' ? 'warning' : 'success'); 
                        ?>">
                            <?php echo ucfirst($task['priority']); ?>
                        </span>
                    </p>
                    <p><strong>Current Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $task['status'] === 'completed' ? 'success' : 
                                ($task['status'] === 'in_progress' ? 'primary' : 'secondary'); 
                        ?>">
                            <?php echo str_replace('_', ' ', ucfirst($task['status'])); ?>
                        </span>
                    </p>
                </div>
                
                <!-- Status Update Form -->
                <div class="mb-3">
                    <h6>Update Status</h6>
                    <hr>
                    <form method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <div class="mb-3">
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $task['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $task['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $task['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Description</h6>
                    <hr>
                    <p><?php echo nl2br($task['description']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Daily Reports</h5>
                <a href="add_report.php?task_id=<?php echo $taskId; ?>" class="btn btn-primary">Add New Report</a>
            </div>
            <hr>
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">No reports submitted for this task yet.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($reports as $report): ?>
                        <a href="report_details.php?id=<?php echo $report['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Report for <?php echo date('M d, Y', strtotime($report['report_date'])); ?></h6>
                                <small><?php echo $report['status'] === 'submitted' ? 'Submitted' : 'Draft'; ?></small>
                            </div>
                            <p class="mb-1"><strong>Hours Worked:</strong> <?php echo $report['hours_worked']; ?></p>
                            <p class="mb-1"><?php echo substr($report['work_description'], 0, 100); ?>...</p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-3">
            <a href="tasks.php" class="btn btn-secondary">Back to Tasks</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
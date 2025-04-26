<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Task Details";

// Get task ID from query string
$taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($taskId === 0) {
    redirect('tasks.php', 'Invalid task ID', 'danger');
}

// Get task details
$stmt = $pdo->prepare("
    SELECT t.*, e.name as employee_name, a.username as assigned_by_name 
    FROM tasks t 
    JOIN employees e ON t.assigned_to = e.id 
    JOIN admins a ON t.assigned_by = a.id 
    WHERE t.id = ?
");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    redirect('tasks.php', 'Task not found', 'danger');
}

// Get daily reports for this task
$stmt = $pdo->prepare("
    SELECT r.*, e.name as employee_name 
    FROM daily_reports r 
    JOIN employees e ON r.employee_id = e.id 
    WHERE r.task_id = ? 
    ORDER BY r.report_date DESC
");
$stmt->execute([$taskId]);
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
                    <p><strong>Assigned To:</strong> <?php echo $task['employee_name']; ?></p>
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
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $task['status'] === 'completed' ? 'success' : 
                                ($task['status'] === 'in_progress' ? 'primary' : 'secondary'); 
                        ?>">
                            <?php echo str_replace('_', ' ', ucfirst($task['status'])); ?>
                        </span>
                    </p>
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
            <h5>Daily Reports</h5>
            <hr>
            <?php if (empty($reports)): ?>
                <div class="alert alert-info">No reports submitted for this task yet.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($reports as $report): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Report for <?php echo date('M d, Y', strtotime($report['report_date'])); ?></h6>
                                <small><?php echo $report['status'] === 'submitted' ? 'Submitted' : 'Draft'; ?></small>
                            </div>
                            <p class="mb-1"><strong>Hours Worked:</strong> <?php echo $report['hours_worked']; ?></p>
                            <p class="mb-1"><strong>Work Done:</strong> <?php echo nl2br(substr($report['work_description'], 0, 100)); ?>...</p>
                            <a href="report_details.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-info mt-2">View Details</a>
                        </div>
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
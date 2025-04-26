<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Report Details";

// Get report ID from query string and sanitize it
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;



if ($reportId === 0) {
    // Assuming redirect() handles setting a flash message and redirecting
    redirect('reports.php', 'Invalid report ID', 'danger');
}

// Get report details
$stmt = $pdo->prepare("
    SELECT r.*, e.name as employee_name, t.title as task_title 
    FROM daily_reports r 
    JOIN employees e ON r.employee_id = e.id 
    JOIN tasks t ON r.task_id = t.id 
    WHERE r.id = ?
");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('reports.php', 'Report not found', 'danger');
}
?>

<div class="card">
    <div class="card-header">
        <h4>Daily Report - <?php echo date('M d, Y', strtotime($report['report_date'])); ?></h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Employee:</strong> <?php echo htmlspecialchars($report['employee_name']); ?></p>
                <p><strong>Task:</strong> <?php echo htmlspecialchars($report['task_title']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Hours Worked:</strong> <?php echo htmlspecialchars($report['hours_worked']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $report['status'] === 'submitted' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($report['status']); ?>
                    </span>
                </p>
                <p><strong>Submitted At:</strong> <?php echo $report['submitted_at'] ? date('M d, Y h:i A', strtotime($report['submitted_at'])) : 'Not submitted'; ?></p>
            </div>
        </div>
        
        <div class="mb-4">
            <h5>Work Description</h5>
            <hr>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br(htmlspecialchars($report['work_description'])); ?>
            </div>
        </div>
        
        <?php if (!empty($report['problems_encountered'])): ?>
            <div class="mb-4">
                <h5>Problems Encountered</h5>
                <hr>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br(htmlspecialchars($report['problems_encountered'])); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="reports.php<?php echo $report['id'] ? '?id=' . $report['id'] : ''; ?>" class="btn btn-secondary">Back to Reports</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

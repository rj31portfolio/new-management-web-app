<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "Report Details";
$employeeId = getUserId();

// Get report ID from query string
$reportId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($reportId === 0) {
    redirect('reports.php', 'Invalid report ID', 'danger');
}

// Get report details
$stmt = $pdo->prepare("
    SELECT r.*, t.title as task_title 
    FROM daily_reports r 
    JOIN tasks t ON r.task_id = t.id 
    WHERE r.id = ? AND r.employee_id = ?
");
$stmt->execute([$reportId, $employeeId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('reports.php', 'Report not found or access denied', 'danger');
}

// Handle report submission if it's a draft
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report']) && $report['status'] === 'draft') {
    $stmt = $pdo->prepare("UPDATE daily_reports SET status = 'submitted', submitted_at = ? WHERE id = ?");
    if ($stmt->execute([date('Y-m-d H:i:s'), $reportId])) {
        redirect("report_details.php?id=$reportId", 'Report submitted successfully', 'success');
    } else {
        redirect("report_details.php?id=$reportId", 'Failed to submit report', 'danger');
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4>Daily Report - <?php echo date('M d, Y', strtotime($report['report_date'])); ?></h4>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Task:</strong> <?php echo $report['task_title']; ?></p>
                <p><strong>Hours Worked:</strong> <?php echo $report['hours_worked']; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $report['status'] === 'submitted' ? 'success' : 'secondary'; ?>">
                        <?php echo ucfirst($report['status']); ?>
                    </span>
                </p>
                <?php if ($report['status'] === 'submitted'): ?>
                    <p><strong>Submitted At:</strong> <?php echo date('M d, Y h:i A', strtotime($report['submitted_at'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mb-4">
            <h5>Work Description</h5>
            <hr>
            <div class="p-3 bg-light rounded">
                <?php echo nl2br($report['work_description']); ?>
            </div>
        </div>
        
        <?php if (!empty($report['problems_encountered'])): ?>
            <div class="mb-4">
                <h5>Problems Encountered</h5>
                <hr>
                <div class="p-3 bg-light rounded">
                    <?php echo nl2br($report['problems_encountered']); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <?php if ($report['status'] === 'draft'): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="submit_report" value="1">
                    <button type="submit" class="btn btn-success">Submit Report</button>
                </form>
                <a href="edit_report.php?id=<?php echo $reportId; ?>" class="btn btn-primary">Edit</a>
            <?php endif; ?>
            <a href="reports.php" class="btn btn-secondary">Back to Reports</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
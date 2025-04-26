<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "Edit Report";
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
    WHERE r.id = ? AND r.employee_id = ? AND r.status = 'draft'
");
$stmt->execute([$reportId, $employeeId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('reports.php', 'Report not found, already submitted, or access denied', 'danger');
}

// Handle report update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportDate = sanitize($_POST['report_date']);
    $workDescription = sanitize($_POST['work_description']);
    $hoursWorked = (float)$_POST['hours_worked'];
    $problemsEncountered = sanitize($_POST['problems_encountered']);
    $status = sanitize($_POST['status']);
    
    $submittedAt = $status === 'submitted' ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("
        UPDATE daily_reports 
        SET report_date = ?, 
            work_description = ?, 
            hours_worked = ?, 
            problems_encountered = ?, 
            status = ?, 
            submitted_at = ? 
        WHERE id = ?
    ");
    
    if ($stmt->execute([
        $reportDate, 
        $workDescription, 
        $hoursWorked, 
        $problemsEncountered, 
        $status,
        $submittedAt,
        $reportId
    ])) {
        $message = $status === 'submitted' ? 
            'Report updated and submitted successfully' : 
            'Report updated successfully';
        redirect("report_details.php?id=$reportId", $message, 'success');
    } else {
        redirect("edit_report.php?id=$reportId", 'Failed to update report', 'danger');
    }
}
?>

<div class="card">
    <div class="card-header">
        <h4>Edit Daily Report for: <?php echo $report['task_title']; ?></h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="report_date" class="form-label">Report Date</label>
                <input type="date" class="form-control" id="report_date" name="report_date" value="<?php echo $report['report_date']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="hours_worked" class="form-label">Hours Worked</label>
                <input type="number" step="0.25" class="form-control" id="hours_worked" name="hours_worked" min="0.25" max="24" value="<?php echo $report['hours_worked']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="work_description" class="form-label">Work Description</label>
                <textarea class="form-control" id="work_description" name="work_description" rows="5" required><?php echo $report['work_description']; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="problems_encountered" class="form-label">Problems Encountered (Optional)</label>
                <textarea class="form-control" id="problems_encountered" name="problems_encountered" rows="3"><?php echo $report['problems_encountered']; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="draft" <?php echo $report['status'] === 'draft' ? 'selected' : ''; ?>>Save as Draft</option>
                    <option value="submitted">Submit Report</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="report_details.php?id=<?php echo $reportId; ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
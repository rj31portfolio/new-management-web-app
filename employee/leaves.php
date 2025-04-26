<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isEmployee()) {
    redirect('../employee/login.php', 'You must be logged in as an employee', 'error');
}

$pdo = getDatabase();

// Handle leave application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $leaveTypeId = sanitize($_POST['leave_type_id']);
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);

    // Validate end date
    if (strtotime($endDate) < strtotime($startDate)) {
        redirect('leaves.php', 'End date cannot be before start date', 'error');
    }

    // Check for overlapping leave
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND (
        (start_date <= ? AND end_date >= ?) OR
        (start_date <= ? AND end_date >= ?) OR
        (start_date >= ? AND end_date <= ?)
    )");
    $stmt->execute([
        $_SESSION['user_id'],
        $startDate, $startDate,
        $endDate, $endDate,
        $startDate, $endDate
    ]);
    $overlap = $stmt->fetchColumn();

    if ($overlap > 0) {
        redirect('leaves.php', 'You already have a leave during this period', 'error');
    }

    // Insert leave request
    $stmt = $pdo->prepare("
        INSERT INTO leaves (employee_id, leave_type_id, start_date, end_date, reason)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $leaveTypeId,
        $startDate,
        $endDate,
        $reason
    ]);

    redirect('leaves.php', 'Leave application submitted successfully', 'success');
}

// Get leave types
$leaveTypes = $pdo->query("SELECT id, name FROM leave_types")->fetchAll();

// Get employee's leave history
$stmt = $pdo->prepare("
    SELECT l.*, lt.name as leave_type, h.username as approved_by_name
    FROM leaves l
    JOIN leave_types lt ON l.leave_type_id = lt.id
    LEFT JOIN hr h ON l.approved_by = h.id
    WHERE l.employee_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$leaveHistory = $stmt->fetchAll();
?>

<div class="container mt-4">
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash']['message']; ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <h2>My Leave Applications</h2>
    
    <div class="card mt-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="view-tab" data-toggle="tab" href="#view" role="tab">My Leaves</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="apply-tab" data-toggle="tab" href="#apply" role="tab">Apply for Leave</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- Leave History -->
                <div class="tab-pane fade show active" id="view" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaveHistory as $leave): 
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                    <td><?php echo $start->format('M d, Y'); ?> to <?php echo $end->format('M d, Y'); ?></td>
                                    <td><?php echo $days; ?></td>
                                    <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $leave['status'] === 'approved' ? 'success' : 
                                                 ($leave['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $leave['approved_by_name'] ?? '--'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Leave Apply Form -->
                <div class="tab-pane fade" id="apply" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="apply_leave" value="1">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="leave_type_id">Leave Type</label>
                                <select class="form-control" id="leave_type_id" name="leave_type_id" required>
                                    <option value="">Select Leave Type</option>
                                    <?php foreach ($leaveTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="start_date">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="end_date">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Apply for Leave</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery & Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Set minimum end date based on start date
$('#start_date').change(function() {
    const startDate = new Date($(this).val());
    if (!isNaN(startDate.getTime())) {
        $('#end_date').attr('min', $(this).val());
        if (!$('#end_date').val() || new Date($('#end_date').val()) < startDate) {
            $('#end_date').val($(this).val());
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>

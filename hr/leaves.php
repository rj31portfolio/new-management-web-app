<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_leave'])) {
        $leaveId = sanitize($_POST['leave_id']);
        $stmt = $pdo->prepare("UPDATE leaves SET status = 'approved', approved_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $leaveId]);
        redirect('leaves.php', 'Leave approved successfully', 'success');
    } elseif (isset($_POST['reject_leave'])) {
        $leaveId = sanitize($_POST['leave_id']);
        $stmt = $pdo->prepare("UPDATE leaves SET status = 'rejected', approved_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $leaveId]);
        redirect('leaves.php', 'Leave rejected', 'info');
    }
}

// Get all leave requests
$stmt = $pdo->query("
    SELECT l.*, e.name as employee_name, lt.name as leave_type, h.username as approved_by_name
    FROM leaves l
    JOIN employees e ON l.employee_id = e.id
    JOIN leave_types lt ON l.leave_type_id = lt.id
    LEFT JOIN hr h ON l.approved_by = h.id
    ORDER BY l.created_at DESC
");
$leaves = $stmt->fetchAll();

// Get leave types for filters
$leaveTypes = $pdo->query("SELECT id, name FROM leave_types")->fetchAll();
?>

<div class="container mt-4">
    <h2>Employee Leave Management</h2>
    
    <div class="card mt-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#pending" data-toggle="tab">Pending Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#all" data-toggle="tab">All Leaves</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="pending">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $leave): 
                                    if ($leave['status'] !== 'pending') continue;
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                    <td>
                                        <?php echo $start->format('M d, Y'); ?> to 
                                        <?php echo $end->format('M d, Y'); ?>
                                    </td>
                                    <td><?php echo $days; ?></td>
                                    <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <button type="submit" name="approve_leave" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                            <button type="submit" name="reject_leave" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="all">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Dates</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $leave): 
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    $days = $start->diff($end)->days + 1;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                                    <td>
                                        <?php echo $start->format('M d, Y'); ?> to 
                                        <?php echo $end->format('M d, Y'); ?>
                                    </td>
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
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
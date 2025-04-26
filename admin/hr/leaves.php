<?php
require_once '../../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Manage Leaves";

$alert = [];

// Handle leave action (approve/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $leaveId = (int)$_POST['leave_id'];
    $action = $_POST['action'];
    $stmt = $pdo->prepare("UPDATE leaves SET status = ?, approved_by = ? WHERE id = ?");
    if ($stmt->execute([$action === 'approve' ? 'approved' : 'rejected', getUserId(), $leaveId])) {
        $alert = ['type' => 'success', 'message' => 'Leave ' . $action . 'd successfully.'];
    } else {
        $alert = ['type' => 'danger', 'message' => 'Failed to update leave status.'];
    }
}

// Fetch all leave requests
$leavesList = $pdo->query("SELECT l.id, e.name, lt.name as leave_type, l.start_date, l.end_date, l.reason, l.status, l.created_at 
                          FROM leaves l 
                          JOIN employees e ON l.employee_id = e.id 
                          JOIN leave_types lt ON l.leave_type_id = lt.id 
                          ORDER BY l.created_at DESC")->fetchAll();
?>

<style>
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }
    .card-header {
        border-radius: 15px 15px 0 0 !important;
        padding: 1rem 1.5rem;
        font-weight: 600;
        background-color: #ffc107;
        color: white;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
    .btn-custom {
        transition: all 0.3s ease;
    }
    .btn-custom:hover {
        transform: translateY(-2px);
    }
</style>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
        <?= $alert['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header">
                <i class="fas fa-calendar-minus me-2"></i>
                <span class="fw-bold">Manage Leave Requests</span>
            </div>
            <div class="card-body">
                <?php if (count($leavesList) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Requested On</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leavesList as $leave): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($leave['name']) ?></td>
                                        <td><?= htmlspecialchars($leave['leave_type']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($leave['start_date'])) ?></td>
                                        <td><?= date('Y-m-d', strtotime($leave['end_date'])) ?></td>
                                        <td><?= htmlspecialchars($leave['reason']) ?></td>
                                        <td><?= htmlspecialchars($leave['status']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($leave['created_at'])) ?></td>
                                        <td>
                                            <?php if ($leave['status'] === 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm btn-custom"
                                                        onclick="return confirm('Are you sure you want to approve this leave?');">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm btn-custom"
                                                        onclick="return confirm('Are you sure you want to reject this leave?');">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No Action</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No leave requests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

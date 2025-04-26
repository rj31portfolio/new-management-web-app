<?php
require_once '../../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Manage Attendance";

$alert = [];

// Handle attendance update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $attendanceId = (int)$_POST['attendance_id'];
    $checkOut = !empty($_POST['check_out']) ? $_POST['check_out'] : null;
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE attendance SET check_out = ?, status = ? WHERE id = ?");
    if ($stmt->execute([$checkOut, $status, $attendanceId])) {
        $alert = ['type' => 'success', 'message' => 'Attendance updated successfully.'];
    } else {
        $alert = ['type' => 'danger', 'message' => 'Failed to update attendance.'];
    }
}

// Fetch all attendance records
$attendanceList = $pdo->query("SELECT a.id, e.name, a.check_in, a.check_out, a.status, a.check_in_location 
                              FROM attendance a 
                              JOIN employees e ON a.employee_id = e.id 
                              ORDER BY a.check_in DESC")->fetchAll();
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
        background-color: #17a2b8;
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
    .modal {
        display: none; /* Ensure modal is hidden by default */
    }
    .modal-backdrop {
        display: none; /* Prevent backdrop from showing on hover */
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
                <i class="fas fa-user-clock me-2"></i>
                <span class="fw-bold">Manage Attendance</span>
            </div>
            <div class="card-body">
                <?php if (count($attendanceList) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceList as $attendance): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($attendance['name']) ?></td>
                                        <td><?= date('Y-m-d H:i', strtotime($attendance['check_in'])) ?></td>
                                        <td><?= $attendance['check_out'] ? date('Y-m-d H:i', strtotime($attendance['check_out'])) : 'Not checked out' ?></td>
                                        <td><?= htmlspecialchars($attendance['status']) ?></td>
                                        <td><?= htmlspecialchars($attendance['check_in_location']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm btn-custom" data-bs-toggle="modal" data-bs-target="#attendanceModal<?= $attendance['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <!-- Attendance Modal -->
                                            <div class="modal fade" id="attendanceModal<?= $attendance['id'] ?>" tabindex="-1" aria-labelledby="attendanceModalLabel<?= $attendance['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="attendanceModalLabel<?= $attendance['id'] ?>">Edit Attendance</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST">
                                                                <input type="hidden" name="attendance_id" value="<?= $attendance['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Check Out</label>
                                                                    <input type="datetime-local" name="check_out" class="form-control" value="<?= $attendance['check_out'] ? date('Y-m-d\TH:i', strtotime($attendance['check_out'])) : '' ?>">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="present" <?= $attendance['status'] === 'present' ? 'selected' : '' ?>>Present</option>
                                                                        <option value="absent" <?= $attendance['status'] === 'absent' ? 'selected' : '' ?>>Absent</option>
                                                                        <option value="half_day" <?= $attendance['status'] === 'half_day' ? 'selected' : '' ?>>Half Day</option>
                                                                        <option value="late" <?= $attendance['status'] === 'late' ? 'selected' : '' ?>>Late</option>
                                                                    </select>
                                                                </div>
                                                                <button type="submit" name="update_attendance" class="btn btn-success btn-custom">Save Changes</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No attendance records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
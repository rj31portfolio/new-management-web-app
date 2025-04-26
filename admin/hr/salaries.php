<?php
require_once '../../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Manage Salaries";

$alert = [];

// Handle salary update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_salary'])) {
    $salaryId = (int)$_POST['salary_id'];
    $status = $_POST['status'];
    $paymentDate = !empty($_POST['payment_date']) ? $_POST['payment_date'] : null;
    $paymentMethod = $_POST['payment_method'];

    $stmt = $pdo->prepare("UPDATE salaries SET status = ?, payment_date = ?, payment_method = ? WHERE id = ?");
    if ($stmt->execute([$status, $paymentDate, $paymentMethod, $salaryId])) {
        $alert = ['type' => 'success', 'message' => 'Salary updated successfully.'];
    } else {
        $alert = ['type' => 'danger', 'message' => 'Failed to update salary.'];
    }
}

// Fetch all salary records
$salaryList = $pdo->query("SELECT s.id, e.name, s.month, s.year, s.basic_salary, s.allowances, s.deductions, s.bonus, s.tax, s.net_salary, s.status, s.payment_date, s.payment_method 
                          FROM salaries s 
                          JOIN employees e ON s.employee_id = e.id 
                          ORDER BY s.created_at DESC")->fetchAll();
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
        background-color: #dc3545;
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
    /* Ensure modal doesn't interfere with hover */
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
                <i class="fas fa-money-bill-wave me-2"></i>
                <span class="fw-bold">Manage Salaries</span>
            </div>
            <div class="card-body">
                <?php if (count($salaryList) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Basic Salary</th>
                                    <th>Allowances</th>
                                    <th>Deductions</th>
                                    <th>Bonus</th>
                                    <th>Tax</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th>Payment Date</th>
                                    <th>Payment Method</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salaryList as $salary): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($salary['name']) ?></td>
                                        <td><?= date('F', mktime(0, 0, 0, $salary['month'], 1)) ?></td>
                                        <td><?= $salary['year'] ?></td>
                                        <td>$<?= number_format($salary['basic_salary'], 2) ?></td>
                                        <td>$<?= number_format($salary['allowances'], 2) ?></td>
                                        <td>$<?= number_format($salary['deductions'], 2) ?></td>
                                        <td>$<?= number_format($salary['bonus'], 2) ?></td>
                                        <td>$<?= number_format($salary['tax'], 2) ?></td>
                                        <td>$<?= number_format($salary['net_salary'], 2) ?></td>
                                        <td><?= htmlspecialchars($salary['status']) ?></td>
                                        <td><?= $salary['payment_date'] ? date('Y-m-d', strtotime($salary['payment_date'])) : 'Not paid' ?></td>
                                        <td><?= htmlspecialchars($salary['payment_method'] ?? 'N/A') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-info btn-sm btn-custom" data-bs-toggle="modal" data-bs-target="#salaryModal<?= $salary['id'] ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <!-- Salary Modal -->
                                            <div class="modal fade" id="salaryModal<?= $salary['id'] ?>" tabindex="-1" aria-labelledby="salaryModalLabel<?= $salary['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="salaryModalLabel<?= $salary['id'] ?>">Edit Salary</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST">
                                                                <input type="hidden" name="salary_id" value="<?= $salary['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="pending" <?= $salary['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="paid" <?= $salary['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Payment Date</label>
                                                                    <input type="date" name="payment_date" class="form-control" value="<?= $salary['payment_date'] ? date('Y-m-d', strtotime($salary['payment_date'])) : '' ?>" <?= $salary['status'] === 'paid' ? '' : 'disabled' ?>>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Payment Method</label>
                                                                    <select name="payment_method" class="form-select" <?= $salary['status'] === 'paid' ? '' : 'disabled' ?>>
                                                                        <option value="cash" <?= ($salary['payment_method'] ?? 'N/A') === 'cash' ? 'selected' : '' ?>>Cash</option>
                                                                        <option value="bank_transfer" <?= ($salary['payment_method'] ?? 'N/A') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                                                                        <option value="cheque" <?= ($salary['payment_method'] ?? 'N/A') === 'cheque' ? 'selected' : '' ?>>Cheque</option>
                                                                    </select>
                                                                </div>
                                                                <button type="submit" name="update_salary" class="btn btn-success btn-custom">Save Changes</button>
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
                    <p class="text-muted">No salary records found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
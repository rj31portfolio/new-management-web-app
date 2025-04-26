<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle salary actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['generate_salary'])) {
            $employeeId = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
            $month = filter_input(INPUT_POST, 'month', FILTER_SANITIZE_NUMBER_INT);
            $year = filter_input(INPUT_POST, 'year', FILTER_SANITIZE_NUMBER_INT);
            $basicSalary = filter_input(INPUT_POST, 'basic_salary', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $allowances = filter_input(INPUT_POST, 'allowances', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $deductions = filter_input(INPUT_POST, 'deductions', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $bonus = filter_input(INPUT_POST, 'bonus', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $tax = filter_input(INPUT_POST, 'tax', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: '';

            if (!$employeeId || !$month || !$year || !$basicSalary || $basicSalary <= 0) {
                redirect('salaries.php', 'Error: Invalid input data. Ensure all required fields are valid.', 'error');
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM salaries WHERE employee_id = ? AND month = ? AND year = ?");
            $stmt->execute([$employeeId, $month, $year]);
            if ($stmt->fetchColumn() > 0) {
                redirect('salaries.php', 'Error: Salary already generated for this employee and period.', 'error');
            }

            $netSalary = $basicSalary + $allowances + $bonus - $deductions - $tax;

            $stmt = $pdo->prepare("
                INSERT INTO salaries (
                    employee_id, month, year, basic_salary, allowances, 
                    deductions, bonus, tax, net_salary, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employeeId, $month, $year, $basicSalary, $allowances,
                $deductions, $bonus, $tax, $netSalary, $notes, $_SESSION['user_id']
            ]);

            redirect('salaries.php', 'Salary generated successfully', 'success');

        } elseif (isset($_POST['mark_paid'])) {
            $salaryId = filter_input(INPUT_POST, 'salary_id', FILTER_SANITIZE_NUMBER_INT);
            $paymentDate = filter_input(INPUT_POST, 'payment_date', FILTER_SANITIZE_STRING);
            $paymentMethod = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
            $transactionRef = filter_input(INPUT_POST, 'transaction_ref', FILTER_SANITIZE_STRING) ?: '';
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

            if (!$salaryId || !$paymentDate || !$paymentMethod || !in_array($status, ['paid', 'pending'])) {
                redirect('salaries.php', 'Error: Invalid payment data.', 'error');
            }

            if (!DateTime::createFromFormat('Y-m-d', $paymentDate)) {
                redirect('salaries.php', 'Error: Invalid payment date format.', 'error');
            }

            $stmt = $pdo->prepare("SELECT status FROM salaries WHERE id = ?");
            $stmt->execute([$salaryId]);
            $salary = $stmt->fetch();
            if (!$salary) {
                redirect('salaries.php', 'Error: Salary record not found.', 'error');
            }

            $stmt = $pdo->prepare("
                UPDATE salaries 
                SET status = ?, 
                    payment_date = ?, 
                    payment_method = ?,
                    transaction_ref = ?
                WHERE id = ?
            ");
            if ($stmt->execute([$status, $paymentDate, $paymentMethod, $transactionRef, $salaryId])) {
                redirect('salaries.php?refresh=' . time(), 'Salary status updated successfully', 'success');
            } else {
                redirect('salaries.php', 'Error: Failed to update salary status.', 'error');
            }

        } elseif (isset($_POST['update_salary'])) {
            $salaryId = filter_input(INPUT_POST, 'salary_id', FILTER_SANITIZE_NUMBER_INT);
            $basicSalary = filter_input(INPUT_POST, 'basic_salary', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $allowances = filter_input(INPUT_POST, 'allowances', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $deductions = filter_input(INPUT_POST, 'deductions', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $bonus = filter_input(INPUT_POST, 'bonus', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $tax = filter_input(INPUT_POST, 'tax', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?: 0;
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING) ?: '';

            if (!$salaryId || !$basicSalary || $basicSalary <= 0) {
                redirect('salaries.php', 'Error: Invalid salary data.', 'error');
            }

            $stmt = $pdo->prepare("SELECT status FROM salaries WHERE id = ?");
            $stmt->execute([$salaryId]);
            $salary = $stmt->fetch();
            if ($salary['status'] === 'paid') {
                redirect('salaries.php', 'Error: Cannot edit paid salary.', 'error');
            }

            $netSalary = $basicSalary + $allowances + $bonus - $deductions - $tax;

            $stmt = $pdo->prepare("
                UPDATE salaries 
                SET basic_salary = ?,
                    allowances = ?,
                    deductions = ?,
                    bonus = ?,
                    tax = ?,
                    net_salary = ?,
                    notes = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $basicSalary, $allowances, $deductions, 
                $bonus, $tax, $netSalary, $notes, $salaryId
            ]);

            redirect('salaries.php', 'Salary updated successfully', 'success');
        }
    } catch (PDOException $e) {
        redirect('salaries.php', 'Database error: ' . $e->getMessage(), 'error');
    }
}

// Get filter parameters
$statusFilter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING) ?: '';
$employeeFilter = filter_input(INPUT_GET, 'employee_id', FILTER_SANITIZE_NUMBER_INT) ?: 0;
$monthFilter = filter_input(INPUT_GET, 'month', FILTER_SANITIZE_NUMBER_INT) ?: '';
$yearFilter = filter_input(INPUT_GET, 'year', FILTER_SANITIZE_NUMBER_INT) ?: '';

// Build filter conditions
$conditions = [];
$params = [];

if (!empty($statusFilter)) {
    $conditions[] = "s.status = ?";
    $params[] = $statusFilter;
}

if ($employeeFilter > 0) {
    $conditions[] = "s.employee_id = ?";
    $params[] = $employeeFilter;
}

if (!empty($monthFilter)) {
    $conditions[] = "s.month = ?";
    $params[] = $monthFilter;
}

if (!empty($yearFilter)) {
    $conditions[] = "s.year = ?";
    $params[] = $yearFilter;
}

$whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get all salary records with filters
$query = "
    SELECT s.*, 
           e.name as employee_name, 
           e.email as employee_email,
           e.phone as employee_phone,
           e.position as employee_position,
           e.department as employee_department,
           e.bank_account as employee_bank_account,
           e.joining_date as employee_joining_date,
           e.emergency_contact as employee_emergency_contact,
           h.username as created_by_name
    FROM salaries s
    JOIN employees e ON s.employee_id = e.id
    JOIN hr h ON s.created_by = h.id
    $whereClause
    ORDER BY s.year DESC, s.month DESC, e.name ASC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $salaries = $stmt->fetchAll();
} catch (PDOException $e) {
    redirect('salaries.php', 'Error fetching salaries: ' . $e->getMessage(), 'error');
}

// Get all employees for dropdown
try {
    $employees = $pdo->query("
        SELECT id, name, email, phone, username, position, department, 
               created_at, salary, bank_account, joining_date, emergency_contact 
        FROM employees 
        ORDER BY name
    ")->fetchAll();
    if (empty($employees)) {
        $employeeError = "No employees found in the database. Please add employees to the employees table.";
    }
} catch (PDOException $e) {
    $employeeError = "Error fetching employees: " . $e->getMessage();
}

// Get payment methods
$paymentMethods = [
    'cash' => 'Cash',
    'bank_transfer' => 'Bank Transfer',
    'cheque' => 'Cheque',
    'digital_wallet' => 'Digital Wallet'
];
?>


<div class="container mt-4">
    <h2>Employee Salary Management</h2>

    <?php if (isset($employeeError)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($employeeError) ?></div>
    <?php endif; ?>
    
    <!-- Filter Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" id="employee_id" name="employee_id">
                        <option value="0">All Employees</option>
                        <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee['id'] ?>" <?= $employeeFilter == $employee['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($employee['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <option value="">All Months</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $monthFilter == $i ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="year" class="form-label">Year</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">All Years</option>
                        <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                        <option value="<?= $i ?>" <?= $yearFilter == $i ? 'selected' : '' ?>>
                            <?= $i ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="salaries.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>
    
    <!-- Salary Records -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Salary Records</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateSalaryModal">
                <i class="fas fa-plus"></i> Generate Salary
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($salaries)): ?>
                <div class="alert alert-info">No salary records found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Employee</th>
                                <th>Period</th>
                                <th>Basic Salary</th>
                                <th>Allowances</th>
                                <th>Deductions</th>
                                <th>Bonus</th>
                                <th>Tax</th>
                                <th>Net Salary</th>
                                <th>Status</th>
                                <th>Payment Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($salaries as $salary): 
                                $monthName = date('F', mktime(0, 0, 0, $salary['month'], 1));
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($salary['employee_name']) ?></strong><br>
                                    <small><strong>Position:</strong> <?= htmlspecialchars($salary['employee_position'] ?: 'Not provided') ?></small><br>
                                    <small><strong>Department:</strong> <?= htmlspecialchars($salary['employee_department'] ?: 'Not provided') ?></small><br>
                                    <small><strong>Email:</strong> <?= htmlspecialchars($salary['employee_email'] ?: 'Not provided') ?></small>
                                </td>
                                <td><?= "$monthName {$salary['year']}" ?></td>
                                <td class="text-end"><?= number_format($salary['basic_salary'], 2) ?></td>
                                <td class="text-end"><?= number_format($salary['allowances'], 2) ?></td>
                                <td class="text-end"><?= number_format($salary['deductions'], 2) ?></td>
                                <td class="text-end"><?= number_format($salary['bonus'], 2) ?></td>
                                <td class="text-end"><?= number_format($salary['tax'], 2) ?></td>
                                <td class="text-end"><strong><?= number_format($salary['net_salary'], 2) ?></strong></td>
                                <td>
                                    <span class="badge bg-<?= $salary['status'] === 'paid' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($salary['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($salary['status'] === 'paid'): ?>
                                        <small>
                                            <strong>Date:</strong> <?= date('M d, Y', strtotime($salary['payment_date'])) ?><br>
                                            <strong>Method:</strong> <?= htmlspecialchars(ucfirst($salary['payment_method'] ?: 'Not provided')) ?><br>
                                            <?php if ($salary['transaction_ref']): ?>
                                                <strong>Ref:</strong> <?= htmlspecialchars($salary['transaction_ref']) ?>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-<?= $salary['status'] === 'paid' ? 'danger' : 'primary' ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#markPaidModal" 
                                                data-salaryid="<?= $salary['id'] ?>"
                                                data-status="<?= $salary['status'] ?>">
                                            <i class="fas fa-<?= $salary['status'] === 'paid' ? 'times-circle' : 'check-circle' ?>"></i>
                                            <?= $salary['status'] === 'paid' ? 'Unpay' : 'Pay' ?>
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editSalaryModal" 
                                                data-salaryid="<?= $salary['id'] ?>"
                                                data-basicsalary="<?= $salary['basic_salary'] ?>"
                                                data-allowances="<?= $salary['allowances'] ?>"
                                                data-deductions="<?= $salary['deductions'] ?>"
                                                data-bonus="<?= $salary['bonus'] ?>"
                                                data-tax="<?= $salary['tax'] ?>"
                                                data-notes="<?= htmlspecialchars($salary['notes'] ?? '') ?>" 
                                                <?= $salary['status'] === 'paid' ? 'disabled' : '' ?>>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-secondary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#salaryDetailsModal" 
                                                data-employeename="<?= htmlspecialchars($salary['employee_name']) ?>"
                                                data-email="<?= htmlspecialchars($salary['employee_email'] ?: 'Not provided') ?>"
                                                data-phone="<?= htmlspecialchars($salary['employee_phone'] ?: 'Not provided') ?>"
                                                data-position="<?= htmlspecialchars($salary['employee_position'] ?: 'Not provided') ?>"
                                                data-department="<?= htmlspecialchars($salary['employee_department'] ?: 'Not provided') ?>"
                                                data-bankaccount="<?= htmlspecialchars($salary['employee_bank_account'] ?: 'Not provided') ?>"
                                                data-joiningdate="<?= htmlspecialchars($salary['employee_joining_date'] ?: 'Not provided') ?>"
                                                data-emergencycontact="<?= htmlspecialchars($salary['employee_emergency_contact'] ?: 'Not provided') ?>"
                                                data-period="<?= "$monthName {$salary['year']}" ?>"
                                                data-basicsalary="<?= number_format($salary['basic_salary'], 2) ?>"
                                                data-allowances="<?= number_format($salary['allowances'], 2) ?>"
                                                data-deductions="<?= number_format($salary['deductions'], 2) ?>"
                                                data-bonus="<?= number_format($salary['bonus'], 2) ?>"
                                                data-tax="<?= number_format($salary['tax'], 2) ?>"
                                                data-netsalary="<?= number_format($salary['net_salary'], 2) ?>"
                                                data-status="<?= ucfirst($salary['status']) ?>"
                                                data-paymentinfo="<?= $salary['status'] === 'paid' ? 
                                                    'Paid on ' . date('M d, Y', strtotime($salary['payment_date'])) . 
                                                    ' via ' . ucfirst($salary['payment_method'] ?: 'Not provided') : 'Pending' ?>"
                                                data-transactionref="<?= htmlspecialchars($salary['transaction_ref'] ?: 'Not provided') ?>"
                                                data-notes="<?= htmlspecialchars($salary['notes'] ?? 'None') ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Generate Salary Modal -->
<div class="modal fade" id="generateSalaryModal" tabindex="-1" aria-labelledby="generateSalaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="generate_salary" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateSalaryModalLabel">Generate Salary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employee_id" class="form-label">Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?>" data-salary="<?= $employee['salary'] ?>">
                                    <?= htmlspecialchars($employee['name']) ?> 
                                    (<?= $employee['salary'] !== null ? '₹' . number_format($employee['salary'], 2) : 'No Salary' ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="month" class="form-label">Month</label>
                            <select class="form-select" id="month" name="month" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == date('n') ? 'selected' : '' ?>>
                                    <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year" required>
                                <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == date('Y') ? 'selected' : '' ?>>
                                    <?= $i ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="basic_salary" class="form-label">Basic Salary</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="basic_salary" name="basic_salary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="allowances" class="form-label">Allowances</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="allowances" name="allowances" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="deductions" class="form-label">Deductions</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="deductions" name="deductions" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="bonus" class="form-label">Bonus</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="bonus" name="bonus" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="tax" class="form-label">Tax</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="tax" name="tax" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="net_salary" class="form-label">Net Salary</label>
                        <input type="number" step="0.01" class="form-control" id="net_salary" name="net_salary" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Salary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Mark as Paid/Unpaid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="markPaidForm">
                <input type="hidden" name="mark_paid" value="1">
                <input type="hidden" name="salary_id" id="paid_salary_id">
                <input type="hidden" name="status" id="paid_status">
                <div class="modal-header">
                    <h5 class="modal-title" id="markPaidModalLabel">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <?php foreach ($paymentMethods as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_ref" class="form-label">Transaction Reference (Optional)</label>
                        <input type="text" class="form-control" id="transaction_ref" name="transaction_ref">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="markPaidButton">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Salary Modal -->
<div class="modal fade" id="editSalaryModal" tabindex="-1" aria-labelledby="editSalaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="update_salary" value="1">
                <input type="hidden" name="salary_id" id="edit_salary_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSalaryModalLabel">Edit Salary Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_basic_salary" class="form-label">Basic Salary</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="edit_basic_salary" name="basic_salary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_allowances" class="form-label">Allowances</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_allowances" name="allowances" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_deductions" class="form-label">Deductions</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_deductions" name="deductions" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_bonus" class="form-label">Bonus</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_bonus" name="bonus" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="edit_tax" class    <label for="edit_tax" class="form-label">Tax</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_tax" name="tax" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_net_salary" class="form-label">Net Salary</label>
                        <input type="number" step="0.01" class="form-control" id="edit_net_salary" name="net_salary" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Salary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Salary Details Modal -->
<div class="modal fade" id="salaryDetailsModal" tabindex="-1" aria-labelledby="salaryDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salaryDetailsModalLabel">Salary Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Employee:</strong> <span id="detail_employee_name"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Period:</strong> <span id="detail_period"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Email:</strong> <span id="detail_email"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Phone:</strong> <span id="detail_phone"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Position:</strong> <span id="detail_position"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Department:</strong> <span id="detail_department"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Bank Account:</strong> <span id="detail_bank_account"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Joining Date:</strong> <span id="detail_joining_date"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Emergency Contact:</strong> <span id="detail_emergency_contact"></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong> <span id="detail_status"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Payment Info:</strong> <span id="detail_payment_info"></span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Transaction Ref:</strong> <span id="detail_transaction_ref"></span>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Basic Salary:</th>
                                <td id="detail_basic_salary" class="text-end"></td>
                            </tr>
                            <tr>
                                <th>Allowances:</th>
                                <td id="detail_allowances" class="text-end"></td>
                            </tr>
                            <tr>
                                <th>Bonus:</th>
                                <td id="detail_bonus" class="text-end"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th>Deductions:</th>
                                <td id="detail_deductions" class="text-end"></td>
                            </tr>
                            <tr>
                                <th>Tax:</th>
                                <td id="detail_tax" class="text-end"></td>
                            </tr>
                            <tr class="table-active">
                                <th>Net Salary:</th>
                                <td id="detail_net_salary" class="text-end"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
                    <strong>Notes:</strong>
                    <div id="detail_notes" class="p-2 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calculate net salary when employee is selected
    document.getElementById('employee_id').addEventListener('change', function() {
        const salary = parseFloat(this.options[this.selectedIndex].dataset.salary) || 0;
        document.getElementById('basic_salary').value = salary > 0 ? salary.toFixed(2) : '';
        calculateNetSalary();
    });

    // Calculate net salary when any amount field changes
    ['basic_salary', 'allowances', 'deductions', 'bonus', 'tax'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateNetSalary);
    });

    // Calculate net salary for edit modal
    ['edit_basic_salary', 'edit_allowances', 'edit_deductions', 'edit_bonus', 'edit_tax'].forEach(id => {
        document.getElementById(id).addEventListener('input', calculateEditNetSalary);
    });

    // Set salary ID and status in mark paid modal
    const markPaidModal = document.getElementById('markPaidModal');
    markPaidModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const salaryId = button.getAttribute('data-salaryid');
        const currentStatus = button.getAttribute('data-status');
        
        document.getElementById('paid_salary_id').value = salaryId;
        document.getElementById('paid_status').value = currentStatus === 'paid' ? 'pending' : 'paid';
        
        const modalTitle = document.getElementById('markPaidModalLabel');
        const submitButton = document.getElementById('markPaidButton');
        
        if (currentStatus === 'paid') {
            modalTitle.textContent = 'Mark Salary as Unpaid';
            submitButton.textContent = 'Mark as Unpaid';
            submitButton.classList.replace('btn-primary', 'btn-danger');
        } else {
            modalTitle.textContent = 'Mark Salary as Paid';
            submitButton.textContent = 'Mark as Paid';
            submitButton.classList.replace('btn-danger', 'btn-primary');
        }
    });

    // Set salary data in edit modal
    const editSalaryModal = document.getElementById('editSalaryModal');
    editSalaryModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('edit_salary_id').value = button.getAttribute('data-salaryid');
        document.getElementById('edit_basic_salary').value = button.getAttribute('data-basicsalary');
        document.getElementById('edit_allowances').value = button.getAttribute('data-allowances');
        document.getElementById('edit_deductions').value = button.getAttribute('data-deductions');
        document.getElementById('edit_bonus').value = button.getAttribute('data-bonus');
        document.getElementById('edit_tax').value = button.getAttribute('data-tax');
        document.getElementById('edit_notes').value = button.getAttribute('data-notes');
        calculateEditNetSalary();
    });

    // Set salary details in view modal
    const salaryDetailsModal = document.getElementById('salaryDetailsModal');
    salaryDetailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('detail_employee_name').textContent = button.getAttribute('data-employeename');
        document.getElementById('detail_email').textContent = button.getAttribute('data-email');
        document.getElementById('detail_phone').textContent = button.getAttribute('data-phone');
        document.getElementById('detail_position').textContent = button.getAttribute('data-position');
        document.getElementById('detail_department').textContent = button.getAttribute('data-department');
        document.getElementById('detail_bank_account').textContent = button.getAttribute('data-bankaccount');
        document.getElementById('detail_joining_date').textContent = button.getAttribute('data-joiningdate');
        document.getElementById('detail_emergency_contact').textContent = button.getAttribute('data-emergencycontact');
        document.getElementById('detail_period').textContent = button.getAttribute('data-period');
        document.getElementById('detail_basic_salary').textContent = button.getAttribute('data-basicsalary');
        document.getElementById('detail_allowances').textContent = button.getAttribute('data-allowances');
        document.getElementById('detail_deductions').textContent = button.getAttribute('data-deductions');
        document.getElementById('detail_bonus').textContent = button.getAttribute('data-bonus');
        document.getElementById('detail_tax').textContent = button.getAttribute('data-tax');
        document.getElementById('detail_net_salary').textContent = button.getAttribute('data-netsalary');
        document.getElementById('detail_status').textContent = button.getAttribute('data-status');
        document.getElementById('detail_payment_info').textContent = button.getAttribute('data-paymentinfo');
        document.getElementById('detail_transaction_ref').textContent = button.getAttribute('data-transactionref');
        document.getElementById('detail_notes').textContent = button.getAttribute('data-notes');
    });

    // Calculate net salary function
    function calculateNetSalary() {
        const basic = parseFloat(document.getElementById('basic_salary').value) || 0;
        const allowances = parseFloat(document.getElementById('allowances').value) || 0;
        const deductions = parseFloat(document.getElementById('deductions').value) || 0;
        const bonus = parseFloat(document.getElementById('bonus').value) || 0;
        const tax = parseFloat(document.getElementById('tax').value) || 0;
        
        const netSalary = basic + allowances + bonus - deductions - tax;
        document.getElementById('net_salary').value = netSalary.toFixed(2);
    }

    // Calculate net salary for edit modal
    function calculateEditNetSalary() {
        const basic = parseFloat(document.getElementById('edit_basic_salary').value) || 0;
        const allowances = parseFloat(document.getElementById('edit_allowances').value) || 0;
        const deductions = parseFloat(document.getElementById('edit_deductions').value) || 0;
        const bonus = parseFloat(document.getElementById('edit_bonus').value) || 0;
        const tax = parseFloat(document.getElementById('edit_tax').value) || 0;
        
        const netSalary = basic + allowances + bonus - deductions - tax;
        document.getElementById('edit_net_salary').value = netSalary.toFixed(2);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
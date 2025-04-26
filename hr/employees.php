<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $username = sanitize($_POST['username']);
        $password = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);
        $position = sanitize($_POST['position']);
        $department = sanitize($_POST['department']);
        $salary = sanitize($_POST['salary']);
        $bankAccount = sanitize($_POST['bank_account']);
        $joiningDate = sanitize($_POST['joining_date']);
        $emergencyContact = sanitize($_POST['emergency_contact']);
        
        $stmt = $pdo->prepare("
            INSERT INTO employees 
            (name, email, phone, username, password, position, department, salary, bank_account, joining_date, emergency_contact)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name, $email, $phone, $username, $password, $position, $department, 
            $salary, $bankAccount, $joiningDate, $emergencyContact
        ]);
        
        redirect('employees.php', 'Employee added successfully', 'success');
    } elseif (isset($_POST['update_employee'])) {
        $id = sanitize($_POST['employee_id']);
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $position = sanitize($_POST['position']);
        $department = sanitize($_POST['department']);
        $salary = sanitize($_POST['salary']);
        $bankAccount = sanitize($_POST['bank_account']);
        $joiningDate = sanitize($_POST['joining_date']);
        $emergencyContact = sanitize($_POST['emergency_contact']);
        
        $stmt = $pdo->prepare("
            UPDATE employees 
            SET name = ?, email = ?, phone = ?, position = ?, department = ?, 
                salary = ?, bank_account = ?, joining_date = ?, emergency_contact = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $email, $phone, $position, $department, $salary, 
            $bankAccount, $joiningDate, $emergencyContact, $id
        ]);
        
        redirect('employees.php', 'Employee updated successfully', 'success');
    }
}

// Get all employees
$employees = $pdo->query("SELECT * FROM employees ORDER BY name")->fetchAll();
?>

<div class="container mt-4">
    <h2>Employee Management</h2>
    
    <div class="card mt-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs">
                <li class="nav-item">
                    <a class="nav-link active" href="#view" data-toggle="tab">View Employees</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#add" data-toggle="tab">Add Employee</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane active" id="view">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Position</th>
                                    <th>Department</th>
                                    <th>Salary</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                    <td>₹<?php echo number_format($employee['salary'], 2); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-toggle="modal" 
                                                data-target="#editEmployeeModal" 
                                                data-employeeid="<?php echo $employee['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($employee['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($employee['phone']); ?>"
                                                data-position="<?php echo htmlspecialchars($employee['position']); ?>"
                                                data-department="<?php echo htmlspecialchars($employee['department']); ?>"
                                                data-salary="<?php echo $employee['salary']; ?>"
                                                data-bankaccount="<?php echo htmlspecialchars($employee['bank_account']); ?>"
                                                data-joiningdate="<?php echo $employee['joining_date']; ?>"
                                                data-emergencycontact="<?php echo htmlspecialchars($employee['emergency_contact']); ?>">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane" id="add">
                    <form method="POST">
                        <input type="hidden" name="add_employee" value="1">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="position">Position</label>
                                <input type="text" class="form-control" id="position" name="position" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="department">Department</label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="salary">Salary</label>
                                <input type="number" step="0.01" class="form-control" id="salary" name="salary" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="bank_account">Bank Account Number</label>
                                <input type="text" class="form-control" id="bank_account" name="bank_account">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="joining_date">Joining Date</label>
                                <input type="date" class="form-control" id="joining_date" name="joining_date">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact">Emergency Contact</label>
                            <input type="text" class="form-control" id="emergency_contact" name="emergency_contact">
                        </div>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="update_employee" value="1">
                <input type="hidden" name="employee_id" id="modal_employee_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="modal_name">Full Name</label>
                            <input type="text" class="form-control" id="modal_name" name="name" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="modal_email">Email</label>
                            <input type="email" class="form-control" id="modal_email" name="email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="modal_phone">Phone</label>
                            <input type="text" class="form-control" id="modal_phone" name="phone" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="modal_position">Position</label>
                            <input type="text" class="form-control" id="modal_position" name="position" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="modal_department">Department</label>
                            <input type="text" class="form-control" id="modal_department" name="department" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="modal_salary">Salary</label>
                            <input type="number" step="0.01" class="form-control" id="modal_salary" name="salary" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="modal_bank_account">Bank Account Number</label>
                            <input type="text" class="form-control" id="modal_bank_account" name="bank_account">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="modal_joining_date">Joining Date</label>
                            <input type="date" class="form-control" id="modal_joining_date" name="joining_date">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="modal_emergency_contact">Emergency Contact</label>
                        <input type="text" class="form-control" id="modal_emergency_contact" name="emergency_contact">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Set employee data in modal when edit button is clicked
$('#editEmployeeModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    
    $('#modal_employee_id').val(button.data('employeeid'));
    $('#modal_name').val(button.data('name'));
    $('#modal_email').val(button.data('email'));
    $('#modal_phone').val(button.data('phone'));
    $('#modal_position').val(button.data('position'));
    $('#modal_department').val(button.data('department'));
    $('#modal_salary').val(button.data('salary'));
    $('#modal_bank_account').val(button.data('bankaccount'));
    $('#modal_joining_date').val(button.data('joiningdate'));
    $('#modal_emergency_contact').val(button.data('emergencycontact'));
});
</script>

<?php include '../includes/footer.php'; ?>
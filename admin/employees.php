<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Employee Management";

// Flags for modal handling
$showModal = false;
$editEmployee = null;

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('employees.php', 'Employee deleted successfully', 'success');
    } else {
        redirect('employees.php', 'Failed to delete employee', 'danger');
    }
}

// Handle add/edit submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $username = sanitize($_POST['username']);
    $position = sanitize($_POST['position']);
    $department = sanitize($_POST['department']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    if (!empty($_POST['employee_id'])) {
        // Edit
        $employeeId = (int)$_POST['employee_id'];

        if ($password) {
            $stmt = $pdo->prepare("UPDATE employees SET name=?, email=?, phone=?, username=?, position=?, department=?, password=? WHERE id=?");
            $params = [$name, $email, $phone, $username, $position, $department, $password, $employeeId];
        } else {
            $stmt = $pdo->prepare("UPDATE employees SET name=?, email=?, phone=?, username=?, position=?, department=? WHERE id=?");
            $params = [$name, $email, $phone, $username, $position, $department, $employeeId];
        }

        if ($stmt->execute($params)) {
            redirect('employees.php', 'Employee updated successfully', 'success');
        } else {
            redirect('employees.php', 'Failed to update employee', 'danger');
        }
    } else {
        // Add
        if (empty($_POST['password'])) {
            redirect('employees.php', 'Password is required for new employee', 'danger');
        }

        $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, username, password, position, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $username, $password, $position, $department])) {
            redirect('employees.php', 'Employee added successfully', 'success');
        } else {
            redirect('employees.php', 'Failed to add employee', 'danger');
        }
    }
}

// Pagination logic
$perPage = 10;
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$totalPages = ceil($totalEmployees / $perPage);
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($currentPage - 1) * $perPage;

// Get employee list
$employees = $pdo->query("SELECT * FROM employees ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();

// Handle edit button
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$editId]);
    $editEmployee = $stmt->fetch();
    $showModal = true;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Employee Management</h2>
    <a href="?add=1" class="btn btn-primary">Add New Employee</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= $employee['id'] ?></td>
                        <td><?= $employee['name'] ?></td>
                        <td><?= $employee['email'] ?></td>
                        <td><?= $employee['username'] ?></td>
                        <td><?= $employee['position'] ?></td>
                        <td><?= $employee['department'] ?></td>
                        <td>
                            <a href="?edit=<?= $employee['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="?delete=<?= $employee['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this employee?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $currentPage - 1 ?>">Previous</a></li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $currentPage + 1 ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal HTML -->
<div class="modal fade" id="employeeModal" tabindex="-1" aria-labelledby="employeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $editEmployee ? 'Edit Employee' : 'Add New Employee' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if ($editEmployee): ?>
                    <input type="hidden" name="employee_id" value="<?= $editEmployee['id'] ?>">
                <?php endif; ?>
                <div class="mb-3"><label>Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $editEmployee['name'] ?? '' ?>" required>
                </div>
                <div class="mb-3"><label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $editEmployee['email'] ?? '' ?>" required>
                </div>
                <div class="mb-3"><label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= $editEmployee['phone'] ?? '' ?>">
                </div>
                <div class="mb-3"><label>Username</label>
                    <input type="text" name="username" class="form-control" value="<?= $editEmployee['username'] ?? '' ?>" required>
                </div>
                <div class="mb-3"><label>Position</label>
                    <input type="text" name="position" class="form-control" value="<?= $editEmployee['position'] ?? '' ?>">
                </div>
                <div class="mb-3"><label>Department</label>
                    <input type="text" name="department" class="form-control" value="<?= $editEmployee['department'] ?? '' ?>">
                </div>
                <div class="mb-3"><label>Password</label>
                    <input type="password" name="password" class="form-control">
                    <?php if ($editEmployee): ?>
                        <small class="text-muted">Leave blank to keep current password.</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit"><?= $editEmployee ? 'Update' : 'Add' ?> Employee</button>
            </div>
        </form>
    </div>
</div>

<!-- Auto show modal on add/edit -->
<?php if (isset($_GET['add']) || $showModal): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('employeeModal')).show();
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

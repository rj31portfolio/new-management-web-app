<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "HR Management";

// Get HR system statistics
$employeesCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$hrCount = $pdo->query("SELECT COUNT(*) FROM hr")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$pendingSalaries = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();

// Handle HR staff management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_hr'])) {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO hr (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $password])) {
            $alert = ['type' => 'success', 'message' => 'HR staff added successfully'];
        } else {
            $alert = ['type' => 'danger', 'message' => 'Failed to add HR staff'];
        }
    }
}

// Get all HR staff
$hrStaff = $pdo->query("SELECT * FROM hr ORDER BY username")->fetchAll();

// Get recent HR activities
$activities = $pdo->query("
    (SELECT 'leave' as type, CONCAT('Leave request from employee #', employee_id) as name, created_at FROM leaves ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'attendance' as type, CONCAT('Attendance marked for employee #', employee_id) as name, check_in as created_at FROM attendance ORDER BY check_in DESC LIMIT 3)
    UNION ALL
    (SELECT 'salary' as type, CONCAT('Salary processed for employee #', employee_id) as name, created_at FROM salaries ORDER BY created_at DESC LIMIT 3)
    ORDER BY created_at DESC LIMIT 5
")->fetchAll();
?>

<div class="container mt-4">
    <h2 class="mb-4">HR Management System</h2>
    
    <?php if (isset($alert)): ?>
        <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
            <?= $alert['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- HR System Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Employees</h6>
                            <h2 class="mb-0"><?= $employeesCount ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <a href="employees.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">HR Staff</h6>
                            <h2 class="mb-0"><?= $hrCount ?></h2>
                        </div>
                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pending Leaves</h6>
                            <h2 class="mb-0"><?= $pendingLeaves ?></h2>
                        </div>
                        <i class="fas fa-calendar-minus fa-3x opacity-50"></i>
                    </div>
                    <a href="hr/leaves.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pending Salaries</h6>
                            <h2 class="mb-0"><?= $pendingSalaries ?></h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                    <a href="hr/salaries.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Today's Attendance</h6>
                            <h2 class="mb-0"><?= $todayAttendance ?></h2>
                        </div>
                        <i class="fas fa-user-clock fa-3x opacity-50"></i>
                    </div>
                    <a href="hr/attendance.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card bg-purple text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Client Followups</h6>
                            <h2 class="mb-0"><?= $pdo->query("SELECT COUNT(*) FROM client_followups WHERE status = 'pending'")->fetchColumn() ?></h2>
                        </div>
                        <i class="fas fa-handshake fa-3x opacity-50"></i>
                    </div>
                    <a href="hr/followups.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- HR Staff Management -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">HR Staff Management</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Add New HR Staff</h6>
                    <form method="POST">
                        <input type="hidden" name="add_hr" value="1">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle me-2"></i> Add HR Staff
                        </button>
                    </form>
                </div>
                
                <div class="col-md-6">
                    <h6>Current HR Staff</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hrStaff as $hr): ?>
                                <tr>
                                    <td><?= htmlspecialchars($hr['username']) ?></td>
                                    <td><?= htmlspecialchars($hr['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($hr['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Access to HR Functions -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">HR System Quick Access</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="hr/employees.php" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-users me-2"></i> Manage Employees
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="hr/leaves.php" class="btn btn-outline-warning w-100 py-3">
                        <i class="fas fa-calendar-minus me-2"></i> Manage Leaves
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="hr/attendance.php" class="btn btn-outline-info w-100 py-3">
                        <i class="fas fa-user-clock me-2"></i> View Attendance
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="hr/salaries.php" class="btn btn-outline-danger w-100 py-3">
                        <i class="fas fa-money-bill-wave me-2"></i> Process Salaries
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="hr/followups.php" class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-handshake me-2"></i> Client Followups
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="hr/dashboard.php" class="btn btn-outline-dark w-100 py-3">
                        <i class="fas fa-tachometer-alt me-2"></i> HR Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent HR Activities -->
    <div class="card">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Recent HR Activities</h5>
        </div>
        <div class="card-body">
            <div class="list-group">
                <?php foreach ($activities as $activity): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <?php 
                            $icon = '';
                            switch($activity['type']) {
                                case 'leave': $icon = 'calendar-minus'; break;
                                case 'attendance': $icon = 'user-clock'; break;
                                case 'salary': $icon = 'money-bill-wave'; break;
                            }
                            ?>
                            <i class="fas fa-<?= $icon ?> me-2"></i>
                            <?= ucfirst($activity['type']) ?>: <?= htmlspecialchars($activity['name']) ?>
                        </h6>
                        <small class="text-muted"><?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-purple {
        background-color: #6f42c1 !important;
    }
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .btn-outline-primary:hover {
        background: linear-gradient(135deg, #007bff, #00d4ff);
        color: white;
    }
    .btn-outline-warning:hover {
        background: linear-gradient(135deg, #ffc107, #ff8c00);
        color: black;
    }
    .btn-outline-info:hover {
        background: linear-gradient(135deg, #17a2b8, #1de9b6);
        color: white;
    }
    .btn-outline-danger:hover {
        background: linear-gradient(135deg, #dc3545, #ff6b6b);
        color: white;
    }
    .btn-outline-success:hover {
        background: linear-gradient(135deg, #28a745, #34c759);
        color: white;
    }
    .btn-outline-dark:hover {
        background: linear-gradient(135deg, #343a40, #6c757d);
        color: white;
    }
</style>

<?php include '../includes/footer.php'; ?>
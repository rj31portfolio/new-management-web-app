<?php
require_once '../../includes/header.php';
require_once '../../includes/auth.php';

checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "HR Dashboard";

$alert = [];

// Fetch HR overview stats
$employeesCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$hrCount = $pdo->query("SELECT COUNT(*) FROM hr")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$pendingSalaries = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();
$pendingFollowups = $pdo->query("SELECT COUNT(*) FROM client_followups WHERE status = 'pending'")->fetchColumn();
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
        background-color: #6f42c1;
        color: white;
    }
    .gradient-purple { background: linear-gradient(135deg, #6f42c1, #9c6bff); }
    .gradient-success { background: linear-gradient(135deg, #28a745, #34c759); }
    .gradient-warning { background: linear-gradient(135deg, #ffc107, #ff8c00); }
    .gradient-danger { background: linear-gradient(135deg, #dc3545, #ff6b6b); }
    .gradient-info { background: linear-gradient(135deg, #17a2b8, #1de9b6); }
    .gradient-primary { background: linear-gradient(135deg, #007bff, #00c4ff); }
    
    .card-icon {
        font-size: 2.5rem;
        opacity: 0.2;
        transition: all 0.3s ease;
    }
    .card:hover .card-icon {
        opacity: 0.3;
        transform: scale(1.1);
    }
    .btn-custom {
        transition: all 0.3s ease;
    }
    .btn-custom:hover {
        transform: translateY(-2px);
    }
</style>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show mt-4">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<div class="container mt-4">
    <h2 class="mb-4">HR Dashboard</h2>
    
    <!-- HR Overview Cards -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <a href="#" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-purple">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">HR Staff</h6>
                            <h2 class="mb-0"><?= $hrCount ?></h2>
                        </div>
                        <i class="fas fa-user-tie card-icon"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <a href="../employees.php" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-success">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Active Employees</h6>
                            <h2 class="mb-0"><?= $employeesCount ?></h2>
                        </div>
                        <i class="fas fa-users card-icon"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <a href="attendance.php" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-info">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Today's Attendance</h6>
                            <h2 class="mb-0"><?= $todayAttendance ?></h2>
                        </div>
                        <i class="fas fa-user-clock card-icon"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <a href="leaves.php" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-warning">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Pending Leaves</h6>
                            <h2 class="mb-0"><?= $pendingLeaves ?></h2>
                        </div>
                        <i class="fas fa-calendar-minus card-icon"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <a href="salaries.php" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-danger">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Pending Salaries</h6>
                            <h2 class="mb-0"><?= $pendingSalaries ?></h2>
                        </div>
                        <i class="fas fa-money-bill-wave card-icon"></i>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <a href="followups.php" class="card shadow h-100 text-white text-decoration-none">
                <div class="card-body gradient-primary">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-uppercase mb-1">Pending Followups</h6>
                            <h2 class="mb-0"><?= $pendingFollowups ?></h2>
                        </div>
                        <i class="fas fa-headset card-icon"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent Activities Section -->
    <!-- Recent Activities Section -->
<div class="card mt-4 shadow">
    <div class="card-header bg-secondary text-white">
        <i class="fas fa-clock me-2"></i>
        <span class="fw-bold">Recent Activities</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Date</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recentActivities = [];
                    $activities = $pdo->query("
                        SELECT 'Attendance' as type, check_in as date, CONCAT(e.name, ' checked in') as detail
                        FROM attendance a
                        JOIN employees e ON a.employee_id = e.id
                        WHERE DATE(check_in) = CURDATE()
                        UNION
                        SELECT 'Leave' as type, l.created_at as date, CONCAT(e.name, ' requested leave') as detail
                        FROM leaves l
                        JOIN employees e ON l.employee_id = e.id
                        WHERE DATE(l.created_at) = CURDATE()
                        UNION
                        SELECT 'Salary' as type, payment_date as date, CONCAT(e.name, ' salary processed') as detail
                        FROM salaries s
                        JOIN employees e ON s.employee_id = e.id
                        WHERE DATE(payment_date) = CURDATE()
                        ORDER BY date DESC
                        LIMIT 5
                    ")->fetchAll();
                    foreach ($activities as $activity) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($activity['type']) . "</td>";
                        echo "<td>" . date('Y-m-d H:i', strtotime($activity['date'])) . "</td>";
                        echo "<td>" . htmlspecialchars($activity['detail']) . "</td>";
                        echo "</tr>";
                    }
                    if (empty($activities)) {
                        echo "<tr><td colspan='3' class='text-muted'>No recent activities today.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<?php include '../../includes/footer.php'; ?>
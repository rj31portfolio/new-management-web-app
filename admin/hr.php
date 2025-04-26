<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "HR Management";

$alert = [];

// Fetch HR overview stats
$employeesCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$hrCount = $pdo->query("SELECT COUNT(*) FROM hr")->fetchColumn();
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$pendingSalaries = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();
?>

<style>
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        position: relative; /* Ensure stretched-link works */
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
    /* Ensure the entire card is clickable */
    .card-body {
        position: relative;
        z-index: 1; /* Ensure content is above the link */
    }
</style>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
        <?= $alert['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- HR Overview Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <a href="manage.php" class="card shadow h-100 text-white text-decoration-none">
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

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="employees.php" class="card shadow h-100 text-white text-decoration-none">
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

    <div class="col-xl-3 col-md-6 mb-4">
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

    <div class="col-xl-3 col-md-6 mb-4">
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

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="manage_attendance_salary.php" class="card shadow h-100 text-white text-decoration-none">
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
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header bg-secondary text-white">
                <i class="fas fa-info-circle me-2"></i>
                <span class="fw-bold">HR Management Overview</span>
            </div>
            <div class="card-body">
                <p class="text-muted">This section provides a centralized view of HR activities. Click on any card above to navigate to specific management pages for employees, leaves, salaries, and attendance. Ensure all actions are reviewed and approved as needed to maintain accurate records.</p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
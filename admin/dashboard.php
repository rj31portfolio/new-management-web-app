<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Admin Dashboard";

// Get counts for summary cards
$blogsCount = $pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$projectsCount = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$clientsCount = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$employeesCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$hrCount = $pdo->query("SELECT COUNT(*) FROM hr")->fetchColumn();
$inquiriesCount = $pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();

// HR System Stats
$pendingLeaves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$pendingSalaries = $pdo->query("SELECT COUNT(*) FROM salaries WHERE status = 'pending'")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_notification'])) {
    $clientId = (int)$_POST['notify_client_id'];
    $message = sanitize($_POST['notification_message']);
    $adminId = getUserId();

    $stmt = $pdo->prepare("SELECT email, name FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();

    if ($client) {
        $stmt = $pdo->prepare("INSERT INTO notifications (client_id, admin_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$clientId, $adminId, $message])) {
            $alert = ['type' => 'success', 'message' => 'Notification sent successfully.'];
        } else {
            $alert = ['type' => 'danger', 'message' => 'Failed to send notification.'];
        }
    } else {
        $alert = ['type' => 'danger', 'message' => 'Client not found.'];
    }
}

$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Get recent activities including HR, employee activities, and inquiries
$activities = $pdo->query("
    (SELECT 'blog' as type, title as name, created_at FROM blogs ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'project' as type, name, created_at FROM projects ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'client' as type, name, created_at FROM clients ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'leave' as type, CONCAT('Leave request from employee #', employee_id) as name, created_at FROM leaves ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'attendance' as type, CONCAT('Attendance marked for employee #', employee_id) as name, check_in as created_at FROM attendance ORDER BY check_in DESC LIMIT 2)
    UNION ALL
    (SELECT 'inquiry' as type, CONCAT('New inquiry from ', name) as name, created_at FROM enquiries ORDER BY created_at DESC LIMIT 2)
    ORDER BY created_at DESC LIMIT 10
")->fetchAll();
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
    }
    .gradient-primary { background: linear-gradient(135deg, #007bff, #00d4ff); }
    .gradient-success { background: linear-gradient(135deg, #28a745, #34c759); }
    .gradient-info { background: linear-gradient(135deg, #17a2b8, #1de9b6); }
    .gradient-warning { background: linear-gradient(135deg, #ffc107, #ff8c00); }
    .gradient-danger { background: linear-gradient(135deg, #dc3545, #ff6b6b); }
    .gradient-purple { background: linear-gradient(135deg, #6f42c1, #9c6bff); }
    .gradient-pink { background: linear-gradient(135deg, #e83e8c, #ff8fab); }
    .gradient-teal { background: linear-gradient(135deg, #20c997, #00b4d8); }
    
    .card-icon {
        font-size: 2.5rem;
        opacity: 0.2;
        transition: all 0.3s ease;
    }
    .card:hover .card-icon {
        opacity: 0.3;
        transform: scale(1.1);
    }
    
    .activity-item {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
    }
    .activity-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }
</style>

<?php if (!empty($alert)): ?>
    <div class="alert alert-<?= $alert['type'] ?> alert-dismissible fade show">
        <?= $alert['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Core System Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Blogs</h6>
                        <h2 class="mb-0"><?= $blogsCount ?></h2>
                    </div>
                    <i class="fas fa-blog card-icon"></i>
                </div>
                <a href="blogs.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Projects</h6>
                        <h2 class="mb-0"><?= $projectsCount ?></h2>
                    </div>
                    <i class="fas fa-project-diagram card-icon"></i>
                </div>
                <a href="projects.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Clients</h6>
                        <h2 class="mb-0"><?= $clientsCount ?></h2>
                    </div>
                    <i class="fas fa-users card-icon"></i>
                </div>
                <a href="clients.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-teal text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">New Inquiries</h6>
                        <h2 class="mb-0"><?= $inquiriesCount ?></h2>
                    </div>
                    <i class="fas fa-envelope card-icon"></i>
                </div>
                <a href="inquiries.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- HR System Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Employees</h6>
                        <h2 class="mb-0"><?= $employeesCount ?></h2>
                    </div>
                    <i class="fas fa-user-tie card-icon"></i>
                </div>
                <a href="employees.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-purple text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">HR Staff</h6>
                        <h2 class="mb-0"><?= $hrCount ?></h2>
                    </div>
                    <i class="fas fa-user-shield card-icon"></i>
                </div>
                <a href="hr/manage.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Pending Leaves</h6>
                        <h2 class="mb-0"><?= $pendingLeaves ?></h2>
                    </div>
                    <i class="fas fa-calendar-minus card-icon"></i>
                </div>
                <a href="hr/leaves.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-pink text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Pending Salaries</h6>
                        <h2 class="mb-0"><?= $pendingSalaries ?></h2>
                    </div>
                    <i class="fas fa-money-bill-wave card-icon"></i>
                </div>
                <a href="hr/salaries.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 gradient-secondary text-black">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-uppercase mb-1">Today's Attendance</h6>
                        <h2 class="mb-0"><?= $todayAttendance ?></h2>
                    </div>
                    <i class="fas fa-user-clock card-icon"></i>
                </div>
                <a href="hr/attendance.php" class="stretched-link"></a>
            </div>
        </div>
    </div>

    
</div>

<!-- Quick Actions Row -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-bell me-2"></i>
                <span class="fw-bold">Quick Notification</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="quick_notification" value="1">
                    <div class="mb-3">
                        <label class="form-label">Select Client</label>
                        <select name="notify_client_id" class="form-select" required>
                            <option value="">-- Select Client --</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="notification_message" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane me-2"></i> Send Notification
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <i class="fas fa-chart-line me-2"></i>
                <span class="fw-bold">HR Quick Stats</span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                <i class="fas fa-user-tie text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">HR Staff</h6>
                                <h4 class="mb-0"><?= $hrCount ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                <i class="fas fa-user-check text-success"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Active Employees</h6>
                                <h4 class="mb-0"><?= $employeesCount ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                <i class="fas fa-calendar-times text-warning"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Pending Leaves</h6>
                                <h4 class="mb-0"><?= $pendingLeaves ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                                <i class="fas fa-money-bill-wave text-danger"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Pending Salaries</h6>
                                <h4 class="mb-0"><?= $pendingSalaries ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="hr/dashboard.php" class="btn btn-info mt-2">
                    <i class="fas fa-arrow-right me-2"></i> Go to HR Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-history me-2"></i>
                <span class="fw-bold">Recent Activities</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <?php
                                $icon = '';
                                $color = '';
                                switch ($activity['type']) {
                                    case 'blog': $icon = 'fa-blog'; $color = 'text-primary'; break;
                                    case 'project': $icon = 'fa-project-diagram'; $color = 'text-success'; break;
                                    case 'client': $icon = 'fa-users'; $color = 'text-info'; break;
                                    case 'leave': $icon = 'fa-calendar-minus'; $color = 'text-warning'; break;
                                    case 'attendance': $icon = 'fa-user-clock'; $color = 'text-purple'; break;
                                    case 'inquiry': $icon = 'fa-envelope'; $color = 'text-teal'; break;
                                    default: $icon = 'fa-bell'; $color = 'text-secondary';
                                }
                                ?>
                                <tr class="activity-item">
                                    <td width="40">
                                        <i class="fas <?= $icon ?> <?= $color ?> fa-lg"></i>
                                    </td>
                                    <td>
                                        <strong><?= ucfirst($activity['type']) ?>:</strong>
                                        <?= htmlspecialchars($activity['name']) ?>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
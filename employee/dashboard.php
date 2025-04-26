<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "Employee Dashboard";
$employeeId = getUserId();

// Get employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

// Count tasks by status
$taskCounts = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM tasks 
    WHERE assigned_to = ? 
    GROUP BY status
");
$taskCounts->execute([$employeeId]);
$taskStatusCounts = $taskCounts->fetchAll(PDO::FETCH_KEY_PAIR);

// Ensure that status keys like 'pending', 'in_progress', 'completed' are safely accessed
$pendingCount = array_key_exists('pending', $taskStatusCounts) ? $taskStatusCounts['pending'] : 0;
$inProgressCount = array_key_exists('in_progress', $taskStatusCounts) ? $taskStatusCounts['in_progress'] : 0;
$completedCount = array_key_exists('completed', $taskStatusCounts) ? $taskStatusCounts['completed'] : 0;

// Get recent tasks
$recentTasks = $pdo->prepare("
    SELECT * FROM tasks 
    WHERE assigned_to = ? 
    ORDER BY due_date ASC 
    LIMIT 5
");
$recentTasks->execute([$employeeId]);
$tasks = $recentTasks->fetchAll();

// Get recent reports
$recentReports = $pdo->prepare("
    SELECT r.*, t.title as task_title 
    FROM daily_reports r 
    JOIN tasks t ON r.task_id = t.id 
    WHERE r.employee_id = ? 
    ORDER BY r.report_date DESC 
    LIMIT 5
");
$recentReports->execute([$employeeId]);
$reports = $recentReports->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h4>Welcome, <?php echo $employee['name']; ?>!</h4>
                <p class="mb-0">Position: <?php echo $employee['position']; ?> | Department: <?php echo $employee['department']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Tasks Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Pending Tasks</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $pendingCount; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                    </div>
                </div>
                <a href="tasks.php?status=pending" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- In Progress Tasks Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            In Progress</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $inProgressCount; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-spinner fa-2x text-gray-300"></i>
                    </div>
                </div>
                <a href="tasks.php?status=in_progress" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Completed Tasks Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Completed Tasks</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $completedCount; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
                <a href="tasks.php?status=completed" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <!-- Reports Card -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total Reports</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo $pdo->query("SELECT COUNT(*) FROM daily_reports WHERE employee_id = $employeeId")->fetchColumn(); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
                <a href="reports.php" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Tasks -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Tasks</h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($tasks as $task): ?>
                        <a href="task_details.php?id=<?php echo $task['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo $task['title']; ?></h6>
                                <small><?php echo date('M d', strtotime($task['due_date'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo substr($task['description'], 0, 50); ?>...</p>
                            <small>
                                <span class="badge bg-<?php 
                                    echo $task['priority'] === 'high' ? 'danger' : 
                                        ($task['priority'] === 'medium' ? 'warning' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($task['priority']); ?>
                                </span>
                                <span class="badge bg-<?php 
                                    echo $task['status'] === 'completed' ? 'success' : 
                                        ($task['status'] === 'in_progress' ? 'primary' : 'secondary'); 
                                ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($task['status'])); ?>
                                </span>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="tasks.php" class="btn btn-primary btn-block mt-3">View All Tasks</a>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Reports</h6>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($reports as $report): ?>
                        <a href="report_details.php?id=<?php echo $report['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo $report['task_title']; ?></h6>
                                <small><?php echo date('M d', strtotime($report['report_date'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo substr($report['work_description'], 0, 50); ?>...</p>
                            <small>
                                <span class="badge bg-<?php echo $report['status'] === 'submitted' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                                <span class="text-muted"><?php echo $report['hours_worked']; ?> hours</span>
                            </small>
                        </a>
                    <?php endforeach; ?>
                </div>
                <a href="reports.php" class="btn btn-primary btn-block mt-3">View All Reports</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

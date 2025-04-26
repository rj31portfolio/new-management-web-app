<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Daily Reports";

// Get task ID from query string if available, sanitize it
$taskId = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;

// Build WHERE clause for queries
$whereClause = $taskId > 0 ? "WHERE r.task_id = $taskId" : "";
$countWhere = $taskId > 0 ? "WHERE task_id = $taskId" : "";

// Get pagination data
$pagination = getPagination('daily_reports r', $countWhere);  // Specify the table alias for `r` here
$offset = $pagination['offset'];
$perPage = $pagination['perPage'];

// Get reports for current page
$stmt = $pdo->prepare("
    SELECT r.*, e.name as employee_name, t.title as task_title 
    FROM daily_reports r 
    JOIN employees e ON r.employee_id = e.id 
    JOIN tasks t ON r.task_id = t.id 
    $whereClause
    ORDER BY r.report_date DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();

// Get task details if viewing reports for a specific task
$task = null;
if ($taskId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <?php if ($task): ?>
        <h2>Daily Reports for Task: <?php echo $task['title']; ?></h2>
        <a href="task_details.php?id=<?php echo $taskId; ?>" class="btn btn-secondary">Back to Task</a>
    <?php else: ?>
        <h2>All Daily Reports</h2>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Task</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($report['report_date'])); ?></td>
                            <td><?php echo $report['employee_name']; ?></td>
                            <td><?php echo $report['task_title']; ?></td>
                            <td><?php echo $report['hours_worked']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $report['status'] === 'submitted' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($report['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="report_details.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($pagination['currentPage'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $taskId > 0 ? 'task_id='.$taskId.'&' : ''; ?>page=<?php echo $pagination['currentPage'] - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo $taskId > 0 ? 'task_id='.$taskId.'&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $taskId > 0 ? 'task_id='.$taskId.'&' : ''; ?>page=<?php echo $pagination['currentPage'] + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

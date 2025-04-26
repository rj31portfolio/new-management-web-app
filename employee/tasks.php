<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "My Tasks";
$employeeId = getUserId();

// Get status filter from query string
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build WHERE clause
$whereClause = "WHERE t.assigned_to = :employeeId";
$params = ['employeeId' => $employeeId];

if (!empty($statusFilter)) {
    $whereClause .= " AND t.status = :status";
    $params['status'] = $statusFilter;
}

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks t $whereClause");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$perPage = 10;
$totalPages = ceil($total / $perPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Get paginated tasks
$query = "
    SELECT t.*, a.username as assigned_by_name 
    FROM tasks t 
    JOIN admins a ON t.assigned_by = a.id 
    $whereClause
    ORDER BY due_date ASC 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Tasks</h2>
    <div>
        <a href="tasks.php" class="btn btn-outline-secondary <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
        <a href="tasks.php?status=pending" class="btn btn-outline-secondary <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
        <a href="tasks.php?status=in_progress" class="btn btn-outline-secondary <?php echo $statusFilter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
        <a href="tasks.php?status=completed" class="btn btn-outline-secondary <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Assigned By</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($task['title']); ?></td>
                                <td><?php echo htmlspecialchars($task['assigned_by_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($task['due_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $task['priority'] === 'high' ? 'danger' : 
                                            ($task['priority'] === 'medium' ? 'warning' : 'success'); 
                                    ?>">
                                        <?php echo ucfirst($task['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $task['status'] === 'completed' ? 'success' : 
                                            ($task['status'] === 'in_progress' ? 'primary' : 'secondary'); 
                                    ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($task['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="task_details.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="add_report.php?task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">Add Report</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No tasks found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

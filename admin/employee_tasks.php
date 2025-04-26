<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Employee Tasks";

// Get employee ID from query string
$employeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($employeeId === 0) {
    redirect('employees.php', 'Invalid employee ID', 'danger');
}

// Get employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    redirect('employees.php', 'Employee not found', 'danger');
}

// Get pagination data
$pagination = getPagination('tasks', "assigned_to = $employeeId");
$offset = $pagination['offset'];
$perPage = $pagination['perPage'];

// Get tasks for current page
$stmt = $pdo->prepare("
    SELECT t.*, a.username as assigned_by_name 
    FROM tasks t 
    JOIN admins a ON t.assigned_by = a.id 
    WHERE t.assigned_to = ? 
    ORDER BY t.due_date ASC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $employeeId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Tasks for <?php echo $employee['name']; ?></h2>
    <a href="tasks.php?assigned_to=<?php echo $employeeId; ?>" class="btn btn-primary">Assign New Task</a>
</div>

<div class="card">
    <div class="card-header">
        <h5>Employee Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> <?php echo $employee['name']; ?></p>
                <p><strong>Email:</strong> <?php echo $employee['email']; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
                <p><strong>Department:</strong> <?php echo $employee['department']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Assigned Tasks</h5>
    </div>
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
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?php echo $task['title']; ?></td>
                            <td><?php echo $task['assigned_by_name']; ?></td>
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
                                <a href="reports.php?task_id=<?php echo $task['id']; ?>" class="btn btn-sm btn-secondary">Reports</a>
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
                        <a class="page-link" href="?id=<?php echo $employeeId; ?>&page=<?php echo $pagination['currentPage'] - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?id=<?php echo $employeeId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?id=<?php echo $employeeId; ?>&page=<?php echo $pagination['currentPage'] + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
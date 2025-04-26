<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Task Management";

// Handle task deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('tasks.php', 'Task deleted successfully', 'success');
    } else {
        redirect('tasks.php', 'Failed to delete task', 'danger');
    }
}

// Handle task creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $assignedTo = (int)$_POST['assigned_to'];
    $dueDate = sanitize($_POST['due_date']);
    $priority = sanitize($_POST['priority']);
    $status = sanitize($_POST['status']);
    $adminId = getUserId();

    if (!empty($_POST['task_id'])) {
        // Update task
        $taskId = (int)$_POST['task_id'];
        $stmt = $pdo->prepare("UPDATE tasks SET title=?, description=?, assigned_to=?, due_date=?, priority=?, status=? WHERE id=?");
        if ($stmt->execute([$title, $description, $assignedTo, $dueDate, $priority, $status, $taskId])) {
            redirect('tasks.php', 'Task updated successfully', 'success');
        } else {
            redirect('tasks.php', 'Failed to update task', 'danger');
        }
    } else {
        // Create task
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, priority, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $description, $assignedTo, $adminId, $dueDate, $priority, $status])) {
            redirect('tasks.php', 'Task created successfully', 'success');
        } else {
            redirect('tasks.php', 'Failed to create task', 'danger');
        }
    }
}

// Pagination
$pagination = getPagination('tasks');
$offset = max(0, $pagination['offset']);
$perPage = $pagination['perPage'];

// Fetch tasks
$stmt = $pdo->prepare("
    SELECT t.*, e.name as employee_name, a.username as assigned_by_name 
    FROM tasks t 
    JOIN employees e ON t.assigned_to = e.id 
    JOIN admins a ON t.assigned_by = a.id 
    ORDER BY t.due_date ASC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();

// Fetch employees
$employees = $pdo->query("SELECT id, name FROM employees ORDER BY name")->fetchAll();

// Get task to edit (if any)
$editTask = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$editId]);
    $editTask = $stmt->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Task Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal">Add New Task</button>
</div>

<div class="card">
    <div class="card-body">
        <!-- Task Table -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= $task['id'] ?></td>
                            <td><?= $task['title'] ?></td>
                            <td><?= $task['employee_name'] ?></td>
                            <td><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?=
                                    $task['priority'] === 'high' ? 'danger' :
                                    ($task['priority'] === 'medium' ? 'warning' : 'success')
                                ?>">
                                    <?= ucfirst($task['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?=
                                    $task['status'] === 'completed' ? 'success' :
                                    ($task['status'] === 'in_progress' ? 'primary' : 'secondary')
                                ?>">
                                    <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?= $task['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?= $task['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                <a href="task_details.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($pagination['currentPage'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $pagination['currentPage'] - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?= $i == $pagination['currentPage'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $pagination['currentPage'] + 1 ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Add/Edit Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskModalLabel"><?= $editTask ? 'Edit Task' : 'Add New Task' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="task_id" value="<?= $editTask['id'] ?? '' ?>">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="<?= $editTask['title'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= $editTask['description'] ?? '' ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="form-select" required>
                        <option value="">Select Employee</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= ($editTask && $editTask['assigned_to'] == $emp['id']) ? 'selected' : '' ?>>
                                <?= $emp['name'] ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control" value="<?= $editTask['due_date'] ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="low" <?= ($editTask && $editTask['priority'] === 'low') ? 'selected' : '' ?>>Low</option>
                        <option value="medium" <?= ($editTask && $editTask['priority'] === 'medium') ? 'selected' : '' ?>>Medium</option>
                        <option value="high" <?= ($editTask && $editTask['priority'] === 'high') ? 'selected' : '' ?>>High</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" <?= ($editTask && $editTask['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="in_progress" <?= ($editTask && $editTask['status'] === 'in_progress') ? 'selected' : '' ?>>In Progress</option>
                        <option value="completed" <?= ($editTask && $editTask['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="rejected" <?= ($editTask && $editTask['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><?= $editTask ? 'Update Task' : 'Create Task' ?></button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php if ($editTask): ?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        new bootstrap.Modal(document.getElementById('taskModal')).show();
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

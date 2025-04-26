<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Project Management";

// Handle project deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('projects.php', 'Project deleted successfully', 'success');
    } else {
        redirect('projects.php', 'Failed to delete project', 'danger');
    }
}

// Handle project creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = sanitize($_POST['name']);
    $clientId = (int)$_POST['client_id'];
    $startDate = sanitize($_POST['start_date']);
    $deadline = sanitize($_POST['deadline']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    $adminId = getUserId();
    $projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;

    if ($projectId) {
        // Update project
        $stmt = $pdo->prepare("UPDATE projects SET name = ?, client_id = ?, start_date = ?, deadline = ?, description = ?, status = ? WHERE id = ?");
        if ($stmt->execute([$name, $clientId, $startDate, $deadline, $description, $status, $projectId])) {
            // Handle file uploads after updating project
            if (!empty($_FILES['attachments']['name'][0])) {
                handleFileUploads($projectId);
            }
            redirect('projects.php', 'Project updated successfully', 'success');
        } else {
            redirect('projects.php', 'Failed to update project', 'danger');
        }
    } else {
        // Create new project
        $stmt = $pdo->prepare("INSERT INTO projects (name, client_id, start_date, deadline, description, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $clientId, $startDate, $deadline, $description, $status, $adminId])) {
            $projectId = $pdo->lastInsertId();
            // Handle file uploads after creating project
            if (!empty($_FILES['attachments']['name'][0])) {
                handleFileUploads($projectId);
            }
            redirect('projects.php', 'Project created successfully', 'success');
        } else {
            redirect('projects.php', 'Failed to create project', 'danger');
        }
    }
}

// Handle file uploads for project
function handleFileUploads($projectId) {
    global $pdo;

    foreach ($_FILES['attachments']['name'] as $key => $name) {
        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $name,
                'type' => $_FILES['attachments']['type'][$key],
                'tmp_name' => $_FILES['attachments']['tmp_name'][$key],
                'error' => $_FILES['attachments']['error'][$key],
                'size' => $_FILES['attachments']['size'][$key]
            ];

            $upload = uploadFile($file, '../uploads/project_files/');
            if ($upload['success']) {
                $stmt = $pdo->prepare("INSERT INTO project_attachments (project_id, file_path, original_name) VALUES (?, ?, ?)");
                $stmt->execute([$projectId, $upload['file_path'], $upload['original_name']]);
            }
        }
    }
}

// Get pagination data
$pagination = getPagination('projects');
$offset = max(0, $pagination['offset']);
$perPage = max(1, $pagination['perPage']);

// Get projects for current page
$stmt = $pdo->prepare("
    SELECT p.*, c.name as client_name, a.username as created_by_name 
    FROM projects p 
    JOIN clients c ON p.client_id = c.id 
    JOIN admins a ON p.created_by = a.id 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

// Get clients for dropdown
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Get project data for editing
$editProject = null;
$editAttachments = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$editId]);
    $editProject = $stmt->fetch();

    // Get attachments for the project
    $stmt = $pdo->prepare("SELECT * FROM project_attachments WHERE project_id = ?");
    $stmt->execute([$editId]);
    $editAttachments = $stmt->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Project Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="resetModal()">Add New Project</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Client</th>
                        <th>Start Date</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['id']; ?></td>
                            <td><?php echo $project['name']; ?></td>
                            <td><?php echo $project['client_name']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['deadline'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="editProject(<?php echo $project['id']; ?>)">Edit</button>
                                <a href="?delete=<?php echo $project['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
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
                        <a class="page-link" href="?page=<?php echo $pagination['currentPage'] - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['currentPage'] + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalLabel">Add New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="project_id" value="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Project Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="" required>
                    </div>
                    <div class="mb-3">
                        <label for="client_id" class="form-label">Client</label>
                        <select class="form-select" id="client_id" name="client_id" required>
                            <option value="">Select Client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo $client['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" value="" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="running">Running</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="attachments" class="form-label">Attachments</label>
                        <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Reset modal fields when clicking "Add New Project"
    function resetModal() {
        document.getElementById('project_id').value = '';
        document.getElementById('name').value = '';
        document.getElementById('client_id').value = '';
        document.getElementById('start_date').value = '';
        document.getElementById('deadline').value = '';
        document.getElementById('description').value = '';
        document.getElementById('status').value = 'running';
    }

    // Populate modal with project data for editing
    function editProject(id) {
        <?php foreach ($projects as $project): ?>
            if (id === <?php echo $project['id']; ?>) {
                document.getElementById('project_id').value = <?php echo $project['id']; ?>;
                document.getElementById('name').value = "<?php echo $project['name']; ?>";
                document.getElementById('client_id').value = <?php echo $project['client_id']; ?>;
                document.getElementById('start_date').value = "<?php echo $project['start_date']; ?>";
                document.getElementById('deadline').value = "<?php echo $project['deadline']; ?>";
                document.getElementById('description').value = "<?php echo $project['description']; ?>";
                document.getElementById('status').value = "<?php echo $project['status']; ?>";
            }
        <?php endforeach; ?>
    }
</script>

<?php
require_once '../includes/footer.php';
?>

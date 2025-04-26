<?php
require_once '../includes/header.php';
checkAuth('client');

$pdo = getDatabase();
$pageTitle = "Project Details";
$clientId = getUserId();

// Get project ID from query string
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($projectId === 0) {
    redirect('projects.php', 'Invalid project ID', 'danger');
}

// Get project details
$stmt = $pdo->prepare("
    SELECT p.*, c.name as client_name 
    FROM projects p 
    JOIN clients c ON p.client_id = c.id 
    WHERE p.id = ? AND p.client_id = ?
");
$stmt->execute([$projectId, $clientId]);
$project = $stmt->fetch();

if (!$project) {
    redirect('projects.php', 'Project not found or access denied', 'danger');
}

// Get project attachments
$stmt = $pdo->prepare("SELECT * FROM project_attachments WHERE project_id = ?");
$stmt->execute([$projectId]);
$attachments = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h4><?php echo $project['name']; ?></h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Project Information</h6>
                    <hr>
                    <p><strong>Client:</strong> <?php echo $project['client_name']; ?></p>
                    <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($project['start_date'])); ?></p>
                    <p><strong>Deadline:</strong> <?php echo date('M d, Y', strtotime($project['deadline'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Description</h6>
                    <hr>
                    <p><?php echo nl2br($project['description']); ?></p>
                </div>
            </div>
        </div>
        
        <?php if (!empty($attachments)): ?>
            <div class="mb-3">
                <h6>Attachments</h6>
                <hr>
                <div class="list-group">
                    <?php foreach ($attachments as $attachment): ?>
                        <a href="<?php echo $attachment['file_path']; ?>" target="_blank" class="list-group-item list-group-item-action">
                            <?php echo $attachment['original_name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="projects.php" class="btn btn-secondary">Back to Projects</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
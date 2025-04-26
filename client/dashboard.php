<?php
require_once '../includes/header.php';
checkAuth('client');

$pdo = getDatabase();
$pageTitle = "Client Dashboard";
$clientId = getUserId();

// Get client's projects
$stmt = $pdo->prepare("
    SELECT p.*, c.name as client_name 
    FROM projects p 
    JOIN clients c ON p.client_id = c.id 
    WHERE p.client_id = ? 
    ORDER BY p.deadline ASC
");
$stmt->execute([$clientId]);
$projects = $stmt->fetchAll();

// Count projects by status
$runningProjects = $completedProjects = 0;
foreach ($projects as $project) {
    if ($project['status'] === 'running') {
        $runningProjects++;
    } else {
        $completedProjects++;
    }
}

// Get unread notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE client_id = ? AND is_read = FALSE");
$stmt->execute([$clientId]);
$unreadNotifications = $stmt->fetchColumn();

// Get latest unread notification message
$stmt = $pdo->prepare("SELECT message FROM notifications WHERE client_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$clientId]);
$latestNotification = $stmt->fetch();
?>

<!-- Admin Notification Popup -->
<?php if ($latestNotification): ?>
<div id="admin-popup" class="admin-popup shadow" style="
    position: fixed;
    top: 20px;
    right: 20px;
    max-width: 350px;
    background: linear-gradient(145deg, #007bff, #0056b3);
    color: white;
    border-radius: 10px;
    padding: 20px;
    z-index: 2000;
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    animation: slideIn 0.5s ease;
">
    <strong>📢 Message from Admin</strong>
    <p class="mb-2"><?php echo htmlspecialchars($latestNotification['message']); ?></p>
    <a href="notifications.php" class="btn btn-sm btn-light">View All</a>
</div>
<style>
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
<?php endif; ?>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Running Projects</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $runningProjects; ?></h5>
                <p class="card-text">Projects in progress</p>
                <a href="projects.php" class="btn btn-light">View Projects</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Completed Projects</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $completedProjects; ?></h5>
                <p class="card-text">Finished projects</p>
                <a href="projects.php" class="btn btn-light">View Projects</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-header">Notifications</div>
            <div class="card-body">
                <h5 class="card-title"><?php echo $unreadNotifications; ?></h5>
                <p class="card-text">Unread notifications</p>
                <a href="notifications.php" class="btn btn-light">View Notifications</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>Recent Projects</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Start Date</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($projects, 0, 5) as $project): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($project['name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($project['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($project['deadline'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">View</a>
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

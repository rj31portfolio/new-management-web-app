<?php
require_once '../includes/header.php';
checkAuth('client');

$pdo = getDatabase();
$pageTitle = "My Projects";
$clientId = getUserId();

// Get pagination data
$pagination = getPagination('projects', "client_id = $clientId");
$offset = $pagination['offset'];
$perPage = $pagination['perPage'];

// Get projects for current page
$stmt = $pdo->prepare("
    SELECT * FROM projects 
    WHERE client_id = ? 
    ORDER BY deadline ASC 
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $clientId, PDO::PARAM_INT);
$stmt->bindValue(2, $perPage, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h4>My Projects</h4>
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
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['name']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['start_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($project['deadline'])); ?></td>
                            <td>
                            <span class="badge bg-<?php echo $project['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info">View Details</a>
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

<?php include '../includes/footer.php'; ?>

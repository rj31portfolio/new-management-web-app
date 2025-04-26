<?php
require_once '../includes/header.php';
checkAuth('employee');

$pdo = getDatabase();
$pageTitle = "My Daily Reports";
$employeeId = getUserId();

// Get filter from URL
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;

// Build WHERE clause
$where = "WHERE r.employee_id = :employee_id";
$params = [':employee_id' => $employeeId];

if ($statusFilter === 'draft' || $statusFilter === 'submitted') {
    $where .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

// Count total rows for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_reports r $where");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();

// Set pagination
$perPage = 10;
$offset = ($currentPage - 1) * $perPage;
$totalPages = ceil($totalRows / $perPage);

// Fetch reports
$sql = "
    SELECT r.*, t.title AS task_title 
    FROM daily_reports r 
    JOIN tasks t ON r.task_id = t.id 
    $where 
    ORDER BY r.report_date DESC 
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>My Daily Reports</h2>
    <div>
        <a href="reports.php" class="btn btn-outline-secondary <?= empty($statusFilter) ? 'active' : ''; ?>">All</a>
        <a href="reports.php?status=draft" class="btn btn-outline-secondary <?= $statusFilter === 'draft' ? 'active' : ''; ?>">Drafts</a>
        <a href="reports.php?status=submitted" class="btn btn-outline-secondary <?= $statusFilter === 'submitted' ? 'active' : ''; ?>">Submitted</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Task</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($report['report_date'])); ?></td>
                                <td><?= htmlspecialchars($report['task_title']); ?></td>
                                <td><?= htmlspecialchars($report['hours_worked']); ?></td>
                                <td>
                                    <span class="badge bg-<?= $report['status'] === 'submitted' ? 'success' : 'secondary'; ?>">
                                        <?= ucfirst($report['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="report_details.php?id=<?= $report['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <?php if ($report['status'] === 'draft'): ?>
                                        <a href="edit_report.php?id=<?= $report['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No reports found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(['status' => $statusFilter, 'page' => $currentPage - 1]); ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?= http_build_query(['status' => $statusFilter, 'page' => $i]); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(['status' => $statusFilter, 'page' => $currentPage + 1]); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<?php
require_once '../includes/header.php';
checkAuth('client');

$pdo = getDatabase();
$pageTitle = "My Notifications";
$clientId = getUserId();

// Mark all notifications as read
$pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE client_id = ?")->execute([$clientId]);

// Get pagination data
$pagination = getPagination('notifications', "client_id = $clientId");

// ✅ Ensure non-negative values
$offset = max(0, (int)$pagination['offset']);
$perPage = max(1, (int)$pagination['perPage']);

// ✅ Build SQL with LIMIT/OFFSET as literals (safe because they’re integers from backend logic)
$sql = "
    SELECT n.*, a.username AS admin_name 
    FROM notifications n 
    JOIN admins a ON n.admin_id = a.id 
    WHERE n.client_id = :client_id 
    ORDER BY n.created_at DESC 
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h4>My Notifications</h4>
    </div>
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">You have no notifications.</div>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">From: <?php echo htmlspecialchars($notification['admin_name']); ?></h6>
                            <small><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <nav class="mt-3">
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
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

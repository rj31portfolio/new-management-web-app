<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Client Notifications";

// Get client ID from query string
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Get client details
$client = null;
if ($clientId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        redirect('clients.php', 'Client not found', 'danger');
    }
}

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = sanitize($_POST['message']);
    $clientId = (int)$_POST['client_id'];
    $adminId = getUserId();
    
    $stmt = $pdo->prepare("INSERT INTO notifications (client_id, admin_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$clientId, $adminId, $message])) {
        redirect('clients.php', 'Notification sent successfully', 'success');
    } else {
        redirect('clients.php', 'Failed to send notification', 'danger');
    }
}

// Get all clients for dropdown
$clients = $pdo->query("SELECT id, name, username FROM clients ORDER BY name")->fetchAll();

// Get notification history for selected client
$notifications = [];
if ($clientId > 0) {
    $stmt = $pdo->prepare("
        SELECT n.*, a.username as admin_name 
        FROM notifications n 
        JOIN admins a ON n.admin_id = a.id 
        WHERE n.client_id = ? 
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$clientId]);
    $notifications = $stmt->fetchAll();
}
?>

<div class="card">
    <div class="card-header">
        <h4>Send Notification to Client</h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="client_id" class="form-label">Select Client</label>
                <select class="form-select" id="client_id" name="client_id" required onchange="if(this.value) window.location.href='notifications.php?client_id='+this.value">
                    <option value="">Select a client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $clientId === $c['id'] ? 'selected' : ''; ?>>
                            <?php echo $c['name']; ?> (<?php echo $c['username']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if ($client): ?>
                <div class="mb-3">
                    <label for="message" class="form-label">Notification Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Notification</button>
            <?php endif; ?>
        </form>
        
        <?php if ($client && !empty($notifications)): ?>
            <hr>
            <h5 class="mt-4">Notification History</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Message</th>
                            <th>Sent By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></td>
                                <td><?php echo substr($notification['message'], 0, 50); ?>...</td>
                                <td><?php echo $notification['admin_name']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $notification['is_read'] ? 'success' : 'warning'; ?>">
                                        <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#notificationModal<?php echo $notification['id']; ?>">View</button>
                                </td>
                            </tr>
                            
                            <!-- Notification Content Modal -->
                            <div class="modal fade" id="notificationModal<?php echo $notification['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Notification Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?></p>
                                            <p><strong>Status:</strong> <?php echo $notification['is_read'] ? 'Read' : 'Unread'; ?></p>
                                            <p><strong>Sent By:</strong> <?php echo $notification['admin_name']; ?></p>
                                            <hr>
                                            <div><?php echo nl2br($notification['message']); ?></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

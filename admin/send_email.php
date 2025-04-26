<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Send Email to Client";

$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Get all clients for dropdown
$clients = $pdo->query("SELECT id, name, email FROM clients ORDER BY name")->fetchAll();

$client = null;
if ($clientId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        redirect('clients.php', 'Client not found', 'danger');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    $clientId = (int)$_POST['client_id'];
    $adminId = getUserId();

    $stmt = $pdo->prepare("SELECT email FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $clientEmail = $stmt->fetchColumn();

    if (!$clientEmail) {
        redirect('clients.php', 'Client email not found.', 'danger');
    }

    // Attempt to send email
    $emailSent = sendEmail($clientEmail, $subject, $message);

    // Save the email to DB regardless of emailSent result
    $stmt = $pdo->prepare("INSERT INTO emails (client_id, admin_id, subject, message, sent_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$clientId, $adminId, $subject, $message]);

    if ($emailSent) {
        redirect('clients.php', 'Email sent successfully.', 'success');
    } else {
        redirect('clients.php', 'Email saved but sending failed.', 'warning');
    }
}

// Get email history
$emailHistory = [];
if ($clientId > 0) {
    $stmt = $pdo->prepare("
        SELECT e.*, a.username as admin_name 
        FROM emails e 
        JOIN admins a ON e.admin_id = a.id 
        WHERE e.client_id = ? 
        ORDER BY e.sent_at DESC
    ");
    $stmt->execute([$clientId]);
    $emailHistory = $stmt->fetchAll();
}
?>

<div class="card">
    <div class="card-header">
        <h4>Send Email to Client</h4>
    </div>
    <div class="card-body">
        <form method="POST">
            <div class="mb-3">
                <label for="client_id" class="form-label">Select Client</label>
                <select class="form-select" id="client_id" name="client_id" required onchange="window.location.href='send_email.php?client_id='+this.value">
                    <option value="">Select a client</option>
                    <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= $clientId === $c['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['email']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($client): ?>
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="8" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Email</button>
            <?php endif; ?>
        </form>

        <?php if ($client && !empty($emailHistory)): ?>
            <hr>
            <h5>Email History</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Subject</th>
                            <th>Sent By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emailHistory as $email): ?>
                            <tr>
                                <td><?= date('M d, Y h:i A', strtotime($email['sent_at'])); ?></td>
                                <td><?= htmlspecialchars($email['subject']); ?></td>
                                <td><?= htmlspecialchars($email['admin_name']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#emailModal<?= $email['id']; ?>">View</button>
                                </td>
                            </tr>

                            <!-- Modal -->
                            <div class="modal fade" id="emailModal<?= $email['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Email Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($email['sent_at'])); ?></p>
                                            <p><strong>Subject:</strong> <?= htmlspecialchars($email['subject']); ?></p>
                                            <p><strong>Sent By:</strong> <?= htmlspecialchars($email['admin_name']); ?></p>
                                            <hr>
                                            <div><?= nl2br(htmlspecialchars($email['message'])); ?></div>
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

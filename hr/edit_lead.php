<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

if (!isset($_GET['id'])) {
    redirect('leads.php', 'Lead ID not provided', 'error');
}

$pdo = getDatabase();
$leadId = (int)$_GET['id'];

// Get lead details
$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead) {
    redirect('leads.php', 'Lead not found', 'error');
}

if ($lead['status'] === 'converted') {
    redirect('view_lead.php?id='.$leadId, 'Converted leads cannot be edited', 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $username = sanitize($_POST['username']);
    $status = sanitize($_POST['status']);
    
    // Check if username or email already exists for other leads
    $stmt = $pdo->prepare("SELECT id FROM leads WHERE (email = ? OR username = ?) AND id != ?");
    $stmt->execute([$email, $username, $leadId]);
    
    if ($stmt->fetch()) {
        redirect("edit_lead.php?id=$leadId", 'Email or username already exists for another lead', 'error');
    }
    
    // Update lead
    $stmt = $pdo->prepare("
        UPDATE leads 
        SET name = ?, email = ?, phone = ?, username = ?, status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$name, $email, $phone, $username, $status, $leadId]);
    
    redirect("view_lead.php?id=$leadId", 'Lead updated successfully', 'success');
}

// Status options
$statusOptions = [
    'interested' => 'Interested',
    'pending' => 'Pending',
    'reschedule' => 'Reschedule'
];
?>

<div class="container mt-4">
    <h2>Edit Lead</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($lead['name']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($lead['email']) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?= htmlspecialchars($lead['phone']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>" <?= $lead['status'] === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($lead['username']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" value="********" disabled>
                        <small class="text-muted">Password cannot be viewed or changed here</small>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Update Lead</button>
                    <a href="view_lead.php?id=<?= $leadId ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
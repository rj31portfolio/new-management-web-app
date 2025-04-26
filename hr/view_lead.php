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

// Handle followup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_followup'])) {
        $followupDate = sanitize($_POST['followup_date']);
        $notes = sanitize($_POST['notes']);
        $nextFollowupDate = sanitize($_POST['next_followup_date']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("
            INSERT INTO lead_followups 
            (lead_id, hr_id, followup_date, notes, next_followup_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $leadId, 
            $_SESSION['user_id'], 
            $followupDate, 
            $notes, 
            $nextFollowupDate ?: null, 
            $status
        ]);
        
        // Update lead status if changed
        if ($status === 'converted') {
            $stmt = $pdo->prepare("UPDATE leads SET status = 'converted' WHERE id = ?");
            $stmt->execute([$leadId]);
        }
        
        redirect("view_lead.php?id=$leadId", 'Followup added successfully', 'success');
    } elseif (isset($_POST['update_followup'])) {
        $followupId = sanitize($_POST['followup_id']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("
            UPDATE lead_followups 
            SET status = ?, followup_date = IF(status = 'pending' AND ? = 'completed', NOW(), followup_date)
            WHERE id = ?
        ");
        $stmt->execute([$status, $status, $followupId]);
        
        redirect("view_lead.php?id=$leadId", 'Followup status updated', 'info');
    }
}

// Get all followups for this lead
$stmt = $pdo->prepare("
    SELECT lf.*, h.username as hr_name 
    FROM lead_followups lf
    JOIN hr h ON lf.hr_id = h.id
    WHERE lf.lead_id = ?
    ORDER BY lf.followup_date DESC
");
$stmt->execute([$leadId]);
$followups = $stmt->fetchAll();

// Status options
$statusOptions = [
    'interested' => 'Interested',
    'pending' => 'Pending',
    'reschedule' => 'Reschedule',
    'converted' => 'Converted'
];
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2>Lead Details</h2>
        <div>
            <a href="leads.php" class="btn btn-secondary">Back to Leads</a>
            <?php if ($lead['status'] !== 'converted'): ?>
                <a href="edit_lead.php?id=<?= $leadId ?>" class="btn btn-primary">Edit Lead</a>
                <form method="POST" action="leads.php" style="display:inline;">
                    <input type="hidden" name="approve_lead" value="1">
                    <input type="hidden" name="lead_id" value="<?= $leadId ?>">
                    <button type="submit" class="btn btn-success">Approve as Client</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Basic Information</h5>
                    <p><strong>Name:</strong> <?= htmlspecialchars($lead['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($lead['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($lead['phone']) ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Account Information</h5>
                    <p><strong>Username:</strong> <?= htmlspecialchars($lead['username']) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?= 
                            $lead['status'] === 'converted' ? 'success' : 
                            ($lead['status'] === 'interested' ? 'primary' : 
                            ($lead['status'] === 'pending' ? 'warning' : 'info')) 
                        ?>">
                            <?= ucfirst($lead['status']) ?>
                        </span>
                    </p>
                    <p><strong>Created At:</strong> <?= date('M d, Y H:i', strtotime($lead['created_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Followups Section -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Followups</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                <i class="fas fa-plus"></i> Add Followup
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($followups)): ?>
                <div class="alert alert-info">No followups found for this lead.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Notes</th>
                                <th>Next Followup</th>
                                <th>Status</th>
                                <th>HR</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($followups as $followup): ?>
                            <tr>
                                <td><?= date('M d, Y H:i', strtotime($followup['followup_date'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($followup['notes'])) ?></td>
                                <td>
                                    <?= $followup['next_followup_date'] ? 
                                        date('M d, Y', strtotime($followup['next_followup_date'])) : '--' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $followup['status'] === 'converted' ? 'success' : 
                                        ($followup['status'] === 'interested' ? 'primary' : 
                                        ($followup['status'] === 'pending' ? 'warning' : 'info')) 
                                    ?>">
                                        <?= ucfirst($followup['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($followup['hr_name']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                            data-bs-target="#editFollowupModal" 
                                            data-followupid="<?= $followup['id'] ?>"
                                            data-currentstatus="<?= $followup['status'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Followup Modal -->
<div class="modal fade" id="addFollowupModal" tabindex="-1" aria-labelledby="addFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="add_followup" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFollowupModalLabel">Add New Followup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="followup_date" class="form-label">Followup Date & Time</label>
                            <input type="datetime-local" class="form-control" id="followup_date" name="followup_date" 
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= $value ?>"><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="next_followup_date" class="form-label">Next Followup Date (Optional)</label>
                        <input type="date" class="form-control" id="next_followup_date" name="next_followup_date">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Followup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Followup Modal -->
<div class="modal fade" id="editFollowupModal" tabindex="-1" aria-labelledby="editFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="update_followup" value="1">
                <input type="hidden" name="followup_id" id="modal_followup_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFollowupModalLabel">Update Followup Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_status" class="form-label">Status</label>
                        <select class="form-select" id="modal_status" name="status" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                            <option value="<?= $value ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize modals and set followup ID/status
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editFollowupModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const followupId = button.getAttribute('data-followupid');
        const currentStatus = button.getAttribute('data-currentstatus');
        
        document.getElementById('modal_followup_id').value = followupId;
        document.getElementById('modal_status').value = currentStatus;
    });

    // Set minimum datetime for followup date to current time
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    
    const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('followup_date').setAttribute('min', minDateTime);
    
    // Set minimum date for next followup date to tomorrow
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    document.getElementById('next_followup_date').setAttribute('min', tomorrowStr);
});
</script>

<?php include '../includes/footer.php'; ?>
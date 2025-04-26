<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle followup actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_followup'])) {
        $clientId = sanitize($_POST['client_id']);
        $followupDate = sanitize($_POST['followup_date']);
        $notes = sanitize($_POST['notes']);
        $nextFollowupDate = sanitize($_POST['next_followup_date']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("
            INSERT INTO client_followups 
            (client_id, hr_id, followup_date, notes, next_followup_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $clientId, 
            $_SESSION['user_id'], 
            $followupDate, 
            $notes, 
            $nextFollowupDate ?: null, 
            $status
        ]);
        
        redirect('followups.php', 'Followup added successfully', 'success');
    } elseif (isset($_POST['update_status'])) {
        $followupId = sanitize($_POST['followup_id']);
        $status = sanitize($_POST['status']);
        
        $stmt = $pdo->prepare("
            UPDATE client_followups 
            SET status = ?, followup_date = IF(status = 'pending' AND ? = 'completed', NOW(), followup_date)
            WHERE id = ?
        ");
        $stmt->execute([$status, $status, $followupId]);
        
        redirect('followups.php', 'Followup status updated', 'info');
    } elseif (isset($_POST['delete_followup'])) {
        $followupId = sanitize($_POST['followup_id']);
        
        $stmt = $pdo->prepare("DELETE FROM client_followups WHERE id = ?");
        $stmt->execute([$followupId]);
        
        redirect('followups.php', 'Followup deleted successfully', 'success');
    }
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$clientFilter = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$dateFrom = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build filter conditions
$conditions = [];
$params = [];

if (!empty($statusFilter)) {
    $conditions[] = "f.status = ?";
    $params[] = $statusFilter;
}

if ($clientFilter > 0) {
    $conditions[] = "f.client_id = ?";
    $params[] = $clientFilter;
}

if (!empty($dateFrom)) {
    $conditions[] = "DATE(f.followup_date) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $conditions[] = "DATE(f.followup_date) <= ?";
    $params[] = $dateTo;
}

$whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get all followups with filters
$query = "
    SELECT f.*, c.name as client_name, c.email as client_email, c.phone as client_phone,
           h.username as hr_name, h.email as hr_email
    FROM client_followups f
    JOIN clients c ON f.client_id = c.id
    JOIN hr h ON f.hr_id = h.id
    $whereClause
    ORDER BY f.followup_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$followups = $stmt->fetchAll();

// Get all clients for dropdown
$clients = $pdo->query("SELECT id, name FROM clients ORDER BY name")->fetchAll();

// Get status options
$statusOptions = [
    'pending' => 'Pending',
    'completed' => 'Completed',
    'rescheduled' => 'Rescheduled'
];
?>


<div class="container mt-4">
    <h2>Client Followups Management</h2>
    
    <!-- Filter Section -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline d-flex flex-wrap gap-2">
                <div class="form-group">
                    <label for="status" class="sr-only">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Statuses</option>
                        <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $statusFilter === $value ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="client_id" class="sr-only">Client</label>
                    <select class="form-control" id="client_id" name="client_id">
                        <option value="0">All Clients</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?= $client['id'] ?>" <?= $clientFilter === $client['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($client['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_from" class="sr-only">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="form-group">
                    <label for="date_to" class="sr-only">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="followups.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>
    
    <!-- Followups List -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Followups</h5>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                <i class="fas fa-plus"></i> Add Followup
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($followups)): ?>
                <div class="alert alert-info">No followups found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Followup Date</th>
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
                                <td>
                                    <strong><?= htmlspecialchars($followup['client_name']) ?></strong><br>
                                    <small><?= htmlspecialchars($followup['client_email']) ?></small><br>
                                    <small><?= htmlspecialchars($followup['client_phone']) ?></small>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($followup['followup_date'])) ?></td>
                                <td><?= nl2br(htmlspecialchars($followup['notes'])) ?></td>
                                <td>
                                    <?= $followup['next_followup_date'] ? 
                                        date('M d, Y', strtotime($followup['next_followup_date'])) : '--' ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $followup['status'] === 'completed' ? 'success' : 
                                        ($followup['status'] === 'pending' ? 'warning' : 'info') 
                                    ?>">
                                        <?= ucfirst($followup['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($followup['hr_name']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editFollowupModal" 
                                                data-followupid="<?= $followup['id'] ?>"
                                                data-currentstatus="<?= $followup['status'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                data-bs-target="#deleteFollowupModal" 
                                                data-followupid="<?= $followup['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
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
                            <label for="client_id" class="form-label">Client</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="followup_date" class="form-label">Followup Date & Time</label>
                            <input type="datetime-local" class="form-control" id="followup_date" name="followup_date" 
                                   value="<?= date('Y-m-d\TH:i') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="next_followup_date" class="form-label">Next Followup Date (Optional)</label>
                            <input type="date" class="form-control" id="next_followup_date" name="next_followup_date">
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
                <input type="hidden" name="update_status" value="1">
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

<!-- Delete Followup Modal -->
<div class="modal fade" id="deleteFollowupModal" tabindex="-1" aria-labelledby="deleteFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="delete_followup" value="1">
                <input type="hidden" name="followup_id" id="delete_followup_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFollowupModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this followup record? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Followup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

    const deleteModal = document.getElementById('deleteFollowupModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const followupId = button.getAttribute('data-followupid');
        document.getElementById('delete_followup_id').value = followupId;
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
</body>
</html>
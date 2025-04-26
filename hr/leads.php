<?php
require_once '../includes/header.php';
require_once '../includes/auth.php';

if (!isHR()) {
    redirect('../hr/login.php', 'You are not authorized to access this page', 'error');
}

$pdo = getDatabase();

// Handle lead approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_lead'])) {
    $leadId = sanitize($_POST['lead_id']);
    
    // Get lead data
    $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->execute([$leadId]);
    $lead = $stmt->fetch();
    
    if ($lead) {
        // Insert into clients table
        $stmt = $pdo->prepare("
            INSERT INTO clients 
            (name, email, phone, username, password, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $lead['name'],
            $lead['email'],
            $lead['phone'],
            $lead['username'],
            $lead['password']
        ]);
        
        // Update lead status to converted
        $stmt = $pdo->prepare("UPDATE leads SET status = 'converted' WHERE id = ?");
        $stmt->execute([$leadId]);
        
        redirect('leads.php', 'Lead approved and moved to clients successfully', 'success');
    } else {
        redirect('leads.php', 'Lead not found', 'error');
    }
}

// Handle lead deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_lead'])) {
    $leadId = sanitize($_POST['lead_id']);
    
    $stmt = $pdo->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->execute([$leadId]);
    
    redirect('leads.php', 'Lead deleted successfully', 'success');
}

// Get filter parameters
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$searchTerm = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build filter conditions
$conditions = [];
$params = [];

if (!empty($statusFilter)) {
    $conditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchTerm)) {
    $conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$whereClause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Get all leads with filters
$query = "SELECT * FROM leads $whereClause ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leads = $stmt->fetchAll();

// Status options
$statusOptions = [
    'interested' => 'Interested',
    'pending' => 'Pending',
    'reschedule' => 'Reschedule',
    'converted' => 'Converted'
];
?>

<div class="container mt-4">
    <h2>Leads Management</h2>
    
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
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search by name, email or phone" value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="leads.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>
    
    <!-- Leads List -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Leads</h5>
            <a href="add_lead.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Lead
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($leads)): ?>
                <div class="alert alert-info">No leads found matching your criteria.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $lead): ?>
                            <tr>
                                <td><?= htmlspecialchars($lead['name']) ?></td>
                                <td>
                                    <small><?= htmlspecialchars($lead['email']) ?></small><br>
                                    <small><?= htmlspecialchars($lead['phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($lead['username']) ?></td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $lead['status'] === 'converted' ? 'success' : 
                                        ($lead['status'] === 'interested' ? 'primary' : 
                                        ($lead['status'] === 'pending' ? 'warning' : 'info')) 
                                    ?>">
                                        <?= ucfirst($lead['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($lead['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_lead.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($lead['status'] !== 'converted'): ?>
                                            <a href="edit_lead.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="approve_lead" value="1">
                                                <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                data-bs-target="#deleteLeadModal" 
                                                data-leadid="<?= $lead['id'] ?>">
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

<!-- Delete Lead Modal -->
<div class="modal fade" id="deleteLeadModal" tabindex="-1" aria-labelledby="deleteLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="delete_lead" value="1">
                <input type="hidden" name="lead_id" id="delete_lead_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteLeadModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this lead? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize modal and set lead ID
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteLeadModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const leadId = button.getAttribute('data-leadid');
        document.getElementById('delete_lead_id').value = leadId;
    });
});
</script>

<?php include '../includes/footer.php'; ?>
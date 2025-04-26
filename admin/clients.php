<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Client Management";

// Handle client deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    if ($stmt->execute([$id])) {
        redirect('clients.php', 'Client deleted successfully', 'success');
    } else {
        redirect('clients.php', 'Failed to delete client', 'danger');
    }
}

// Handle client creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $username = sanitize($_POST['username']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    if (isset($_POST['client_id']) && $_POST['client_id'] != '') {
        // Update existing client
        $clientId = (int)$_POST['client_id'];
        
        if ($password) {
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, username = ?, password = ? WHERE id = ?");
            $params = [$name, $email, $phone, $username, $password, $clientId];
        } else {
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, username = ? WHERE id = ?");
            $params = [$name, $email, $phone, $username, $clientId];
        }
        
        if ($stmt->execute($params)) {
            redirect('clients.php', 'Client updated successfully', 'success');
        } else {
            redirect('clients.php', 'Failed to update client', 'danger');
        }
    } else {
        // Create new client
        if (empty($_POST['password'])) {
            redirect('clients.php', 'Password is required for new client', 'danger');
        }
        
        $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, username, password) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $username, $password])) {
            redirect('clients.php', 'Client created successfully', 'success');
        } else {
            redirect('clients.php', 'Failed to create client', 'danger');
        }
    }
}

// Get pagination data
$pagination = getPagination('clients');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = $pagination['perPage'];
$offset = ($page - 1) * $perPage; // Calculate the offset based on the current page

// Get clients for current page
$stmt = $pdo->prepare("SELECT * FROM clients ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();

// Get client data for editing
$editClient = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$editId]);
    $editClient = $stmt->fetch();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Client Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#clientModal">Add New Client</button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Username</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo $client['id']; ?></td>
                            <td><?php echo $client['name']; ?></td>
                            <td><?php echo $client['email']; ?></td>
                            <td><?php echo $client['phone']; ?></td>
                            <td><?php echo $client['username']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                            <td>
                                <a href="?edit=<?php echo $client['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?delete=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $pagination['totalPages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Client Modal -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientModalLabel"><?php echo $editClient ? 'Edit Client' : 'Add New Client'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $editClient ? $editClient['id'] : ''; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo $editClient ? $editClient['name'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo $editClient ? $editClient['email'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $editClient ? $editClient['phone'] : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo $editClient ? $editClient['username'] : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" <?php echo !$editClient ? 'required' : ''; ?>>
                        <?php if ($editClient): ?>
                            <small class="text-muted">Leave blank to keep current password</small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($_GET['edit'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var clientModal = new bootstrap.Modal(document.getElementById('clientModal'));
            clientModal.show();
        });
    </script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

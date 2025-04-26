<?php
require_once '../../includes/header.php';
require_once '../../includes/auth.php';

// Check if user is authenticated and has admin role
checkAuth('admin');

$pdo = getDatabase();
$pageTitle = "Manage HR Staff";

// Handle delete action
if (isset($_GET['delete'])) {
    $hrId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM hr WHERE id = ?");
        $stmt->execute([$hrId]);
        $_SESSION['message'] = "HR staff deleted successfully.";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting HR staff: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }
    header("Location: manage.php");
    exit;
}

// Fetch HR staff (Updated query to use full_name instead of name)
$stmt = $pdo->query("SELECT id, full_name, email, phone, role, created_at FROM hr ORDER BY created_at DESC");
$hrStaff = $stmt->fetchAll();
?>

<style>
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card-header {
        border-radius: 15px 15px 0 0 !important;
        padding: 1rem 1.5rem;
        font-weight: 600;
        background-color: #6f42c1;
        color: white;
    }
    .table-responsive {
        border-radius: 10px;
    }
    .btn-custom {
        transition: all 0.3s ease;
    }
    .btn-custom:hover {
        transform: translateY(-2px);
    }
    .gradient-purple {
        background: linear-gradient(135deg, #6f42c1, #9c6bff);
    }
</style>

<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show mt-4">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<div class="container mt-4">
    <h2 class="mb-4">Manage HR Staff</h2>

    <!-- Add HR Staff Button -->
    <div class="mb-4">
        <a href="add_hr.php" class="btn btn-custom gradient-purple text-white">
            <i class="fas fa-plus me-2"></i>Add New HR Staff
        </a>
    </div>

    <!-- HR Staff Table -->
    <div class="card shadow">
        <div class="card-header gradient-purple">
            <i class="fas fa-user-tie me-2"></i>
            <span class="fw-bold">HR Staff List</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hrStaff)): ?>
                            <tr>
                                <td colspan="7" class="text-muted text-center">No HR staff found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($hrStaff as $index => $staff): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($staff['full_name']) ?></td>
                                    <td><?= htmlspecialchars($staff['email']) ?></td>
                                    <td><?= htmlspecialchars($staff['phone']) ?></td>
                                    <td><?= htmlspecialchars($staff['role']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($staff['created_at'])) ?></td>
                                    <td>
                                        <a href="edit_hr.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-warning btn-custom me-1" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="manage.php?delete=<?= $staff['id'] ?>" class="btn btn-sm btn-danger btn-custom" 
                                           title="Delete" onclick="return confirm('Are you sure you want to delete this HR staff?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
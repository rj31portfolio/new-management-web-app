<?php
require_once '../includes/header.php';
checkAuth('admin');

$pdo = getDatabase();

// Delete enquiry
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM enquiries WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg'] = "Enquiry deleted successfully.";
        header("Location: inquiries.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['msg'] = "Error deleting enquiry: " . $e->getMessage();
        header("Location: inquiries.php");
        exit();
    }
}

try {
    $stmt = $pdo->query("SELECT * FROM enquiries ORDER BY id DESC");
    $enquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enquiries = [];
    $_SESSION['msg'] = "Error fetching enquiries: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enquiries - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h3 class="mb-4">Manage Enquiries</h3>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($enquiries as $row): ?>
                <tr>
                    <td><?= $i++; ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['phone']); ?></td>
                    <td><?= htmlspecialchars($row['message']); ?></td>
                    <td><?= date("d M Y H:i", strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="inquiries.php?delete=<?= $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this enquiry?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
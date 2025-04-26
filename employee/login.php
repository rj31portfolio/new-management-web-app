<?php
require_once '../includes/header.php';

if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : (isClient() ? '../client/dashboard.php' : 'dashboard.php'));
}

// Add employee login function to auth.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = sanitize($_POST['password']);
    
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $employee = $stmt->fetch();
    
    if ($employee && password_verify($password, $employee['password'])) {
        $_SESSION['user_id'] = $employee['id'];
        $_SESSION['user_role'] = 'employee';
        $_SESSION['username'] = $employee['username'];
        redirect('dashboard.php', 'Login successful', 'success');
    } else {
        $error = "Invalid username or password";
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Employee Login</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            <div class="card-footer text-center">
                <a href="../admin/login.php">Admin Login</a> | 
                <a href="../client/login.php">Client Login</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
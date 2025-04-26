<?php
require_once 'auth.php';

// Set dynamic base path for links like profile and logout
if (isAdmin()) {
    $basePath = '/admin/admin';
} elseif (isClient()) {
    $basePath = '/admin/client';
} elseif (isEmployee()) {
    $basePath = '/admin/employee';
} else {
    $basePath = '/admin/hr';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Project Management'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
<?php if (isLoggedIn()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $basePath; ?>/dashboard.php">Project Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/blogs.php">Blogs</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/projects.php">Projects</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/clients.php">Clients</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/employees.php">Employees</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/tasks.php">Tasks</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/hr/dashboard.php">HR Management</a></li>

                    <?php elseif (isClient()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/projects.php">Projects</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/notifications.php">Notifications</a></li>

                    <?php elseif (isEmployee()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/tasks.php">Tasks</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/reports.php">Reports</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/leaves.php">Leaves</a></li>

                    <?php elseif (isHR()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/employees.php">Employees</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/leaves.php">Leaves</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/salaries.php">Salaries</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/followups.php">Client Followups</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>/leads.php">Lead Followups</a></li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php if (isHR()): ?>
                                <span class="badge bg-info ms-1">HR</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $basePath; ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="container mt-4">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

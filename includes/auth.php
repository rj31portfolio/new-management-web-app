<?php
require_once 'functions.php';

// Handle admin login
function adminLogin($username, $password) {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['user_role'] = 'admin';
        $_SESSION['username'] = $admin['username'];
        return true;
    }
    return false;
}

// Handle client login
function clientLogin($username, $password) {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE username = ?");
    $stmt->execute([$username]);
    $client = $stmt->fetch();

    if ($client && password_verify($password, $client['password'])) {
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['user_role'] = 'client';
        $_SESSION['username'] = $client['username'];
        return true;
    }
    return false;
}

// Handle employee login
function employeeLogin($username, $password) {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE username = ?");
    $stmt->execute([$username]);
    $employee = $stmt->fetch();

    if ($employee && password_verify($password, $employee['password'])) {
        $_SESSION['user_id'] = $employee['id'];
        $_SESSION['user_role'] = 'employee';
        $_SESSION['username'] = $employee['username'];
        return true;
    }
    return false;
}

// Handle HR login
function hrLogin($username, $password) {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM hr WHERE username = ?");
    $stmt->execute([$username]);
    $hr = $stmt->fetch();

    if ($hr && password_verify($password, $hr['password'])) {
        $_SESSION['user_id'] = $hr['id'];
        $_SESSION['user_role'] = 'hr';
        $_SESSION['username'] = $hr['username'];
        return true;
    }
    return false;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Role checks
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isClient() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
}

function isEmployee() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee';
}

function isHR() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'hr';
}

// Universal auth check
function checkAuth($requiredRole = null) {
    if (!isLoggedIn()) {
        redirect('../index.php', 'Please login first', 'danger');
    }

    $allowedRoles = ['admin', 'client', 'employee', 'hr'];
    if ($requiredRole && !in_array($_SESSION['user_role'], $allowedRoles)) {
        redirect('../index.php', 'Unauthorized access', 'danger');
    }

    if ($requiredRole && $_SESSION['user_role'] !== $requiredRole) {
        redirect('../index.php', 'Unauthorized access', 'danger');
    }
}

// Logout
function logout() {
    session_unset();
    session_destroy();
    redirect('../index.php', 'You have been logged out', 'success');
}
?>

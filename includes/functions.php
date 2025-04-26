<?php
session_start();
require_once 'db.php'; // Include the database connection

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Redirect with message
function redirect($location, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: $location");
    exit();
}

// Check if user is logged in
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Check if user is admin
if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

// Check if user is client
if (!function_exists('isClient')) {
    function isClient() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
    }
}

// Get current user ID
if (!function_exists('getUserId')) {
    function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

// Pagination function with fixed WHERE clause handling
function getPagination($table, $where = '1=1', $perPage = 10, $params = []) {
    $pdo = getDatabase(); // Use the getDatabase function from db.php

    // Ensure $where condition is sanitized
    $where = $where ? $where : '1=1'; // Default to '1=1' if no condition is provided

    // Prepare the COUNT query with the WHERE clause
    $sql = "SELECT COUNT(*) FROM $table WHERE $where";
    $stmt = $pdo->prepare($sql);

    // Execute with parameters
    $stmt->execute($params);
    $totalRecords = $stmt->fetchColumn();

    // Calculate total pages
    $totalPages = ceil($totalRecords / $perPage);

    // Get current page
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($currentPage < 1) $currentPage = 1;
    if ($currentPage > $totalPages) $currentPage = $totalPages;

    // Calculate offset
    $offset = ($currentPage - 1) * $perPage;

    // Return pagination data
    return [
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'perPage' => $perPage,
        'offset' => $offset
    ];
}

// Upload file
if (!function_exists('uploadFile')) {
    function uploadFile($file, $targetDir) {
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($file['name']);
        $targetPath = $targetDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return [
                'success' => true,
                'file_path' => $targetPath,
                'original_name' => $file['name']
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to upload file'
            ];
        }
    }
}

// Send email
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message) {
        $headers = "From: no-reply@yourdomain.com\r\n";
        $headers .= "Reply-To: no-reply@yourdomain.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $message, $headers);
    }
}
?>

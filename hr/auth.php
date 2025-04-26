// Add this with your other role checking functions
function isHR() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'hr';
}

// Update the isLoggedIn function if needed
function isLoggedIn() {
    return isset($_SESSION['user_id']) && (isAdmin() || isClient() || isEmployee() || isHR());
}

// Update redirect logic as needed
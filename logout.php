<?php
session_start();

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies
setcookie('remember_user', '', time() - 3600, '/');

// Determine where to redirect
$referer = $_SERVER['HTTP_REFERER'] ?? '../index.php';

// Check if user was on admin page
$is_admin_page = strpos($referer, '/admin/') !== false;

// Redirect based on where they came from
if ($is_admin_page) {
    // Redirect admin to admin login
    header("Location: ./login.php?logout=success");
} else {
    // Redirect regular user to main site login
    header("Location: ./login.php?logout=success");
}
exit();
?>
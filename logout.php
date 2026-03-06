<?php
/**
 * Logout Handler
 * Securely destroys session and redirects user
 */

session_start();

// Clear all session variables
$_SESSION = [];

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
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect to login page with success message
header("Location: login.php?logout=success");
exit();
?>

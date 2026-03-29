<?php
/**
 * Secure Database Configuration
 *
 * Features:
 * - PDO + MySQLi dual connection ($conn for legacy pages)
 * - Safe session_start (idempotent)
 * - CSRF token generation
 * - Security headers
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'carrerguidence');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'CareerGuide');
define('APP_URL', 'http://localhost/careerGuidenceSystem');

// Session hardening — must happen BEFORE session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0);    // set to 1 in production (HTTPS)
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// ─────────────────────────────────────────────
// MySQLi connection ($conn) — used by all existing pages
// ─────────────────────────────────────────────
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Prevent modern PHP 8.1+ from throwing fatal exceptions on simple query failures
mysqli_report(MYSQLI_REPORT_OFF);

// Ensure strict handling of character encoding
if (!$conn) {
    error_log("MySQLi Connection Error: " . mysqli_connect_error());
    http_response_code(503);
    die("<!DOCTYPE html><html><head><title>Database Error</title>
    <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f8fafc;}
    .box{text-align:center;padding:2rem;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);max-width:420px;}
    h2{color:#dc2626;margin-bottom:.5rem;}p{color:#475569;line-height:1.6;}code{background:#f1f5f9;padding:2px 6px;border-radius:4px;}</style></head>
    <body><div class='box'>
    <h2>&#9888; Database Unavailable</h2>
    <p>Cannot connect to MySQL.<br>Please make sure <strong>MySQL</strong> is running in XAMPP Control Panel.</p>
    <p style='font-size:.82rem;color:#94a3b8;margin-top:1rem;'>
      Host: <code>" . DB_HOST . "</code> &nbsp;|&nbsp; DB: <code>" . DB_NAME . "</code>
    </p>
    </div></body></html>");
}
$conn->set_charset("utf8mb4");

// ─────────────────────────────────────────────
// PDO connection (for new code using prepared statements)
// ─────────────────────────────────────────────
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("PDO Connection Error: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    return $pdo;
}

// ─────────────────────────────────────────────
// CSRF helpers
// ─────────────────────────────────────────────
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ─────────────────────────────────────────────
// Auth helpers
// ─────────────────────────────────────────────
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: " . (isAdmin() ? "admin/index.php" : "dashboard.php"));
        exit();
    }
}

// ─────────────────────────────────────────────
// Security headers
// ─────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

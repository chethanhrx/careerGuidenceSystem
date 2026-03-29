<?php
/**
 * api/save-resource.php
 * AJAX endpoint: toggle a user's saved resource (course or college)
 */
require_once '../config.php';

header('Content-Type: application/json');

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Validate CSRF
$csrf = $_POST['csrf'] ?? '';
if (!validateCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit();
}

$user_id       = (int)$_SESSION['user_id'];
$resource_type = sanitizeInput($_POST['type'] ?? '');
$resource_id   = (int)($_POST['id'] ?? 0);

// Validate inputs
if (!in_array($resource_type, ['course', 'college']) || $resource_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resource']);
    exit();
}

// Verify the resource actually exists
$table   = $resource_type === 'course' ? 'courses' : 'colleges';
$chk_sql = "SELECT id FROM `$table` WHERE id = ?";
$chk = $conn->prepare($chk_sql);
$chk->bind_param('i', $resource_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    $chk->close();
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
    exit();
}
$chk->close();

// Check if already saved
$exists_stmt = $conn->prepare(
    "SELECT id FROM user_saved_resources WHERE user_id = ? AND resource_type = ? AND resource_id = ?"
);
$exists_stmt->bind_param('isi', $user_id, $resource_type, $resource_id);
$exists_stmt->execute();
$exists_stmt->store_result();
$already_saved = $exists_stmt->num_rows > 0;
$exists_stmt->close();

if ($already_saved) {
    // Remove (toggle off)
    $del = $conn->prepare(
        "DELETE FROM user_saved_resources WHERE user_id = ? AND resource_type = ? AND resource_id = ?"
    );
    $del->bind_param('isi', $user_id, $resource_type, $resource_id);
    $del->execute();
    $del->close();
    echo json_encode(['success' => true, 'saved' => false]);
} else {
    // Insert (toggle on)
    $ins = $conn->prepare(
        "INSERT INTO user_saved_resources (user_id, resource_type, resource_id) VALUES (?, ?, ?)"
    );
    $ins->bind_param('isi', $user_id, $resource_type, $resource_id);
    $ins->execute();
    $ins->close();
    echo json_encode(['success' => true, 'saved' => true]);
}

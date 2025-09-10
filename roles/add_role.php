<?php
// add_role.php - Add new role to database
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
    exit;
}

if (!isset($input['roleName']) || empty(trim($input['roleName']))) {
    json_response(false, 'roleName is required');
    exit;
}

try {
    $roleName = sanitize_input($input['roleName']);
    $description = isset($input['description']) ? sanitize_input($input['description']) : '';

    if (strlen($roleName) < 2) {
        throw new Exception('RoleName must be at least 2 characters');
    }

    // Ensure roles table exists
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows !== 1) {
        throw new Exception("'roles' table does not exist in database");
    }

    // Check uniqueness
    $check = $conn->prepare('SELECT RoleID FROM roles WHERE RoleName = ?');
    $check->bind_param('s', $roleName);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        throw new Exception('RoleName already exists');
    }
    $check->close();

    $stmt = $conn->prepare('INSERT INTO roles (RoleName, Description) VALUES (?, ?)');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ss', $roleName, $description);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        json_response(true, 'Role created successfully', [
            'RoleID' => (int)$newId,
            'RoleName' => $roleName,
            'Description' => $description
        ]);
    } else {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    $stmt->close();
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>

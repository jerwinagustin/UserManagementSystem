<?php
// get_role.php - Get single role by RoleID
include '../config.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    json_response(false, 'RoleID parameter is required');
    exit;
}

try {
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows !== 1) {
        throw new Exception("'roles' table does not exist");
    }

    $roleID = (int)$_GET['id'];

    $stmt = $conn->prepare('SELECT RoleID, RoleName, Description FROM roles WHERE RoleID = ?');
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $roleID);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $role = $res->fetch_assoc();
        $role['RoleID'] = (int)$role['RoleID'];
        json_response(true, 'Role retrieved successfully', $role);
    } else {
        json_response(false, 'Role not found');
    }
    $stmt->close();
} catch (Exception $e) {
    json_response(false, 'Error retrieving role: ' . $e->getMessage());
}

$conn->close();
?>

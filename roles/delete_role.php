<?php
// delete_role.php - Delete role by RoleID
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['RoleID'])) {
    json_response(false, 'RoleID is required');
    exit;
}

try {
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows !== 1) {
        throw new Exception("'roles' table does not exist");
    }

    $roleID = (int)$input['RoleID'];

    // Check existence
    $check = $conn->prepare('SELECT RoleName FROM roles WHERE RoleID = ?');
    $check->bind_param('i', $roleID);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) {
        throw new Exception('Role not found');
    }
    $roleData = $res->fetch_assoc();
    $check->close();

    // (If a userroles mapping table exists, ensure no assignments or cascade delete)
    $hasUserRoles = $conn->query("SHOW TABLES LIKE 'userroles'")->num_rows === 1;
    if ($hasUserRoles) {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM userroles WHERE RoleID = ?');
        $countStmt->bind_param('i', $roleID);
        $countStmt->execute();
        $cRes = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();
        if ((int)$cRes['c'] > 0) {
            throw new Exception('Cannot delete role: it is assigned to users');
        }
    }

    $del = $conn->prepare('DELETE FROM roles WHERE RoleID = ?');
    $del->bind_param('i', $roleID);
    if ($del->execute()) {
        if ($del->affected_rows === 0) {
            throw new Exception('No role deleted');
        }
        json_response(true, 'Role deleted successfully', [
            'deletedRoleID' => $roleID,
            'deletedRoleName' => $roleData['RoleName']
        ]);
    } else {
        throw new Exception('Delete failed: ' . $del->error);
    }
    $del->close();
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>

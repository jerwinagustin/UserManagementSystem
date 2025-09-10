<?php
// update_role.php - Update existing role
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

    // Check existing role
    $check = $conn->prepare('SELECT RoleName, Description FROM roles WHERE RoleID = ?');
    $check->bind_param('i', $roleID);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) {
        throw new Exception('Role not found');
    }
    $current = $res->fetch_assoc();
    $check->close();

    $updateFields = [];
    $bindValues = [];
    $bindTypes = '';

    if (isset($input['roleName']) && !empty(trim($input['roleName']))) {
        $newName = sanitize_input($input['roleName']);
        if (strlen($newName) < 2) {
            throw new Exception('RoleName must be at least 2 characters');
        }
        // Unique check except self
        $unique = $conn->prepare('SELECT RoleID FROM roles WHERE RoleName = ? AND RoleID != ?');
        $unique->bind_param('si', $newName, $roleID);
        $unique->execute();
        $uRes = $unique->get_result();
        if ($uRes->num_rows > 0) {
            throw new Exception('RoleName already in use');
        }
        $unique->close();

        $updateFields[] = 'RoleName = ?';
        $bindValues[] = $newName;
        $bindTypes .= 's';
    }

    if (array_key_exists('description', $input)) { // allow empty description reset
        $newDesc = sanitize_input($input['description']);
        $updateFields[] = 'Description = ?';
        $bindValues[] = $newDesc;
        $bindTypes .= 's';
    }

    if (empty($updateFields)) {
        throw new Exception('No valid fields provided for update');
    }

    $bindValues[] = $roleID;
    $bindTypes .= 'i';

    $sql = 'UPDATE roles SET ' . implode(', ', $updateFields) . ' WHERE RoleID = ?';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param($bindTypes, ...$bindValues);
    if ($stmt->execute()) {
        $get = $conn->prepare('SELECT RoleID, RoleName, Description FROM roles WHERE RoleID = ?');
        $get->bind_param('i', $roleID);
        $get->execute();
        $updated = $get->get_result()->fetch_assoc();
        $get->close();

        json_response(true, 'Role updated successfully', [
            'RoleID' => (int)$updated['RoleID'],
            'RoleName' => $updated['RoleName'],
            'Description' => $updated['Description']
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

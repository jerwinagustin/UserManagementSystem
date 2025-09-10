<?php
// update_user_roles.php - Replace a user's role assignments with provided list
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_response(false, 'Only POST requests allowed'); exit; }
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['UserID'])) { json_response(false, 'UserID is required'); exit; }
if (!isset($input['roleIDs']) || !is_array($input['roleIDs'])) { json_response(false, 'roleIDs array is required'); exit; }

try {
    $userID = (int)$input['UserID'];
    $roleIDs = array_unique(array_map('intval', $input['roleIDs']));

    // verify user
    $uStmt = $conn->prepare('SELECT UserID FROM users WHERE UserID = ?');
    $uStmt->bind_param('i', $userID);
    $uStmt->execute();
    if ($uStmt->get_result()->num_rows === 0) { throw new Exception('User not found'); }
    $uStmt->close();

    // ensure tables
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows !== 1) { throw new Exception("roles table missing"); }
    if ($conn->query("SHOW TABLES LIKE 'userroles'")->num_rows !== 1) { throw new Exception("userroles table missing"); }

    // validate provided role IDs exist
    if (count($roleIDs) > 0) {
        $placeholders = implode(',', array_fill(0, count($roleIDs), '?'));
        $types = str_repeat('i', count($roleIDs));
        $checkSql = 'SELECT RoleID FROM roles WHERE RoleID IN (' . $placeholders . ')';
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param($types, ...$roleIDs);
        $checkStmt->execute();
        $existing = [];
        $res = $checkStmt->get_result();
        while ($r = $res->fetch_assoc()) { $existing[] = (int)$r['RoleID']; }
        $checkStmt->close();
        $missing = array_diff($roleIDs, $existing);
        if (!empty($missing)) { throw new Exception('Invalid RoleID(s): ' . implode(',', $missing)); }
    }

    $conn->begin_transaction();
    try {
        // delete current
    $del = $conn->prepare('DELETE FROM userroles WHERE UserID = ?');
        $del->bind_param('i', $userID);
        $del->execute();
        $del->close();

        // insert new
        if (count($roleIDs) > 0) {
            $ins = $conn->prepare('INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)');
            foreach ($roleIDs as $rid) { $ins->bind_param('ii', $userID, $rid); $ins->execute(); }
            $ins->close();
        }

        $conn->commit();
        
        // Log the role update activity
        $roleNamesQuery = "SELECT RoleName FROM roles WHERE RoleID IN (" . implode(',', array_fill(0, count($roleIDs), '?')) . ")";
        if (count($roleIDs) > 0) {
            $roleStmt = $conn->prepare($roleNamesQuery);
            $roleStmt->bind_param(str_repeat('i', count($roleIDs)), ...$roleIDs);
            $roleStmt->execute();
            $roleResult = $roleStmt->get_result();
            $roleNames = [];
            while ($row = $roleResult->fetch_assoc()) {
                $roleNames[] = $row['RoleName'];
            }
            $roleStmt->close();
            $rolesText = implode(', ', $roleNames);
        } else {
            $rolesText = 'None';
        }
        
        log_activity($userID, 'Roles Updated', "User roles updated to: $rolesText");
        
    } catch (Exception $inner) {
        $conn->rollback();
        throw $inner;
    }

    json_response(true, 'User roles updated', [ 'UserID'=>$userID, 'roleIDs'=>$roleIDs ]);
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>

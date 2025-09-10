<?php
// get_user_roles.php - Get roles assigned to a user plus all roles list
include '../config.php';
header('Content-Type: application/json');

// Support both 'user_id' and 'UserID' parameter names
$userID = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_GET['UserID']) ? (int)$_GET['UserID'] : null);

if (!$userID) {
    json_response(false, 'user_id or UserID parameter is required');
    exit;
}

try {
    // Basic user existence check
    $uCheck = $conn->prepare('SELECT UserID, Username FROM users WHERE UserID = ?');
    $uCheck->bind_param('i', $userID);
    $uCheck->execute();
    $uRes = $uCheck->get_result();
    if ($uRes->num_rows === 0) {
        throw new Exception('User not found');
    }
    $userRow = $uRes->fetch_assoc();
    $uCheck->close();

    // Get all roles
    $roles = [];
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows === 1) {
        $rRes = $conn->query('SELECT RoleID, RoleName, Description FROM roles ORDER BY RoleName ASC');
        while ($r = $rRes->fetch_assoc()) { 
            $roles[(int)$r['RoleID']] = [ 
                'RoleID' => (int)$r['RoleID'], 
                'RoleName' => $r['RoleName'], 
                'Description' => $r['Description'], 
                'assigned' => false 
            ]; 
        }
    }

    // Get assigned roles
    if ($conn->query("SHOW TABLES LIKE 'userroles'")->num_rows === 1) {
        $aStmt = $conn->prepare('SELECT r.RoleID, r.RoleName, r.Description FROM userroles ur JOIN roles r ON ur.RoleID = r.RoleID WHERE ur.UserID = ?');
        $aStmt->bind_param('i', $userID);
        $aStmt->execute();
        $aRes = $aStmt->get_result();
        
        $assignedRoles = [];
        while ($ar = $aRes->fetch_assoc()) {
            $rid = (int)$ar['RoleID'];
            $assignedRoles[] = [
                'RoleID' => $rid,
                'RoleName' => $ar['RoleName'],
                'Description' => $ar['Description']
            ];
            if (isset($roles[$rid])) { 
                $roles[$rid]['assigned'] = true; 
            }
        }
        $aStmt->close();
    }

    json_response(true, 'User roles retrieved successfully', [
        'user' => [ 'UserID' => (int)$userRow['UserID'], 'Username' => $userRow['Username'] ],
        'roles' => isset($assignedRoles) ? $assignedRoles : [],
        'allRoles' => array_values($roles)
    ]);
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>

<?php
// get_roles.php - Retrieve all roles
include '../config.php';
header('Content-Type: application/json');

try {
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows !== 1) {
        throw new Exception("'roles' table does not exist");
    }

    $sql = 'SELECT RoleID, RoleName, Description FROM roles ORDER BY RoleName ASC';
    $result = $conn->query($sql);
    $roles = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $roles[] = [
                'RoleID' => (int)$row['RoleID'],
                'RoleName' => $row['RoleName'],
                'Description' => $row['Description']
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Roles retrieved successfully',
            'roles' => $roles,
            'total' => count($roles)
        ]);
    } else {
        throw new Exception('Query failed: ' . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving roles: ' . $e->getMessage(),
        'roles' => [],
        'total' => 0
    ]);
}

$conn->close();
?>

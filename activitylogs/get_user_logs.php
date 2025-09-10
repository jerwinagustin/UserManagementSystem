<?php
// get_user_logs.php - Retrieve activity logs for a specific user
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Only GET requests allowed');
    exit;
}

// Get user ID from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_GET['UserID']) ? (int)$_GET['UserID'] : null);

if (!$user_id) {
    json_response(false, 'user_id or UserID parameter is required');
    exit;
}

try {
    // Verify user exists
    $user_check = $conn->prepare("SELECT UserID, Username, Email FROM users WHERE UserID = ?");
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        json_response(false, 'User not found');
        exit;
    }
    
    $user_data = $user_result->fetch_assoc();
    $user_check->close();
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
    $action_filter = isset($_GET['action']) ? sanitize_input($_GET['action']) : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause for action filter
    $action_where = '';
    $action_param = [];
    $action_types = '';
    
    if ($action_filter) {
        $action_where = ' AND Action LIKE ?';
        $action_param[] = "%$action_filter%";
        $action_types = 's';
    }
    
    // Count total logs for this user
    $count_sql = "SELECT COUNT(*) as total FROM activitylogs WHERE UserID = ? $action_where";
    $count_stmt = $conn->prepare($count_sql);
    
    if ($action_filter) {
        $count_stmt->bind_param("i$action_types", $user_id, ...$action_param);
    } else {
        $count_stmt->bind_param("i", $user_id);
    }
    
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    
    // Get user's activity logs
    $sql = "SELECT 
                ActivityLogID,
                Action,
                Details,
                Timestamp
            FROM activitylogs 
            WHERE UserID = ? $action_where
            ORDER BY Timestamp DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($action_filter) {
        // Build parameters array for binding
        $params = array_merge([$user_id], $action_param, [$limit, $offset]);
        $stmt->bind_param("i{$action_types}ii", ...$params);
    } else {
        $stmt->bind_param("iii", $user_id, $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'ActivityLogID' => (int)$row['ActivityLogID'],
            'Action' => $row['Action'],
            'Details' => $row['Details'],
            'Timestamp' => $row['Timestamp']
        ];
    }
    
    $stmt->close();
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    
    json_response(true, 'User activity logs retrieved successfully', [
        'user' => [
            'UserID' => (int)$user_data['UserID'],
            'Username' => $user_data['Username'],
            'Email' => $user_data['Email']
        ],
        'logs' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => (int)$total_records,
            'per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ],
        'filters' => [
            'action' => $action_filter
        ]
    ]);
    
} catch (Exception $e) {
    json_response(false, 'Error retrieving user activity logs: ' . $e->getMessage());
}

$conn->close();
?>

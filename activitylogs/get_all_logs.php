<?php
// get_all_logs.php - Retrieve all activity logs with pagination and filtering
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(false, 'Only GET requests allowed');
    exit;
}

try {
    // Get query parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 20;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $action_filter = isset($_GET['action']) ? sanitize_input($_GET['action']) : null;
    $start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : null;
    $end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if ($user_id) {
        $where_conditions[] = "al.UserID = ?";
        $params[] = $user_id;
        $types .= 'i';
    }
    
    if ($action_filter) {
        $where_conditions[] = "al.Action LIKE ?";
        $params[] = "%$action_filter%";
        $types .= 's';
    }
    
    if ($start_date) {
        $where_conditions[] = "DATE(al.Timestamp) >= ?";
        $params[] = $start_date;
        $types .= 's';
    }
    
    if ($end_date) {
        $where_conditions[] = "DATE(al.Timestamp) <= ?";
        $params[] = $end_date;
        $types .= 's';
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records
    $count_sql = "SELECT COUNT(*) as total 
                  FROM activitylogs al 
                  LEFT JOIN users u ON al.UserID = u.UserID 
                  $where_clause";
    
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
    
    // Get logs with user information
    $sql = "SELECT 
                al.ActivityLogID,
                al.UserID,
                al.Action,
                al.Details,
                al.Timestamp,
                u.Username,
                u.Email
            FROM activitylogs al 
            LEFT JOIN users u ON al.UserID = u.UserID 
            $where_clause
            ORDER BY al.Timestamp DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    // Add limit and offset to parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'ActivityLogID' => (int)$row['ActivityLogID'],
            'UserID' => (int)$row['UserID'],
            'Username' => $row['Username'] ?? 'Unknown User',
            'Email' => $row['Email'] ?? '',
            'Action' => $row['Action'],
            'Details' => $row['Details'],
            'Timestamp' => $row['Timestamp']
        ];
    }
    
    $stmt->close();
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    
    json_response(true, 'Activity logs retrieved successfully', [
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
            'user_id' => $user_id,
            'action' => $action_filter,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]
    ]);
    
} catch (Exception $e) {
    json_response(false, 'Error retrieving activity logs: ' . $e->getMessage());
}

$conn->close();
?>

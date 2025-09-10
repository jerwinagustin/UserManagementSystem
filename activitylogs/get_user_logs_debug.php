<?php
// get_user_logs_debug.php - Debug version with detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// First, try to include config
try {
    include '../config.php';
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Config include failed: ' . $e->getMessage(),
        'debug' => 'Failed at config inclusion'
    ]);
    exit;
}

header('Content-Type: application/json');

// Check if we have a database connection
if (!isset($conn) || !$conn) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection not available',
        'debug' => 'No $conn variable or connection failed'
    ]);
    exit;
}

// Check if activitylogs table exists
try {
    $table_check = $conn->query("SHOW TABLES LIKE 'activitylogs'");
    if ($table_check->num_rows === 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'activitylogs table does not exist',
            'debug' => 'Table check failed'
        ]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error checking activitylogs table: ' . $e->getMessage(),
        'debug' => 'Table check exception'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

// Get user ID from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_GET['UserID']) ? (int)$_GET['UserID'] : null);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'user_id or UserID parameter is required']);
    exit;
}

try {
    // Verify user exists
    $user_check = $conn->prepare("SELECT UserID, Username, Email FROM users WHERE UserID = ?");
    if (!$user_check) {
        throw new Exception('User check prepare failed: ' . $conn->error);
    }
    
    $user_check->bind_param("i", $user_id);
    if (!$user_check->execute()) {
        throw new Exception('User check execute failed: ' . $user_check->error);
    }
    
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => "User with ID $user_id not found"]);
        exit;
    }
    
    $user_data = $user_result->fetch_assoc();
    $user_check->close();
    
    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
    $action_filter = isset($_GET['action']) ? trim($_GET['action']) : null;
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause for action filter
    $action_where = '';
    $action_param = [];
    $action_types = '';
    
    if ($action_filter && $action_filter !== '') {
        $action_where = ' AND Action LIKE ?';
        $action_param[] = "%$action_filter%";
        $action_types = 's';
    }
    
    // Count total logs for this user
    $count_sql = "SELECT COUNT(*) as total FROM activitylogs WHERE UserID = ? $action_where";
    $count_stmt = $conn->prepare($count_sql);
    
    if (!$count_stmt) {
        throw new Exception('Count prepare failed: ' . $conn->error);
    }
    
    if ($action_filter && $action_filter !== '') {
        $count_stmt->bind_param("i$action_types", $user_id, ...$action_param);
    } else {
        $count_stmt->bind_param("i", $user_id);
    }
    
    if (!$count_stmt->execute()) {
        throw new Exception('Count execute failed: ' . $count_stmt->error);
    }
    
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
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
    if (!$stmt) {
        throw new Exception('Main query prepare failed: ' . $conn->error);
    }
    
    if ($action_filter && $action_filter !== '') {
        // Build parameters array for binding
        $params = array_merge([$user_id], $action_param, [$limit, $offset]);
        $stmt->bind_param("i{$action_types}ii", ...$params);
    } else {
        $stmt->bind_param("iii", $user_id, $limit, $offset);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Main query execute failed: ' . $stmt->error);
    }
    
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
    
    echo json_encode([
        'success' => true, 
        'message' => 'User activity logs retrieved successfully', 
        'data' => [
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
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error retrieving user activity logs: ' . $e->getMessage(),
        'debug' => [
            'user_id' => $user_id,
            'file' => __FILE__,
            'line' => __LINE__
        ]
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>

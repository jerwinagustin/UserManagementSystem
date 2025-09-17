<?php
include '../config.php';

header('Content-Type: application/json');

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    json_response(false, 'Only POST or DELETE requests allowed');
    exit;
}

$logIdInput = null;

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['ActivityLogID'])) {
        $logIdInput = (int)$_GET['ActivityLogID'];
    } else {
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
                exit;
            }
            if (isset($decoded['ActivityLogID'])) {
                $logIdInput = (int)$decoded['ActivityLogID'];
            }
        }
    }
} else {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
        exit;
    }
    if (isset($decoded['ActivityLogID'])) {
        $logIdInput = (int)$decoded['ActivityLogID'];
    }
}

if (!$logIdInput) {
    json_response(false, 'ActivityLogID is required');
    exit;
}

try {
    $log_id = $logIdInput;
    
    $check_log = $conn->prepare("SELECT al.ActivityLogID, al.UserID, al.Action, al.Details, al.Timestamp, u.Username 
                                FROM activitylogs al 
                                LEFT JOIN users u ON al.UserID = u.UserID 
                                WHERE al.ActivityLogID = ?");
    $check_log->bind_param("i", $log_id);
    $check_log->execute();
    $result = $check_log->get_result();
    
    if ($result->num_rows === 0) {
        json_response(false, 'Activity log not found');
        exit;
    }
    
    $log_data = $result->fetch_assoc();
    $check_log->close();
    
    $delete_stmt = $conn->prepare("DELETE FROM activitylogs WHERE ActivityLogID = ?");
    $delete_stmt->bind_param("i", $log_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Failed to delete activity log: " . $delete_stmt->error);
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception("No activity log was deleted");
    }
    
    if ($log_data['UserID']) {
        log_activity($log_data['UserID'], 'Activity Log Deleted', 
                    "Deleted log entry: {$log_data['Action']} from {$log_data['Timestamp']}");
    }
    
    json_response(true, 'Activity log deleted successfully', [
        'deletedLogID' => $log_id,
        'deletedAction' => $log_data['Action'],
        'deletedTimestamp' => $log_data['Timestamp'],
        'username' => $log_data['Username'] ?? 'Unknown User'
    ]);
    
} catch (Exception $e) {
    json_response(false, 'Error deleting activity log: ' . $e->getMessage());
}

$conn->close();
?>

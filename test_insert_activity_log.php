<?php
// test_insert_activity_log.php - Test inserting activity logs
require_once 'config.php';

echo "<h2>Testing Activity Log Insertion</h2>";

try {
    // Check if activitylogs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'activitylogs'");
    if ($table_check->num_rows === 0) {
        echo "<p style='color: red;'>❌ activitylogs table does not exist!</p>";
        echo "<p>Please run the database_schema_update.sql file to create the table.</p>";
        exit;
    } else {
        echo "<p style='color: green;'>✅ activitylogs table exists</p>";
    }

    // Check table structure
    $structure = $conn->query("DESCRIBE activitylogs");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $col) {
            echo "<td>" . htmlspecialchars($col ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // Check if we have any users to test with
    $users_check = $conn->query("SELECT UserID, Username FROM users LIMIT 3");
    if ($users_check->num_rows === 0) {
        echo "<p style='color: red;'>❌ No users found in database!</p>";
        exit;
    }

    echo "<h3>Available Users for Testing:</h3>";
    echo "<ul>";
    $test_user_id = null;
    while ($user = $users_check->fetch_assoc()) {
        echo "<li>UserID: {$user['UserID']}, Username: {$user['Username']}</li>";
        if ($test_user_id === null) {
            $test_user_id = $user['UserID'];
        }
    }
    echo "</ul>";

    // Test the log_activity function
    echo "<h3>Testing log_activity Function:</h3>";
    
    $test_result = log_activity($test_user_id, 'Test Action', 'This is a test log entry created by test_insert_activity_log.php');
    
    if ($test_result) {
        echo "<p style='color: green;'>✅ log_activity function succeeded</p>";
    } else {
        echo "<p style='color: red;'>❌ log_activity function failed</p>";
    }

    // Check current activity logs count
    $count_result = $conn->query("SELECT COUNT(*) as total FROM activitylogs");
    $total_logs = $count_result->fetch_assoc()['total'];
    echo "<p><strong>Total activity logs in database:</strong> $total_logs</p>";

    // Show recent logs
    echo "<h3>Recent Activity Logs:</h3>";
    $recent_logs = $conn->query("SELECT al.*, u.Username FROM activitylogs al LEFT JOIN users u ON al.UserID = u.UserID ORDER BY al.Timestamp DESC LIMIT 5");
    
    if ($recent_logs->num_rows === 0) {
        echo "<p style='color: orange;'>⚠️ No activity logs found</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User</th><th>Action</th><th>Details</th><th>Timestamp</th></tr>";
        while ($log = $recent_logs->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['ActivityLogID']) . "</td>";
            echo "<td>" . htmlspecialchars($log['Username'] ?? 'Unknown') . " (ID: " . htmlspecialchars($log['UserID']) . ")</td>";
            echo "<td>" . htmlspecialchars($log['Action']) . "</td>";
            echo "<td>" . htmlspecialchars($log['Details']) . "</td>";
            echo "<td>" . htmlspecialchars($log['Timestamp']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Test the API endpoint
    echo "<h3>Testing API Endpoint:</h3>";
    $api_url = "activitylogs/get_user_logs_debug.php?user_id=$test_user_id&limit=3";
    echo "<p>Testing URL: <a href='$api_url' target='_blank'>$api_url</a></p>";
    
    echo "<p><a href='profile.php?id=$test_user_id' target='_blank'>Test Profile Page for User $test_user_id</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>

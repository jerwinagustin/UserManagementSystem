<?php
// dashboard_stats.php - Get dashboard statistics
include 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    $stats = array();

    // Get total users count
    $total_users_query = "SELECT COUNT(*) as total FROM users";
    $result = $conn->query($total_users_query);
    $stats['totalUsers'] = $result ? (int) $result->fetch_assoc()['total'] : 0;

    // Get active users count
    $active_users_query = "SELECT COUNT(*) as active FROM users WHERE Status = 'Active'";
    $result = $conn->query($active_users_query);
    $stats['activeUsers'] = $result ? (int) $result->fetch_assoc()['active'] : 0;

    // Get inactive users count
    $inactive_users_query = "SELECT COUNT(*) as inactive FROM users WHERE Status = 'Inactive'";
    $result = $conn->query($inactive_users_query);
    $stats['inactiveUsers'] = $result ? (int) $result->fetch_assoc()['inactive'] : 0;

    // Get users created today
    $today_users_query = "SELECT COUNT(*) as today FROM users WHERE DATE(CreatedAt) = CURDATE()";
    $result = $conn->query($today_users_query);
    $stats['usersToday'] = $result ? (int) $result->fetch_assoc()['today'] : 0;

    // Get users created this week
    $week_users_query = "SELECT COUNT(*) as week FROM users WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $result = $conn->query($week_users_query);
    $stats['usersThisWeek'] = $result ? (int) $result->fetch_assoc()['week'] : 0;

    // Get users created this month
    $month_users_query = "SELECT COUNT(*) as month FROM users WHERE MONTH(CreatedAt) = MONTH(CURRENT_DATE()) AND YEAR(CreatedAt) = YEAR(CURRENT_DATE())";
    $result = $conn->query($month_users_query);
    $stats['usersThisMonth'] = $result ? (int) $result->fetch_assoc()['month'] : 0;

    // Get recent user registrations (last 5)
    $recent_users_query = "SELECT Username, Email, CreatedAt FROM users ORDER BY CreatedAt DESC LIMIT 5";
    $result = $conn->query($recent_users_query);
    $recent_users = array();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $recent_users[] = array(
                'username' => $row['Username'],
                'email' => $row['Email'],
                'created' => $row['CreatedAt']
            );
        }
    }
    $stats['recentUsers'] = $recent_users;

    // Try to get activity logs count if table exists
    $activity_today_query = "SELECT COUNT(*) as activity FROM activity_logs WHERE DATE(Timestamp) = CURDATE()";
    $result = $conn->query($activity_today_query);
    $stats['todayActivity'] = $result ? (int) $result->fetch_assoc()['activity'] : 0;

    // Calculate percentage of active users
    if ($stats['totalUsers'] > 0) {
        $stats['activePercentage'] = round(($stats['activeUsers'] / $stats['totalUsers']) * 100, 1);
    } else {
        $stats['activePercentage'] = 0;
    }

    // Get user growth trend (compare with last month)
    $last_month_query = "SELECT COUNT(*) as last_month FROM users WHERE 
                         MONTH(CreatedAt) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
                         AND YEAR(CreatedAt) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
    $result = $conn->query($last_month_query);
    $last_month_users = $result ? (int) $result->fetch_assoc()['last_month'] : 0;

    // Roles count if roles table exists
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows === 1) {
        $roles_count_q = $conn->query("SELECT COUNT(*) as rc FROM roles");
        $stats['totalRoles'] = $roles_count_q ? (int)$roles_count_q->fetch_assoc()['rc'] : 0;
    } else {
        $stats['totalRoles'] = 0;
    }

    // Total assignments (userroles)
    if ($conn->query("SHOW TABLES LIKE 'userroles'")->num_rows === 1) {
        $assign_q = $conn->query("SELECT COUNT(*) as ac FROM userroles");
        $stats['totalAssignments'] = $assign_q ? (int)$assign_q->fetch_assoc()['ac'] : 0;
    } else {
        $stats['totalAssignments'] = 0;
    }

    if ($last_month_users > 0) {
        $growth_rate = (($stats['usersThisMonth'] - $last_month_users) / $last_month_users) * 100;
        $stats['growthRate'] = round($growth_rate, 1);
    } else {
        $stats['growthRate'] = $stats['usersThisMonth'] > 0 ? 100 : 0;
    }

    echo json_encode(array(
        'success' => true,
        'message' => 'Statistics retrieved successfully',
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Error retrieving statistics: ' . $e->getMessage(),
        'stats' => array(
            'totalUsers' => 0,
            'activeUsers' => 0,
            'inactiveUsers' => 0,
            'usersToday' => 0,
            'todayActivity' => 0,
            'recentUsers' => array()
        )
    ));
}

// Close connection
$conn->close();
?>
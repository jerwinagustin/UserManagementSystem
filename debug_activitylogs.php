<?php
// Simple test to check if activitylogs endpoint is accessible
echo "Testing activitylogs endpoint access...\n";

// Test 1: Check if file exists
$file_path = './activitylogs/get_user_logs.php';
if (file_exists($file_path)) {
    echo "✅ File exists: $file_path\n";
} else {
    echo "❌ File not found: $file_path\n";
}

// Test 2: Try to include config from activitylogs directory perspective
echo "\nTesting config.php inclusion from activitylogs directory...\n";
$config_from_activitylogs = './activitylogs/../config.php';
if (file_exists($config_from_activitylogs)) {
    echo "✅ Config accessible from activitylogs: $config_from_activitylogs\n";
} else {
    echo "❌ Config not accessible from activitylogs: $config_from_activitylogs\n";
}

// Test 3: Try to make a simple request
echo "\nTesting actual endpoint response...\n";
$url = 'http://localhost/UserManagementSystem/activitylogs/get_user_logs.php?user_id=1&limit=1';
echo "Testing URL: $url\n";

// Use curl if available
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $http_code\n";
    echo "Response:\n$response\n";
} else {
    echo "cURL not available, cannot test endpoint directly.\n";
}
?>

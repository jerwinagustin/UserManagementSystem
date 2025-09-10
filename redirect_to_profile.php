<?php
// redirect_to_profile.php - Example redirection handler for viewing user profiles
require_once __DIR__ . '/config.php';

// This file demonstrates how to redirect from the user management interface to a user's profile page
// Usage examples:
// 1. Direct link: redirect_to_profile.php?user_id=123
// 2. From JavaScript: window.location.href = 'redirect_to_profile.php?user_id=' + userId;
// 3. From form action: <form action="redirect_to_profile.php" method="get">

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    // Handle missing user_id parameter
    header("Location: index.php?error=" . urlencode("User ID is required to view profile"));
    exit;
}

try {
    // Verify that the user exists before redirecting
    $stmt = $conn->prepare("SELECT UserID, Username FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found, redirect back with error
        header("Location: index.php?error=" . urlencode("User with ID $user_id not found"));
        exit;
    }
    
    $stmt->close();
    
    // User exists, redirect to profile page
    header("Location: profile_page.php?user_id=" . $user_id);
    exit;
    
} catch (Exception $e) {
    // Database error, redirect back with error
    header("Location: index.php?error=" . urlencode("Database error: " . $e->getMessage()));
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Profile...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #f5f5f5;
        }
        .loading {
            color: #007bff;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="loading">
        <p>Redirecting to user profile...</p>
        <p>If you are not redirected automatically, <a href="profile_page.php?user_id=<?php echo $user_id; ?>">click here</a>.</p>
    </div>
    
    <script>
        // Fallback JavaScript redirect in case PHP headers don't work
        setTimeout(function() {
            window.location.href = 'profile_page.php?user_id=<?php echo $user_id; ?>';
        }, 2000);
    </script>
</body>
</html>

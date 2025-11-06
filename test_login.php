<?php
session_start();

echo "<h2>Admin Login Test</h2>";

// Check if admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo "<p style='color: green;'>✅ Admin is logged in!</p>";
    echo "<p>Admin ID: " . ($_SESSION['admin_id'] ?? 'Not set') . "</p>";
    echo "<p>Username: " . ($_SESSION['admin_username'] ?? 'Not set') . "</p>";
    echo "<p>Full Name: " . ($_SESSION['admin_name'] ?? 'Not set') . "</p>";
    echo "<p>Role: " . ($_SESSION['admin_role'] ?? 'Not set') . "</p>";
    
    echo "<br><a href='admin_dashboard.php'>Go to Admin Dashboard</a>";
} else {
    echo "<p style='color: red;'>❌ Admin is NOT logged in</p>";
    echo "<p>Session data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    echo "<br><a href='admin_login.php'>Go to Admin Login</a>";
}
?>

<br><br>
<a href="admin_login.php">← Back to Admin Login</a>

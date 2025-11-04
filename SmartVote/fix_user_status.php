<?php
require_once 'config/database.php';

// Script to check and fix user status issues
$database = new Database();
$conn = $database->getConnection();

echo "<h2>User Status Check and Fix</h2>";

try {
    // Check current users and their status
    $query = "SELECT id, voter_id, first_name, last_name, status, created_at FROM users";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Voter ID</th><th>Name</th><th>Status</th><th>Created</th><th>Action</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['voter_id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</td>";
        echo "<td style='color: " . ($user['status'] == 'active' ? 'green' : 'red') . ";'>" . strtoupper($user['status']) . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "<td>";
        if ($user['status'] != 'active') {
            echo "<a href='?fix_user=" . $user['id'] . "'>Set to Active</a>";
        } else {
            echo "OK";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Handle fix action
    if (isset($_GET['fix_user'])) {
        $user_id = $_GET['fix_user'];
        $update_query = "UPDATE users SET status = 'active' WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $result = $update_stmt->execute([$user_id]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ User ID $user_id status updated to 'active'</p>";
            echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update user status</p>";
        }
    }
    
    // Check for any users with inactive status
    $inactive_query = "SELECT COUNT(*) as count FROM users WHERE status != 'active'";
    $inactive_stmt = $conn->prepare($inactive_query);
    $inactive_stmt->execute();
    $inactive_count = $inactive_stmt->fetch()['count'];
    
    if ($inactive_count > 0) {
        echo "<h3>⚠️ Warning:</h3>";
        echo "<p style='color: orange;'>There are $inactive_count users with inactive status. These users cannot login.</p>";
        echo "<p><a href='?fix_all=1' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Fix All Users to Active</a></p>";
    }
    
    // Handle fix all action
    if (isset($_GET['fix_all'])) {
        $fix_all_query = "UPDATE users SET status = 'active' WHERE status != 'active'";
        $fix_all_stmt = $conn->prepare($fix_all_query);
        $result = $fix_all_stmt->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✓ All users status updated to 'active'</p>";
            echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update all users status</p>";
        }
    }
    
    echo "<h3>Session Management Test:</h3>";
    session_start();
    echo "<p><strong>Current Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Session Status:</strong> " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";
    
    if (isset($_SESSION['logged_in'])) {
        echo "<p><strong>Login Status:</strong> <span style='color: green;'>Logged In</span></p>";
        echo "<p><strong>User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
        echo "<p><strong>Voter ID:</strong> " . ($_SESSION['voter_id'] ?? 'Not set') . "</p>";
    } else {
        echo "<p><strong>Login Status:</strong> <span style='color: red;'>Not Logged In</span></p>";
    }
    
    echo "<h3>Recommendations:</h3>";
    echo "<ul>";
    echo "<li>The 'status' field in the users table should remain 'active' for all valid users</li>";
    echo "<li>Login/logout status is managed by PHP sessions, not database status</li>";
    echo "<li>If you're having logout issues, clear your browser cookies and try again</li>";
    echo "<li>Make sure your web server has proper session configuration</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 20px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>

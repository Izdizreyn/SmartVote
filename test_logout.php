<?php
require_once 'classes/UserAuth.php';

$auth = new UserAuth();

echo "<h2>Logout Test Page</h2>";

// Show current session status
echo "<h3>Before Logout:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Is Logged In:</strong> " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "</p>";

if (isset($_SESSION['user_id'])) {
    echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";
    echo "<p><strong>Voter ID:</strong> " . ($_SESSION['voter_id'] ?? 'Not set') . "</p>";
    echo "<p><strong>Full Name:</strong> " . ($_SESSION['full_name'] ?? 'Not set') . "</p>";
}

// Handle logout
if (isset($_GET['logout'])) {
    echo "<h3>Performing Logout...</h3>";
    $result = $auth->logout();
    echo "<p style='color: " . ($result['success'] ? 'green' : 'red') . ";'>" . $result['message'] . "</p>";
    
    echo "<h3>After Logout:</h3>";
    echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
    echo "<p><strong>Is Logged In:</strong> " . ($auth->isLoggedIn() ? 'YES' : 'NO') . "</p>";
    
    if (isset($_SESSION['user_id'])) {
        echo "<p style='color: red;'><strong>WARNING:</strong> Session data still exists!</p>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    } else {
        echo "<p style='color: green;'><strong>SUCCESS:</strong> Session data cleared!</p>";
    }
    
    echo "<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 3000);</script>";
} else {
    echo "<p><a href='?logout=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Logout</a></p>";
}

echo "<h3>Session Debug Info:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h3>Instructions:</h3>";
echo "<ul>";
echo "<li>Click 'Test Logout' to test the logout functionality</li>";
echo "<li>After logout, you should see 'Is Logged In: NO'</li>";
echo "<li>If session data still exists after logout, there's a problem</li>";
echo "<li>Try clearing your browser cookies if logout doesn't work</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>

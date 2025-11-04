<?php
require_once 'config/database.php';

echo "<h2>Reset Admin Password</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        echo "<p style='color: red;'>❌ Database connection failed!</p>";
        exit;
    }
    
    // Reset admin password to 'admin123'
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // First, check if admin user exists
    $query = "SELECT id FROM admin_users WHERE username = 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        // Update existing admin
        $query = "UPDATE admin_users SET password_hash = ?, status = 'active' WHERE username = 'admin'";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$hashed_password]);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin password updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update admin password!</p>";
        }
    } else {
        // Create new admin user
        $query = "INSERT INTO admin_users (username, password_hash, full_name, email, role, status) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute(['admin', $hashed_password, 'System Administrator', 'admin@smartvote.com', 'super_admin', 'active']);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create admin user!</p>";
        }
    }
    
    echo "<p><strong>Admin Credentials:</strong></p>";
    echo "<p>Username: <code>admin</code></p>";
    echo "<p>Password: <code>admin123</code></p>";
    echo "<p>Role: <code>super_admin</code></p>";
    
    // Verify the password
    $query = "SELECT password_hash FROM admin_users WHERE username = 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        $is_valid = password_verify('admin123', $admin['password_hash']);
        if ($is_valid) {
            echo "<p style='color: green;'>✅ Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password verification failed!</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<br><br>
<a href="admin_login.php">← Back to Admin Login</a>

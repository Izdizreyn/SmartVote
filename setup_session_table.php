<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Read the SQL file
    $sql = file_get_contents('database/create_active_sessions_table.sql');
    
    // Execute the SQL
    $conn->exec($sql);
    
    echo "Active sessions table created successfully!\n";
    echo "Session restriction functionality is now ready to use.\n";
    
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>

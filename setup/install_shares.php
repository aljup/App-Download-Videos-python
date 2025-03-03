<?php
require_once '../includes/db_connect.php';

try {
    // Create shares table
    $query = "CREATE TABLE IF NOT EXISTS shares (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id INT NOT NULL,
        share_code VARCHAR(10) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL,
        views INT DEFAULT 0,
        max_views INT DEFAULT NULL,
        FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($query);
    echo "Shares table created successfully.<br>";
    
    // Create share_logs table for tracking
    $query = "CREATE TABLE IF NOT EXISTS share_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        share_id INT NOT NULL,
        accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        FOREIGN KEY (share_id) REFERENCES shares(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($query);
    echo "Share logs table created successfully.";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}
?>

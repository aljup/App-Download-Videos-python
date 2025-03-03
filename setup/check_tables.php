<?php
require_once '../includes/db_connect.php';

// Check if shares table exists, if not create it
try {
    $result = $pdo->query("SHOW TABLES LIKE 'shares'");
    if ($result->rowCount() == 0) {
        $pdo->exec("CREATE TABLE shares (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_id INT NOT NULL,
            share_code VARCHAR(10) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            views INT DEFAULT 0,
            max_views INT DEFAULT NULL,
            FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
        )");
        echo "Shares table created successfully.<br>";
    }

    $result = $pdo->query("SHOW TABLES LIKE 'share_logs'");
    if ($result->rowCount() == 0) {
        $pdo->exec("CREATE TABLE share_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            share_id INT NOT NULL,
            accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            FOREIGN KEY (share_id) REFERENCES shares(id) ON DELETE CASCADE
        )");
        echo "Share logs table created successfully.<br>";
    }
} catch (PDOException $e) {
    die("Error checking/creating tables: " . $e->getMessage());
}

echo "All required tables are present.";

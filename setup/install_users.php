<?php
require_once '../includes/db_connect.php';

try {
    // Create users table
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        storage_limit BIGINT NOT NULL DEFAULT 5368709120, -- 5GB in bytes
        storage_used BIGINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL,
        is_admin BOOLEAN DEFAULT FALSE
    )";
    
    $pdo->exec($query);
    
    // Create default admin user
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_email = 'admin@aljup.com';
    
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $admin_username]);
    
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (:username, :password, :email, TRUE)");
        $stmt->execute([
            ':username' => $admin_username,
            ':password' => $admin_password,
            ':email' => $admin_email
        ]);
        echo "Admin user created successfully!<br>";
    }
    
    echo "Users table created successfully!";
} catch (PDOException $e) {
    die("Error creating users table: " . $e->getMessage());
}
?>

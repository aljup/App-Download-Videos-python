<?php
require_once '../includes/db_connect.php';

try {
    // Create files table
    $query = "CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        filepath VARCHAR(255) NOT NULL,
        filetype VARCHAR(100) NOT NULL,
        filesize INT NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_id INT DEFAULT NULL,
        folder_id INT DEFAULT NULL,
        public BOOLEAN DEFAULT FALSE
    )";
    
    $pdo->exec($query);
    echo "Files table created successfully.<br>";
    
    // Create folders table
    $query = "CREATE TABLE IF NOT EXISTS folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        parent_id INT DEFAULT NULL,
        user_id INT DEFAULT NULL,
        created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($query);
    echo "Folders table created successfully.<br>";
    
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
    echo "Users table created successfully.<br>";
    
    // Create default admin user (if not exists)
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_email = 'admin@example.com';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    
    $stmt->execute(['username' => $admin_username]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, is_admin) VALUES (:username, :password, :email, :is_admin)");
        $stmt->execute([
            'username' => $admin_username,
            'password' => $admin_password,
            'email' => $admin_email,
            'is_admin' => true
        ]);
        echo "Default admin user created successfully.<br>";
    } else {
        echo "Default admin user already exists.<br>";
    }
    
    echo "Database tables created successfully!";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}
?>

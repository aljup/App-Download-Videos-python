<?php
class User {
    private $pdo;
    private $id;
    private $username;
    private $email;
    private $storage_limit;
    private $storage_used;
    private $is_admin;
    private $is_logged_in = false;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        session_start();
        
        // Check if user is already logged in
        if (isset($_SESSION['user_id'])) {
            $this->loadUserById($_SESSION['user_id']);
        }
    }

    public function register($username, $password, $email) {
        // Check if username already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Create new user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, password, email, storage_limit) VALUES (:username, :password, :email, :storage_limit)");
        
        $default_storage = 5 * 1024 * 1024 * 1024; // 5GB in bytes
        
        try {
            $stmt->execute([
                ':username' => $username,
                ':password' => $hashed_password,
                ':email' => $email,
                ':storage_limit' => $default_storage
            ]);
            
            return ['success' => true, 'message' => 'Registration successful'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Update last login time
            $update_stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
            $update_stmt->execute([':id' => $user['id']]);
            
            // Save user data in session
            $_SESSION['user_id'] = $user['id'];
            
            // Load user data
            $this->loadUserById($user['id']);
            
            return ['success' => true, 'message' => 'Login successful'];
        }
        
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    public function logout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Reset user properties
        $this->id = null;
        $this->username = null;
        $this->email = null;
        $this->storage_limit = null;
        $this->storage_used = null;
        $this->is_admin = false;
        $this->is_logged_in = false;
        
        return ['success' => true, 'message' => 'Logout successful'];
    }
    
    private function loadUserById($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $this->id = $user['id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->storage_limit = $user['storage_limit'];
            $this->storage_used = $user['storage_used'];
            $this->is_admin = $user['is_admin'];
            $this->is_logged_in = true;
            return true;
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return $this->is_logged_in;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function isAdmin() {
        return $this->is_admin;
    }
    
    public function getStorageLimit() {
        return $this->storage_limit;
    }
    
    public function getStorageUsed() {
        return $this->storage_used;
    }
    
    public function getRemainingStorage() {
        return $this->storage_limit - $this->storage_used;
    }
    
    public function hasEnoughStorage($fileSize) {
        return ($this->storage_used + $fileSize) <= $this->storage_limit;
    }
    
    public function updateStorageUsed($additionalBytes) {
        if ($this->id) {
            $newUsed = $this->storage_used + $additionalBytes;
            $stmt = $this->pdo->prepare("UPDATE users SET storage_used = :storage_used WHERE id = :id");
            $stmt->execute([
                ':storage_used' => $newUsed,
                ':id' => $this->id
            ]);
            
            $this->storage_used = $newUsed;
            return true;
        }
        return false;
    }
    
    public function setStorageLimit($newLimit) {
        if ($this->id) {
            $stmt = $this->pdo->prepare("UPDATE users SET storage_limit = :storage_limit WHERE id = :id");
            $stmt->execute([
                ':storage_limit' => $newLimit,
                ':id' => $this->id
            ]);
            
            $this->storage_limit = $newLimit;
            return true;
        }
        return false;
    }
    
    // Format storage size to human-readable format
    public function formatStorage($bytes) {
        $units = ['bytes', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    // Get storage usage as percentage
    public function getStoragePercentage() {
        if ($this->storage_limit > 0) {
            return round(($this->storage_used / $this->storage_limit) * 100, 2);
        }
        return 0;
    }
    
    // Get all users (admin function)
    public function getAllUsers() {
        if (!$this->is_admin) {
            return null;
        }
        
        $stmt = $this->pdo->prepare("SELECT id, username, email, storage_limit, storage_used, created_at, last_login, is_admin FROM users ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Update user (admin function)
    public function updateUser($userId, $data) {
        if (!$this->is_admin) {
            return ['success' => false, 'message' => 'Permission denied'];
        }
        
        $validFields = ['storage_limit', 'is_admin'];
        $updates = [];
        $params = [':id' => $userId];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $validFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'No valid fields to update'];
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        try {
            $stmt->execute($params);
            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
}
?>

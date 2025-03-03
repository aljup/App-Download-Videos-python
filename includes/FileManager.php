<?php
class FileManager {
    private $pdo;
    private $uploadDirectory;
    private $user;
    
    public function __construct($pdo, $user = null) {
        $this->pdo = $pdo;
        $this->user = $user;
        $this->uploadDirectory = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }
    
    public function uploadFile($file, $folderId = null, $userId = null, $isPublic = false) {
        if ($file['error'] != 0) {
            return ['success' => false, 'message' => 'Upload error: ' . $this->getFileErrorMessage($file['error'])];
        }
        
        $filename = basename($file['name']);
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $tempPath = $file['tmp_name'];
        
        // If user is provided, check storage quota
        if ($this->user && $this->user->isLoggedIn()) {
            $userId = $this->user->getId();
            
            // Check if user has enough storage
            if (!$this->user->hasEnoughStorage($fileSize)) {
                return ['success' => false, 'message' => 'Storage quota exceeded. Please free up some space.'];
            }
        }
        
        // Generate a unique filename to prevent overwriting
        $uniqueFilename = time() . '_' . $filename;
        $targetPath = $this->uploadDirectory . $uniqueFilename;
        
        if (move_uploaded_file($tempPath, $targetPath)) {
            $stmt = $this->pdo->prepare("INSERT INTO files (filename, filepath, filetype, filesize, folder_id, user_id, public) 
                                        VALUES (:filename, :filepath, :filetype, :filesize, :folder_id, :user_id, :public)");
            
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':filepath', $uniqueFilename);
            $stmt->bindParam(':filetype', $fileType);
            $stmt->bindParam(':filesize', $fileSize);
            $stmt->bindParam(':folder_id', $folderId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':public', $isPublic, PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                // Update user's storage usage
                if ($this->user && $this->user->isLoggedIn()) {
                    $this->user->updateStorageUsed($fileSize);
                }
                
                return ['success' => true, 'message' => 'File uploaded successfully', 'id' => $this->pdo->lastInsertId()];
            } else {
                // Delete the file if database insert fails
                unlink($targetPath);
                return ['success' => false, 'message' => 'Database error while uploading file'];
            }
        }
        
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    public function getFiles($folderId = null, $userId = null) {
        $sql = "SELECT * FROM files WHERE 1=1";
        $params = [];
        
        if ($folderId !== null) {
            $sql .= " AND folder_id = :folder_id";
            $params[':folder_id'] = $folderId;
        }
        
        // If specific user ID is passed, use it
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        } 
        // Otherwise, if user object exists and logged in, get their files
        else if ($this->user && $this->user->isLoggedIn() && !$this->user->isAdmin()) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $this->user->getId();
        }
        
        $sql .= " ORDER BY upload_date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getFolders($parentId = null, $userId = null) {
        $sql = "SELECT * FROM folders WHERE 1=1";
        $params = [];
        
        if ($parentId !== null) {
            $sql .= " AND parent_id = :parent_id";
            $params[':parent_id'] = $parentId;
        } else {
            $sql .= " AND parent_id IS NULL";
        }
        
        // If specific user ID is passed, use it
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $userId;
        } 
        // Otherwise, if user object exists and logged in, get their folders
        else if ($this->user && $this->user->isLoggedIn() && !$this->user->isAdmin()) {
            $sql .= " AND user_id = :user_id";
            $params[':user_id'] = $this->user->getId();
        }
        
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function createFolder($name, $parentId = null, $userId = null) {
        // If no user ID provided but user object exists and logged in
        if ($userId === null && $this->user && $this->user->isLoggedIn()) {
            $userId = $this->user->getId();
        }
        
        $stmt = $this->pdo->prepare("INSERT INTO folders (name, parent_id, user_id) VALUES (:name, :parent_id, :user_id)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':parent_id', $parentId);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Failed to create folder'];
    }
    
    public function deleteFile($fileId) {
        // First get the file info to delete the actual file
        $stmt = $this->pdo->prepare("SELECT filepath, filesize, user_id FROM files WHERE id = :id");
        $stmt->bindParam(':id', $fileId);
        $stmt->execute();
        
        $file = $stmt->fetch();
        if (!$file) {
            return false;
        }
        
        // Check if user has permission to delete this file
        if ($this->user && !$this->user->isAdmin() && $file['user_id'] != $this->user->getId()) {
            return false;
        }
        
        // Delete the physical file
        if (file_exists($this->uploadDirectory . $file['filepath'])) {
            unlink($this->uploadDirectory . $file['filepath']);
        }
        
        // Delete the database record
        $stmt = $this->pdo->prepare("DELETE FROM files WHERE id = :id");
        $stmt->bindParam(':id', $fileId);
        $result = $stmt->execute();
        
        // Update user's storage usage
        if ($result && $this->user && $file['user_id'] == $this->user->getId()) {
            $this->user->updateStorageUsed(-$file['filesize']);
        }
        
        return $result;
    }
    
    public function deleteFolder($folderId) {
        // Check if folder belongs to current user (if not admin)
        if ($this->user && !$this->user->isAdmin()) {
            $stmt = $this->pdo->prepare("SELECT user_id FROM folders WHERE id = :id");
            $stmt->bindParam(':id', $folderId);
            $stmt->execute();
            $folder = $stmt->fetch();
            
            if (!$folder || $folder['user_id'] != $this->user->getId()) {
                return false;
            }
        }
        
        // First delete all files in the folder and track storage to update
        $storageFreed = 0;
        if ($this->user) {
            $stmt = $this->pdo->prepare("SELECT id, filesize FROM files WHERE folder_id = :folder_id AND user_id = :user_id");
            $stmt->execute([
                ':folder_id' => $folderId,
                ':user_id' => $this->user->getId()
            ]);
            
            while ($file = $stmt->fetch()) {
                $this->deleteFile($file['id']);
                $storageFreed += $file['filesize'];
            }
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM files WHERE folder_id = :folder_id");
            $stmt->bindParam(':folder_id', $folderId);
            $stmt->execute();
        }
        
        // Delete subfolders
        $stmt = $this->pdo->prepare("SELECT id FROM folders WHERE parent_id = :parent_id");
        $stmt->bindParam(':parent_id', $folderId);
        $stmt->execute();
        
        while ($subfolder = $stmt->fetch()) {
            $this->deleteFolder($subfolder['id']);
        }
        
        // Delete the folder itself
        $stmt = $this->pdo->prepare("DELETE FROM folders WHERE id = :id");
        $stmt->bindParam(':id', $folderId);
        
        return $stmt->execute();
    }
    
    private function getFileErrorMessage($code) {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload";
            default:
                return "Unknown upload error";
        }
    }
    
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    public function getFileIcon($fileType) {
        if (strpos($fileType, 'image/') === 0) {
            return 'fas fa-file-image';
        } elseif (strpos($fileType, 'video/') === 0) {
            return 'fas fa-file-video';
        } elseif (strpos($fileType, 'audio/') === 0) {
            return 'fas fa-file-audio';
        } elseif (strpos($fileType, 'text/') === 0) {
            return 'fas fa-file-alt';
        } elseif ($fileType == 'application/pdf') {
            return 'fas fa-file-pdf';
        } elseif (strpos($fileType, 'application/') === 0) {
            return 'fas fa-file-archive';
        } else {
            return 'fas fa-file';
        }
    }
}
?>

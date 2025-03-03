<?php
class ShareManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function createShare($fileId, $maxViews = null, $expiresIn = null) {
        try {
            // التحقق من وجود الملف أولاً
            $fileCheck = $this->pdo->prepare("SELECT id FROM files WHERE id = ?");
            $fileCheck->execute([$fileId]);
            if (!$fileCheck->fetch()) {
                error_log("ShareManager: File ID $fileId not found");
                return ['success' => false, 'message' => 'File not found'];
            }

            // إنشاء كود المشاركة
            $shareCode = $this->generateShareCode();
            $expiresAt = null;

            // التحقق من التكرار
            $exists = $this->pdo->prepare("SELECT id FROM shares WHERE share_code = ?");
            $exists->execute([$shareCode]);
            if ($exists->fetch()) {
                error_log("ShareManager: Duplicate share code generated");
                return ['success' => false, 'message' => 'Failed to generate unique share code'];
            }

            // حساب وقت الانتهاء
            if ($expiresIn) {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiresIn hours"));
            }

            // إدخال البيانات
            $stmt = $this->pdo->prepare("
                INSERT INTO shares (file_id, share_code, expires_at, max_views, created_at)
                VALUES (:file_id, :share_code, :expires_at, :max_views, NOW())
            ");
            
            $params = [
                ':file_id' => $fileId,
                ':share_code' => $shareCode,
                ':expires_at' => $expiresAt,
                ':max_views' => $maxViews
            ];

            error_log("ShareManager: Attempting to create share with params: " . json_encode($params));
            
            if (!$stmt->execute($params)) {
                $error = $stmt->errorInfo();
                error_log("ShareManager: Database error: " . json_encode($error));
                return ['success' => false, 'message' => 'Database error while creating share'];
            }

            $shareId = $this->pdo->lastInsertId();
            error_log("ShareManager: Share created successfully with ID: $shareId");

            return [
                'success' => true,
                'share_code' => $shareCode,
                'share_url' => $this->getShareUrl($shareCode),
                'expires_at' => $expiresAt,
                'id' => $shareId
            ];

        } catch (PDOException $e) {
            error_log("ShareManager Exception: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Database error while creating share',
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getShare($shareCode) {
        try {
            // Split share code and hash if exists
            $parts = explode('_', $shareCode);
            $originalCode = $parts[0];
            $timeHash = $parts[1] ?? null;

            $stmt = $this->pdo->prepare("
                SELECT s.*, f.filename, f.filepath, f.filetype, f.filesize
                FROM shares s
                JOIN files f ON s.file_id = f.id
                WHERE s.share_code LIKE :share_code
            ");
            
            $stmt->execute([':share_code' => $originalCode . '%']);
            $share = $stmt->fetch();
            
            if (!$share) {
                return ['success' => false, 'message' => 'Share link not found'];
            }
            
            // Validate time hash if exists
            if ($timeHash && $share['expires_at']) {
                $expectedHash = $this->generateTimeHash($originalCode, $share['expires_at']);
                if ($timeHash !== $expectedHash) {
                    return ['success' => false, 'message' => 'Invalid share link'];
                }
            }

            // Check if expired
            if ($share['expires_at'] && strtotime($share['expires_at']) < time()) {
                return ['success' => false, 'message' => 'Share link has expired'];
            }
            
            // Check max views
            if ($share['max_views'] && $share['views'] >= $share['max_views']) {
                return ['success' => false, 'message' => 'Maximum views reached'];
            }
            
            // Log access
            $this->logAccess($share['id']);
            
            // Update views counter
            $this->pdo->prepare("UPDATE shares SET views = views + 1 WHERE id = ?")->execute([$share['id']]);
            
            return ['success' => true, 'data' => $share];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error accessing share'];
        }
    }
    
    private function generateShareCode($length = 8) {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    private function getShareUrl($shareCode) {
        return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . 
               $_SERVER['HTTP_HOST'] . '/s/?code=' . $shareCode;
    }
    
    private function logAccess($shareId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO share_logs (share_id, ip_address, user_agent)
                VALUES (:share_id, :ip_address, :user_agent)
            ");
            
            $stmt->execute([
                ':share_id' => $shareId,
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':user_agent' => $_SERVER['HTTP_USER_AGENT']
            ]);
        } catch (PDOException $e) {
            // Silent fail for logging
        }
    }
    
    public function getShareStats($shareId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, 
                       COUNT(sl.id) as total_views,
                       COUNT(DISTINCT sl.ip_address) as unique_views
                FROM shares s
                LEFT JOIN share_logs sl ON s.id = sl.share_id
                WHERE s.id = :share_id
                GROUP BY s.id
            ");
            
            $stmt->execute([':share_id' => $shareId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    private function generateTimeHash($shareCode, $expiresAt) {
        // Create a unique hash based on share code and expiration time
        $secret = 'your-secret-key-here'; // تغيير هذا المفتاح إلى قيمة سرية خاصة بك
        return substr(hash('sha256', $shareCode . $expiresAt . $secret), 0, 8);
    }
}
?>

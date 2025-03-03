<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';
require_once '../includes/ShareManager.php';
require_once '../includes/User.php';

// تعريف وضع التصحيح
define('DEBUG', false);

try {
    error_log("Create Share API: Starting share creation process");
    
    // التحقق من المستخدم
    $user = new User($pdo);
    if (!$user->isLoggedIn()) {
        throw new Exception('User not authorized');
    }
    
    error_log("Create Share API: User authenticated: " . $user->getId());

    // التحقق من البيانات المستلمة
    $rawData = file_get_contents('php://input');
    error_log("Create Share API: Received raw data: " . $rawData);
    
    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!isset($data['file_id'])) {
        throw new Exception('File ID is missing');
    }

    // التحقق من الملف
    $stmt = $pdo->prepare("SELECT id, user_id, public FROM files WHERE id = ?");
    $stmt->execute([$data['file_id']]);
    $file = $stmt->fetch();

    if (!$file) {
        throw new Exception("File not found: {$data['file_id']}");
    }

    if ($file['user_id'] != $user->getId() && !$file['public'] && !$user->isAdmin()) {
        throw new Exception('Access denied: You do not have permission to share this file');
    }

    // إنشاء المشاركة
    $shareManager = new ShareManager($pdo);
    $maxViews = isset($data['max_views']) && is_numeric($data['max_views']) ? (int)$data['max_views'] : null;
    $expiresIn = isset($data['expires_in']) && is_numeric($data['expires_in']) ? (int)$data['expires_in'] : 24;

    // تسجيل محاولة إنشاء المشاركة
    error_log("Creating share for file_id: {$data['file_id']}, max_views: $maxViews, expires_in: $expiresIn");
    
    $result = $shareManager->createShare(
        $data['file_id'],
        $maxViews,
        $expiresIn
    );

    if (!$result['success']) {
        throw new Exception($result['message'] ?? 'Failed to create share link');
    }

    // تسجيل نجاح العملية
    error_log("Share created successfully: " . json_encode($result));
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Create Share API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => DEBUG ? [
            'file_id' => $data['file_id'] ?? null,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}

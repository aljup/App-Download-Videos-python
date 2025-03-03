<?php
require_once 'includes/db_connect.php';
require_once 'includes/User.php';

$user = new User($pdo);

// Check if user is logged in
if (!$user->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$fileId = $data['file_id'] ?? null;
$public = $data['public'] ?? false;

if ($fileId === null) {
    echo json_encode(['success' => false, 'message' => 'Missing file ID']);
    exit;
}

// Update file visibility
try {
    $stmt = $pdo->prepare("UPDATE files SET public = :public WHERE id = :id AND user_id = :user_id");
    $result = $stmt->execute([
        ':public' => $public,
        ':id' => $fileId,
        ':user_id' => $user->getId()
    ]);
    
    echo json_encode(['success' => $result]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

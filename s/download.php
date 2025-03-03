<?php
require_once '../includes/db_connect.php';
require_once '../includes/ShareManager.php';

$shareManager = new ShareManager($pdo);

if (isset($_GET['code'])) {
    $result = $shareManager->getShare($_GET['code']);
    
    if ($result['success']) {
        $file = $result['data'];
        $filepath = '../uploads/' . $file['filepath'];
        
        if (file_exists($filepath)) {
            header('Content-Type: ' . $file['filetype']);
            header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
            header('Content-Length: ' . $file['filesize']);
            readfile($filepath);
            exit;
        }
    }
}

// If we get here, something went wrong
header('Location: index.php');
exit;

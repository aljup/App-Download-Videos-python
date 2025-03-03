<?php
require_once '../includes/db_connect.php';
require_once '../includes/ShareManager.php';

// إضافة التحقق من الخطأ
error_reporting(E_ALL);
ini_set('display_errors', 1);

$shareManager = new ShareManager($pdo);
$message = '';
$file = null;

// التحقق من وجود الكود
if (isset($_GET['code']) && !empty($_GET['code'])) {
    $shareCode = strip_tags($_GET['code']);
    $result = $shareManager->getShare($shareCode);
    
    if ($result['success']) {
        $file = $result['data'];
        // إضافة عرض الوقت المتبقي إذا كان هناك وقت انتهاء
        if (!empty($file['expires_at'])) {
            $remainingTime = strtotime($file['expires_at']) - time();
            $file['remaining_hours'] = floor($remainingTime / 3600);
            $file['remaining_minutes'] = floor(($remainingTime % 3600) / 60);
        }
    } else {
        $message = $result['message'];
    }
} else {
    // عرض صفحة 404 مخصصة
    http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Not Found - Cloud File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="text-center">
        <div class="text-8xl text-gray-300 mb-8">
            <i class="fas fa-link-slash"></i>
        </div>
        <h1 class="text-4xl font-bold text-gray-800 mb-4">Invalid Share Link</h1>
        <p class="text-lg text-gray-600 mb-8">The shared file link you're trying to access doesn't exist or has expired.</p>
        <a href="/" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center">
            <i class="fas fa-home mr-2"></i>
            Return Home
        </a>
    </div>
</body>
</html>
<?php
    exit;
}

// تعديل زر التحميل ليستخدم نفس الصفحة
$downloadUrl = isset($shareCode) ? "?code=" . $shareCode : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared File - Preview</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .preview-container {
            max-width: 100%;
            max-height: 400px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 0.5rem;
        }
        .preview-container img {
            max-width: 100%;
            max-height: 400px;
            object-fit: contain;
        }
        .preview-container video {
            max-width: 100%;
            max-height: 400px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6">
            <?php if ($message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    <?php echo $message; ?>
                </div>
            <?php elseif ($file): ?>
                <div class="text-center mb-6">
                    <!-- معاينة الملف -->
                    <div class="preview-container mb-6">
                        <?php if (strpos($file['filetype'], 'image/') === 0): ?>
                            <img src="../uploads/<?php echo $file['filepath']; ?>" alt="File preview" class="rounded-lg">
                        <?php elseif (strpos($file['filetype'], 'video/') === 0): ?>
                            <video controls class="rounded-lg">
                                <source src="../uploads/<?php echo $file['filepath']; ?>" type="<?php echo $file['filetype']; ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <div class="text-6xl text-gray-400 py-12">
                                <i class="fas <?php 
                                    if (strpos($file['filetype'], 'audio/') === 0) echo 'fa-file-audio';
                                    elseif ($file['filetype'] === 'application/pdf') echo 'fa-file-pdf';
                                    elseif (strpos($file['filetype'], 'application/') === 0) echo 'fa-file-archive';
                                    else echo 'fa-file-alt';
                                ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- معلومات الملف -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                            <?php 
                                $ext = pathinfo($file['filename'], PATHINFO_EXTENSION);
                                echo strtoupper($ext);
                            ?>
                        </div>
                        <div class="mt-2 text-gray-600">
                            <?php echo number_format($file['filesize'] / 1024 / 1024, 2); ?> MB
                        </div>
                    </div>
                    
                    <!-- معلومات إضافية -->
                    <?php if ($file['expires_at'] || $file['max_views']): ?>
                        <div class="text-sm text-gray-500 space-y-1 mb-6">
                            <?php if ($file['expires_at']): ?>
                                <p class="flex items-center justify-center">
                                    <i class="fas fa-clock mr-2"></i>
                                    Expires: <?php echo date('M d, Y H:i', strtotime($file['expires_at'])); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($file['max_views']): ?>
                                <p class="flex items-center justify-center">
                                    <i class="fas fa-eye mr-2"></i>
                                    Downloads: <?php echo $file['views']; ?>/<?php echo $file['max_views']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- إضافة عرض الوقت المتبقي في القالب -->
                    <?php if (!empty($file['expires_at'])): ?>
                        <div class="text-sm text-gray-500 mb-4"></div>
                            <p class="flex items-center justify-center">
                                <i class="fas fa-clock mr-2"></i>
                                Time remaining: 
                                <?php if ($file['remaining_hours'] > 0): ?>
                                    <?php echo $file['remaining_hours']; ?> hours
                                <?php endif; ?>
                                <?php if ($file['remaining_minutes'] > 0): ?>
                                    <?php echo $file['remaining_minutes']; ?> minutes
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- أزرار التحميل والمعاينة -->
                <div class="flex justify-center space-x-4">
                    <a href="/s/<?php echo $downloadUrl; ?>" 
                       class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center"
                       <?php echo isset($_GET['download']) ? '' : 'onclick="this.href += \'&download=1\'; return true;"'; ?>>
                        <i class="fas fa-download mr-2"></i>
                        Download File
                    </a>
                    
                    <button onclick="showShareModal()" 
                            class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center">
                        <i class="fas fa-share-alt mr-2"></i>
                        Share
                    </button>
                    
                    <?php if (strpos($file['filetype'], 'image/') === 0 || strpos($file['filetype'], 'video/') === 0): ?>
                        <a href="../uploads/<?php echo $file['filepath']; ?>" target="_blank"
                           class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg inline-flex items-center">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            Open in New Tab
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (strpos($file['filetype'] ?? '', 'audio/') === 0): ?>
    <!-- مشغل الصوت -->
    <div class="fixed bottom-0 left-0 right-0 bg-white shadow-lg p-4">
        <audio controls class="w-full">
            <source src="../uploads/<?php echo $file['filepath']; ?>" type="<?php echo $file['filetype']; ?>">
            Your browser does not support the audio element.
        </audio>
    </div>
    <?php endif; ?>

    <!-- نافذة مشاركة الرابط -->
    <div id="shareModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Share Link</h3>
                <button onclick="closeShareModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex space-x-2">
                <input type="text" id="shareUrl" 
                       value="<?php 
                           $currentUrl = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . '/s/?code=' . $shareCode;
                           echo htmlspecialchars($currentUrl);
                       ?>" 
                       class="flex-1 px-3 py-2 border rounded-lg bg-gray-50" readonly>
                <button onclick="copyShareLink()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        function showShareModal() {
            document.getElementById('shareModal').classList.remove('hidden');
            document.getElementById('shareModal').classList.add('flex');
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.add('hidden');
            document.getElementById('shareModal').classList.remove('flex');
        }

        function copyShareLink() {
            const shareUrl = document.getElementById('shareUrl');
            shareUrl.select();
            document.execCommand('copy');
            
            const button = event.currentTarget;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('bg-blue-500', 'hover:bg-blue-600');
            button.classList.add('bg-green-500');
            
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-copy"></i>';
                button.classList.remove('bg-green-500');
                button.classList.add('bg-blue-500', 'hover:bg-blue-600');
            }, 2000);
        }

        // إغلاق النافذة عند النقر خارجها
        document.getElementById('shareModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeShareModal();
            }
        });
    </script>
</body>
</html>

<?php
// إضافة كود التحميل في نفس الصفحة
if (isset($_GET['download']) && $file) {
    $filepath = '../uploads/' . $file['filepath'];
    if (file_exists($filepath)) {
        header('Content-Type: ' . $file['filetype']);
        header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
        header('Content-Length: ' . $file['filesize']);
        readfile($filepath);
        exit;
    }
}
?>

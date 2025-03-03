<?php
require_once 'includes/db_connect.php';
require_once 'includes/FileManager.php';
require_once 'includes/User.php';
require_once 'includes/ShareManager.php';  // إضافة هذا السطر

// Initialize managers
$user = new User($pdo);
$fileManager = new FileManager($pdo, $user);
$shareManager = new ShareManager($pdo);  // إضافة هذا السطر

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $user->logout();
    header("Location: index.php");
    exit;
}

// Get view mode (grid or list)
$viewMode = isset($_GET['view']) && $_GET['view'] === 'list' ? 'list' : 'grid';

// Handle file upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only allow actions for logged-in users
    if (!$user->isLoggedIn()) {
        $message = 'You must be logged in to perform this action.';
    } else if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload' && isset($_FILES['file'])) {
            $folderId = !empty($_POST['folder_id']) ? $_POST['folder_id'] : null;
            $isPublic = isset($_POST['is_public']) ? true : false;
            $result = $fileManager->uploadFile($_FILES['file'], $folderId, null, $isPublic);
            $message = $result['message'];
        } elseif ($_POST['action'] === 'createFolder' && isset($_POST['folder_name'])) {
            $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $result = $fileManager->createFolder($_POST['folder_name'], $parentId);
            $message = $result['success'] ? 'Folder created successfully' : 'Failed to create folder';
        } elseif ($_POST['action'] === 'deleteFile' && isset($_POST['file_id'])) {
            $result = $fileManager->deleteFile($_POST['file_id']);
            $message = $result ? 'File deleted successfully' : 'Failed to delete file';
        } elseif ($_POST['action'] === 'deleteFolder' && isset($_POST['folder_id'])) {
            $result = $fileManager->deleteFolder($_POST['folder_id']);
            $message = $result ? 'Folder deleted successfully' : 'Failed to delete folder';
        }
    }
}

// Get current folder - for logged-in users only
$currentFolderId = isset($_GET['folder']) ? (int)$_GET['folder'] : null;
$folders = $user->isLoggedIn() ? $fileManager->getFolders($currentFolderId) : [];
$files = $user->isLoggedIn() ? $fileManager->getFiles($currentFolderId) : [];

// Get parent folders for breadcrumb navigation - for logged-in users only
$breadcrumbs = [];
if ($user->isLoggedIn() && $currentFolderId !== null) {
    $stmt = $pdo->prepare("SELECT id, name, parent_id FROM folders WHERE id = :id");
    $currentId = $currentFolderId;
    
    while ($currentId !== null) {
        $stmt->execute([':id' => $currentId]);
        $folder = $stmt->fetch();
        if ($folder) {
            array_unshift($breadcrumbs, ['id' => $folder['id'], 'name' => $folder['name']]);
            $currentId = $folder['parent_id'];
        } else {
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloud File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .file-card {
            transition: all 0.3s ease;
        }
        .file-card:hover {
            transform: translateY(-5px);
        }
        .folder-card {
            transition: all 0.3s ease;
        }
        .folder-card:hover {
            transform: translateY(-5px);
        }
        .custom-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .bg-gradient {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        .sidebar-item {
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background-color: rgba(219, 234, 254, 0.4);
        }
        .sidebar-item.active {
            background-color: rgba(219, 234, 254, 0.8);
            border-right: 3px solid #3b82f6;
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            z-index: 10;
        }
        
        .dropdown-container {
            position: relative;
        }
        
        .dropdown-container:hover .dropdown-menu {
            display: block;
        }
        .context-menu {
            display: none;
            position: fixed;
            z-index: 1000;
            min-width: 200px;
            padding: 0.5rem 0;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            animation: scaleIn 0.15s ease-in-out;
        }
        
        .context-menu-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            color: #4B5563;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .context-menu-item:hover {
            background-color: #F3F4F6;
            color: #2563EB;
        }
        
        .context-menu-item i {
            width: 20px;
            margin-right: 8px;
        }
        
        .context-menu-divider {
            height: 1px;
            background-color: #E5E7EB;
            margin: 0.25rem 0;
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .file-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .file-card:hover .file-badge {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <svg class="h-8 w-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4l2 2h4a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-2 font-semibold text-xl text-gray-800">Cloud File Manager</span>
                </div>
                <div class="flex items-center">
                    <?php if ($user->isLoggedIn()): ?>
                        <span class="text-gray-600 mr-4">
                            <i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($user->getUsername()); ?>
                        </span>
                        <a href="index.php?action=logout" class="bg-red-500 hover:bg-red-600 text-white font-medium py-1.5 px-3 rounded-md text-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1.5 px-3 rounded-md text-sm mr-2">
                            <i class="fas fa-sign-in-alt mr-1"></i> Login
                        </a>
                        <a href="register.php" class="bg-green-500 hover:bg-green-600 text-white font-medium py-1.5 px-3 rounded-md text-sm">
                            <i class="fas fa-user-plus mr-1"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow flex">
        <?php if ($user->isLoggedIn()): ?>
            <!-- Sidebar -->
            <div class="bg-white shadow-sm w-60 flex-shrink-0 hidden sm:block">
                <div class="p-4">
                    <div class="bg-blue-50 rounded-lg p-4 mb-6 text-center">
                        <div class="text-sm font-semibold text-gray-700 mb-1">Storage Usage</div>
                        <div class="relative pt-1">
                            <div class="overflow-hidden h-3 mb-1 text-xs flex rounded bg-blue-100">
                                <?php $percentage = $user->getStoragePercentage(); ?>
                                <div style="width: <?php echo $percentage; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-blue-500'); ?>"></div>
                            </div>
                            <div class="text-xs text-gray-600 flex justify-between">
                                <span><?php echo $percentage; ?>% used</span>
                                <span><?php echo $user->formatStorage($user->getStorageUsed()); ?> / <?php echo $user->formatStorage($user->getStorageLimit()); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <a href="index.php" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md <?php echo !$currentFolderId ? 'active' : ''; ?>">
                            <i class="fas fa-home mr-3 text-blue-500"></i>
                            <span>Home</span>
                        </a>
                        <a href="index.php?view=<?php echo $viewMode; ?>" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-file-alt mr-3 text-blue-500"></i>
                            <span>All Files</span>
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-star mr-3 text-blue-500"></i>
                            <span>Starred</span>
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-trash-alt mr-3 text-blue-500"></i>
                            <span>Trash</span>
                        </a>
                    </div>
                    
                    <hr class="my-6 border-gray-200">
                    
                    <div class="space-y-1">
                        <div class="text-xs uppercase text-gray-500 tracking-wide font-semibold mb-2 px-4">Categories</div>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-file-image mr-3 text-green-500"></i>
                            <span>Images</span>
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-file-video mr-3 text-purple-500"></i>
                            <span>Videos</span>
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-file-pdf mr-3 text-red-500"></i>
                            <span>Documents</span>
                        </a>
                        <a href="#" class="sidebar-item flex items-center px-4 py-2.5 text-gray-700 rounded-md">
                            <i class="fas fa-file-archive mr-3 text-yellow-500"></i>
                            <span>Archives</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Content Area -->
        <div class="flex-grow p-6">
            <?php if(!empty($message)): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>

            <?php if (!$user->isLoggedIn()): ?>
                <!-- Welcome message for non-logged in users -->
                <div class="bg-gradient text-white p-10 rounded-2xl shadow-lg mb-8">
                    <h1 class="text-4xl font-bold mb-3">Welcome to Cloud File Manager</h1>
                    <p class="text-lg mb-8 opacity-90">Securely store, manage and share your files from anywhere.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="login.php" class="bg-white text-blue-600 hover:bg-gray-100 font-semibold py-2.5 px-5 rounded-lg shadow-sm">
                            Login to Your Account
                        </a>
                        <a href="register.php" class="bg-blue-800 bg-opacity-30 hover:bg-opacity-40 text-white font-semibold py-2.5 px-5 rounded-lg border border-white border-opacity-20">
                            Create New Account
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="bg-white rounded-xl shadow-sm p-6 flex items-center">
                        <div class="bg-blue-100 p-3 rounded-full mr-4">
                            <i class="fas fa-cloud text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">5GB Free Storage</h3>
                            <p class="text-gray-600">Store your important files securely</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 flex items-center">
                        <div class="bg-green-100 p-3 rounded-full mr-4">
                            <i class="fas fa-folder-open text-green-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">Organize with Folders</h3>
                            <p class="text-gray-600">Keep your files neatly organized</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-6 flex items-center">
                        <div class="bg-purple-100 p-3 rounded-full mr-4">
                            <i class="fas fa-share-alt text-purple-500 text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">Easy File Sharing</h3>
                            <p class="text-gray-600">Share files with anyone instantly</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Why Choose Our File Manager?</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex items-start mb-4">
                                <div class="bg-blue-100 text-blue-500 rounded-full p-1 mr-3 mt-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-1">Secure Storage</h3>
                                    <p class="text-gray-600 text-sm">Your files are encrypted and protected with the latest security standards.</p>
                                </div>
                            </div>
                            <div class="flex items-start mb-4">
                                <div class="bg-blue-100 text-blue-500 rounded-full p-1 mr-3 mt-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-1">Easy Access</h3>
                                    <p class="text-gray-600 text-sm">Access your files from anywhere, on any device.</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-start mb-4">
                                <div class="bg-blue-100 text-blue-500 rounded-full p-1 mr-3 mt-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-1">User-Friendly Interface</h3>
                                    <p class="text-gray-600 text-sm">Our intuitive design makes file management a breeze.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="bg-blue-100 text-blue-500 rounded-full p-1 mr-3 mt-1">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 mb-1">Free to Use</h3>
                                    <p class="text-gray-600 text-sm">Get started with 5GB of free storage, no credit card required.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Content for logged-in users -->
                <div class="flex justify-between items-center mb-6">
                    <!-- Breadcrumb Navigation -->
                    <div class="flex items-center text-sm">
                        <a href="index.php" class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-home"></i> Home
                        </a>
                        <?php foreach($breadcrumbs as $index => $crumb): ?>
                            <span class="text-gray-500 mx-2">/</span>
                            <a href="index.php?folder=<?php echo $crumb['id']; ?>&view=<?php echo $viewMode; ?>" class="text-blue-500 hover:text-blue-700">
                                <?php echo htmlspecialchars($crumb['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- View Toggle & Action Buttons -->
                    <div class="flex items-center space-x-2">
                        <div class="bg-white rounded-md shadow-sm p-1 flex mr-2">
                            <a href="index.php?<?php echo $currentFolderId ? 'folder='.$currentFolderId.'&' : ''; ?>view=grid" 
                               class="px-3 py-1 rounded-md <?php echo $viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                                <i class="fas fa-th"></i>
                            </a>
                            <a href="index.php?<?php echo $currentFolderId ? 'folder='.$currentFolderId.'&' : ''; ?>view=list" 
                               class="px-3 py-1 rounded-md <?php echo $viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'; ?>">
                                <i class="fas fa-list"></i>
                            </a>
                        </div>
                        <button onclick="toggleModal('uploadModal')" class="bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md">
                            <i class="fas fa-upload mr-1"></i> Upload
                        </button>
                        <button onclick="toggleModal('folderModal')" class="bg-green-500 hover:bg-green-600 text-white text-sm font-medium py-2 px-4 rounded-md">
                            <i class="fas fa-folder-plus mr-1"></i> New Folder
                        </button>
                    </div>
                </div>

                <!-- Mobile Storage Display (Only visible on small screens) -->
                <div class="sm:hidden bg-white rounded-lg p-4 mb-6 shadow-sm">
                    <div class="text-sm font-semibold text-gray-700 mb-1">Storage Usage</div>
                    <div class="relative pt-1">
                        <div class="overflow-hidden h-3 mb-1 text-xs flex rounded bg-blue-100">
                            <?php $percentage = $user->getStoragePercentage(); ?>
                            <div style="width: <?php echo $percentage; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $percentage > 90 ? 'bg-red-500' : ($percentage > 70 ? 'bg-yellow-500' : 'bg-blue-500'); ?>"></div>
                        </div>
                        <div class="text-xs text-gray-600 flex justify-between">
                            <span><?php echo $percentage; ?>% used</span>
                            <span><?php echo $user->formatStorage($user->getStorageUsed()); ?> / <?php echo $user->formatStorage($user->getStorageLimit()); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Folders Section -->
                <?php if (!empty($folders)): ?>
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-700 mb-4">Folders</h2>
                        
                        <?php if ($viewMode === 'grid'): ?>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <?php foreach($folders as $folder): ?>
                                    <div class="folder-card bg-white rounded-lg shadow-sm hover:shadow p-4">
                                        <a href="index.php?folder=<?php echo $folder['id']; ?>&view=<?php echo $viewMode; ?>" class="block text-center">
                                            <div class="text-yellow-500 text-5xl mb-3 flex justify-center">
                                                <i class="fas fa-folder"></i>
                                            </div>
                                            <p class="truncate text-gray-700 font-medium"><?php echo htmlspecialchars($folder['name']); ?></p>
                                        </a>
                                        <div class="mt-3 flex justify-center">
                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this folder and all its contents?');" class="inline-block">
                                                <input type="hidden" name="action" value="deleteFolder">
                                                <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                            <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                            <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach($folders as $folder): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="py-3 px-4">
                                                    <a href="index.php?folder=<?php echo $folder['id']; ?>&view=<?php echo $viewMode; ?>" class="flex items-center">
                                                        <i class="fas fa-folder text-yellow-500 text-xl mr-3"></i>
                                                        <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($folder['name']); ?></span>
                                                    </a>
                                                </td>
                                                <td class="py-3 px-4 text-gray-500 text-sm">
                                                    <?php echo date('M d, Y', strtotime($folder['created_date'])); ?>
                                                </td>
                                                <td class="py-3 px-4 text-right">
                                                    <form method="post" onsubmit="return confirm('Are you sure you want to delete this folder and all its contents?');" class="inline-block">
                                                        <input type="hidden" name="action" value="deleteFolder">
                                                        <input type="hidden" name="folder_id" value="<?php echo $folder['id']; ?>">
                                                        <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Files Section -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Files</h2>
                    
                    <?php if (empty($files)): ?>
                        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                            <div class="text-gray-400 text-5xl mb-4">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <p class="text-gray-500">No files found in this folder.</p>
                            <p class="text-gray-500 text-sm mt-1">Upload files using the button above.</p>
                        </div>
                    <?php elseif ($viewMode === 'grid'): ?>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <?php foreach($files as $file): ?>
                                <div class="file-card bg-white rounded-lg shadow-sm hover:shadow p-4 relative" 
                                     oncontextmenu="showContextMenu(event, <?php echo htmlspecialchars(json_encode($file)); ?>); return false;">
                                    <div class="text-center">
                                        <div class="h-20 flex items-center justify-center mb-3">
                                            <i class="<?php echo $fileManager->getFileIcon($file['filetype']); ?> text-5xl 
                                                <?php 
                                                    if (strpos($file['filetype'], 'image/') === 0) echo 'text-green-500';
                                                    elseif (strpos($file['filetype'], 'video/') === 0) echo 'text-purple-500';
                                                    elseif (strpos($file['filetype'], 'audio/') === 0) echo 'text-pink-500';
                                                    elseif ($file['filetype'] == 'application/pdf') echo 'text-red-500';
                                                    elseif (strpos($file['filetype'], 'application/') === 0) echo 'text-yellow-500';
                                                    else echo 'text-blue-500';
                                                ?>"></i>
                                        </div>
                                        <p class="truncate text-gray-700 font-medium"><?php echo htmlspecialchars($file['filename']); ?></p>
                                        <p class="text-gray-500 text-sm"><?php echo $fileManager->formatFileSize($file['filesize']); ?></p>
                                        <p class="text-gray-500 text-xs"><?php echo date('M d, Y', strtotime($file['upload_date'])); ?></p>
                                    </div>
                                    <div class="file-badge">
                                        <?php 
                                            if (strpos($file['filetype'], 'image/') === 0) echo 'Image';
                                            elseif (strpos($file['filetype'], 'video/') === 0) echo 'Video';
                                            elseif (strpos($file['filetype'], 'audio/') === 0) echo 'Audio';
                                            elseif ($file['filetype'] == 'application/pdf') echo 'PDF';
                                            elseif (strpos($file['filetype'], 'application/') === 0) echo 'Document';
                                            else echo 'File';
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                        <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Date</th>
                                        <th class="py-3 px-4 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($files as $file): ?>
                                        <tr class="hover:bg-gray-50" oncontextmenu="showContextMenu(event, <?php echo htmlspecialchars(json_encode($file)); ?>); return false;">
                                            <td class="py-3 px-4">
                                                <i class="<?php echo $fileManager->getFileIcon($file['filetype']); ?> text-blue-500 text-xl"></i>
                                            </td>
                                            <td class="py-3 px-4 font-medium">
                                                <?php echo htmlspecialchars($file['filename']); ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-500">
                                                <?php echo $fileManager->formatFileSize($file['filesize']); ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-500">
                                                <?php echo date('Y-m-d H:i', strtotime($file['upload_date'])); ?>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <div class="flex justify-end">
                                                    <div class="dropdown-container">
                                                        <button class="text-gray-400 hover:text-gray-600" title="Options">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu bg-white rounded-lg shadow-lg py-2 min-w-[160px]">
                                                            <a href="uploads/<?php echo $file['filepath']; ?>" download="<?php echo $file['filename']; ?>" 
                                                               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-download w-5"></i>
                                                                <span>Download</span>
                                                            </a>
                                                            <button onclick="shareFile(<?php echo htmlspecialchars(json_encode($file)); ?>)" 
                                                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-share-alt w-5"></i>
                                                                <span>Share</span>
                                                            </button>
                                                            <button onclick="renameFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['filename']); ?>')" 
                                                                    class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                <i class="fas fa-edit w-5"></i>
                                                                <span>Rename</span>
                                                            </button>
                                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this file?');" class="block">
                                                                <input type="hidden" name="action" value="deleteFile">
                                                                <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                                                    <i class="fas fa-trash-alt w-5"></i>
                                                                    <span>Delete</span>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upload Modal -->
                <div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Upload File</h3>
                            <button onclick="toggleModal('uploadModal')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="folder_id" value="<?php echo $currentFolderId; ?>">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2" for="file">
                                    Select File
                                </label>
                                <input type="file" name="file" id="file" class="w-full border border-gray-300 p-2 rounded" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_public" class="mr-2">
                                    <span class="text-gray-700">Make file public</span>
                                </label>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" onclick="toggleModal('uploadModal')" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded mr-2">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- New Folder Modal -->
                <div id="folderModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Create New Folder</h3>
                            <button onclick="toggleModal('folderModal')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="createFolder">
                            <input type="hidden" name="parent_id" value="<?php echo $currentFolderId; ?>">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2" for="folder_name">
                                    Folder Name
                                </label>
                                <input type="text" name="folder_name" id="folder_name" class="w-full border border-gray-300 p-2 rounded" required>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" onclick="toggleModal('folderModal')" class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded mr-2">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Create
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Rename Modal -->
                <div id="renameModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Rename File</h3>
                            <button onclick="toggleModal('renameModal')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="post" id="renameForm">
                            <input type="hidden" name="action" value="renameFile">
                            <input type="hidden" name="file_id" id="renameFileId">
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 font-medium mb-2" for="new_filename">
                                    New Filename
                                </label>
                                <input type="text" name="new_filename" id="new_filename" 
                                       class="w-full border border-gray-300 p-2 rounded" required>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="button" onclick="toggleModal('renameModal')" 
                                        class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded mr-2">
                                    Cancel
                                </button>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Rename
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Share Modal -->
                <div id="shareModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
                    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold">Share File</h3>
                            <button onclick="toggleModal('shareModal')" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <input type="hidden" id="shareFileId" value="">
                        <div class="mb-4">
                            <label class="block text-gray-700 font-medium mb-2">Share Link</label>
                            <div class="flex">
                                <input type="text" id="shareLink" readonly 
                                       class="w-full border border-gray-300 p-2 rounded-l" value="">
                                <button onclick="copyShareLink()" 
                                        class="bg-blue-500 hover:bg-blue-700 text-white px-4 rounded-r">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="makePublic" onchange="updateFileVisibility(this)">
                                <span class="ml-2 text-gray-700">Make file public</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="contextMenu" class="context-menu">
                    <div class="context-menu-item" onclick="handleContextMenu('download')">
                        <i class="fas fa-download"></i>
                        <span>Download</span>
                    </div>
                    <div class="context-menu-item" onclick="handleContextMenu('share')">
                        <i class="fas fa-share-alt"></i>
                        <span>Share</span>
                    </div>
                    <div class="context-menu-item" onclick="handleContextMenu('rename')">
                        <i class="fas fa-edit"></i>
                        <span>Rename</span>
                    </div>
                    <div class="context-menu-divider"></div>
                    <div class="context-menu-item text-red-600 hover:bg-red-50" onclick="handleContextMenu('delete')">
                        <i class="fas fa-trash-alt"></i>
                        <span>Delete</span>
                    </div>
                </div>

                <script>
                    function toggleModal(modalId) {
                        const modal = document.getElementById(modalId);
                        if (modal.classList.contains('hidden')) {
                            modal.classList.remove('hidden');
                        } else {
                            modal.classList.add('hidden');
                        }
                    }

                    function renameFile(fileId, currentName) {
                        document.getElementById('renameFileId').value = fileId;
                        document.getElementById('new_filename').value = currentName;
                        toggleModal('renameModal');
                    }

                    function shareFile(file) {
                        fetch('api/create_share.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                file_id: file.id
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('shareLink').value = data.share_url;
                                document.getElementById('makePublic').checked = file.public == 1;
                                document.getElementById('shareFileId').value = file.id;
                                toggleModal('shareModal');
                            } else {
                                alert('Failed to create share link: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to create share link');
                        });
                    }

                    function copyShareLink() {
                        const shareLink = document.getElementById('shareLink');
                        shareLink.select();
                        document.execCommand('copy');
                        alert('Link copied to clipboard!');
                    }

                    function updateFileVisibility(checkbox) {
                        // Add AJAX call to update file visibility
                        const fileId = document.getElementById('shareFileId').value;
                        fetch('update_visibility.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                file_id: fileId,
                                public: checkbox.checked
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                alert('Failed to update file visibility');
                                checkbox.checked = !checkbox.checked;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Failed to update file visibility');
                            checkbox.checked = !checkbox.checked;
                        });
                    }

                    let activeFileData = null;
                    const contextMenu = document.getElementById('contextMenu');
                    
                    document.addEventListener('click', () => {
                        contextMenu.style.display = 'none';
                    });
                    
                    function showContextMenu(event, fileData) {
                        event.preventDefault();
                        activeFileData = fileData;
                        
                        contextMenu.style.display = 'block';
                        
                        // Position the menu
                        const x = event.clientX;
                        const y = event.clientY;
                        const windowWidth = window.innerWidth;
                        const windowHeight = window.innerHeight;
                        const menuWidth = contextMenu.offsetWidth;
                        const menuHeight = contextMenu.offsetHeight;
                        
                        // Check if menu goes beyond screen bounds
                        const xPos = x + menuWidth > windowWidth ? x - menuWidth : x;
                        const yPos = y + menuHeight > windowHeight ? y - menuHeight : y;
                        
                        contextMenu.style.left = `${xPos}px`;
                        contextMenu.style.top = `${yPos}px`;
                    }
                    
                    function handleContextMenu(action) {
                        if (!activeFileData) return;
                        
                        switch(action) {
                            case 'download':
                                window.location.href = `uploads/${activeFileData.filepath}`;
                                break;
                            case 'share':
                                shareFile(activeFileData);
                                break;
                            case 'rename':
                                renameFile(activeFileData.id, activeFileData.filename);
                                break;
                            case 'delete':
                                if (confirm('Are you sure you want to delete this file?')) {
                                    const form = document.createElement('form');
                                    form.method = 'post';
                                    form.innerHTML = `
                                        <input type="hidden" name="action" value="deleteFile">
                                        <input type="hidden" name="file_id" value="${activeFileData.id}">
                                    `;
                                    document.body.appendChild(form);
                                    form.submit();
                                }
                                break;
                        }
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

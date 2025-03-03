<?php
require_once 'includes/db_connect.php';
require_once 'includes/User.php';

$user = new User($pdo);
$message = '';
$messageType = '';

// Redirect if already logged in
if ($user->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $result = $user->login($username, $password);
        
        if ($result['success']) {
            $messageType = 'success';
            $message = $result['message'];
            
            // Redirect to index after successful login
            header("Location: index.php");
            exit;
        } else {
            $messageType = 'error';
            $message = $result['message'];
        }
    } else {
        $messageType = 'error';
        $message = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - File Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
            <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">Login to File Management System</h1>
            
            <?php if (!empty($message)): ?>
                <div class="<?php echo $messageType === 'error' ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'; ?> border-l-4 p-4 mb-6" role="alert">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="username">
                        Username
                    </label>
                    <input type="text" name="username" id="username" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="password">
                        Password
                    </label>
                    <input type="password" name="password" id="password" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Login
                    </button>
                </div>
                
                <div class="text-center">
                    <p>Don't have an account? <a href="register.php" class="text-blue-500 hover:text-blue-700">Register</a></p>
                </div>
            </form>
            
            <div class="mt-4 text-center">
                <a href="index.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>

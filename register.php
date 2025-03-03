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
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email)) {
        $messageType = 'error';
        $message = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $messageType = 'error';
        $message = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $messageType = 'error';
        $message = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $messageType = 'error';
        $message = 'Invalid email format';
    } else {
        $result = $user->register($username, $password, $email);
        
        if ($result['success']) {
            $messageType = 'success';
            $message = $result['message'] . '. You can now <a href="login.php" class="font-bold">login</a>.';
        } else {
            $messageType = 'error';
            $message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - File Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
            <h1 class="text-2xl font-bold text-center text-blue-600 mb-6">Register Account</h1>
            
            <?php if (!empty($message)): ?>
                <div class="<?php echo $messageType === 'error' ? 'bg-red-100 border-red-500 text-red-700' : 'bg-green-100 border-green-500 text-green-700'; ?> border-l-4 p-4 mb-6" role="alert">
                    <p><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="register.php">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="username">
                        Username
                    </label>
                    <input type="text" name="username" id="username" class="w-full border border-gray-300 p-2 rounded" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="email">
                        Email
                    </label>
                    <input type="email" name="email" id="email" class="w-full border border-gray-300 p-2 rounded" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2" for="password">
                        Password
                    </label>
                    <input type="password" name="password" id="password" class="w-full border border-gray-300 p-2 rounded" required>
                    <p class="text-sm text-gray-500 mt-1">Password must be at least 6 characters long</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2" for="confirm_password">
                        Confirm Password
                    </label>
                    <input type="password" name="confirm_password" id="confirm_password" class="w-full border border-gray-300 p-2 rounded" required>
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Register
                    </button>
                </div>
                
                <div class="text-center">
                    <p>Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-700">Login</a></p>
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

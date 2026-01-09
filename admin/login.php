<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Redirect jika sudah login
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        try {
            // Add error logging for debugging
            error_log("Login attempt for username: " . $username);
            
            // Use PDO with error mode exception
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Debug log
                error_log("User found. Verifying password...");
                
                if (password_verify($password, $admin['password'])) {
                    // Login berhasil
                    session_regenerate_id(true); // Prevent session fixation
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_nama'] = $admin['fullname'];
                    $_SESSION['last_activity'] = time();
                    
                    try {
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE admin SET last_login = NOW() WHERE id = :id");
                        $updateStmt->bindParam(':id', $admin['id'], PDO::PARAM_INT);
                        $updateStmt->execute();
                        
                        // Debug log
                        error_log("Login successful for user: " . $username);
                        
                        // Redirect ke halaman admin
                        header('Location: index.php');
                        exit();
                    } catch (PDOException $e) {
                        error_log("Error updating last login: " . $e->getMessage());
                        // Continue with login even if update fails
                        header('Location: index.php');
                        exit();
                    }
                } else {
                    // Log failed login attempt
                    error_log("Login failed: Invalid password for username: " . $username);
                    $error = 'Username atau password salah';
                }
            } else {
                // Log failed login attempt
                error_log("Login failed: User not found - " . $username);
                $error = 'Username atau password salah';
            }
        } catch (PDOException $e) {
            // Log the actual error for debugging
            $errorMsg = 'Database error: ' . $e->getMessage();
            error_log($errorMsg);
            
            // Show generic error to user
            $error = 'Terjadi kesalahan. Silakan coba lagi nanti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
        }
        .login-container {
            min-height: 100vh;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="login-container flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></h1>
                <p class="text-gray-600 mt-2">Panel Admin</p>
            </div>
            
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="px-8 py-10">
                    <div class="text-center mb-8">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                            <i class="fas fa-user-shield text-blue-600 text-2xl"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800">Masuk ke Dashboard</h2>
                        <p class="text-gray-600 mt-1">Masukkan kredensial Anda untuk melanjutkan</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-red-500"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700"><?php echo $error; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" class="space-y-6">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md" 
                                       placeholder="Masukkan username"
                                       required
                                       autofocus>
                            </div>
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 pr-3 py-2 sm:text-sm border border-gray-300 rounded-md" 
                                       placeholder="Masukkan password"
                                       required>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                    Ingat saya
                                </label>
                            </div>
                            
                            <div class="text-sm">
                                <a href="forgot-password.php" class="font-medium text-blue-600 hover:text-blue-500">
                                    Lupa password?
                                </a>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-600">
                        &copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>. All rights reserved.
                    </p>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="../index.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Menghilangkan pesan error setelah 5 detik
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.bg-red-50');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            // Auto focus ke field username
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

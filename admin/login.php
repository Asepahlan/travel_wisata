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
        .login-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-screen flex">
        <!-- Left side with background -->
        <div class="hidden lg:flex flex-col justify-between flex-1 login-bg p-12 text-white">
            <div class="max-w-md">
                <h1 class="text-4xl font-bold mb-4"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></h1>
                <p class="text-blue-100 text-lg">Sistem Manajemen Perjalanan Wisata</p>
            </div>
            <div class="mt-auto">
                <p class="text-blue-200 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right side with login form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-md">
                <div class="text-center">
                    <h2 class="text-3xl font-extrabold text-gray-900">Masuk ke Dashboard</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        atau
                        <a href="../" class="font-medium text-blue-600 hover:text-blue-500">
                            kembali ke beranda
                        </a>
                    </p>
                </div>

                <div class="mt-8 bg-white py-8 px-4 shadow login-card sm:rounded-lg sm:px-10">
                    <?php if ($error): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700">
                                        <?php echo htmlspecialchars($error); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-6" action="login.php" method="POST">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700">
                                Username
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input id="username" name="username" type="text" required 
                                    class="appearance-none block w-full px-3 pl-10 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="Masukkan username">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password" name="password" type="password" autocomplete="current-password" required 
                                    class="appearance-none block w-full px-3 pl-10 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="Masukkan password">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember_me" name="remember_me" type="checkbox" 
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                    Ingat saya
                                </label>
                            </div>

                            <div class="text-sm">
                                <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                                    Lupa password?
                                </a>
                            </div>
                        </div>

                        <div>
                            <button type="submit" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                                Masuk
                            </button>
                        </div>
                    </form>

                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">
                                    Atau hubungi admin
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-3">
                            <a href="#" class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Sign in with Email</span>
                                <i class="fas fa-envelope text-gray-500"></i>
                                <span class="ml-2">Email Admin</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobile footer -->
                <div class="mt-8 text-center text-sm text-gray-500 lg:hidden">
                    <p>
                        &copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>. All rights reserved.
                    </p>
                </div>
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

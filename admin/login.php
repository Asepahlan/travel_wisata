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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        @media (max-width: 640px) {
            .login-card {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
        body {
            background-color: #f9fafb;
        }
        .login-bg {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
        }
        .form-input, .form-textarea, .form-select, .form-multiselect {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            border-color: #d1d5db;
            border-width: 1px;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            width: 100%;
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus, .form-multiselect:focus {
            outline: none;
            ring: 2px;
            ring-color: #3b82f6;
            border-color: #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <!-- Left side with background -->
        <div class="lg:w-1/2 xl:w-2/3 hidden lg:flex flex-col justify-between p-6 md:p-12 text-white login-bg">
            <div class="max-w-md mx-auto lg:mx-0 w-full">
                <h1 class="text-3xl md:text-4xl font-bold mb-3 md:mb-4"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></h1>
                <p class="text-blue-100 text-base md:text-lg">Sistem Manajemen Perjalanan Wisata</p>
            </div>
            <div class="mt-8 lg:mt-auto text-center lg:text-left">
                <p class="text-blue-200 text-xs md:text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>. All rights reserved.
                </p>
            </div>
        </div>

        <!-- Right side with login form -->
        <div class="flex-1 flex flex-col justify-center py-6 sm:py-12 px-4 sm:px-6 lg:flex-none lg:px-12 xl:px-16">
            <div class="mx-auto w-full max-w-md">
                <!-- Mobile Logo (only shown on small screens) -->
                <div class="lg:hidden mb-8 text-center">
                    <h1 class="text-2xl font-bold text-blue-800 mb-2"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></h1>
                    <p class="text-blue-600 text-sm">Sistem Manajemen Perjalanan Wisata</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm login-card">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="text-center">
                            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Masuk ke Dashboard</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                atau
                                <a href="../" class="font-medium text-blue-600 hover:text-blue-500">
                                    kembali ke beranda
                                </a>
                            </p>
                        </div>

                        <?php if ($error): ?>
                            <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 pt-0.5">
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

                        <form class="space-y-4 md:space-y-6" action="login.php" method="POST">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Username
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input id="username" name="username" type="text" required 
                                        class="form-input pl-10 block w-full sm:text-sm"
                                        placeholder="Masukkan username">
                                </div>
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Password
                                </label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                                        class="form-input pl-10 block w-full sm:text-sm"
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

                            <div class="pt-2">
                                <button type="submit" 
                                    class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    Masuk
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-200"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-2 bg-white text-gray-500">
                                        Atau hubungi admin
                                    </span>
                                </div>
                            </div>

                            <div class="mt-4 text-center">
                                <p class="text-sm text-gray-600">
                                    Butuh bantuan? 
                                    <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                                        Hubungi tim support
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile footer -->
                <div class="mt-6 lg:mt-8 text-center text-xs text-gray-500 lg:hidden">
                    <p>
                        &copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata' ?>. All rights reserved.
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

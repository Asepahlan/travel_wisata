<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Pengaturan Sistem';

// Inisialisasi pesan
$success = '';
$error = '';

// Tangani form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update pengaturan umum
        if (isset($_POST['update_general'])) {
            $settings = [
                'site_name' => trim($_POST['site_name']),
                'site_description' => trim($_POST['site_description']),
                'contact_email' => trim($_POST['contact_email']),
                'contact_phone' => trim($_POST['contact_phone']),
                'contact_address' => trim($_POST['contact_address']),
                'currency' => trim($_POST['currency']),
                'timezone' => trim($_POST['timezone'])
            ];

            // Update ke config.php
            $config_content = file_get_contents('../config/config.php');
            
            foreach ($settings as $key => $value) {
                $pattern = "/define\('" . $key . "',\s*'[^']*'\)/i";
                $replacement = "define('" . $key . "', '" . addslashes($value) . "')";
                $config_content = preg_replace($pattern, $replacement, $config_content);
            }
            
            file_put_contents('../config/config.php', $config_content);
            $success = 'Pengaturan berhasil diperbarui';
        }
        
        // Update pengaturan pembayaran
        if (isset($_POST['update_payment'])) {
            $settings = [
                'bank_name' => trim($_POST['bank_name']),
                'account_number' => trim($_POST['account_number']),
                'account_holder' => trim($_POST['account_holder']),
                'payment_auto_confirm' => isset($_POST['payment_auto_confirm']) ? 1 : 0
            ];
            
            // Simpan ke database
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $success = 'Pengaturan pembayaran berhasil diperbarui';
        }
        
        // Update pengaturan email
        if (isset($_POST['update_email'])) {
            $settings = [
                'smtp_host' => trim($_POST['smtp_host']),
                'smtp_port' => (int)$_POST['smtp_port'],
                'smtp_username' => trim($_POST['smtp_username']),
                'smtp_password' => trim($_POST['smtp_password']),
                'smtp_secure' => trim($_POST['smtp_secure']),
                'email_from' => trim($_POST['email_from']),
                'email_from_name' => trim($_POST['email_from_name'])
            ];
            
            // Simpan ke database
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $success = 'Pengaturan email berhasil diperbarui';
        }
        
    } catch (Exception $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Ambil pengaturan dari database
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .tab-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            margin-bottom: -1px;
            border: none;
            border-bottom: 2px solid transparent;
            background: none;
            color: #6b7280;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .tab-button:hover {
            color: #2563eb;
        }
        .tab-button.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
        }
        .tab-button i {
            transition: all 0.2s ease-in-out;
        }
        .tab-button.active i {
            color: #2563eb;
        }
        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Include Sidebar -->
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800"><?php echo $page_title; ?></h2>
                    <div class="flex items-center">
                        <span class="text-gray-600 mr-4"><?php echo htmlspecialchars($_SESSION['admin_nama']); ?></span>
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($_SESSION['admin_nama'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="py-1"><i class="fas fa-check-circle mr-2"></i></div>
                            <div><?php echo $success; ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                            <div><?php echo $error; ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab Navigation -->
                <div class="border-b border-gray-200 mb-8">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="openTab(event, 'general')" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200 ease-in-out" id="default-tab">
                            <i class="fas fa-cog mr-2"></i>
                            <span>Umum</span>
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform scale-x-0 transition-transform duration-200 ease-in-out group-hover:scale-x-100"></span>
                        </button>
                        <button onclick="openTab(event, 'admin')" class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200 ease-in-out">
                            <i class="fas fa-user-shield mr-2"></i>
                            <span>Akun Admin</span>
                            <span class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform scale-x-0 transition-transform duration-200 ease-in-out group-hover:scale-x-100"></span>
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="bg-white shadow rounded-lg p-6">
                    <!-- Tab 1: General Settings -->
                    <div id="general" class="tab-content">
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Pengaturan Umum</h3>
                            <p class="text-sm text-gray-500">Kelola pengaturan dasar website Anda</p>
                        </div>
                        
                        <form method="post" class="space-y-8">
                            <!-- Informasi Website -->
                            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                                <h4 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-globe mr-2 text-blue-600"></i>
                                    Informasi Website
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Website</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-sitemap text-gray-400"></i>
                                            </div>
                                            <input type="text" name="site_name" id="site_name" 
                                                value="<?php echo htmlspecialchars(defined('site_name') ? constant('site_name') : 'Travel Wisata'); ?>"
                                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Mata Uang</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-money-bill-wave text-gray-400"></i>
                                            </div>
                                            <select name="currency" id="currency" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                                                <option value="IDR" <?php echo (defined('currency') && constant('currency') === 'IDR') ? 'selected' : ''; ?>>Rupiah (IDR)</option>
                                                <option value="USD" <?php echo (defined('currency') && constant('currency') === 'USD') ? 'selected' : ''; ?>>Dolar AS (USD)</option>
                                                <option value="SGD" <?php echo (defined('currency') && constant('currency') === 'SGD') ? 'selected' : ''; ?>>Dolar Singapura (SGD)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label for="site_description" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Situs</label>
                                        <div class="relative">
                                            <div class="absolute top-0 left-0 pl-3 pt-3">
                                                <i class="fas fa-align-left text-gray-400"></i>
                                            </div>
                                            <textarea name="site_description" id="site_description" rows="3"
                                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"><?php echo htmlspecialchars(defined('site_description') ? constant('site_description') : 'Layanan travel terpercaya untuk perjalanan Anda'); ?></textarea>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Deskripsi singkat tentang layanan Anda</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Kontak & Lokasi -->
                            <div class="bg-white shadow-sm rounded-lg p-6 border border-gray-200">
                                <h4 class="text-base font-medium text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-address-card mr-2 text-blue-600"></i>
                                    Kontak & Lokasi
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-phone text-gray-400"></i>
                                            </div>
                                            <input type="text" name="contact_phone" id="contact_phone" 
                                                value="<?php echo htmlspecialchars(defined('contact_phone') ? constant('contact_phone') : ($settings['contact_phone'] ?? '')); ?>"
                                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                                placeholder="Contoh: +6281234567890">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-envelope text-gray-400"></i>
                                            </div>
                                            <input type="email" name="contact_email" id="contact_email" 
                                                value="<?php echo htmlspecialchars(defined('contact_email') ? constant('contact_email') : ($settings['contact_email'] ?? 'info@travelwisata.com')); ?>"
                                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                                placeholder="contoh: info@travelwisata.com">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">Zona Waktu</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="far fa-clock text-gray-400"></i>
                                            </div>
                                            <select name="timezone" id="timezone" class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md">
                                                <option value="Asia/Jakarta" <?php echo (date_default_timezone_get() === 'Asia/Jakarta') ? 'selected' : ''; ?>>WIB (Asia/Jakarta)</option>
                                                <option value="Asia/Makassar" <?php echo (date_default_timezone_get() === 'Asia/Makassar') ? 'selected' : ''; ?>>WITA (Asia/Makassar)</option>
                                                <option value="Asia/Jayapura" <?php echo (date_default_timezone_get() === 'Asia/Jayapura') ? 'selected' : ''; ?>>WIT (Asia/Jayapura)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="md:col-span-2">
                                        <label for="contact_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                                        <div class="relative">
                                            <div class="absolute top-0 left-0 pl-3 pt-3">
                                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                                            </div>
                                            <textarea name="contact_address" id="contact_address" rows="2"
                                                class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                                placeholder="Masukkan alamat lengkap"><?php echo htmlspecialchars(defined('contact_address') ? constant('contact_address') : ($settings['contact_address'] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                                <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-undo-alt mr-2"></i>
                                    Reset
                                </button>
                                <button type="submit" name="update_general" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-save mr-2"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tab 2: Admin Account -->

                    <!-- Tab 2: Admin Account -->
                    <div id="admin" class="tab-content">
                        <div class="mb-8">
                            <h3 class="text-2xl font-semibold text-gray-900">Kelola Akun Admin</h3>
                            <p class="text-sm text-gray-500 mt-1">Kelola informasi akun dan keamanan</p>
                        </div>
                        <form method="post" class="space-y-6">
                            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                                <h4 class="text-sm font-medium text-blue-800">Informasi Akun</h4>
                                <p class="mt-1 text-sm text-blue-700">Kelola informasi akun admin Anda di sini.</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="admin_username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" name="admin_username" id="admin_username" 
                                           value="<?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           disabled>
                                </div>
                                
                                <div>
                                    <label for="admin_email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="admin_email" id="admin_email" 
                                           value="<?php echo htmlspecialchars($_SESSION['admin_email'] ?? ''); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Password Saat Ini</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="password" name="current_password" id="current_password" 
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <button type="button" onclick="togglePassword('current_password')" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="password" name="new_password" id="new_password" 
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <button type="button" onclick="togglePassword('new_password')" 
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Biarkan kosong jika tidak ingin mengubah password</p>
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label for="admin_fullname" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                                    <input type="text" name="admin_fullname" id="admin_fullname" 
                                           value="<?php echo htmlspecialchars($_SESSION['admin_nama'] ?? ''); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            
                            <div class="pt-5">
                                <div class="flex justify-end">
                                    <button type="reset" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Reset
                                    </button>
                                    <button type="submit" name="update_admin" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function openTab(evt, tabName) {
            // Prevent default anchor behavior if event exists
            if (evt && evt.preventDefault) {
                evt.preventDefault();
            }
            
            // Get the target tab content
            const targetTab = document.getElementById(tabName);
            if (!targetTab) return; // Exit if target tab doesn't exist
            
            // Get all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            
            // Hide all tab content with smooth transition
            for (let i = 0; i < tabContents.length; i++) {
                const tab = tabContents[i];
                if (tab !== targetTab) {
                    tab.style.opacity = '0';
                    tab.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        tab.style.display = 'none';
                    }, 150);
                }
            }

            // Show the target tab with animation
            targetTab.style.display = 'block';
            setTimeout(() => {
                targetTab.style.opacity = '1';
                targetTab.style.transform = 'translateY(0)';
            }, 50);

            // Update active tab button if event was triggered by a button
            if (evt && evt.currentTarget) {
                const tabButtons = document.getElementsByClassName('tab-button');
                for (let i = 0; i < tabButtons.length; i++) {
                    tabButtons[i].classList.remove('active');
                    tabButtons[i].setAttribute('aria-selected', 'false');
                }
                evt.currentTarget.classList.add('active');
                evt.currentTarget.setAttribute('aria-selected', 'true');
            }

            // Update URL hash without page jump
            if (history && history.pushState) {
                history.pushState(null, null, '#' + tabName);
            }
        }
        
        // Check URL hash on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default tab
            let defaultTab = document.getElementById('default-tab');
            
            // Check for hash in URL
            if (window.location.hash) {
                const tabName = window.location.hash.substring(1);
                const tabButton = document.querySelector(`[onclick*="${tabName}"]`);
                if (tabButton) {
                    defaultTab = tabButton;
                }
            }
            
            // Show the default tab
            if (defaultTab) {
                // Get the tab name from the onclick attribute
                const tabName = defaultTab.getAttribute('onclick').match(/'([^']+)'/)[1];
                const tabContent = document.getElementById(tabName);
                
                if (tabContent) {
                    // Show the tab content
                    tabContent.style.display = 'block';
                    tabContent.style.opacity = '1';
                    tabContent.style.transform = 'translateY(0)';
                    
                    // Update active tab button
                    const tabButtons = document.getElementsByClassName('tab-button');
                    for (let i = 0; i < tabButtons.length; i++) {
                        tabButtons[i].classList.remove('active');
                    }
                    defaultTab.classList.add('active');
                }
            }
        });

        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (!icon) return; // Exit if icon not found
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Initialize tabs on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check URL hash first for direct tab access
            if (window.location.hash) {
                const tabName = window.location.hash.substring(1);
                const tabContent = document.getElementById(tabName);
                const tabButton = document.querySelector(`[onclick*="${tabName}"]`);
                
                if (tabContent && tabButton) {
                    // Show the requested tab
                    openTab({ currentTarget: tabButton }, tabName);
                    return;
                }
            }
            
            // If no hash or invalid hash, show default tab
            const defaultTab = document.getElementById('default-tab');
            if (defaultTab) {
                // Get the tab name from the onclick attribute
                const tabMatch = defaultTab.getAttribute('onclick').match(/'([^']+)'/);
                if (tabMatch && tabMatch[1]) {
                    const tabName = tabMatch[1];
                    const tabContent = document.getElementById(tabName);
                    
                    if (tabContent) {
                        openTab({ currentTarget: defaultTab }, tabName);
                    }
                }
            }
        });
    </script>
</body>
</html>

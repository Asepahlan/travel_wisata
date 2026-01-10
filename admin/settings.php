<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Pengaturan Sistem';

// Inisialisasi pesan
$success = null;
$error = null;

// Ambil data admin saat ini
$admin_id = $_SESSION['admin_id'] ?? 0;
$current_admin = [
    'username' => '',
    'fullname' => '',
    'email' => '',
    'phone' => ''
];

if ($admin_id) {
    $stmt = $pdo->prepare("SELECT username, fullname, email, phone FROM admin WHERE id = ?");
    $stmt->execute([$admin_id]);
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin_data) {
        $current_admin = array_merge($current_admin, $admin_data);
    }
}

// Tangani form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update akun admin
        if (isset($_POST['update_admin'])) {
            // Validasi input
            $fullname = trim($_POST['fullname']);
            $email = trim($_POST['email']);
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';

            // Validasi nama lengkap
            if (empty($fullname)) {
                throw new Exception("Nama lengkap tidak boleh kosong");
            }

            // Validasi email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email tidak valid");
            }

            // Ambil data admin saat ini
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current_admin) {
                throw new Exception("Data admin tidak ditemukan");
            }

            $changes_made = false;
            $update_fields = [];
            $update_values = [];

            // Cek perubahan nama lengkap
            if ($fullname !== $current_admin['fullname']) {
                $update_fields[] = 'fullname = ?';
                $update_values[] = $fullname;
                $changes_made = true;
            }

            // Cek perubahan email
            if ($email !== $current_admin['email']) {
                $update_fields[] = 'email = ?';
                $update_values[] = $email;
                $changes_made = true;
            }

            // Jika ada password baru
            if (!empty($new_password)) {
                if (strlen($new_password) < 8) {
                    throw new Exception("Password minimal 8 karakter");
                }

                // Verifikasi password saat ini
                if (!password_verify($current_password, $current_admin['password'])) {
                    throw new Exception("Password saat ini tidak valid");
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_fields[] = 'password = ?';
                $update_values[] = $hashed_password;
                $changes_made = true;
            }

            // Jika ada perubahan, lakukan update
            if ($changes_made) {
                $update_values[] = $_SESSION['admin_id'];
                $sql = "UPDATE admin SET " . implode(', ', $update_fields) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_values);

                // Update session
                $_SESSION['admin_nama'] = $fullname;
                $_SESSION['admin_email'] = $email;

                if ($changes_made) {
                    $success = 'Profil admin berhasil diperbarui';
                } else {
                    $success = 'Tidak ada perubahan data yang dilakukan';
                }
            }
        }

        // Update pengaturan umum
        if (isset($_POST['update_general'])) {
            $new_settings = [
                'site_name' => trim($_POST['site_name']),
                'site_description' => trim($_POST['site_description']),
                'contact_email' => trim($_POST['contact_email']),
                'contact_phone' => trim($_POST['contact_phone']),
                'contact_address' => trim($_POST['contact_address']),
                'currency' => trim($_POST['currency']),
                'timezone' => trim($_POST['timezone'])
            ];

            // Ambil pengaturan saat ini dari database
            $current_settings = [];
            $result = $pdo->query("SELECT * FROM settings");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $current_settings[$row['setting_key']] = $row['setting_value'];
            }

            // Update ke config.php
            $config_content = file_get_contents('../config/config.php');
            $changes_made = false;

            foreach ($new_settings as $key => $value) {
                $current_value = defined($key) ? constant($key) : '';

                // Cek apakah ada perubahan
                if ($value !== $current_value) {
                    $pattern = "/define\('" . $key . "',\s*'[^']*'\)/i";
                    $replacement = "define('" . $key . "', '" . addslashes($value) . "')";
                    $config_content = preg_replace($pattern, $replacement, $config_content);
                    $changes_made = true;
                }

                // Update database settings
                if (isset($current_settings[$key]) && $current_settings[$key] !== $value) {
                    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                    $changes_made = true;
                }
            }

            if ($changes_made) {
                file_put_contents('../config/config.php', $config_content);
                $success = 'Pengaturan berhasil diperbarui';
            } else {
                $success = 'Tidak ada perubahan yang dilakukan';
            }
        }

        // Update pengaturan pembayaran
        if (isset($_POST['update_payment'])) {
            $settings = [
                'bank_name' => trim($_POST['bank_name']),
                'account_number' => trim($_POST['account_number']),
                'account_holder' => trim($_POST['account_holder']),
                'payment_auto_confirm' => isset($_POST['payment_auto_confirm']) ? 1 : 0
            ];

            // Check for changes
            $changes_made = false;
            $current_settings = [];
            $result = $pdo->query("SELECT * FROM settings");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $current_settings[$row['setting_key']] = $row['setting_value'];
            }

            // Simpan ke database
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                if (!isset($current_settings[$key]) || $current_settings[$key] != $value) {
                    $stmt->execute([$key, $value]);
                    $changes_made = true;
                }
            }

            if ($changes_made) {
                $success = 'Pengaturan pembayaran berhasil diperbarui';
            } else {
                $success = 'Tidak ada perubahan yang dilakukan';
            }
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
            // Check for changes
            $changes_made = false;
            $current_settings = [];
            $result = $pdo->query("SELECT * FROM settings WHERE setting_key IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'email_from', 'email_from_name')");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $current_settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $key => $value) {
                if (!isset($current_settings[$key]) || $current_settings[$key] != $value) {
                    $stmt->execute([$key, $value]);
                    $changes_made = true;
                }
            }
            
            if ($changes_made) {
                $success = 'Pengaturan email berhasil diperbarui';
            }
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
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
    </div>

    <!-- Flash Messages -->
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $success; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
            <a href="#general" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm" id="general-tab">
                <i class="fas fa-cog mr-2"></i>
                Pengaturan Umum
            </a>
            <a href="#admin" class="tab-link whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300" id="admin-tab">
                <i class="fas fa-user-shield mr-2"></i>
                Akun Admin
            </a>
        </nav>
    </div>


    <!-- Tab Content -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <!-- Tab 1: General Settings -->
        <div id="general" class="tab-content p-6">
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
        <div id="admin" class="tab-content" style="display: none;">
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Pengaturan Umum</h3>
                <p class="text-sm text-gray-500">Kelola pengaturan dasar website Anda</p>
            </div>

            <form method="post" class="space-y-6">
                <!-- Account Information Card -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
                    <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
                        <h4 class="text-lg font-medium text-gray-900 flex items-center">
                            <i class="fas fa-user-shield text-blue-600 mr-2"></i>
                            Informasi Akun
                        </h4>
                        <p class="mt-1 text-sm text-gray-500">Kelola informasi akun admin Anda</p>
                    </div>
                    <div class="px-6 py-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username Field -->
                            <div class="space-y-1">
                                <label for="username" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-user text-blue-500 mr-1"></i>
                                    Username
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                    <input type="text" name="username" id="username"
                                        value="<?php echo htmlspecialchars($current_admin['username']); ?>"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md bg-gray-50"
                                        readonly>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Username tidak dapat diubah</p>
                            </div>

                            <!-- Email Field -->
                            <div class="space-y-1">
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-envelope text-blue-500 mr-1"></i>
                                    Email
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input type="email" name="email" id="email"
                                        value="<?php echo htmlspecialchars($current_admin['email']); ?>"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="email@contoh.com" required>
                                </div>
                            </div>

                            <!-- Full Name Field -->
                            <div class="space-y-1 md:col-span-2">
                                <label for="fullname" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-id-card text-blue-500 mr-1"></i>
                                    Nama Lengkap
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-user-tag text-gray-400"></i>
                                    </div>
                                    <input type="text" name="fullname" id="fullname"
                                        value="<?php echo htmlspecialchars($current_admin['fullname']); ?>"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="Nama lengkap admin" required>
                                </div>
                            </div>

                            <!-- Phone Number Field -->
                            <div class="space-y-1">
                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-phone text-blue-500 mr-1"></i>
                                    Nomor Telepon
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-phone-alt text-gray-400"></i>
                                    </div>
                                    <input type="tel" name="phone" id="phone"
                                        value="<?php echo htmlspecialchars($current_admin['phone']); ?>"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="081234567890" required>
                                </div>
                            </div>

                            <!-- Current Password Field -->
                            <div class="space-y-1">
                                <label for="current_password" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-lock text-blue-500 mr-1"></i>
                                    Password Saat Ini
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-key text-gray-400"></i>
                                    </div>
                                    <input type="password" name="current_password" id="current_password"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="••••••••"
                                        autocomplete="current-password">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Wajib diisi untuk mengubah password</p>
                            </div>

                            <!-- New Password Field -->
                            <div class="space-y-1">
                                <label for="new_password" class="block text-sm font-medium text-gray-700">
                                    <i class="fas fa-key text-blue-500 mr-1"></i>
                                    Password Baru
                                </label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="new_password" id="new_password"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md"
                                        placeholder="••••••••"
                                        autocomplete="new-password">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Minimal 8 karakter, gunakan kombinasi huruf dan angka</p>
                            </div>

                            <!-- Password Strength Meter -->
                            <div class="md:col-span-2">
                                <div id="password-strength" class="h-1 bg-gray-200 rounded-full overflow-hidden mt-1 hidden">
                                    <div id="password-strength-bar" class="h-full w-0 transition-all duration-300"></div>
                                </div>
                                <p id="password-strength-text" class="text-xs mt-1 hidden"></p>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Action Buttons -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-right">
                        <button type="reset" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                            <i class="fas fa-undo mr-2"></i> Reset
                        </button>
                        <button type="submit" name="update_admin" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                            <i class="fas fa-save mr-2"></i> Simpan Perubahan
                        </button>
                    </div>
            </form>
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

        // Hide all tab content
        const tabContents = document.getElementsByClassName('tab-content');
        for (let i = 0; i < tabContents.length; i++) {
            tabContents[i].style.display = 'none';
        }

        // Remove active class from all tab links
        const tabLinks = document.getElementsByClassName('tab-link');
        for (let i = 0; i < tabLinks.length; i++) {
            tabLinks[i].classList.remove('border-blue-500', 'text-blue-600');
            tabLinks[i].classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        }

        // Show the current tab and add active class
        document.getElementById(tabName).style.display = 'block';
        const activeTab = document.querySelector(`[href="#${tabName}"]`);
        if (activeTab) {
            activeTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            activeTab.classList.add('border-blue-500', 'text-blue-600');
        }

        // Update URL hash
        if (history.pushState) {
            history.pushState(null, null, '#' + tabName);
        } else {
            window.location.hash = '#' + tabName;
        }
    }

    // Check for hash on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Check URL hash first for direct tab access
        if (window.location.hash) {
            const tabName = window.location.hash.substring(1);
            const tabContent = document.getElementById(tabName);
            if (tabContent) {
                openTab(null, tabName);
                return;
            }
        }

        // Default to first tab if no hash or invalid hash
        const firstTab = document.querySelector('.tab-content');
        if (firstTab) {
            firstTab.style.display = 'block';
            const firstTabLink = document.querySelector('.tab-link');
            if (firstTabLink) {
                firstTabLink.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                firstTabLink.classList.add('border-blue-500', 'text-blue-600');
            }
        }

        // Add click event listeners to tab links
        const tabLinks = document.getElementsByClassName('tab-link');
        for (let i = 0; i < tabLinks.length; i++) {
            tabLinks[i].addEventListener('click', function(e) {
                const tabName = this.getAttribute('href').substring(1);
                openTab(e, tabName);
            });
        }
    });

    // Handle back/forward navigation
    window.addEventListener('popstate', function() {
        if (window.location.hash) {
            const tabName = window.location.hash.substring(1);
            openTab(null, tabName);
        } else {
            // Default to first tab if no hash
            const firstTab = document.querySelector('.tab-content');
            if (firstTab) {
                openTab(null, firstTab.id);
            }
        }
    });
</script>

<?php
// Get the buffered content and include the layout
$content = ob_get_clean();
include 'includes/layout.php';
?>
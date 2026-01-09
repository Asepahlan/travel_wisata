<?php
/**
 * File Konfigurasi Aplikasi
 * 
 * File ini berisi pengaturan dasar untuk aplikasi Travel Wisata.
 * Pastikan untuk mengkonfigurasi dengan benar sebelum digunakan.
 */

// Konfigurasi Umum
define('site_name', 'Travel Deas Royan');
define('SITE_URL', 'http://localhost/');
define('site_description', 'Layanan travel terpercaya untuk perjalanan Anda');
define('contact_email', 'info@travelwdfgdfgdisata.com');
define('contact_phone', '085798347675');
define('contact_address', 'G723+473, Jalan, Mandalawangi, Kec. Salopa, Kabupaten Tasikmalaya, Jawa Barat');
define('timezone', 'Asia/Makassar');
define('currency', 'IDR');

// Konfigurasi WhatsApp
// Menggunakan contact_phone yang sudah didefinisikan di atas
define('WHATSAPP_NUMBER', '62' . ltrim(contact_phone, '0')); // Format: 62xxxxxxxxxxx (tanpa + atau 0 di depan)
define('WHATSAPP_MESSAGE', 'Halo, saya ingin konfirmasi pembayaran untuk kode booking: ');

// Konfigurasi Admin
define('ADMIN_EMAIL', 'admin@travelwisata.com');
// Menggunakan contact_phone yang sudah didefinisikan di atas
define('ADMIN_PHONE', '+' . WHATSAPP_NUMBER);

// Set default timezone
date_default_timezone_set(timezone);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    
    // Start the session
    session_start();
    
    // Regenerate session ID to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    } else if (time() - $_SESSION['last_regeneration'] > 1800) {
        // Regenerate session ID every 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Include database connection
require_once __DIR__ . '/database.php';

// Load settings from database
require_once __DIR__ . '/settings.php';

// Helper functions
require_once __DIR__ . '/../helpers/functions.php';

// Check if user is logged in (for admin pages)
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
}
?>

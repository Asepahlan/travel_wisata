<?php
// Load settings from database
function load_settings() {
    global $pdo;
    
    $settings = [];
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        if (!empty($results)) {
            $settings = $results;
            
            // Set timezone
            if (!empty($settings['timezone'])) {
                date_default_timezone_set($settings['timezone']);
            }
        }
    } catch (PDOException $e) {
        // If settings table doesn't exist or there's an error, use defaults
        error_log("Error loading settings: " . $e->getMessage());
    }
    
    return $settings;
}

// Load settings
$settings = load_settings();

// Default values
$defaults = [
    'site_name' => defined('site_name') ? constant('site_name') : 'Travel Deas Royan',
    'site_description' => defined('site_description') ? constant('site_description') : 'Layanan travel terpercaya untuk perjalanan Anda',
    'contact_email' => defined('contact_email') ? constant('contact_email') : 'info@travelwisata.com',
    'contact_phone' => defined('contact_phone') ? constant('contact_phone') : '+62 812-3456-7890',
    'contact_address' => defined('contact_address') ? constant('contact_address') : 'Jl. Contoh No. 123, Kota Bandung, Jawa Barat, Indonesia',
    'timezone' => defined('timezone') ? constant('timezone') : 'Asia/Jakarta',
    'currency' => defined('currency') ? constant('currency') : 'IDR'
];

// Merge settings with defaults
$settings = array_merge($defaults, $settings);

// Make settings available as constants
foreach ($settings as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

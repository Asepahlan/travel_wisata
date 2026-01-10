<?php
require_once '../config/config.php';

// Start the session
session_start();

// Unset all session variables
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set success message in session
session_start();
$_SESSION['logout_success'] = 'Anda telah berhasil keluar. Sampai jumpa kembali!';

// Redirect to login page with success message
header('Location: login.php');
exit();

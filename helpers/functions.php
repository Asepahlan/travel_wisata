<?php
// Generate random booking code
function generateBookingCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return 'TRV-' . $code;
}

// Format date to Indonesian format
function indonesianDate($date) {
    $month = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember'
    ];
    
    $date = explode('-', $date);
    return $date[2] . ' ' . $month[$date[1]] . ' ' . $date[0];
}

// Sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Set flash message
function setFlash($name, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$name] = $message;
}

// Get flash message
function getFlash($name) {
    if (isset($_SESSION['flash'][$name])) {
        $message = $_SESSION['flash'][$name];
        unset($_SESSION['flash'][$name]);
        return $message;
    }
    return '';
}

// Format currency
function formatRupiah($number) {
    return 'Rp ' . number_format($number, 0, ',', '.');
}
?>

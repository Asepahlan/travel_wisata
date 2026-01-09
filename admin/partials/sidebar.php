<?php
// Fungsi untuk mengecek halaman aktif
function isActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page) ? 'bg-blue-900' : 'hover:bg-blue-700';
}
?>

<!-- Sidebar -->
<div class="bg-blue-800 text-white w-64 flex-shrink-0 sidebar">
    <div class="p-4">
        <h1 class="text-2xl font-bold"><span class="ml-3"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></span></h1>
        <p class="text-blue-200 text-sm">Panel Admin</p>
    </div>
    <nav class="mt-6">
        <a href="index.php" class="flex items-center px-6 py-3 text-white <?php echo isActive('index.php'); ?>">
            <i class="fas fa-tachometer-alt mr-3"></i>
            Dashboard
        </a>
        <a href="bookings.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('bookings.php'); ?>">
            <i class="fas fa-calendar-alt mr-3"></i>
            Pemesanan
        </a>
        <a href="packages.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('packages.php'); ?>">
            <i class="fas fa-box-open mr-3"></i>
            Paket Wisata
        </a>
        <ul>
            <li class="mb-2">
                <a href="fleets.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 <?php echo isActive('fleets.php'); ?>">
                    <i class="fas fa-bus mr-3"></i>
                    <span>Armada</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="drivers.php" class="flex items-center px-4 py-3 rounded-lg transition-colors duration-200 <?php echo isActive('drivers.php'); ?>">
                    <i class="fas fa-id-card-alt mr-3"></i>
                    <span>Supir</span>
                </a>
            </li>
        </ul>
        <a href="routes.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('routes.php'); ?>">
            <i class="fas fa-route mr-3"></i>
            Rute
        </a>
        <a href="settings.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('settings.php'); ?>">
            <i class="fas fa-cog mr-3"></i>
            Pengaturan
        </a>
        <div class="border-t border-blue-700 my-2"></div>
        <a href="reports.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('reports.php'); ?>">
            <i class="fas fa-chart-bar mr-3"></i>
            Laporan
        </a>
        <a href="logout.php" class="flex items-center px-6 py-3 text-blue-100 hover:bg-blue-700 absolute bottom-0 w-64">
            <i class="fas fa-sign-out-alt mr-3"></i>
            Logout
        </a>
    </nav>
</div>

<?php
// Function to check if current page matches the menu item
function isActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page) ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
}
?>

<nav class="bg-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="text-xl font-bold text-blue-600">
                        <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>
                    </a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="index.php" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo isActive('index.php'); ?>">
                        Beranda
                    </a>
                    <a href="paket.php" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo isActive('paket.php'); ?>">
                        Paket Wisata
                    </a>
                    <a href="pesan.php" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo isActive('pesan.php'); ?>">
                        Pesan Sekarang
                    </a>
                    <a href="cek-status.php" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo isActive('cek-status.php'); ?>">
                        Cek Status
                    </a>
                </div>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <a href="/admin/" class="text-gray-500 hover:text-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                        Dashboard Admin
                    </a>
                    <a href="/admin/logout.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="/admin/login.php" class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Admin Login
                    </a>
                <?php endif; ?>
            </div>
            <!-- Mobile menu button -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                    <span class="sr-only">Open main menu</span>
                    <!-- Icon when menu is closed -->
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <!-- Icon when menu is open -->
                    <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu, show/hide based on menu state -->
    <div class="sm:hidden hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="index.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                Beranda
            </a>
            <a href="paket.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'paket.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                Paket Wisata
            </a>
            <a href="pesan.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'pesan.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                Pesan Sekarang
            </a>
            <a href="cek-status.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'cek-status.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                Cek Status
            </a>
            <?php if (isset($_SESSION['admin_id'])): ?>
                <a href="admin/dashboard.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                    Dashboard Admin
                </a>
                <a href="admin/logout.php" class="block pl-3 pr-4 py-2 border-l-4 text-base font-medium <?php echo basename($_SERVER['PHP_SELF']) === 'logout.php' ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800'; ?>">
                <a href="admin/logout.php" class="border-transparent text-red-600 hover:bg-gray-50 hover:border-gray-300 hover:text-red-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Logout
                </a>
            <?php else: ?>
                <a href="admin/" class="border-transparent text-blue-600 hover:bg-gray-50 hover:border-gray-300 hover:text-blue-800 block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    Admin Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
    // Toggle mobile menu
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        const isHidden = menu.classList.contains('hidden');
        
        // Toggle menu visibility
        if (isHidden) {
            menu.classList.remove('hidden');
            // Change icon to X
            this.querySelector('svg:first-child').classList.add('hidden');
            this.querySelector('svg:last-child').classList.remove('hidden');
        } else {
            menu.classList.add('hidden');
            // Change icon to hamburger
            this.querySelector('svg:first-child').classList.remove('hidden');
            this.querySelector('svg:last-child').classList.add('hidden');
        }
    });
</script>

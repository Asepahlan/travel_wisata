<?php
if (!function_exists('isActive')) {
    function isActive($page) {
        $current_page = basename($_SERVER['PHP_SELF']);
        return ($current_page === $page) ? 'bg-blue-900' : 'hover:bg-blue-700';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            transition: transform 0.3s ease-in-out;
            z-index: 40;
        }
        @media (max-width: 1023px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                overflow-y: auto;
            }
            .sidebar-open .sidebar {
                transform: translateX(0);
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 30;
            }
            .sidebar-open .overlay {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-100 h-full">
    <div class="flex h-full" id="app">
        <!-- Mobile overlay -->
        <div class="overlay" id="overlay"></div>
        
        <!-- Sidebar -->
        <aside class="bg-blue-800 text-white w-64 flex-shrink-0 sidebar">
            <div class="p-4 border-b border-blue-700">
                <div class="flex justify-between items-center">
                    <h1 class="text-xl font-bold">
                        <span class="ml-3"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></span>
                    </h1>
                    <button class="lg:hidden text-white focus:outline-none" id="closeSidebar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <p class="text-blue-200 text-sm">Panel Admin</p>
            </div>
            <nav class="mt-2">
                <a href="index.php" class="flex items-center px-6 py-3 text-white <?php echo isActive('index.php'); ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Dashboard</span>
                </a>
                <a href="bookings.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('bookings.php'); ?>">
                    <i class="fas fa-calendar-alt mr-3"></i>
                    <span>Pemesanan</span>
                </a>
                <a href="packages.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('packages.php'); ?>">
                    <i class="fas fa-box-open mr-3"></i>
                    <span>Paket Wisata</span>
                </a>
                <a href="fleets.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('fleets.php'); ?>">
                    <i class="fas fa-bus mr-3"></i>
                    <span>Armada</span>
                </a>
                <a href="drivers.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('drivers.php'); ?>">
                    <i class="fas fa-id-card-alt mr-3"></i>
                    <span>Supir</span>
                </a>
                <a href="routes.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('routes.php'); ?>">
                    <i class="fas fa-route mr-3"></i>
                    <span>Rute</span>
                </a>
                <a href="settings.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('settings.php'); ?>">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Pengaturan</span>
                </a>
                <a href="reports.php" class="flex items-center px-6 py-3 text-blue-100 <?php echo isActive('reports.php'); ?>">
                    <i class="fas fa-chart-bar mr-3"></i>
                    <span>Laporan</span>
                </a>
                <div class="border-t border-blue-700 my-2"></div>
                <a href="logout.php" class="flex items-center px-6 py-3 text-blue-100 hover:bg-blue-700">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    <span>Keluar</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-white shadow">
                <div class="flex items-center justify-between px-4 py-3 sm:px-6">
                    <div class="flex items-center">
                        <button class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none" id="toggleSidebar">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        <h2 class="ml-2 text-lg font-semibold text-gray-800"><?php echo $page_title ?? 'Dashboard'; ?></h2>
                    </div>
                    <div class="flex items-center">
                        <div class="relative">
                            <button class="flex items-center text-sm text-gray-700 hover:text-gray-900 focus:outline-none">
                                <span class="mr-2"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                                <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_username'] ?? 'A'); ?>" alt="User avatar">
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                <?php if (isset($success_message)): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $success_message; ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?php echo $error_message; ?></span>
                    </div>
                <?php endif; ?>

                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- jQuery and other scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('toggleSidebar');
            const closeBtn = document.getElementById('closeSidebar');
            const overlay = document.getElementById('overlay');
            const app = document.getElementById('app');

            function toggleSidebar() {
                app.classList.toggle('sidebar-open');
            }

            if (toggleBtn) toggleBtn.addEventListener('click', toggleSidebar);
            if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if (overlay) overlay.addEventListener('click', toggleSidebar);

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const isClickInside = sidebar.contains(event.target) || 
                                    (toggleBtn && toggleBtn.contains(event.target));
                
                if (!isClickInside && window.innerWidth < 1024) {
                    app.classList.remove('sidebar-open');
                }
            });

            // Handle window resize
            function handleResize() {
                if (window.innerWidth >= 1024) {
                    app.classList.remove('sidebar-open');
                }
            }

            window.addEventListener('resize', handleResize);
        });
    </script>
</body>
</html>

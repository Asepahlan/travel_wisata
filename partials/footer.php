<footer class="bg-gray-800 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-lg font-semibold mb-4">Tentang Kami</h3>
                <p class="text-gray-300">
                    <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>
                </p>
                <p class="text-gray-300 text-sm mt-2">
                    <?php echo defined('site_description') ? constant('site_description') : 'Layanan travel terpercaya untuk perjalanan Anda'; ?>
                </p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Kontak</h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-phone-alt text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-400">Nomor Telepon</p>
                            <p class="text-gray-300"><?php echo htmlspecialchars(defined('contact_phone') ? constant('contact_phone') : '+62 812-3456-7890'); ?></p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-envelope text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-400">Email</p>
                            <a href="mailto:<?php echo htmlspecialchars(defined('contact_email') ? constant('contact_email') : 'info@travelwisata.com'); ?>" class="text-gray-300 hover:text-blue-400 transition">
                                <?php echo htmlspecialchars(defined('contact_email') ? constant('contact_email') : 'info@travelwisata.com'); ?>
                            </a>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-map-marker-alt text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-400">Alamat</p>
                            <p class="text-gray-300"><?php echo nl2br(htmlspecialchars(defined('contact_address') ? constant('contact_address') : 'Jl. Contoh No. 123, Kota Bandung, Jawa Barat, Indonesia')); ?></p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-clock text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-400">Zona Waktu</p>
                            <p class="text-gray-300">
                                <?php 
                                $tz = defined('timezone') ? constant('timezone') : 'Asia/Jakarta';
                                $timezone = new DateTimeZone($tz);
                                $now = new DateTime('now', $timezone);
                                $timezone_abbr = $now->format('T');
                                echo htmlspecialchars($timezone_abbr . ' (' . $tz . ')');
                                ?>
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Jam Operasional</h3>
                <ul class="space-y-2">
                    <li class="flex justify-between">
                        <span>Senin - Jumat</span>
                        <span>08:00 - 17:00 WIB</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Sabtu</span>
                        <span>09:00 - 15:00 WIB</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Minggu</span>
                        <span>Tutup</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center">
            <p>&copy; <?php echo date('Y'); ?> <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
    // Close flash messages after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 1s ease-in-out';
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 1000);
            }, 5000);
        });
    });
</script>
</body>
</html>

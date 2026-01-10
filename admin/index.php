<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Dashboard';

// Hitung total data
$counts = [
    'bookings' => $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn(),
    'packages' => $pdo->query("SELECT COUNT(*) FROM paket")->fetchColumn(),
    'fleets' => $pdo->query("SELECT COUNT(*) FROM armada")->fetchColumn(),
    'routes' => $pdo->query("SELECT COUNT(*) FROM rute")->fetchColumn(),
];

// Ambil data booking terbaru
$recentBookings = $pdo->query("
    SELECT b.*, p.nama_paket, a.nama as nama_armada 
    FROM booking b 
    JOIN paket p ON b.id_paket = p.id 
    JOIN armada a ON b.id_armada = a.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll();

ob_start();
?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 card hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                    <i class="fas fa-calendar-check text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Total Pemesanan</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($counts['bookings']); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 card hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                    <i class="fas fa-box-open text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Paket Wisata</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($counts['packages']); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 card hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                    <i class="fas fa-bus text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Armada</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($counts['fleets']); ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 card hover:shadow-md transition-shadow">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                    <i class="fas fa-route text-xl"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Rute</p>
                    <h3 class="text-2xl font-bold"><?php echo number_format($counts['routes']); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Pemesanan Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($recentBookings) > 0): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($booking['kode_booking']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['nama_pemesan']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($booking['nama_paket']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y', strtotime($booking['tanggal_berangkat'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_classes = [
                                        'menunggu' => 'bg-yellow-100 text-yellow-800',
                                        'dikonfirmasi' => 'bg-green-100 text-green-800',
                                        'ditolak' => 'bg-red-100 text-red-800',
                                        'selesai' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $status_text = [
                                        'menunggu' => 'Menunggu',
                                        'dikonfirmasi' => 'Dikonfirmasi',
                                        'ditolak' => 'Ditolak',
                                        'selesai' => 'Selesai'
                                    ];
                                    $status = $booking['status'] ?? 'menunggu';
                                    $class = $status_classes[$status] ?? 'bg-gray-100 text-gray-800';
                                    $text = $status_text[$status] ?? ucfirst($status);
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $class; ?>">
                                        <?php echo $text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="booking-detail.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i> Lihat
                                    </a>
                                    <?php if ($status === 'menunggu'): ?>
                                        <a href="#" onclick="return confirmAction('Apakah Anda yakin ingin mengkonfirmasi pemesanan ini?', 'bookings.php?action=confirm&id=<?php echo $booking['id']; ?>')" class="text-green-600 hover:text-green-900 mr-3" title="Konfirmasi">
                                            <i class="fas fa-check"></i> Konfirmasi
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                Belum ada data pemesanan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
            <div class="text-sm text-gray-500">
                Menampilkan <?php echo min(5, count($recentBookings)); ?> dari <?php echo $counts['bookings']; ?> pemesanan
            </div>
            <a href="bookings.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                Lihat Semua <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
<?php
$content = ob_get_clean();
?>
<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Function to confirm actions
function confirmAction(message, url) {
    Swal.fire({
        title: 'Konfirmasi',
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, lanjutkan',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}
</script>
<?php
include 'includes/layout.php';
?>

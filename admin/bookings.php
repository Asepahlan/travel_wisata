<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Pemesanan';

// Handle actions (confirm/reject/complete/delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if (in_array($action, ['confirm', 'reject', 'complete', 'delete'])) {
        try {
            $status_map = [
                'confirm' => 'dikonfirmasi',
                'reject' => 'ditolak',
                'complete' => 'selesai'
            ];
            
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM booking WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = 'Pesanan berhasil dihapus';
            } else {
                $stmt = $pdo->prepare("UPDATE booking SET status = ? WHERE id = ?");
                $stmt->execute([$status_map[$action], $id]);
                $_SESSION['success'] = 'Status pesanan berhasil diperbarui';
            }
            
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Initialize filters
$where = [];
$params = [];

// Apply status filter
if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] !== 'all') {
    $where[] = "b.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%" . $_GET['search'] . "%";
    $where[] = "(b.kode_booking LIKE ? OR b.nama_pemesan LIKE ? OR b.email LIKE ? OR b.no_wa LIKE ?)";
    $params = array_merge($params, array_fill(0, 4, $search));
}

// Query to fetch booking data with package, fleet, and route information
$query = "
    SELECT 
        b.*, 
        p.nama_paket, 
        p.jenis_layanan,
        a.nama as nama_armada,
        r.asal,
        r.tujuan
    FROM booking b
    LEFT JOIN paket p ON b.id_paket = p.id
    LEFT JOIN armada a ON b.id_armada = a.id
    LEFT JOIN rute r ON p.id_rute = r.id
";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY b.created_at DESC";

// Add pagination
$per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM booking b" . (!empty($where) ? " WHERE " . implode(" AND ", $where) : "");
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_bookings = $stmt->fetchColumn();
$total_pages = ceil($total_bookings / $per_page);

// Add pagination to the main query
$query .= " LIMIT :per_page OFFSET :offset";

// Execute the main query with named parameters
$stmt = $pdo->prepare($query);

// Bind parameters with explicit types
foreach ($params as $key => $value) {
    $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
}

// Bind pagination parameters with explicit types
$stmt->bindValue(':per_page', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$bookings = $stmt->fetchAll();

// Get booking counts by status
$status_counts = [
    'all' => $pdo->query("SELECT COUNT(*) FROM booking")->fetchColumn(),
    'menunggu' => $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'menunggu'")->fetchColumn(),
    'dikonfirmasi' => $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'dikonfirmasi'")->fetchColumn(),
    'ditolak' => $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'ditolak'")->fetchColumn(),
    'selesai' => $pdo->query("SELECT COUNT(*) FROM booking WHERE status = 'selesai'")->fetchColumn(),
];

// Reset notification messages after displaying
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Start output buffering for the layout
ob_start();
?>

<div class="space-y-6">
    <!-- Filter Section -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Filter Pemesanan</h3>
        </div>
        <div class="p-6">
            <form method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" 
                                   name="search" 
                                   id="search" 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                                   class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="Cari kode booking, nama, email, atau no HP">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" 
                                name="status" 
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Semua Status</option>
                            <option value="menunggu" <?php echo (isset($_GET['status']) && $_GET['status'] === 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                            <option value="dikonfirmasi" <?php echo (isset($_GET['status']) && $_GET['status'] === 'dikonfirmasi') ? 'selected' : ''; ?>>Dikonfirmasi</option>
                            <option value="ditolak" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="selesai" <?php echo (isset($_GET['status']) && $_GET['status'] === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-filter mr-2"></i> Terapkan Filter
                        </button>
                        <?php if (isset($_GET['search']) || isset($_GET['status'])): ?>
                        <a href="bookings.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i> Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Summary -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total</p>
                        <p class="text-2xl font-semibold"><?php echo number_format($status_counts['all']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Menunggu</p>
                        <p class="text-2xl font-semibold"><?php echo number_format($status_counts['menunggu']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Dikonfirmasi</p>
                        <p class="text-2xl font-semibold"><?php echo number_format($status_counts['dikonfirmasi']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Ditolak</p>
                        <p class="text-2xl font-semibold"><?php echo number_format($status_counts['ditolak']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-4">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Selesai</p>
                        <p class="text-2xl font-semibold"><?php echo number_format($status_counts['selesai']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
    <div class="rounded-md bg-green-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">
                    <?php echo htmlspecialchars($success_message); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="rounded-md bg-red-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">
                    <?php echo htmlspecialchars($error_message); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bookings Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Pemesanan</h3>
                <div class="flex space-x-2">
                    <a href="export_bookings.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-file-export mr-2"></i> Ekspor Excel
                    </a>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kode</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): 
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
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['kode_booking']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['nama_pemesan']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['no_wa']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($booking['nama_paket'] ?? '-'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($booking['nama_armada'] ?? '-'); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('d M Y', strtotime($booking['tanggal_berangkat'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $booking['jumlah_orang'] ?? '1'; ?> orang</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900">Rp <?php echo number_format($booking['total_harga'] ?? 0, 0, ',', '.'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $booking['metode_pembayaran'] ?? '-'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full <?php echo $class; ?>">
                                        <?php echo $text; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="booking-detail.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($status === 'menunggu'): ?>
                                            <a href="#" onclick="return confirmAction('Apakah Anda yakin ingin mengkonfirmasi pemesanan ini?', 'bookings.php?action=confirm&id=<?php echo $booking['id']; ?>')" class="text-green-600 hover:text-green-900" title="Konfirmasi">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="#" onclick="return confirmAction('Apakah Anda yakin ingin menolak pemesanan ini?', 'bookings.php?action=reject&id=<?php echo $booking['id']; ?>')" class="text-yellow-600 hover:text-yellow-900" title="Tolak">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($status === 'dikonfirmasi'): ?>
                                            <a href="#" onclick="return confirmAction('Apakah Anda ingin menandai pemesanan ini sebagai selesai?', 'bookings.php?action=complete&id=<?php echo $booking['id']; ?>')" class="text-indigo-600 hover:text-indigo-900" title="Selesai">
                                                <i class="fas fa-check-double"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="#" onclick="return confirmAction('Apakah Anda yakin ingin menghapus pemesanan ini?', 'bookings.php?action=delete&id=<?php echo $booking['id']; ?>')" class="text-red-600 hover:text-red-900" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-inbox text-4xl mb-2 text-gray-300"></i>
                                    <p class="text-gray-600">Tidak ada data pemesanan</p>
                                    <p class="text-sm text-gray-500 mt-2">Coba ubah filter pencarian Anda</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Menampilkan <span class="font-medium"><?php echo ($offset + 1); ?></span> sampai 
                        <span class="font-medium"><?php echo min($offset + $per_page, $total_bookings); ?></span> dari 
                        <span class="font-medium"><?php echo $total_bookings; ?></span> hasil
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Sebelumnya</span>
                                <i class="fas fa-chevron-left h-5 w-5"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $i == $page ? 'bg-blue-50 text-blue-600 border-blue-500' : 'text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php elseif (($i == $page - 3 && $page > 4) || ($i == $page + 3 && $page < $total_pages - 3)): ?>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    ...
                                </span>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                <span class="sr-only">Selanjutnya</span>
                                <i class="fas fa-chevron-right h-5 w-5"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

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

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips if needed
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php
// Get the buffered content and include the layout
$content = ob_get_clean();
include 'includes/layout.php';
?>

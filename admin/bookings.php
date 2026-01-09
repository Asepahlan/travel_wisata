<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Pemesanan';

// Tangani aksi konfirmasi/tolak
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

// Filter
$where = [];
$params = [];

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $where[] = "b.status = ?";
    $params[] = $_GET['status'];
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = "%{$_GET['search']}%";
    $where[] = "(b.kode_booking LIKE ? OR b.nama_pemesan LIKE ? OR b.email LIKE ? OR b.no_hp LIKE ?)";
    $params = array_merge($params, [$search, $search, $search, $search]);
}

// Query untuk mengambil data pemesanan
$query = "
    SELECT 
        b.*, 
        p.nama_paket,
        p.jenis_layanan,
        a.nama as nama_armada,
        r.asal, 
        r.tujuan,
        b.no_wa
    FROM 
        booking b
    JOIN 
        paket p ON b.id_paket = p.id
    LEFT JOIN
        armada a ON b.id_armada = a.id
    LEFT JOIN
        rute r ON p.id_rute = r.id
";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .status-pending {
            background-color: #fef9c3;
            color: #854d0e;
        }
        .status-confirmed {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Include Sidebar -->
        <?php include 'partials/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <header class="bg-white shadow">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">Manajemen Pemesanan</h2>
                    <div class="flex items-center">
                        <span class="text-gray-600 mr-4"><?php echo htmlspecialchars($_SESSION['admin_nama']); ?></span>
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($_SESSION['admin_nama'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="bg-white shadow-sm rounded-lg m-6 overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Filter Pemesanan</h3>
                </div>
                <div class="p-6">
                    <form action="" method="get" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <div class="mt-1 relative">
                                    <select id="status" name="status" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm">
                                        <option value="">Semua Status</option>
                                        <option value="menunggu" <?php echo (isset($_GET['status']) && $_GET['status'] === 'menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                        <option value="dikonfirmasi" <?php echo (isset($_GET['status']) && $_GET['status'] === 'dikonfirmasi') ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                        <option value="ditolak" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                        <option value="selesai" <?php echo (isset($_GET['status']) && $_GET['status'] === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" 
                                           id="search" 
                                           name="search" 
                                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                                           class="focus:ring-blue-500 focus:border-blue-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" 
                                           placeholder="Kode Booking / Nama / Email / No HP">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-search mr-2"></i> Cari
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center pt-2">
                            <div class="text-sm text-gray-500">
                                <?php echo count($bookings); ?> data ditemukan
                            </div>
                            <div class="flex space-x-2">
                                <a href="bookings.php" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-sync-alt mr-2"></i> Reset
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fas fa-filter mr-2"></i> Terapkan Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                </form>
            </div>

            <!-- Flash Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mx-6 mb-6" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-check-circle mr-2"></i></div>
                        <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mx-6 mb-6" role="alert">
                    <div class="flex">
                        <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                        <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Bookings Table -->
            <div class="bg-white shadow-sm rounded-lg m-6 overflow-hidden border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
                        <h3 class="text-lg font-semibold text-gray-800">Daftar Pemesanan</h3>
                        <div class="w-full sm:w-auto">
                            <a href="export_bookings.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 w-full justify-center sm:w-auto">
                                <i class="fas fa-file-export mr-2"></i> Export Excel
                            </a>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Kode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paket & Rute</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Tanggal</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Total</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
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
                                            <div class="text-sm font-medium text-gray-900 font-mono"><?php echo htmlspecialchars($booking['kode_booking']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 truncate max-w-[150px]" title="<?php echo htmlspecialchars($booking['nama_pemesan']); ?>">
                                                <?php echo htmlspecialchars($booking['nama_pemesan']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> 
                                                <?php echo !empty($booking['no_wa']) ? htmlspecialchars($booking['no_wa']) : '<span class="text-gray-400">-</span>'; ?>
                                            </div>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900 truncate max-w-[200px]">
                                                <?php 
                                                    $rute = $booking['asal'] . ' - ' . $booking['tujuan'];
                                                    $jenis_layanan = $booking['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In';
                                                    
                                                    // Hapus rute dan jenis layanan dari nama paket
                                                    $nama_paket = $booking['nama_paket'];
                                                    $nama_paket = str_replace($rute, '', $nama_paket);
                                                    $nama_paket = str_replace($jenis_layanan, '', $nama_paket);
                                                    $nama_paket = trim(preg_replace('/\s+/', ' ', $nama_paket));
                                                    
                                                    echo !empty($nama_paket) ? htmlspecialchars($nama_paket) : $jenis_layanan; 
                                                ?>
                                            </div>
                                            <div class="mt-1">
                                                <span class="text-xs font-medium text-gray-600">
                                                    <?php echo htmlspecialchars($rute); ?>
                                                </span>
                                                <?php if (!empty($nama_paket)): ?>
                                                <span class="ml-2 px-2 py-0.5 inline-flex text-[10px] leading-4 font-semibold rounded-full <?php echo $booking['jenis_layanan'] === 'all_in' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                    <?php echo $jenis_layanan; ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo date('d M Y', strtotime($booking['tanggal_berangkat'])); ?>
                                            </div>
                                            <?php if (!empty($booking['waktu_jemput'])): ?>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('H:i', strtotime($booking['waktu_jemput'])); ?> WIB
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="text-gray-900">Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?></div>
                                            <?php if (!empty($booking['jumlah_penumpang'])): ?>
                                            <div class="text-xs text-gray-500">
                                                <?php echo $booking['jumlah_penumpang']; ?> orang
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full <?php echo $class; ?>">
                                                <?php echo $text; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end items-center space-x-2">
                                                <a href="booking-detail.php?id=<?php echo $booking['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800 p-1.5 rounded-full hover:bg-blue-50" 
                                                   title="Lihat Detail">
                                                    <i class="fas fa-eye w-4 h-4"></i>
                                                </a>
                                                
                                                <?php if ($booking['status'] === 'menunggu'): ?>
                                                    <button type="button" 
                                                            onclick="confirmBooking(event, '<?php echo $booking['id']; ?>')"
                                                            class="text-green-600 hover:text-green-800 p-1.5 rounded-full hover:bg-green-50"
                                                            title="Konfirmasi">
                                                        <i class="fas fa-check w-4 h-4"></i>
                                                    </button>
                                                    <button type="button" 
                                                            onclick="rejectBooking(event, '<?php echo $booking['id']; ?>')"
                                                            class="text-amber-600 hover:text-amber-800 p-1.5 rounded-full hover:bg-amber-50"
                                                            title="Tolak">
                                                        <i class="fas fa-times w-4 h-4"></i>
                                                    </button>
                                                <?php elseif ($booking['status'] === 'dikonfirmasi'): ?>
                                                    <button type="button"
                                                            onclick="completeBooking(event, '<?php echo $booking['id']; ?>')"
                                                            class="text-indigo-600 hover:text-indigo-800 p-1.5 rounded-full hover:bg-indigo-50"
                                                            title="Tandai Selesai">
                                                        <i class="fas fa-flag-checkered w-4 h-4"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button"
                                                        onclick="confirmDelete(event, '<?php echo $booking['id']; ?>')"
                                                        class="text-red-600 hover:text-red-800 p-1.5 rounded-full hover:bg-red-50"
                                                        title="Hapus">
                                                    <i class="fas fa-trash w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-400">
                                            <i class="fas fa-inbox text-4xl mb-2"></i>
                                            <p class="text-sm font-medium">Tidak ada data pemesanan</p>
                                            <p class="text-xs mt-1">Saat ini belum ada data pemesanan yang tersedia</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Konfirmasi sebelum menghapus
        function confirmDelete(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Hapus',
                html: '<div class="text-left">' +
                      '<p class="mb-4">Apakah Anda yakin ingin menghapus pemesanan ini?</p>' +
                      '<div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4">' +
                      '<div class="flex">' +
                      '<div class="flex-shrink-0">' +
                      '<i class="fas fa-exclamation-triangle text-red-500"></i>' +
                      '</div>' +
                      '<div class="ml-3">' +
                      '<p class="text-sm text-red-700">Tindakan ini tidak dapat dibatalkan dan akan menghapus data secara permanen.</p>' +
                      '</div>' +
                      '</div>' +
                      '</div>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white',
                    cancelButton: 'px-4 py-2 rounded-md bg-gray-500 hover:bg-gray-600 text-white mr-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=delete&id=${id}`;
                }
            });
        }

        // Konfirmasi pemesanan
        function confirmBooking(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Konfirmasi Pemesanan',
                html: '<div class="text-center">' +
                      '<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">' +
                      '<i class="fas fa-check-circle text-green-600 text-3xl"></i>' +
                      '</div>' +
                      '<p class="mb-4">Anda akan mengonfirmasi pemesanan ini?</p>' +
                      '<div class="bg-blue-50 p-3 rounded-lg mb-4">' +
                      '<p class="text-sm text-blue-700">Pastikan semua data sudah benar sebelum mengonfirmasi.</p>' +
                      '</div>' +
                      '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Konfirmasi',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md bg-green-600 hover:bg-green-700 text-white',
                    cancelButton: 'px-4 py-2 rounded-md bg-gray-500 hover:bg-gray-600 text-white mr-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=confirm&id=${id}`;
                }
            });
        }

        // Tolak pemesanan
        function rejectBooking(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Tolak Pemesanan',
                html: '<div class="text-left">' +
                      '<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">' +
                      '<i class="fas fa-exclamation-circle text-yellow-600 text-3xl"></i>' +
                      '</div>' +
                      '<p class="mb-3">Anda akan menolak pemesanan ini?</p>' +
                      '<div class="mb-4">' +
                      '<label for="rejectReason" class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan</label>' +
                      '<textarea id="rejectReason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan alasan penolakan..."></textarea>' +
                      '</div>' +
                      '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tolak',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md bg-yellow-500 hover:bg-yellow-600 text-white',
                    cancelButton: 'px-4 py-2 rounded-md bg-gray-500 hover:bg-gray-600 text-white mr-2'
                },
                buttonsStyling: false,
                preConfirm: () => {
                    const reason = document.getElementById('rejectReason').value;
                    if (!reason.trim()) {
                        Swal.showValidationMessage('Harap masukkan alasan penolakan');
                        return false;
                    }
                    return { reason: reason };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'bookings.php';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = id;
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'reject';
                    
                    const reasonInput = document.createElement('input');
                    reasonInput.type = 'hidden';
                    reasonInput.name = 'catatan_admin';
                    reasonInput.value = result.value.reason;
                    
                    form.appendChild(idInput);
                    form.appendChild(actionInput);
                    form.appendChild(reasonInput);
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Tandai selesai
        function completeBooking(e, id) {
            e.preventDefault();
            Swal.fire({
                title: 'Tandai Selesai',
                html: '<div class="text-center">' +
                      '<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">' +
                      '<i class="fas fa-flag-checkered text-blue-600 text-3xl"></i>' +
                      '</div>' +
                      '<p class="mb-4">Tandai pemesanan ini sebagai selesai?</p>' +
                      '<div class="bg-blue-50 p-3 rounded-lg mb-4">' +
                      '<p class="text-sm text-blue-700">Pastikan perjalanan sudah selesai dan semua pembayaran sudah dilunasi.</p>' +
                      '</div>' +
                      '</div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Tandai Selesai',
                cancelButtonText: 'Batal',
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white',
                    cancelButton: 'px-4 py-2 rounded-md bg-gray-500 hover:bg-gray-600 text-white mr-2'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?action=complete&id=${id}`;
                }
            });
        }
    </script>
</body>
</html>

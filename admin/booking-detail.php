<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'ID pemesanan tidak valid';
    header('Location: bookings.php');
    exit();
}

$id = (int)$_GET['id'];
$page_title = 'Detail Pemesanan';

// Ambil data pemesanan
$query = "
    SELECT 
        b.*, 
        p.nama_paket,
        p.deskripsi as deskripsi_paket,
        p.jenis_layanan,
        a.nama as nama_armada,
        a.jenis as jenis_armada,
        a.kapasitas,
        r.asal, 
        r.tujuan,
        r.jarak,
        r.durasi_jam
    FROM 
        booking b
    JOIN 
        paket p ON b.id_paket = p.id
    LEFT JOIN
        armada a ON b.id_armada = a.id
    LEFT JOIN
        rute r ON p.id_rute = r.id
    WHERE 
        b.id = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
$booking = $stmt->fetch();

if (!$booking) {
    $_SESSION['error'] = 'Data pemesanan tidak ditemukan';
    header('Location: bookings.php');
    exit();
}

// Format data
$status_classes = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'dikonfirmasi' => 'bg-green-100 text-green-800',
    'ditolak' => 'bg-red-100 text-red-800',
    'selesai' => 'bg-blue-100 text-blue-800'
];

$status_text = [
    'menunggu' => 'Menunggu Konfirmasi',
    'dikonfirmasi' => 'Dikonfirmasi',
    'ditolak' => 'Ditolak',
    'selesai' => 'Selesai'
];

$status = $booking['status'];
$status_class = $status_classes[$status] ?? 'bg-gray-100 text-gray-800';
$status_display = $status_text[$status] ?? ucfirst($status);
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
        @page {
            size: A4;
            margin: 1.5cm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                font-size: 12px;
                background: #fff;
                color: #000;
                font-family: Arial, sans-serif;
                line-height: 1.4;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-section {
                border: none !important;
                box-shadow: none !important;
                margin: 0;
                padding: 0;
            }
            .print-container {
                padding: 0 !important;
                margin: 0 !important;
            }
            .bg-blue-50, .bg-gray-50, .bg-white {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            table {
                page-break-inside: auto;
                width: 100%;
                border-collapse: collapse;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
            .print-container {
                width: 100%;
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            .print-section {
                box-shadow: none;
                border: 1px solid #eee;
                margin: 10px 0;
            }
        }
        .sidebar {
            min-height: calc(100vh - 4rem);
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
            <header class="bg-white shadow no-print">
                <div class="flex justify-between items-center px-6 py-4">
                    <h2 class="text-xl font-semibold text-gray-800">Detail Pemesanan</h2>
                    <div class="flex items-center space-x-4">
                        <a href="bookings.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </header>

            <div class="p-4 sm:p-6 print-container">
                <!-- Header Invoice -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 print-section">
                    <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                        <!-- Company Info -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="bg-blue-600 text-white p-3 rounded-lg mr-4">
                                    <i class="fas fa-bus text-2xl"></i>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-800"><?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?></div>
                                    <p class="text-blue-600 font-medium">Travel & Tour Agency</p>
                                </div>
                            </div>
                            <div class="space-y-1 text-sm text-gray-600 border-t border-gray-100 pt-3">
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt mt-1 mr-2 text-blue-500 w-4 flex-shrink-0"></i>
                                    <span>Jl. Contoh No. 123, Kota Anda, Indonesia</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-phone-alt mr-2 text-blue-500 w-4"></i>
                                    <span>+62 123 4567 8901</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-envelope mr-2 text-blue-500 w-4"></i>
                                    <span>info@travelwisata.com</span>
                                </div>
                            </div>
                        </div>

                        <!-- Invoice Info -->
                        <div class="w-full md:w-auto">
                            <div class="bg-gradient-to-r from-blue-50 to-gray-50 p-5 rounded-lg border border-gray-100">
                                <div class="text-center md:text-right">
                                    <h2 class="text-2xl font-bold text-blue-700 mb-1">INVOICE</h2>
                                    <p class="text-sm text-gray-600 font-mono bg-gray-100 px-3 py-1 rounded inline-block">
                                        #<?php echo $booking['kode_booking']; ?>
                                    </p>
                                    
                                    <div class="mt-4 space-y-2">
                                        <div class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium <?php echo $status_class; ?>">
                                            <span class="w-2 h-2 rounded-full mr-2 <?php echo str_replace('text-', 'bg-', explode(' ', $status_class)[0]); ?>"></span>
                                            <?php echo $status_display; ?>
                                        </div>
                                        
                                        <div class="mt-3 space-y-1 text-sm">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Tanggal:</span>
                                                <span class="font-medium text-gray-700"><?php echo date('d M Y', strtotime($booking['created_at'])); ?></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">No. Pesanan:</span>
                                                <span class="font-mono font-medium text-gray-900">#<?php echo $booking['id']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Informasi Pemesan -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print-section lg:col-span-1">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center">
                            <i class="fas fa-user-circle text-blue-500 mr-2"></i>
                            <span>Informasi Pemesan</span>
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <div class="w-24 text-gray-500 flex-shrink-0">Nama</div>
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($booking['nama_pemesan'] ?? '-'); ?></div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-24 text-gray-500 flex-shrink-0">No. HP</div>
                                <div class="text-gray-700"><?php echo !empty($booking['no_wa']) ? htmlspecialchars($booking['no_wa']) : '-'; ?></div>
                            </div>
                            <?php if (!empty($booking['email'])): ?>
                            <div class="flex items-center">
                                <div class="w-24 text-gray-500 flex-shrink-0">Email</div>
                                <div class="text-gray-700"><?php echo htmlspecialchars($booking['email']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($booking['catatan'])): ?>
                            <div class="pt-2 border-t border-gray-100">
                                <div class="text-sm text-gray-500 mb-1">Catatan:</div>
                                <div class="text-gray-700 bg-gray-50 p-3 rounded-lg text-sm">
                                    <?php echo nl2br(htmlspecialchars($booking['catatan'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Rincian Perjalanan -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 print-section lg:col-span-2">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center">
                            <i class="fas fa-route text-blue-500 mr-2"></i>
                            <span>Rincian Perjalanan</span>
                        </h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                                <h4 class="font-semibold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($booking['nama_paket']); ?></h4>
                                <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($booking['deskripsi_paket'] ?? ''); ?></p>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full <?php echo $booking['jenis_layanan'] === 'all_in' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $booking['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                                        <i class="fas fa-map-marked-alt text-blue-500 mr-2"></i>
                                        Rute Perjalanan
                                    </h5>
                                    <div class="flex items-start">
                                        <div class="text-center mr-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mb-1">
                                                <i class="fas fa-map-marker-alt text-xs"></i>
                                            </div>
                                            <div class="w-0.5 h-8 bg-gray-200 mx-auto"></div>
                                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                                <i class="fas fa-flag text-xs"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['asal']); ?></p>
                                            <p class="text-xs text-gray-500 mb-3">Keberangkatan</p>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['tujuan']); ?></p>
                                            <p class="text-xs text-gray-500">Tujuan</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    <h5 class="font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="far fa-calendar-alt text-blue-500 mr-2"></i>
                                        Jadwal
                                    </h5>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <div class="w-6 text-gray-500">
                                                <i class="far fa-calendar"></i>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium"><?php echo date('l, d F Y', strtotime($booking['tanggal_berangkat'])); ?></div>
                                                <?php if (!empty($booking['waktu_jemput'])): ?>
                                                <div class="text-xs text-gray-500">
                                                    <i class="far fa-clock mr-1"></i> <?php echo date('H:i', strtotime($booking['waktu_jemput'])); ?> WIB
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($booking['durasi_jam'])): ?>
                                        <div class="flex items-center">
                                            <div class="w-6 text-gray-500">
                                                <i class="fas fa-hourglass-half"></i>
                                            </div>
                                            <div class="text-sm">
                                                <span class="font-medium">Durasi:</span> 
                                                <span class="text-gray-700"><?php echo $booking['durasi_jam']; ?> Jam</span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($booking['nama_armada'])): ?>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                    <h5 class="font-medium text-gray-700 mb-3 flex items-center">
                                        <i class="fas fa-bus text-blue-500 mr-2"></i>
                                        Kendaraan
                                    </h5>
                                    <div class="flex items-center">
                                        <div class="bg-blue-50 p-2 rounded-lg mr-3">
                                            <i class="fas fa-bus text-blue-500 text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['nama_armada']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($booking['jenis_armada'] ?? 'Kendaraan'); ?></p>
                                            <?php if (!empty($booking['kapasitas'])): ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-users mr-1"></i> Kapasitas: <?php echo $booking['kapasitas']; ?> orang
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rincian Pembayaran -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 print-section">
                    <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4 flex items-center">
                        <i class="fas fa-receipt text-blue-500 mr-2"></i>
                        <span>Rincian Pembayaran</span>
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-normal text-sm">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($booking['nama_paket']); ?></div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo htmlspecialchars($booking['asal'] . ' - ' . $booking['tujuan']); ?>
                                            <span class="mx-1">•</span>
                                            <?php echo $booking['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-50 text-blue-600 font-medium">
                                            <?php echo $booking['jumlah_penumpang'] ?? '1'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                        Rp <?php echo number_format($booking['harga_per_pax'] ?? $booking['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                        Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                                <!-- Total -->
                                <tr class="border-t-2 border-gray-200">
                                    <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-700">
                                        Total Pembayaran
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-lg font-bold text-blue-600">
                                        Rp <?php echo number_format($booking['total_harga'], 0, ',', '.'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($booking['status'] === 'menunggu'): ?>
                    <div class="mt-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-r">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Menunggu Pembayaran</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>Silakan lakukan pembayaran sebelum:</p>
                                    <p class="font-semibold mt-1">
                                        <?php 
                                        $dueDate = new DateTime($booking['created_at']);
                                        $dueDate->add(new DateInterval('P1D')); // Add 1 day
                                        echo $dueDate->format('d F Y H:i') . ' WIB';
                                        ?>
                                    </p>
                    <?php endif; ?>
                </div>

                <!-- Catatan Admin -->
                <?php if ($booking['status'] === 'ditolak' && !empty($booking['catatan_admin'])): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                <span class="font-semibold">Catatan Penolakan:</span><br>
                                <?php echo nl2br(htmlspecialchars($booking['catatan_admin'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tombol Aksi -->
                <div class="flex flex-wrap gap-3 mb-6 no-print">
                    <?php if ($booking['status'] === 'menunggu'): ?>
                        <button type="button" 
                                onclick="confirmBooking(<?php echo $booking['id']; ?>)"
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center">
                            <i class="fas fa-check mr-1"></i> Konfirmasi
                        </button>
                        <button type="button" 
                                onclick="showRejectModal()" 
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 flex items-center">
                            <i class="fas fa-times mr-1"></i> Tolak
                        </button>
                    <?php elseif ($booking['status'] === 'dikonfirmasi'): ?>
                        <button type="button"
                                onclick="completeBooking(<?php echo $booking['id']; ?>)"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                            <i class="fas fa-flag-checkered mr-1"></i> Tandai Selesai
                        </button>
                    <?php endif; ?>
                    
                    <button type="button" 
                            onclick="printInvoice()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 flex items-center">
                        <i class="fas fa-print mr-1"></i> Cetak Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Konfirmasi pemesanan
        function confirmBooking(bookingId) {
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
                    window.location.href = `bookings.php?action=confirm&id=${bookingId}`;
                }
            });
        }

        // Tandai selesai
        function completeBooking(bookingId) {
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
                    window.location.href = `bookings.php?action=complete&id=${bookingId}`;
                }
            });
        }

        // Modal Tolak Pemesanan
        function showRejectModal() {
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
                    idInput.value = '<?php echo $booking['id']; ?>';
                    
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
        // Fungsi untuk mencetak invoice
        function printInvoice() {
            // Clone the print section
            const printContents = document.querySelector('.print-container').cloneNode(true);
            
            // Remove no-print elements
            const noPrintElements = printContents.querySelectorAll('.no-print');
            noPrintElements.forEach(el => el.remove());
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            // Add print styles
            const styles = `
                <style>
                    @page {
                        size: A4;
                        margin: 1.5cm;
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        line-height: 1.4;
                        color: #000;
                        background: #fff;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    .print-section {
                        margin-bottom: 20px;
                        page-break-inside: avoid;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 15px;
                    }
                    th, td {
                        padding: 8px;
                        border: 1px solid #e2e8f0;
                        text-align: left;
                    }
                    th {
                        background-color: #f7fafc;
                        font-weight: 600;
                    }
                    .text-right {
                        text-align: right;
                    }
                    .text-center {
                        text-align: center;
                    }
                    .font-bold {
                        font-weight: 700;
                    }
                    .border-b {
                        border-bottom: 1px solid #e2e8f0;
                    }
                    .p-4 {
                        padding: 1rem;
                    }
                    .mb-4 {
                        margin-bottom: 1rem;
                    }
                    .text-blue-600 {
                        color: #3182ce;
                    }
                    .bg-blue-50 {
                        background-color: #ebf8ff;
                    }
                    .border {
                        border: 1px solid #e2e8f0;
                    }
                    .rounded-lg {
                        border-radius: 0.5rem;
                    }
                    .flex {
                        display: flex;
                    }
                    .items-center {
                        align-items: center;
                    }
                    .justify-between {
                        justify-content: space-between;
                    }
                    .w-full {
                        width: 100%;
                    }
                    .text-sm {
                        font-size: 0.875rem;
                    }
                    .text-lg {
                        font-size: 1.125rem;
                    }
                    .font-semibold {
                        font-weight: 600;
                    }
                    .text-gray-800 {
                        color: #2d3748;
                    }
                    .text-gray-600 {
                        color: #718096;
                    }
                    .bg-white {
                        background-color: #fff;
                    }
                </style>
            `;
            
            // Add content to the print window
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Invoice #${'<?php echo $booking["kode_booking"]; ?>'}</title>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        ${styles}
                    </head>
                    <body>
                        ${printContents.innerHTML}
                        <script>
                            // Auto print and close after printing
                            window.onload = function() {
                                setTimeout(function() {
                                    window.print();
                                    window.onafterprint = function() {
                                        window.close();
                                    };
                                }, 200);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            
            printWindow.document.close();
        }
    </script>
</body>
</html>

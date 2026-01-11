<?php
require_once '../config/config.php';
require_once '../config/database.php';

// Check login
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

// Get booking details
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

// Format dates
$tanggal_berangkat = new DateTime($booking['tanggal_berangkat']);
$booking['tanggal_berangkat_formatted'] = $tanggal_berangkat->format('d F Y');
$booking['tanggal_berangkat_short'] = $tanggal_berangkat->format('d/m/Y');

// Format price
$booking['total_harga_formatted'] = 'Rp ' . number_format($booking['total_harga'] ?? 0, 0, ',', '.');

// Set default values for potentially missing fields
$booking['jumlah_penumpang'] = $booking['jumlah_penumpang'] ?? 1;

// Status badge
$status_badges = [
    'menunggu' => 'bg-yellow-100 text-yellow-800',
    'dikonfirmasi' => 'bg-green-100 text-green-800',
    'ditolak' => 'bg-red-100 text-red-800',
    'selesai' => 'bg-blue-100 text-blue-800'
];
$booking['status_badge'] = $status_badges[$booking['status']] ?? 'bg-gray-100 text-gray-800';

// Start output buffering
ob_start();
?>

<div id="print-section" class="space-y-6">
    <!-- Action Buttons -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Detail Pemesanan</h1>
        <div class="flex space-x-2">
            <a href="bookings.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
            <button onclick="printInvoice()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 no-print">
                <i class="fas fa-print mr-2"></i> Cetak
            </button>
        </div>
    </div>

    <!-- Booking Details Card -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail Pemesanan</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Customer Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pemesan</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Nama</p>
                            <p class="font-medium"><?php echo htmlspecialchars($booking['nama_pemesan']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">No. WhatsApp</p>
                            <p class="font-medium"><?php echo htmlspecialchars($booking['no_wa']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Pemesanan</p>
                            <p class="font-medium"><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $booking['status_badge']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Package Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Paket Wisata</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Nama Paket</p>
                            <p class="font-medium"><?php echo htmlspecialchars($booking['nama_paket']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Jenis Layanan</p>
                            <p class="font-medium"><?php echo htmlspecialchars($booking['jenis_layanan']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Berangkat</p>
                            <p class="font-medium"><?php echo $booking['tanggal_berangkat_formatted']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Armada</p>
                            <p class="font-medium">
                                <?php 
                                if (!empty($booking['nama_armada'])) {
                                    echo htmlspecialchars($booking['nama_armada'] . ' (' . $booking['jenis_armada'] . ')' . ' - ' . $booking['kapasitas'] . ' Kursi');
                                } else {
                                    echo 'Belum dipilih';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pembayaran</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <p class="text-sm text-gray-500">Total Harga</p>
                            <p class="font-medium"><?php echo $booking['total_harga_formatted']; ?></p>
                        </div>
                        <?php if ($booking['status'] === 'ditolak' && !empty($booking['catatan_admin'])): ?>
                            <div class="mt-4 p-3 bg-red-50 rounded-md">
                                <p class="text-sm font-medium text-red-800">Alasan Penolakan:</p>
                                <p class="text-sm text-red-700"><?php echo nl2br(htmlspecialchars($booking['catatan_admin'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #print-section, #print-section * {
            visibility: visible;
        }
        #print-section {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
        }
        .no-print {
            display: none !important;
        }
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        .print-header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a365d;
        }
        .print-header p {
            margin: 5px 0 0;
            color: #4a5568;
        }
        .print-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-size: 12px;
            color: #718096;
        }
        .print-section {
            page-break-inside: avoid;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .bg-white {
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        h2, h3 {
            color: #2d3748;
            margin-top: 0;
        }
        .text-sm {
            font-size: 0.875rem;
        }
        .text-xs {
            font-size: 0.75rem;
        }
        .font-medium {
            font-weight: 500;
        }
        .text-gray-500 {
            color: #6b7280;
        }
        .text-gray-700 {
            color: #374151;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
        .mt-4 {
            margin-top: 1rem;
        }
        .space-y-3 > * + * {
            margin-top: 0.75rem;
        }
    }
</style>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Konfirmasi pemesanan
function confirmBooking(bookingId) {
    Swal.fire({
        title: 'Konfirmasi Pemesanan',
        text: 'Apakah Anda yakin ingin mengonfirmasi pemesanan ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Konfirmasi',
        cancelButtonText: 'Batal',
        reverseButtons: true
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
        text: 'Apakah Anda yakin ingin menandai pemesanan ini sebagai selesai?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tandai Selesai',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `bookings.php?action=complete&id=${bookingId}`;
        }
    });
}

// Tampilkan modal tolak
function showRejectModal() {
    Swal.fire({
        title: 'Tolak Pemesanan',
        html: '<div class="text-left">' +
              '<div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">' +
              '<i class="fas fa-exclamation-triangle text-yellow-600 text-2xl"></i>' +
              '</div>' +
              '<p class="mb-3">Apakah Anda yakin ingin menolak pemesanan ini?</p>' +
              '<div class="mb-4">' +
              '<label for="rejectReason" class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan</label>' +
              '<textarea id="rejectReason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan alasan penolakan..."></textarea>' +
              '</div>' +
              '</div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Tolak',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            confirmButton: 'px-4 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white',
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
            idInput.value = <?php echo $booking['id']; ?>;
            
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

// Cetak invoice
function printInvoice() {
    // Create a print window
    const printWindow = window.open('', '_blank');
    const bookingDate = new Date('<?php echo $booking['created_at']; ?>').toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    // Get the current page content
    const content = `
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invoice Pemesanan #<?php echo $booking['kode_booking']; ?></title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.4;
                color: #2d3748;
                max-width: 800px;
                margin: 0 auto;
                padding: 15px 20px;
                font-size: 13px;
            }
            
            @page {
                margin: 0.5cm;
                size: A4 portrait;
            }
            .print-header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 15px;
                border-bottom: 2px solid #eee;
            }
            .print-header h1 {
                margin: 0 0 10px 0;
                color: #1a365d;
                font-size: 24px;
            }
            .print-header p {
                margin: 5px 0;
                color: #4a5568;
            }
            .print-section {
                margin-bottom: 30px;
                page-break-inside: avoid;
            }
            .print-section h2 {
                color: #2d3748;
                font-size: 15px;
                margin: 10px 0 8px;
                padding-bottom: 4px;
                border-bottom: 1px solid #e2e8f0;
            }
            .grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 12px;
                margin-bottom: 12px;
            }
            .info-card {
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 12px 15px;
                background-color: #fff;
                height: 100%;
                box-sizing: border-box;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            }
            .info-item {
                margin-bottom: 8px;
                padding-bottom: 8px;
                border-bottom: 1px solid #f0f4f8;
            }
            .info-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            .info-label {
                font-size: 13px;
                color: #718096;
                margin-bottom: 5px;
                font-weight: 500;
            }
            .info-value {
                font-weight: 500;
                color: #2d3748;
                font-size: 14px;
            }
            .status-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 600;
                text-transform: capitalize;
            }
            .print-footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 15px;
                border-top: 2px solid #eee;
                font-size: 12px;
                color: #718096;
            }
            .text-right {
                text-align: right;
            }
            .text-center {
                text-align: center;
            }
            .mt-4 {
                margin-top: 1rem;
            }
            .mb-4 {
                margin-bottom: 1rem;
            }
            .font-bold {
                font-weight: 700;
            }
            .text-lg {
                font-size: 1.125rem;
            }
            .text-xl {
                font-size: 1.25rem;
            }
            .text-2xl {
                font-size: 1.5rem;
            }
            .bg-gray-100 {
                background-color: #f7fafc;
            }
            .p-4 {
                padding: 1rem;
            }
            .border-b {
                border-bottom: 1px solid #e2e8f0;
            }
            .w-full {
                width: 100%;
            }
            .border {
                border: 1px solid #e2e8f0;
            }
            .border-t {
                border-top: 1px solid #e2e8f0;
            }
            .py-2 {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }
            .px-4 {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .text-red-600 {
                color: #e53e3e;
            }
        </style>
    </head>
    <body>
        <div class="print-header" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="width: 60%; vertical-align: top;">
                    <div style="display: flex; align-items: center; margin-bottom: 10px;">
                        <img src="${window.location.origin}/assets/images/deas.png" alt="Logo" style="height: 70px; margin-right: 15px;">
                        <div>
                            <h1 style="color: #1a365d; font-size: 22px; font-weight: bold; margin: 0 0 5px 0; letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars(strtoupper($settings['site_name'] ?? 'TRAVEL WISATA')); ?>
                            </h1>
                            <?php if (!empty($settings['address'])): ?>
                            <p style="color: #4a5568; margin: 0 0 5px 0; font-size: 12px;">
                                <?php echo htmlspecialchars($settings['address']); ?>
                            </p>
                            <?php endif; ?>
                            <div style="color: #4a5568; font-size: 12px; line-height: 1.4;">
                                <?php
                                $contact_info = [];
                                if (!empty($settings['contact_phone'])) {
                                    $contact_info[] = 'Telp: ' . htmlspecialchars($settings['contact_phone']);
                                }
                                if (!empty($settings['contact_email'])) {
                                    $contact_info[] = 'Email: ' . htmlspecialchars($settings['contact_email']);
                                }
                                echo implode(' | ', $contact_info);
                                ?>
                            </div>
                        </div>
                    </div>
                    <div style="font-size: 16px; font-weight: 600; color: #2d3748; margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0;">
                        INVOICE PEMESANAN
                    </div>
                </td>
                <td style="text-align: right; vertical-align: top; font-size: 12px;">
                    <div style="margin-bottom: 5px;">
                        <strong>No. Invoice:</strong> <?php echo htmlspecialchars($booking['kode_booking'] ?? 'TRV-' . uniqid()); ?>
                    </div>
                    <div style="margin-bottom: 8px;">
                        <strong>Tanggal:</strong> ${bookingDate}
                    </div>
                    <div>
                        <span class="status-badge" style="
                            background-color: 
                            <?php 
                                switch($booking['status']) {
                                    case 'dikonfirmasi': echo '#c6f6d5; color: #22543d;'; break;
                                    case 'menunggu': echo '#feebc8; color: #744210;'; break;
                                    case 'ditolak': echo '#fed7d7; color: #742a2a;'; break;
                                    case 'selesai': echo '#bee3f8; color: #2a4365;'; break;
                                    default: echo '#e2e8f0; color: #4a5568;';
                                }
                            ?>
                            padding: 3px 10px;
                            border-radius: 12px;
                            font-size: 11px;
                            font-weight: 600;
                            display: inline-block;
                            margin-top: 5px;">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

        <div class="print-section">

            <div class="grid">
                <div class="info-card">
                    <h2>Informasi Pemesan</h2>
                    <div class="info-item">
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['nama_pemesan']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">No. WhatsApp</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['no_wa']); ?></div>
                    </div>
                </div>

                <div class="info-card">
                    <h2>Detail Perjalanan</h2>
                    <div class="info-item">
                        <div class="info-label">Paket Wisata</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['nama_paket']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Jenis Layanan</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['jenis_layanan']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Tanggal Berangkat</div>
                        <div class="info-value"><?php echo $booking['tanggal_berangkat_formatted']; ?></div>
                    </div>
                    <?php if (!empty($booking['asal']) && !empty($booking['tujuan'])): ?>
                    <div class="info-item">
                        <div class="info-label">Rute</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['asal'] . ' - ' . $booking['tujuan']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid">
                <div class="info-card">
                    <h2>Detail Armada</h2>
                    <?php if (!empty($booking['nama_armada'])): ?>
                        <div class="info-item">
                            <div class="info-label">Nama Armada</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['nama_armada']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Jenis Armada</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['jenis_armada']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Kapasitas</div>
                            <div class="info-value"><?php echo $booking['kapasitas']; ?> Kursi</div>
                        </div>
                    <?php else: ?>
                        <p>Belum ada armada yang dipilih</p>
                    <?php endif; ?>
                </div>

                <div class="info-card">
                    <h2 style="margin-bottom: 15px;">Rincian Pembayaran</h2>
                    <div style="background: #f8fafc; border-radius: 8px; padding: 15px; margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #4a5568;">Harga Paket</span>
                            <span style="font-weight: 600;"><?php echo $booking['total_harga_formatted']; ?></span>
                        </div>
                        <div style="margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1em; color: #2d3748;">
                                <span>Total Pembayaran</span>
                                <span><?php echo $booking['total_harga_formatted']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($booking['catatan_admin']) && $booking['status'] === 'ditolak'): ?>
            <div class="info-card" style="background-color: #fff5f5; border-left: 4px solid #fc8181; margin-top: 20px;">
                <h3 style="color: #c53030; margin-top: 0;">Catatan Admin</h3>
                <p style="color: #9b2c2c; margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($booking['catatan_admin'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="print-footer" style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; text-align: center; color: #718096; font-size: 11px; line-height: 1.5;">
            <div style="margin-bottom: 5px;">
                <span>Terima kasih telah memesan di <?php echo htmlspecialchars($settings['site_name'] ?? 'Travel Wisata'); ?></span>
            </div>
            <div style="margin-bottom: 5px; color: #a0aec0; font-size: 10px;">
                <i>Invoice ini sah dan diproses oleh sistem</i>
            </div>
            <div style="font-size: 10px; color: #a0aec0;">
                Dicetak: ${new Date().toLocaleString('id-ID')}
            </div>
        </div>
    </body>
    </html>`;

    // Write the content to the new window
    printWindow.document.open();
    printWindow.document.write(content);
    printWindow.document.close();

    // Wait for the content to load before printing
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.print();
        }, 500);
    };
}
</script>

<?php
// Get settings from database
$settings = [];
$settingsQuery = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $settingsQuery->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get the buffered content and include the layout
$content = ob_get_clean();
include 'includes/layout.php';
?>

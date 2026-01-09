<?php
require_once 'config/config.php';

$page_title = 'Pemesanan Berhasil';

// Ambil kode booking dari URL
$kode_booking = isset($_GET['kode']) ? $_GET['kode'] : '';

// Jika tidak ada kode booking, redirect ke halaman beranda
if (empty($kode_booking)) {
    header('Location: index.php');
    exit();
}

// Ambil detail pemesanan dari database
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.nama_paket, p.jenis_layanan, 
               CONCAT(r.asal, ' - ', r.tujuan) as rute,
               a.nama as nama_armada, a.jenis as jenis_armada
        FROM booking b
        JOIN paket p ON b.id_paket = p.id
        JOIN rute r ON p.id_rute = r.id
        JOIN armada a ON b.id_armada = a.id
        WHERE b.kode_booking = ?
    ");
    $stmt->execute([$kode_booking]);
    $pemesanan = $stmt->fetch();
} catch (PDOException $e) {
    // Jika terjadi error, redirect ke halaman beranda
    header('Location: index.php');
    exit();
}

// Jika pemesanan tidak ditemukan, redirect ke halaman beranda
if (!$pemesanan) {
    header('Location: index.php');
    exit();
}
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- Success Section -->
<div class="bg-gradient-to-b from-blue-50 to-white py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 transform transition-all duration-300 hover:scale-110">
                <svg class="h-16 w-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-extrabold text-gray-900 mt-6">Pemesanan Berhasil!</h1>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                Terima kasih telah memesan di <span class="text-blue-600 font-semibold"><?php echo site_name; ?></span>. Berikut adalah detail pemesanan Anda:
            </p>
            <div class="mt-6">
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    Kode Booking: <?php echo htmlspecialchars($pemesanan['kode_booking']); ?>
                </span>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="mt-10 bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
            <div class="bg-blue-600 px-6 py-4">
                <h2 class="text-lg font-semibold text-white">Detail Pemesanan</h2>
            </div>
            <div class="p-6 md:p-8">
                <div class="md:grid md:grid-cols-2 md:gap-8">
                    <div class="mb-8 md:mb-0 space-y-4">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Kode Booking</span>
                            <span class="font-medium"><?php echo htmlspecialchars($pemesanan['kode_booking']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Nama Pemesan</span>
                            <span class="font-medium"><?php echo htmlspecialchars($pemesanan['nama_pemesan']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Pemesanan</span>
                            <span class="font-medium"><?php echo date('d F Y', strtotime($pemesanan['created_at'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium 
                                <?php 
                                $status_class = [
                                    'menunggu' => 'bg-yellow-100 text-yellow-800',
                                    'dikonfirmasi' => 'bg-blue-100 text-blue-800',
                                    'ditolak' => 'bg-red-100 text-red-800',
                                    'selesai' => 'bg-green-100 text-green-800'
                                ][$pemesanan['status']] ?? 'bg-gray-100 text-gray-800';
                                echo $status_class;
                                ?>">
                                <?php 
                                $status_text = [
                                    'menunggu' => 'Menunggu Konfirmasi',
                                    'dikonfirmasi' => 'Terkonfirmasi',
                                    'ditolak' => 'Ditolak',
                                    'selesai' => 'Selesai'
                                ][$pemesanan['status']] ?? $pemesanan['status'];
                                echo $status_text;
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                    </div>
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Detail Perjalanan</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Rute</span>
                            <span class="font-medium text-right"><?php echo htmlspecialchars($pemesanan['rute']); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tanggal Berangkat</span>
                            <span class="font-medium"><?php echo date('d F Y', strtotime($pemesanan['tanggal_berangkat'])); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Jenis Layanan</span>
                            <span class="font-medium"><?php echo $pemesanan['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Armada</span>
                            <span class="font-medium"><?php echo htmlspecialchars($pemesanan['nama_armada'] . ' (' . $pemesanan['jenis_armada'] . ')'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Price Summary -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Pembayaran</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Harga Paket</span>
                        <span class="font-medium"><?php echo formatRupiah($pemesanan['total_harga']); ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-blue-600"><?php echo formatRupiah($pemesanan['total_harga']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            <div class="mt-8 bg-gradient-to-r from-blue-600 to-blue-700 p-6 rounded-xl text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div class="mb-4 md:mb-0">
                        <h3 class="text-lg font-semibold mb-1">Butuh Bantuan?</h3>
                        <p class="text-blue-100 text-sm">
                            Tim kami siap membantu Anda 24/7 melalui WhatsApp
                        </p>
                    </div>
                    <?php
                    $wa_message = WHATSAPP_MESSAGE . urlencode($pemesanan['kode_booking']);
                    $wa_link = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . $wa_message;
                    ?>
                    <a href="<?php echo $wa_link; ?>" 
                       target="_blank" 
                       class="inline-flex items-center text-green-600 hover:text-green-800 font-medium">
                        <i class="fab fa-whatsapp text-green-500 text-xl mr-2"></i> 
                        <span>Hubungi Kami via WhatsApp</span>
                    </a>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Instruksi Pembayaran</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Tim kami akan menghubungi Anda melalui WhatsApp untuk konfirmasi dan petunjuk pembayaran lebih lanjut. Pastikan nomor WhatsApp yang Anda daftarkan aktif.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="mt-8">
                <h3 class="text-md font-medium text-gray-900 mb-3">Langkah Selanjutnya</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Konfirmasi Pemesanan</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                Tim kami akan mengirimkan konfirmasi melalui WhatsApp dalam waktu 1x24 jam.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Pembayaran</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                Lakukan pembayaran sesuai dengan petunjuk yang diberikan oleh tim kami.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-medium text-gray-900">Hari Perjalanan</h4>
                            <p class="mt-1 text-sm text-gray-600">
                                Pastikan Anda tiba di lokasi penjemputan 30 menit sebelum waktu keberangkatan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CTA Buttons -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl mx-auto">
            <a href="index.php" class="inline-flex justify-center items-center px-6 py-4 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:-translate-y-1">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Kembali ke Beranda
            </a>
            <a href="cek-status.php" class="inline-flex justify-center items-center px-6 py-4 border border-gray-300 shadow-sm text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:-translate-y-1">
                <svg class="-ml-1 mr-2 h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Cek Status Pemesanan
            </a>
        </div>
        
        <!-- Download Receipt -->
        <!-- <div class="mt-8 text-center">
            <a href="generate_pdf.php?kode=<?php echo urlencode($pemesanan['kode_booking']); ?>" 
               target="_blank"
               class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Unduh Bukti Pemesanan (PDF)
            </a>
            <p class="mt-2 text-xs text-gray-500">
                File akan otomatis terunduh dalam format PDF
            </p>
        </div> -->
    </div>
</div>

<?php include 'partials/footer.php'; ?>

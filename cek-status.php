<?php
require_once 'config/config.php';

$page_title = 'Cek Status Pemesanan';
$kode_booking = '';
$pemesanan = null;
$error = '';

// Cek apakah ada kode booking di URL
if (isset($_GET['kode'])) {
    $kode_booking = trim($_GET['kode']);
    
    // Menerima semua format kode booking yang mungkin
    // - TRV-XXXXXX (format lama)
    // - BOOKYYYYMMDDXXXXXX (format baru)
    // - Format lainnya yang mungkin digunakan
    // Tidak perlu validasi format yang ketat, biarkan database yang menentukan apakah kode ada atau tidak
        try {
            // Query untuk mendapatkan detail pemesanan
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
            
            if (!$pemesanan) {
                $error = 'Pesanan dengan kode ' . htmlspecialchars($kode_booking) . ' tidak ditemukan.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan saat memeriksa status pemesanan. Silakan coba lagi nanti.';
        }
    // Selalu lanjutkan pencarian ke database tanpa validasi format
    // karena format kode booking bisa bervariasi
}
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- Status Section -->
<div class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900">Cek Status Pemesanan</h1>
            <p class="mt-2 text-lg text-gray-600">Masukkan kode booking Anda untuk melihat status pemesanan</p>
            <button onclick="toggleRequestForm()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fab fa-whatsapp mr-2"></i> Minta Kode Booking via WhatsApp
            </button>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form action="" method="get" class="max-w-2xl mx-auto">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-grow">
                        <label for="kode" class="sr-only">Kode Booking</label>
                        <input type="text" 
                               name="kode" 
                               id="kode" 
                               value="<?php echo htmlspecialchars($kode_booking); ?>" 
                               placeholder="Contoh: TRV-ABC123" 
                               class="form-input w-full"
                               required>
                    </div>
                    <button type="submit" class="btn-primary whitespace-nowrap">
                        <i class="fas fa-search mr-2"></i> Cari
                    </button>
                </div>
                <?php if ($error): ?>
                    <p class="mt-2 text-sm text-red-600"><?php echo $error; ?></p>
                <?php endif; ?>
            </form>
        </div>

        <!-- Form Minta Kode via WhatsApp -->
        <div id="requestForm" class="hidden bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Minta Kode Booking</h3>
            <form id="waRequestForm" class="space-y-4">
                <div>
                    <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" id="nama" name="nama" required 
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700">Tanggal Keberangkatan <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_berangkat" name="tanggal_berangkat" required 
                           min="<?php echo date('Y-m-d'); ?>"
                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <button type="submit" class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fab fa-whatsapp mr-2"></i> Kirim via WhatsApp
                    </button>
                </div>
            </form>
        </div>

        <?php if ($pemesanan): ?>
            <!-- Booking Status -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <!-- Status Header -->
                <div class="px-6 py-5 border-b border-gray-200 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">
                            Status Pemesanan #<?php echo htmlspecialchars($pemesanan['kode_booking']); ?>
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Pemesanan pada <?php echo date('d F Y H:i', strtotime($pemesanan['created_at'])); ?>
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0">
                        <span class="px-3 py-1 rounded-full text-sm font-medium 
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

                <!-- Booking Details -->
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Pemesanan</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nama Pemesan</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($pemesanan['nama_pemesan']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nomor WhatsApp</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($pemesanan['no_wa']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tanggal Pemesanan</span>
                                    <span class="font-medium"><?php echo date('d F Y H:i', strtotime($pemesanan['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Detail Perjalanan</h3>
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
                </div>

                <!-- Price Summary -->
                <div class="px-6 py-5 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Pembayaran</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Harga Paket</span>
                            <span class="font-medium"><?php echo formatRupiah($pemesanan['total_harga']); ?></span>
                        </div>
                        <div class="pt-2 mt-2 border-t border-gray-200">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total</span>
                                <span class="text-blue-600"><?php echo formatRupiah($pemesanan['total_harga']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Next Steps -->
                <div class="px-6 py-5">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Langkah Selanjutnya</h3>
                    <div class="space-y-4">
                        <?php if ($pemesanan['status'] === 'menunggu'): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                    <i class="fas fa-clock text-yellow-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">Menunggu Konfirmasi</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Pesanan Anda sedang dalam proses verifikasi. Tim kami akan menghubungi Anda segera.
                                    </p>
                                </div>
                            </div>
                        <?php elseif ($pemesanan['status'] === 'dikonfirmasi'): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-blue-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">Pesanan Dikonfirmasi</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Pesanan Anda telah dikonfirmasi. Silakan lakukan pembayaran sesuai dengan petunjuk yang telah dikirim ke WhatsApp Anda.
                                    </p>
                                </div>
                            </div>
                        <?php elseif ($pemesanan['status'] === 'ditolak'): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <i class="fas fa-times-circle text-red-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">Pesanan Ditolak</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Maaf, pesanan Anda tidak dapat diproses. <?php echo !empty($pemesanan['catatan_admin']) ? 'Catatan: ' . htmlspecialchars($pemesanan['catatan_admin']) : ''; ?>
                                    </p>
                                </div>
                            </div>
                        <?php elseif ($pemesanan['status'] === 'selesai'): ?>
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-600"></i>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-gray-900">Perjalanan Selesai</h4>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Terima kasih telah menggunakan layanan kami. Semoga perjalanan Anda menyenangkan!
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($pemesanan['status'] === 'dikonfirmasi'): ?>
                            <div class="mt-6 bg-blue-50 p-4 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-800 mb-2">Petunjuk Pembayaran</h4>
                                <p class="text-sm text-blue-700 mb-3">
                                    Silakan lakukan pembayaran ke rekening berikut:
                                </p>
                                <div class="bg-white p-3 rounded-md border border-blue-200">
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm text-gray-600">Bank</span>
                                        <span class="text-sm font-medium">BCA (Bank Central Asia)</span>
                                    </div>
                                    <div class="flex justify-between mb-2">
                                        <span class="text-sm text-gray-600">Nomor Rekening</span>
                                        <span class="text-sm font-mono font-medium">123 456 7890</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-600">Atas Nama</span>
                                        <span class="text-sm font-medium">PT Travel Wisata Indonesia</span>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-blue-600">
                                    Setelah melakukan pembayaran, harap konfirmasi dengan mengirimkan bukti transfer ke WhatsApp kami.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="mt-8 flex flex-col sm:flex-row justify-center gap-4">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-home mr-2"></i> Kembali ke Beranda
                </a>
                <?php
                $wa_message = urlencode('Halo, saya ingin menanyakan status pemesanan dengan kode: ' . $pemesanan['kode_booking']);
                $wa_link = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . $wa_message;
                ?>
                <a href="<?php echo $wa_link; ?>" 
                   target="_blank" 
                   class="btn-primary">
                    <i class="fab fa-whatsapp mr-2"></i> Hubungi Kami
                </a>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($kode_booking) && empty($error)): ?>
            <!-- Empty state when no booking found but search was performed -->
            <div class="text-center py-12 bg-white rounded-lg shadow-sm">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
                    <i class="fas fa-search text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Pesanan Tidak Ditemukan</h3>
                <p class="text-gray-500 mb-6">
                    Kami tidak dapat menemukan pesanan dengan kode <span class="font-medium"><?php echo htmlspecialchars($kode_booking); ?></span>.
                </p>
                <a href="pesan.php" class="btn-primary inline-flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i> Buat Pesanan Baru
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleRequestForm() {
    const form = document.getElementById('requestForm');
    form.classList.toggle('hidden');
    
    // Scroll ke form jika ditampilkan
    if (!form.classList.contains('hidden')) {
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

document.getElementById('waRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const nama = document.getElementById('nama').value;
    const tanggal = document.getElementById('tanggal_berangkat').value;
    
    // Format pesan
    const message = `Halo, saya ${nama} ingin menanyakan kode booking untuk perjalanan tanggal ${tanggal}. Mohon bantuannya.`;
    
    // Encode pesan untuk URL
    const encodedMessage = encodeURIComponent(message);
    
    // Buka WhatsApp Web/App dengan pesan
    window.open(`https://wa.me/<?php echo WHATSAPP_NUMBER; ?>?text=${encodedMessage}`, '_blank');
});
</script>

<?php include 'partials/footer.php'; ?>

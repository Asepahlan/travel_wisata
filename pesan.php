<?php
require_once 'config/config.php';

$page_title = 'Pesan Sekarang';
$errors = [];
$success = false;

// Get package details if package ID is provided
$selected_package = null;
if (isset($_GET['paket']) && is_numeric($_GET['paket'])) {
    $stmt = $pdo->prepare("SELECT p.*, CONCAT(r.asal, ' - ', r.tujuan) as rute, r.durasi_jam 
                          FROM paket p 
                          JOIN rute r ON p.id_rute = r.id 
                          WHERE p.id = ?");
    $stmt->execute([$_GET['paket']]);
    $selected_package = $stmt->fetch();
}

// Get available vehicles
$vehicles = $pdo->query("SELECT * FROM armada WHERE status = 'tersedia' ORDER BY nama")->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $nama = htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8');
    $no_wa = htmlspecialchars($_POST['no_wa'] ?? '', ENT_QUOTES, 'UTF-8');
    $tanggal_berangkat = htmlspecialchars($_POST['tanggal_berangkat'] ?? '', ENT_QUOTES, 'UTF-8');
    $id_paket = filter_input(INPUT_POST, 'id_paket', FILTER_VALIDATE_INT);
    $id_armada = filter_input(INPUT_POST, 'id_armada', FILTER_VALIDATE_INT);
    $jumlah_kendaraan = 1; // Always use 1 vehicle
    $catatan = htmlspecialchars($_POST['catatan'] ?? '', ENT_QUOTES, 'UTF-8');

    // Validation
    if (empty($nama)) {
        $errors['nama'] = 'Nama pemesan harus diisi';
    }
    
    if (empty($no_wa) || !preg_match('/^[0-9+\-\s]{10,15}$/', $no_wa)) {
        $errors['no_wa'] = 'Nomor WhatsApp tidak valid';
    }
    
    if (empty($tanggal_berangkat)) {
        $errors['tanggal_berangkat'] = 'Tanggal berangkat harus diisi';
    } else {
        $selected_date = new DateTime($tanggal_berangkat);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        if ($selected_date < $today) {
            $errors['tanggal_berangkat'] = 'Tanggal tidak boleh kurang dari hari ini';
        }
    }
    
    if (empty($id_paket)) {
        $errors['id_paket'] = 'Pilih paket perjalanan';
    }
    
    if (empty($id_armada)) {
        $errors['id_armada'] = 'Pilih jenis armada';
    }
    
    if (empty($jumlah_kendaraan) || $jumlah_kendaraan < 1) {
        $errors['jumlah_kendaraan'] = 'Jumlah kendaraan minimal 1';
    }
    
    // If no errors, process the booking
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get package price
            $stmt = $pdo->prepare("SELECT harga FROM paket WHERE id = ?");
            $stmt->execute([$id_paket]);
            $package = $stmt->fetch();
            
            if (!$package) {
                throw new Exception('Paket tidak ditemukan');
            }
            
            // Calculate total price
            $harga_per_kendaraan = $package['harga'];
            $total_harga = $harga_per_kendaraan * $jumlah_kendaraan;
            
            // Generate booking code
            $kode_booking = 'TRV-' . strtoupper(uniqid());
            
            // Insert booking
            $stmt = $pdo->prepare("
                INSERT INTO booking (
                    kode_booking, nama_pemesan, no_wa, id_paket, id_armada, 
                    tanggal_berangkat, total_harga, catatan, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')
            ") or die(print_r($pdo->errorInfo(), true));
            
            $stmt->execute([
                $kode_booking,
                $nama,
                $no_wa,
                $id_paket,
                $id_armada,
                $tanggal_berangkat,
                $total_harga,
                $catatan
            ]);
            
            // Update vehicle status
            $stmt = $pdo->prepare("UPDATE armada SET status = 'dipesan' WHERE id = ?");
            $stmt->execute([$id_armada]);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to success page
            header("Location: sukses.php?kode=" . urlencode($kode_booking));
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errors['general'] = 'Terjadi kesalahan saat memproses pemesanan. Silakan coba lagi.';
            error_log('Booking error: ' . $e->getMessage());
        }
    }
}

// Get all packages for the dropdown
$packages = $pdo->query("SELECT p.*, CONCAT(r.asal, ' - ', r.tujuan) as rute FROM paket p JOIN rute r ON p.id_rute = r.id")->fetchAll();
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- Page Header -->
<div class="bg-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Pesan Sekarang</h1>
        <p class="text-xl">Isi formulir di bawah ini untuk memesan armada travel wisata</p>
    </div>
</div>

<!-- Booking Form -->
<div class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-6">
                <?php if (!empty($errors['general'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?php echo $errors['general']; ?></p>
                    </div>
                <?php endif; ?>
                
                <form id="bookingForm" method="POST" class="space-y-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Data Pemesan</h3>
                        
                        <div>
                            <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="nama" name="nama" value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                   class="mt-1 form-input <?php echo isset($errors['nama']) ? 'border-red-500' : ''; ?>" 
                                   required>
                            <?php if (isset($errors['nama'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['nama']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="no_wa" class="block text-sm font-medium text-gray-700">Nomor WhatsApp <span class="text-red-500">*</span></label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">+62</span>
                                </div>
                                <input type="tel" id="no_wa" name="no_wa" 
                                       value="<?php echo isset($_POST['no_wa']) ? htmlspecialchars($_POST['no_wa']) : ''; ?>" 
                                       class="pl-12 form-input <?php echo isset($errors['no_wa']) ? 'border-red-500' : ''; ?>" 
                                       placeholder="81234567890" required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Contoh: 81234567890</p>
                            <?php if (isset($errors['no_wa'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['no_wa']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Travel Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Detail Perjalanan</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="tanggal_berangkat" class="block text-sm font-medium text-gray-700">Tanggal Berangkat <span class="text-red-500">*</span></label>
                                <input type="date" id="tanggal_berangkat" name="tanggal_berangkat" 
                                       value="<?php echo isset($_POST['tanggal_berangkat']) ? htmlspecialchars($_POST['tanggal_berangkat']) : ''; ?>" 
                                       min="<?php echo date('Y-m-d'); ?>" 
                                       class="mt-1 form-input <?php echo isset($errors['tanggal_berangkat']) ? 'border-red-500' : ''; ?>" 
                                       required>
                                <?php if (isset($errors['tanggal_berangkat'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['tanggal_berangkat']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label for="id_paket" class="block text-sm font-medium text-gray-700">Paket Perjalanan <span class="text-red-500">*</span></label>
                                <select id="id_paket" name="id_paket" class="mt-1 form-input <?php echo isset($errors['id_paket']) ? 'border-red-500' : ''; ?>" required>
                                    <option value="">Pilih Paket</option>
                                    <?php foreach ($packages as $package): ?>
                                        <option value="<?php echo $package['id']; ?>" 
                                            <?php echo (isset($_POST['id_paket']) && $_POST['id_paket'] == $package['id']) || (isset($selected_package) && $selected_package['id'] == $package['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($package['rute'] . ' - ' . ($package['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['id_paket'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['id_paket']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="id_armada" class="block text-sm font-medium text-gray-700">Jenis Armada <span class="text-red-500">*</span></label>
                                <select id="id_armada" name="id_armada" class="mt-1 form-input <?php echo isset($errors['id_armada']) ? 'border-red-500' : ''; ?>" required>
                                    <option value="">Pilih Armada</option>
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <option value="<?php echo $vehicle['id']; ?>" 
                                            data-kapasitas="<?php echo $vehicle['kapasitas']; ?>"
                                            <?php echo (isset($_POST['id_armada']) && $_POST['id_armada'] == $vehicle['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($vehicle['nama'] . ' (' . $vehicle['jenis'] . ' - Kapasitas: ' . $vehicle['kapasitas'] . ' orang)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['id_armada'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['id_armada']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div>
                            <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan Tambahan</label>
                            <textarea id="catatan" name="catatan" rows="3" class="mt-1 form-input"><?php echo isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : ''; ?></textarea>
                            <p class="mt-1 text-xs text-gray-500">Contoh: Titik penjemputan khusus, permintaan khusus, dll.</p>
                        </div>
                    </div>
                    
                    <!-- Price Summary -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Ringkasan Harga</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Harga per kendaraan</span>
                                <span id="hargaPerKendaraan" class="font-medium">-</span>
                            </div>
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Harga</span>
                                <span id="totalHarga" class="text-blue-600">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input id="terms" name="terms" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" required>
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="font-medium text-gray-700">Saya menyetujui syarat dan ketentuan yang berlaku</label>
                            <p class="text-gray-500">Dengan mencentang kotak ini, Anda menyetujui kebijakan privasi dan syarat & ketentuan yang berlaku.</p>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-paper-plane mr-2"></i> Kirim Pesanan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const hargaPerKendaraan = document.getElementById('hargaPerKendaraan');
    const totalHarga = document.getElementById('totalHarga');
    const idPaket = document.getElementById('id_paket');
    const idArmada = document.getElementById('id_armada');
    const jumlahKendaraan = document.getElementById('jumlah_kendaraan');
    
    // Initialize form with default values if package is pre-selected
    if (idPaket && idPaket.value) {
        updateHarga();
    }
    
    // Update price when package or vehicle changes
    if (idPaket) idPaket.addEventListener('change', updateHarga);
    if (idArmada) idArmada.addEventListener('change', updateHarga);
    if (jumlahKendaraan) jumlahKendaraan.addEventListener('input', updateHarga);
    
    // Function to update price
    function updateHarga() {
        const packageId = idPaket ? idPaket.value : null;
        const vehicleId = idArmada ? idArmada.value : null;
        const jumlah = jumlahKendaraan ? (parseInt(jumlahKendaraan.value) || 0) : 0;
        
        if (packageId && vehicleId) {
            // In a real app, you would fetch the price from the server
            // This is just a fallback with fixed prices
            const harga = 1000000; // Default price
            const total = harga * jumlah;
            
            if (hargaPerKendaraan) hargaPerKendaraan.textContent = formatRupiah(harga);
            if (totalHarga) totalHarga.textContent = formatRupiah(total);
        } else {
            if (hargaPerKendaraan) hargaPerKendaraan.textContent = 'Rp 0';
            if (totalHarga) totalHarga.textContent = 'Rp 0';
        }
    }
    
    // Format number to Rupiah
    function formatRupiah(angka) {
        return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
});
</script>

<?php include 'partials/footer.php'; ?>

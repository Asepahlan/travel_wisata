<?php
require_once 'config/config.php';

$page_title = 'Beranda';

// Get featured packages
$stmt = $pdo->query("SELECT p.*, CONCAT(r.asal, ' - ', r.tujuan) as rute 
                     FROM paket p 
                     JOIN rute r ON p.id_rute = r.id 
                     ORDER BY p.created_at DESC LIMIT 3");
$featured_packages = $stmt->fetchAll();
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- Hero Section -->
<div class="bg-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">Sewa Armada Travel Wisata Terpercaya</h1>
        <p class="text-xl mb-8 max-w-3xl mx-auto">Nikmati perjalanan nyaman dan aman bersama armada terbaik kami. Layanan profesional dengan harga terjangkau.</p>
        <div class="space-x-4">
            <a href="pesan.php" class="btn-primary inline-block">
                Pesan Sekarang
            </a>
            <a href="#layanan" class="bg-white text-blue-700 hover:bg-gray-100 font-medium py-2 px-6 rounded-lg transition duration-300 inline-block">
                Lihat Layanan
            </a>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-white" id="layanan">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Mengapa Memilih Kami?</h2>
            <p class="mt-4 text-xl text-gray-600">Kami memberikan pelayanan terbaik untuk kenyamanan perjalanan Anda</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Armada Terawat</h3>
                <p class="text-gray-600">Armada kami selalu dalam kondisi prima dan terawat dengan baik untuk kenyamanan perjalanan Anda.</p>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Supir Profesional</h3>
                <p class="text-gray-600">Dilayani oleh supir yang berpengalaman dan menguasai rute perjalanan dengan baik.</p>
            </div>
            
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-headset text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold mb-2">Layanan 24/7</h3>
                <p class="text-gray-600">Kami siap melayani Anda kapan saja dengan customer service yang ramah dan responsif.</p>
            </div>
        </div>
    </div>
</div>

<!-- Featured Packages -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Paket Perjalanan Populer</h2>
            <p class="mt-4 text-xl text-gray-600">Temukan paket perjalanan terbaik untuk liburan Anda</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($featured_packages as $package): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-route text-4xl text-gray-400"></i>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($package['rute']); ?></h3>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                <?php echo $package['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?>
                            </span>
                        </div>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($package['deskripsi']); ?></p>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900"><?php echo formatRupiah($package['harga']); ?></span>
                            <a href="pesan.php?paket=<?php echo $package['id']; ?>" class="btn-primary">
                                Pesan Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-12 text-center">
            <a href="paket.php" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Lihat Semua Paket
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</div>

<!-- How It Works -->
<div class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Cara Memesan</h2>
            <p class="mt-4 text-xl text-gray-600">Hanya 3 langkah mudah untuk memesan armada kami</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">1</div>
                <h3 class="text-lg font-semibold mb-2">Pilih Paket</h3>
                <p class="text-gray-600">Pilih paket perjalanan dan armada yang sesuai dengan kebutuhan Anda.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">2</div>
                <h3 class="text-lg font-semibold mb-2">Isi Form Pemesanan</h3>
                <p class="text-gray-600">Lengkapi formulir pemesanan dengan data yang valid.</p>
            </div>
            
            <div class="text-center">
                <div class="bg-blue-600 text-white w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mx-auto mb-4">3</div>
                <h3 class="text-lg font-semibold mb-2">Konfirmasi Admin</h3>
                <p class="text-gray-600">Tim kami akan menghubungi Anda untuk konfirmasi pemesanan.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-6">Siap Memulai Perjalanan Anda?</h2>
        <p class="text-xl mb-8 max-w-3xl mx-auto">Pesan sekarang dan dapatkan pengalaman perjalanan yang tak terlupakan bersama kami.</p>
        <a href="pesan.php" class="bg-white text-blue-700 hover:bg-gray-100 font-medium py-3 px-8 rounded-lg transition duration-300 inline-block">
            Pesan Sekarang
        </a>
    </div>
</div>

<?php include 'partials/footer.php'; ?>

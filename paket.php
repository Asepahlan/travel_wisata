<?php
require_once 'config/config.php';

$page_title = 'Paket Wisata';

// Get all packages with route information
$sql = "SELECT p.*, CONCAT(r.asal, ' - ', r.tujuan) as rute, r.durasi_jam 
        FROM paket p 
        JOIN rute r ON p.id_rute = r.id 
        ORDER BY p.harga ASC";
$stmt = $pdo->query($sql);
$packages = $stmt->fetchAll();

// Get unique routes for filter
$routes = $pdo->query("SELECT DISTINCT CONCAT(asal, ' - ', tujuan) as rute FROM rute ORDER BY rute")->fetchAll();
?>

<?php include 'partials/header.php'; ?>
<?php include 'partials/navbar.php'; ?>

<!-- Page Header -->
<div class="bg-blue-700 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Paket Wisata</h1>
        <p class="text-xl">Temukan paket perjalanan terbaik untuk liburan Anda</p>
    </div>
</div>

<!-- Filter Section -->
<div class="bg-white py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
            <form id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="rute" class="block text-sm font-medium text-gray-700 mb-1">Rute</label>
                    <select id="rute" name="rute" class="form-input">
                        <option value="">Semua Rute</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo htmlspecialchars($route['rute']); ?>">
                                <?php echo htmlspecialchars($route['rute']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="layanan" class="block text-sm font-medium text-gray-700 mb-1">Jenis Layanan</label>
                    <select id="layanan" name="layanan" class="form-input">
                        <option value="">Semua Layanan</option>
                        <option value="all_in">All In</option>
                        <option value="non_all_in">Non All In</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga</label>
                    <div class="flex space-x-2">
                        <input type="number" id="harga_min" name="harga_min" placeholder="Min" class="form-input w-1/2">
                        <input type="number" id="harga_max" name="harga_max" placeholder="Max" class="form-input w-1/2">
                    </div>
                </div>
                <div class="flex items-end">
                    <button type="button" id="resetFilter" class="btn-secondary w-full">
                        <i class="fas fa-sync-alt mr-2"></i> Reset Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Packages Grid -->
<div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div id="packageList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (count($packages) > 0): ?>
                <?php foreach ($packages as $package): ?>
                    <div class="package-item bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 hover:shadow-lg hover:-translate-y-1 flex flex-col h-full"
                         data-rute="<?php echo htmlspecialchars($package['rute']); ?>"
                         data-layanan="<?php echo $package['jenis_layanan']; ?>"
                         data-harga="<?php echo $package['harga']; ?>">
                        <div class="h-48 bg-gray-200 relative overflow-hidden">
                            <img src="assets/images/deas.png" alt="Paket Wisata" class="w-full h-full object-cover">
                            <div class="absolute top-3 right-3">
                                <span class="bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                                    <?php echo $package['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($package['rute']); ?></h3>
                            <div class="flex items-center text-sm text-gray-600 mb-4">
                                <i class="fas fa-clock mr-1"></i>
                                <span><?php echo $package['durasi_jam']; ?> Jam Perjalanan</span>
                            </div>
                            <div class="mb-4">
                                <p class="text-gray-600 text-sm">
                                    <i class="fas fa-info-circle text-blue-500 mr-1"></i> 
                                    <?php echo htmlspecialchars($package['deskripsi']); ?>
                                </p>
                                <div class="flex items-center text-sm text-gray-500 mt-2">
                                    <i class="fas fa-users mr-2"></i>
                                    <span>Kapasitas: <?php echo $package['kapasitas'] ?? 'Tersedia'; ?> orang</span>
                                </div>
                            </div>
                            <div class="mt-auto pt-4 border-t border-gray-100">
                                <div class="flex justify-between items-center mb-4">
                                    <div>
                                        <span class="text-sm text-gray-500">Mulai dari</span>
                                        <p class="text-2xl font-bold text-blue-600"><?php echo formatRupiah($package['harga']); ?></p>
                                        <span class="text-xs text-gray-400">per kendaraan</span>
                                    </div>
                                </div>
                                <a href="pesan.php?paket=<?php echo $package['id']; ?>" class="w-full btn-primary text-center block">
                                    Pesan Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-3 text-center py-12">
                    <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Tidak ada paket tersedia</h3>
                    <p class="mt-1 text-gray-500">Silakan coba dengan filter yang berbeda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="bg-blue-700 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Tidak menemukan paket yang sesuai?</h2>
        <p class="text-xl mb-8 max-w-3xl mx-auto">Hubungi kami untuk informasi lebih lanjut tentang paket perjalanan khusus sesuai kebutuhan Anda.</p>
        <?php
        $wa_message = urlencode('Halo, saya ingin bertanya tentang paket perjalanan');
        $wa_link = 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . $wa_message;
        ?>
        <a href="<?php echo $wa_link; ?>" target="_blank" class="bg-white text-blue-700 hover:bg-gray-100 font-medium py-3 px-8 rounded-lg transition duration-300 inline-flex items-center">
            <i class="fab fa-whatsapp text-xl mr-2"></i> Hubungi via WhatsApp
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const packageItems = document.querySelectorAll('.package-item');
    
    // Filter function
    function filterPackages() {
        const rute = document.getElementById('rute').value.toLowerCase();
        const layanan = document.getElementById('layanan').value;
        const hargaMin = parseFloat(document.getElementById('harga_min').value) || 0;
        const hargaMax = parseFloat(document.getElementById('harga_max').value) || Infinity;
        
        packageItems.forEach(item => {
            const itemRute = item.getAttribute('data-rute').toLowerCase();
            const itemLayanan = item.getAttribute('data-layanan');
            const itemHarga = parseFloat(item.getAttribute('data-harga'));
            
            const ruteMatch = rute === '' || itemRute.includes(rute);
            const layananMatch = layanan === '' || itemLayanan === layanan;
            const hargaMatch = itemHarga >= hargaMin && itemHarga <= hargaMax;
            
            if (ruteMatch && layananMatch && hargaMatch) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show no results message if no items match
        const visibleItems = document.querySelectorAll('.package-item[style="display: block;"]');
        const noResults = document.querySelector('.no-results');
        
        if (visibleItems.length === 0) {
            if (!noResults) {
                const packageList = document.getElementById('packageList');
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'col-span-3 text-center py-12 no-results';
                noResultsDiv.innerHTML = `
                    <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">Tidak ada paket yang sesuai</h3>
                    <p class="mt-1 text-gray-500">Silakan coba dengan filter yang berbeda.</p>
                `;
                packageList.appendChild(noResultsDiv);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }
    
    // Event listeners for filter changes
    filterForm.addEventListener('change', filterPackages);
    filterForm.addEventListener('keyup', function(e) {
        if (e.target.matches('input[type="number"]')) {
            filterPackages();
        }
    });
    
    // Reset filter
    document.getElementById('resetFilter').addEventListener('click', function() {
        filterForm.reset();
        filterPackages();
    });
    
    // Check for URL parameters to pre-fill filters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('rute')) {
        document.getElementById('rute').value = urlParams.get('rute');
        filterPackages();
    }
});
</script>

<?php include 'partials/footer.php'; ?>

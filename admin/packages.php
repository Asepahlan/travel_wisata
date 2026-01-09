<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Paket Wisata';

// Tangani aksi tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'tambah' || $_POST['action'] === 'edit') {
                $data = [
                    'nama_paket' => trim($_POST['nama_paket']),
                    'id_rute' => (int)$_POST['id_rute'],
                    'jenis_layanan' => $_POST['jenis_layanan'],
                    'harga' => (int)str_replace('.', '', $_POST['harga']),
                    'deskripsi' => trim($_POST['deskripsi']),
                    'status' => isset($_POST['status']) ? 'aktif' : 'nonaktif'
                ];

                // Validasi input
                if (empty($data['nama_paket']) || empty($data['id_rute']) || empty($data['harga'])) {
                    throw new Exception('Semua field bertanda bintang (*) harus diisi');
                }

                if ($_POST['action'] === 'tambah') {
                    // Tambah paket baru
                    $query = "INSERT INTO paket (nama_paket, id_rute, jenis_layanan, harga, deskripsi, status, created_at) 
                              VALUES (:nama_paket, :id_rute, :jenis_layanan, :harga, :deskripsi, :status, NOW())";
                    $message = 'Paket berhasil ditambahkan';
                } else {
                    // Update paket yang ada
                    $id = (int)$_POST['id'];
                    $query = "UPDATE paket SET 
                                nama_paket = :nama_paket, 
                                id_rute = :id_rute, 
                                jenis_layanan = :jenis_layanan, 
                                harga = :harga, 
                                deskripsi = :deskripsi, 
                                status = :status,
                                updated_at = NOW()
                              WHERE id = $id";
                    $message = 'Paket berhasil diperbarui';
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                $_SESSION['success'] = $message;
                header('Location: packages.php');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

// Tangani aksi hapus
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("DELETE FROM paket WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Paket berhasil dihapus';
        header('Location: packages.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Gagal menghapus paket: ' . $e->getMessage();
    }
}

// Ambil daftar rute untuk dropdown
$rutes = $pdo->query("SELECT * FROM rute ORDER BY asal, tujuan")->fetchAll();

// Ambil daftar paket
$query = "
    SELECT p.*, CONCAT(r.asal, ' - ', r.tujuan) as rute 
    FROM paket p 
    LEFT JOIN rute r ON p.id_rute = r.id 
    ORDER BY p.nama_paket
";
$packages = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #3b82f6;
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
                    <h2 class="text-xl font-semibold text-gray-800">Manajemen Paket Wisata</h2>
                    <div class="flex items-center">
                        <span class="text-gray-600 mr-4"><?php echo htmlspecialchars($_SESSION['admin_nama']); ?></span>
                        <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                            <?php echo strtoupper(substr($_SESSION['admin_nama'], 0, 1)); ?>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-6">
                <!-- Flash Message -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="py-1"><i class="fas fa-check-circle mr-2"></i></div>
                            <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <div class="flex">
                            <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tombol Tambah Paket -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Paket Wisata</h3>
                    <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Paket
                    </button>
                </div>

                <!-- Tabel Paket -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Paket</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rute</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Layanan</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($packages) > 0): ?>
                                    <?php foreach ($packages as $package): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($package['nama_paket']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo substr(strip_tags($package['deskripsi']), 0, 50) . '...'; ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($package['rute']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $package['jenis_layanan'] === 'all_in' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                                    <?php echo $package['jenis_layanan'] === 'all_in' ? 'All In' : 'Non All In'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500">
                                                Rp <?php echo number_format($package['harga'], 0, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $status = $package['status'] ?? 'nonaktif'; // Default value jika status tidak ada
                                                $statusClass = $status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                                $statusText = $status === 'aktif' ? 'Aktif' : 'Nonaktif';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="#" onclick="deletePackage(<?php echo $package['id']; ?>, '<?php echo addslashes(htmlspecialchars($package['nama_paket'])); ?>')" class="text-red-600 hover:text-red-900" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Belum ada data paket wisata
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit Paket -->
    <div id="packageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-5 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
            <div class="mt-2">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Tambah Paket Wisata</h3>
                    <button type="button" onclick="hideModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="packageForm" action="" method="post" class="mt-6 space-y-6">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="id" id="packageId">
                    
                    <div class="space-y-6">
                        <!-- Baris 1: Nama Paket -->
                        <div>
                            <label for="nama_paket" class="block text-sm font-medium text-gray-700 mb-1">Nama Paket <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input type="text" name="nama_paket" id="nama_paket" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Baris 2: Rute -->
                            <div>
                                <label for="id_rute" class="block text-sm font-medium text-gray-700 mb-1">Rute <span class="text-red-500">*</span></label>
                                <select name="id_rute" id="id_rute" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                                    <option value="">-- Pilih Rute --</option>
                                    <?php foreach ($rutes as $rute): ?>
                                        <option value="<?php echo $rute['id']; ?>">
                                            <?php echo htmlspecialchars($rute['asal'] . ' - ' . $rute['tujuan']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Baris 2: Jenis Layanan -->
                            <div>
                                <label for="jenis_layanan" class="block text-sm font-medium text-gray-700 mb-1">Jenis Layanan <span class="text-red-500">*</span></label>
                                <select name="jenis_layanan" id="jenis_layanan" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                                    <option value="all_in">All In</option>
                                    <option value="non_all_in">Non All In</option>
                                </select>
                            </div>
                            
                            <!-- Baris 3: Harga -->
                            <div>
                                <label for="harga" class="block text-sm font-medium text-gray-700 mb-1">Harga <span class="text-red-500">*</span></label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500">Rp</span>
                                    </div>
                                    <input type="text" name="harga" id="harga" required
                                           class="pl-12 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           oninput="formatCurrency(this)"
                                           placeholder="0">
                                </div>
                            </div>
                            
                            <!-- Baris 3: Status -->
                            <div class="flex items-end">
                                <div class="flex items-center h-10">
                                    <input type="checkbox" name="status" id="status" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="status" class="ml-2 block text-sm text-gray-700">Aktif</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Baris 4: Deskripsi -->
                        <div>
                            <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <div class="mt-1">
                                <textarea name="deskripsi" id="deskripsi" rows="3"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"></textarea>
                            </div>
                        </div>
                        
                        
                    </div>
                    
                    <!-- Tombol Aksi -->
                    <div class="pt-5 border-t border-gray-200">
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideModal()" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                                Batal
                            </button>
                            <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Inisialisasi Select2
        $(document).ready(function() {
            $('#id_rute').select2({
                placeholder: 'Pilih Rute',
                allowClear: true,
                dropdownParent: $('#packageModal')
            });
        });

        // Format input harga menjadi currency
        function formatCurrency(input) {
            // Hapus karakter selain angka
            let value = input.value.replace(/\D/g, '');
            
            // Format angka dengan pemisah ribuan
            value = new Intl.NumberFormat('id-ID').format(value);
            
            // Set nilai input
            input.value = value;
        }

        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Paket Wisata';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('packageForm').reset();
            document.getElementById('status').checked = true;
            document.getElementById('packageModal').classList.remove('hidden');
        }

        // Tampilkan modal edit
        function editPackage(packageData) {
            document.getElementById('modalTitle').textContent = 'Edit Paket Wisata';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('packageId').value = packageData.id;
            document.getElementById('nama_paket').value = packageData.nama_paket;
            document.getElementById('id_rute').value = packageData.id_rute;
            $('#id_rute').trigger('change'); // Update Select2
            document.getElementById('jenis_layanan').value = packageData.jenis_layanan;
            document.getElementById('harga').value = new Intl.NumberFormat('id-ID').format(packageData.harga);
            document.getElementById('deskripsi').value = packageData.deskripsi || '';
            document.getElementById('status').checked = packageData.status === 'aktif';
            document.getElementById('packageModal').classList.remove('hidden');
        }

        // Sembunyikan modal
        function hideModal() {
            document.getElementById('packageModal').classList.add('hidden');
        }

        // Fungsi untuk menghapus paket dengan konfirmasi SweetAlert2
        function deletePackage(id, packageName) {
            Swal.fire({
                title: 'Hapus Paket',
                html: `Apakah Anda yakin ingin menghapus paket <strong>${packageName}</strong>?<br><span class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500',
                    cancelButton: 'px-4 py-2 mr-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `packages.php?action=hapus&id=${id}`;
                }
            });
        }

        // Tutup modal saat mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('packageModal');
            if (event.target === modal) {
                hideModal();
            }
        }
    </script>
</body>
</html>

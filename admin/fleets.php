<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Armada';

// Tangani aksi tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'tambah' || $_POST['action'] === 'edit') {
                $data = [
                    'nama' => trim($_POST['nama_armada']),
                    'nomor_polisi' => trim($_POST['nomor_polisi']),
                    'jenis' => trim($_POST['jenis_kendaraan']),
                    'kapasitas' => (int)$_POST['kapasitas'],
                    'deskripsi' => trim($_POST['keterangan']),
                    'status' => isset($_POST['status']) ? 'tersedia' : 'tidak_tersedia'
                ];

                // Validasi input
                if (empty($data['nama']) || empty($data['jenis'])) {
                    throw new Exception('Nama armada dan jenis kendaraan harus diisi');
                }
                
                // Hanya validasi nomor polisi untuk data baru
                if ($_POST['action'] === 'tambah' && empty($data['nomor_polisi'])) {
                    throw new Exception('Nomor polisi harus diisi');
                }

                if ($_POST['action'] === 'tambah') {
                    // Tambah armada baru
                    $query = "INSERT INTO armada (nama, nomor_polisi, jenis, kapasitas, deskripsi, status) 
                              VALUES (:nama, :nomor_polisi, :jenis, :kapasitas, :deskripsi, :status)";
                    $message = 'Armada berhasil ditambahkan';
                } else {
                    // Update armada yang ada
                    $id = (int)$_POST['id'];
                    $query = "UPDATE armada SET 
                                nama = :nama, 
                                nomor_polisi = :nomor_polisi,
                                jenis = :jenis, 
                                kapasitas = :kapasitas, 
                                deskripsi = :deskripsi, 
                                status = :status
                              WHERE id = $id";
                    $message = 'Armada berhasil diperbarui';
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                $_SESSION['success'] = $message;
                header('Location: fleets.php');
                exit();
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

// Tangani aksi hapus
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = (int)$_GET['id'];
        $response = ['success' => false, 'message' => ''];
        
        // Cek apakah armada digunakan di pemesanan
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM booking WHERE id_armada = ? AND status IN ('menunggu', 'dikonfirmasi')");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Tidak dapat menghapus armada karena sedang digunakan dalam pemesanan aktif');
        }
        
        // Hapus data armada
        $stmt = $pdo->prepare("DELETE FROM armada WHERE id = ?");
        $deleted = $stmt->execute([$id]);
        
        if ($deleted) {
            $response['success'] = true;
            $response['message'] = 'Armada berhasil dihapus';
        } else {
            throw new Exception('Gagal menghapus data armada');
        }
        
        echo json_encode($response);
        exit();
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Ambil daftar armada
$query = "SELECT * FROM armada ORDER BY status, nama";
$fleets = $pdo->query($query)->fetchAll();

// Daftar jenis kendaraan yang tersedia
$jenis_kendaraan_list = [
    'Avanza', 'Innova', 'Hiace', 'Elf', 'Hino', 'Bus Medium', 'Bus Besar', 'Lainnya'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .status-available { background-color: #DCFCE7; color: #166534; }
        .status-not-available { background-color: #FEE2E2; color: #991B1B; }
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
                    <h2 class="text-xl font-semibold text-gray-800">Manajemen Armada</h2>
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

                <!-- Tombol Tambah Armada -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Armada</h3>
                    <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Armada
                    </button>
                </div>

                <!-- Tabel Armada -->
                <div class="bg-white shadow rounded-lg overflow-hidden border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nama Armada</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Jenis</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Kapasitas</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($fleets) > 0): ?>
                                    <?php foreach ($fleets as $fleet): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($fleet['nama']); ?>
                                                    <?php if (!empty($fleet['nomor_polisi'])): ?>
                                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($fleet['nomor_polisi']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($fleet['jenis']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                                <?php echo $fleet['kapasitas']; ?> orang
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $statusClass = $fleet['status'] === 'tersedia' ? 'status-available' : 'status-not-available';
                                                $statusText = $fleet['status'] === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia';
                                                ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php 
                                                    $fleetData = [
                                                        'id' => $fleet['id'],
                                                        'nama' => $fleet['nama'],
                                                        'nomor_polisi' => $fleet['nomor_polisi'] ?? '',
                                                        'jenis' => $fleet['jenis'],
                                                        'kapasitas' => $fleet['kapasitas'],
                                                        'deskripsi' => $fleet['deskripsi'] ?? '',
                                                        'status' => $fleet['status']
                                                    ];
                                                ?>
                                                <button onclick="editFleet(<?php echo htmlspecialchars(json_encode($fleetData), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button 
                                                   onclick="deleteFleet(<?php echo $fleet['id']; ?>, '<?php echo addslashes($fleet['nama']); ?>')" 
                                                   class="text-red-600 hover:text-red-900 focus:outline-none"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Belum ada data armada
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

    <!-- Modal Tambah/Edit Armada -->
    <div id="fleetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-5 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-2">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Tambah Armada</h3>
                    <button type="button" onclick="hideModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="fleetForm" action="" method="post" class="mt-6 space-y-6">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="id" id="fleetId">
                    
                    <div class="space-y-6">
                        <!-- Nama Armada -->
                        <div>
                            <label for="nama_armada" class="block text-sm font-medium text-gray-700 mb-1">Nama Armada <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input type="text" name="nama_armada" id="nama_armada" required
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                       placeholder="Contoh: Avanza Hitam B 1234 CD">
                                <p class="mt-1 text-xs text-gray-500">Masukkan nama lengkap armada</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Jenis Kendaraan -->
                            <div>
                                <label for="jenis_kendaraan" class="block text-sm font-medium text-gray-700 mb-1">Jenis Kendaraan <span class="text-red-500">*</span></label>
                                <select name="jenis_kendaraan" id="jenis_kendaraan" required
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                                    <option value="">-- Pilih Jenis Kendaraan --</option>
                                    <?php foreach ($jenis_kendaraan_list as $jenis): ?>
                                        <option value="<?php echo $jenis; ?>"><?php echo $jenis; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            

                            <!-- Kapasitas -->
                            <div>
                                <label for="kapasitas" class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang) <span class="text-red-500">*</span></label>
                                <input type="number" name="kapasitas" id="kapasitas" required min="1"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                            </div>
                            

                            <!-- Nomor Polisi -->
                            <div>
                                <label for="nomor_polisi" class="block text-sm font-medium text-gray-700 mb-1">Nomor Polisi <span class="text-red-500">*</span></label>
                                <input type="text" name="nomor_polisi" id="nomor_polisi" required
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                       placeholder="Contoh: B 1234 CD"
                                       style="text-transform: uppercase">
                            </div>
                        </div>
                        
                        <!-- Keterangan -->
                        <div>
                            <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                            <div class="mt-1">
                                <textarea name="keterangan" id="keterangan" rows="3"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                          placeholder="Informasi tambahan tentang armada"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Tambahkan catatan atau informasi penting lainnya</p>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="pt-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="status" id="status" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="status" class="ml-2 block text-sm text-gray-700">Tersedia</label>
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
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Armada';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('fleetForm').reset();
            document.getElementById('kapasitas').value = '4'; // Set default value
            document.getElementById('status').checked = true;
            // Set required attribute for nomor_polisi in add mode
            document.getElementById('nomor_polisi').required = true;
            document.getElementById('fleetModal').classList.remove('hidden');
        }

        // Tampilkan modal edit
        function editFleet(fleet) {
            document.getElementById('modalTitle').textContent = 'Edit Armada';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('fleetId').value = fleet.id;
            document.getElementById('nama_armada').value = fleet.nama;
            document.getElementById('nomor_polisi').value = fleet.nomor_polisi || '';
            document.getElementById('jenis_kendaraan').value = fleet.jenis;
            document.getElementById('kapasitas').value = fleet.kapasitas;
            document.getElementById('keterangan').value = fleet.deskripsi || '';
            document.getElementById('status').checked = fleet.status === 'tersedia';
            // Remove required attribute in edit mode
            document.getElementById('nomor_polisi').required = false;
            document.getElementById('fleetModal').classList.remove('hidden');
        }

        // Sembunyikan modal
        function hideModal() {
            document.getElementById('fleetModal').classList.add('hidden');
        }

        // Format nomor polisi menjadi huruf besar
        document.getElementById('nomor_polisi')?.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        // Validasi form sebelum submit
        document.getElementById('fleetForm')?.addEventListener('submit', function(e) {
            const nomorPolisi = document.getElementById('nomor_polisi').value.trim();
            if (nomorPolisi && !/^[A-Za-z]\s?\d{1,4}\s?[A-Za-z]{1,3}$/.test(nomorPolisi)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Format Nomor Polisi Tidak Valid',
                    text: 'Format yang benar contoh: B 1234 CD atau B1234CD',
                    icon: 'error',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });

        // Fungsi untuk mengubah status armada
        function updateFleetStatus(id, status) {
            fetch('fleets.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: `action=update_status&id=${id}&status=${status}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Status armada berhasil diperbarui',
                        icon: 'success',
                        confirmButtonText: 'Tutup'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status armada');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Terjadi kesalahan saat memperbarui status armada',
                    icon: 'error',
                    confirmButtonText: 'Tutup'
                });
            });
        }

        // Tambahkan SweetAlert untuk konfirmasi hapus
        function deleteFleet(id, nama) {
            Swal.fire({
                title: 'Kelola Armada',
                html: `
                    <div class="text-left">
                        <p class="mb-4">Apa yang ingin Anda lakukan dengan armada <strong>${nama}</strong>?</p>
                        <div class="space-y-3">
                            <button id="btn-delete" class="w-full text-left p-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-md border border-red-200 flex items-center">
                                <i class="fas fa-trash mr-2"></i>
                                <div>
                                    <div class="font-medium">Hapus Armada</div>
                                    <div class="text-xs text-red-500">Hanya jika tidak ada pemesanan aktif</div>
                                </div>
                            </button>
                            <button id="btn-mark-unavailable" class="w-full text-left p-3 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-md border border-yellow-200 flex items-center">
                                <i class="fas fa-times-circle mr-2"></i>
                                <div>
                                    <div class="font-medium">Tandai Tidak Tersedia</div>
                                    <div class="text-xs text-yellow-600">Menonaktifkan armada sementara</div>
                                </div>
                            </button>
                        </div>
                    </div>
                `,
                showConfirmButton: false,
                showCloseButton: true,
                customClass: {
                    closeButton: 'text-gray-400 hover:text-gray-500 focus:outline-none',
                    container: 'text-left'
                },
                didOpen: () => {
                    document.getElementById('btn-delete').addEventListener('click', () => {
                        Swal.close();
                        confirmDelete(id, nama);
                    });
                    
                    document.getElementById('btn-mark-unavailable').addEventListener('click', () => {
                        Swal.close();
                        updateFleetStatus(id, 'tidak_tersedia');
                    });
                }
            });
        }

        // Fungsi konfirmasi hapus
        function confirmDelete(id, nama) {
            Swal.fire({
                title: 'Hapus Armada',
                html: `Apakah Anda yakin ingin menghapus armada <strong>${nama}</strong>?<br><span class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</span>`,
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
                    // Show loading state
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Sedang menghapus data armada',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the delete request
                    fetch(`fleets.php?action=hapus&id=${id}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: data.message || 'Armada berhasil dihapus',
                                icon: 'success',
                                confirmButtonText: 'Tutup'
                            }).then(() => {
                                // Reload the page to see the changes
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Gagal menghapus armada');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Tidak Dapat Dihapus',
                            html: `
                                <div class="text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Dapat Menghapus Armada</h3>
                                    <p class="text-sm text-gray-500 mb-4">${error.message || 'Armada tidak dapat dihapus karena sedang digunakan dalam pemesanan aktif.'}</p>
                                    <div class="mt-4">
                                        <button id="mark-unavailable" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:ml-3 sm:w-auto sm:text-sm">
                                            Tandai Tidak Tersedia
                                        </button>
                                        <button id="cancel-delete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Tutup
                                        </button>
                                    </div>
                                </div>
                            `,
                            showConfirmButton: false,
                            showCloseButton: false,
                            didOpen: () => {
                                document.getElementById('mark-unavailable').addEventListener('click', () => {
                                    Swal.close();
                                    updateFleetStatus(id, 'tidak_tersedia');
                                });
                                
                                document.getElementById('cancel-delete').addEventListener('click', () => {
                                    Swal.close();
                                });
                            }
                        });
                    });
                }
            });
        }

        // Tutup modal saat mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('fleetModal');
            if (event.target === modal) {
                hideModal();
            }
        }

    </script>
</body>
</html>

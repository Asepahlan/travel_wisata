<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Supir';

// Tangani aksi tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'tambah' || $_POST['action'] === 'edit') {
                $data = [
                    'nama_supir' => trim($_POST['nama_supir']),
                    'no_telepon' => trim($_POST['no_telepon']),
                    'alamat' => trim($_POST['alamat']),
                    'no_sim' => trim($_POST['no_sim']),
                    'id_armada' => !empty($_POST['id_armada']) ? (int)$_POST['id_armada'] : null,
                    'status' => isset($_POST['status']) ? 'tersedia' : 'tidak_tersedia'
                ];

                // Validasi input
                if (empty($data['nama_supir']) || empty($data['no_telepon']) || empty($data['no_sim'])) {
                    throw new Exception('Nama, No. Telepon, dan No. SIM harus diisi');
                }

                if ($_POST['action'] === 'tambah') {
                    // Tambah supir baru
                    $query = "INSERT INTO supir (nama_supir, no_telepon, alamat, no_sim, id_armada, status, created_at) 
                              VALUES (:nama_supir, :no_telepon, :alamat, :no_sim, :id_armada, :status, NOW())";
                    $message = 'Data supir berhasil ditambahkan';
                } else {
                    // Update supir yang ada
                    $id = (int)$_POST['id'];
                    $query = "UPDATE supir SET 
                                nama_supir = :nama_supir, 
                                no_telepon = :no_telepon, 
                                alamat = :alamat, 
                                no_sim = :no_sim, 
                                id_armada = :id_armada,
                                status = :status,
                                updated_at = NOW()
                              WHERE id = $id";
                    $message = 'Data supir berhasil diperbarui';
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                $_SESSION['success'] = $message;
                header('Location: drivers.php');
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
        
        // Cek apakah supir sedang bertugas
        $stmt = $pdo->prepare("SELECT * FROM booking WHERE id_supir = ? AND status IN ('dikonfirmasi', 'dalam_perjalanan')");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Tidak dapat menghapus supir yang sedang bertugas');
        }
        
        // Mulai transaksi
        $pdo->beginTransaction();
        
        try {
            // Hapus relasi armada terlebih dahulu
            $stmt = $pdo->prepare("UPDATE supir SET id_armada = NULL WHERE id = ?");
            $stmt->execute([$id]);
            
            // Kemudian hapus supir
            $stmt = $pdo->prepare("DELETE FROM supir WHERE id = ?");
            $stmt->execute([$id]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Data supir berhasil dihapus'
            ]);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus data supir: ' . $e->getMessage()
        ]);
        exit();
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Ambil daftar supir
$query = "SELECT s.*, a.nama as nama_armada, a.kapasitas, a.jenis as jenis_armada 
          FROM supir s 
          LEFT JOIN armada a ON s.id_armada = a.id 
          ORDER BY s.status, s.nama_supir";
$drivers = $pdo->query($query)->fetchAll();

// Ambil daftar armada yang tersedia
$armadas = $pdo->query("SELECT * FROM armada WHERE status = 'tersedia' OR id IN (SELECT id_armada FROM supir)")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('site_name') ? constant('site_name') : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .status-available { 
            background-color: #DCFCE7; 
            color: #166534; 
        }
        .status-not-available { 
            background-color: #FEE2E2; 
            color: #991B1B; 
        }
    </style>
</head>
<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Supir</h1>
        <!-- <button onclick="showAddModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i> Tambah Supir
        </button> -->
    </div>

    <!-- Flash Message -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Drivers Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <!-- <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Supir</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. SIM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Armada</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead> -->

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

                <!-- Tombol Tambah Supir -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Supir</h3>
                    <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Supir
                    </button>
                </div>

                <!-- Tabel Supir -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Supir</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. SIM</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Armada</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($drivers) > 0): ?>
                                    <?php foreach ($drivers as $driver): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($driver['nama_supir']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo $driver['alamat'] ? htmlspecialchars($driver['alamat']) : '-' ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($driver['no_telepon']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($driver['no_sim']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php 
                                                if ($driver['id_armada']) {
                                                    echo htmlspecialchars($driver['nama_armada'] . ' (' . $driver['jenis_armada'] . ' - ' . $driver['kapasitas'] . ' orang)');
                                                } else {
                                                    echo '<span class="text-gray-400">Belum ada armada</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $statusClass = $driver['status'] === 'tersedia' ? 'status-available' : 'status-not-available';
                                                $statusText = $driver['status'] === 'tersedia' ? 'Tersedia' : 'Tidak Tersedia';
                                                ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="editDriver(<?php echo htmlspecialchars(json_encode($driver)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button 
                                                   onclick="deleteDriver(<?php echo $driver['id']; ?>, '<?php echo addslashes($driver['nama_supir']); ?>')" 
                                                   class="text-red-600 hover:text-red-900 focus:outline-none"
                                                   title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Belum ada data supir
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

    <!-- Modal Tambah/Edit Supir -->
    <div id="driverModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-5 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-2">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Tambah Supir</h3>
                    <button type="button" onclick="hideModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="driverForm" action="" method="post" class="mt-6 space-y-6">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="id" id="driverId">
                    
                    <div class="space-y-6">
                        <!-- Nama Supir -->
                        <div>
                            <label for="nama_supir" class="block text-sm font-medium text-gray-700 mb-1">Nama Supir <span class="text-red-500">*</span></label>
                            <div class="mt-1">
                                <input type="text" name="nama_supir" id="nama_supir" required
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                       placeholder="Nama lengkap">
                                <p class="mt-1 text-xs text-gray-500">Masukkan nama lengkap supir</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- No. Telepon -->
                            <div>
                                <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="tel" name="no_telepon" id="no_telepon" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Contoh: 081234567890"
                                           pattern="[0-9]{10,13}"
                                           title="Masukkan nomor telepon yang valid (10-13 digit)">
                                    <p class="mt-1 text-xs text-gray-500">Contoh: 081234567890</p>
                                </div>
                            </div>
                            

                            <!-- No. SIM -->
                            <div>
                                <label for="no_sim" class="block text-sm font-medium text-gray-700 mb-1">No. SIM <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="no_sim" id="no_sim" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Nomor SIM">
                                    <p class="mt-1 text-xs text-gray-500">Masukkan nomor SIM yang valid</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alamat -->
                        <div>
                            <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                            <div class="mt-1">
                                <textarea name="alamat" id="alamat" rows="3"
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                          placeholder="Alamat lengkap"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Masukkan alamat lengkap supir</p>
                            </div>
                        </div>
                        
                        <!-- Armada -->
                        <div>
                            <label for="id_armada" class="block text-sm font-medium text-gray-700 mb-1">Armada</label>
                            <div class="mt-1">
                                <select name="id_armada" id="id_armada"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border">
                                    <option value="">-- Pilih Armada (Opsional) --</option>
                                    <?php foreach ($armadas as $armada): ?>
                                        <option value="<?php echo $armada['id']; ?>">
                                            <?php echo htmlspecialchars($armada['nama'] . ' (' . $armada['jenis'] . ' - ' . $armada['kapasitas'] . ' kursi)'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Pilih armada yang akan dikendarai (jika ada)</p>
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="pt-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="status" id="status" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="status" class="ml-2 block text-sm text-gray-700">Tersedia</label>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Centang jika supir tersedia untuk bertugas</p>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Supir';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('driverForm').reset();
            document.getElementById('status').checked = true;
            document.getElementById('driverModal').classList.remove('hidden');
        }

        // Tampilkan modal edit
        function editDriver(driver) {
            document.getElementById('modalTitle').textContent = 'Edit Supir';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('driverId').value = driver.id;
            document.getElementById('nama_supir').value = driver.nama_supir;
            document.getElementById('no_telepon').value = driver.no_telepon;
            document.getElementById('no_sim').value = driver.no_sim;
            document.getElementById('alamat').value = driver.alamat || '';
            document.getElementById('id_armada').value = driver.id_armada || '';
            document.getElementById('status').checked = driver.status === 'tersedia';
            document.getElementById('driverModal').classList.remove('hidden');
        }

        // Sembunyikan modal
        function hideModal() {
            document.getElementById('driverModal').classList.add('hidden');
        }

        // Format nomor telepon
        document.getElementById('no_telepon')?.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Format nomor SIM
        document.getElementById('no_sim')?.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        // Validasi form sebelum submit
        document.getElementById('driverForm')?.addEventListener('submit', function(e) {
            const noTelepon = document.getElementById('no_telepon').value.trim();
            if (noTelepon && !/^[0-9]{10,13}$/.test(noTelepon)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Format Nomor Telepon Tidak Valid',
                    text: 'Nomor telepon harus terdiri dari 10-13 digit angka',
                    icon: 'error',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });

        // Fungsi hapus dengan konfirmasi
        function deleteDriver(id, nama) {
            Swal.fire({
                title: 'Hapus Supir',
                html: `Apakah Anda yakin ingin menghapus supir <strong>${nama}</strong>?<br><span class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</span>`,
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
                        text: 'Sedang menghapus data supir',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the delete request
                    fetch(`drivers.php?action=hapus&id=${id}`, {
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
                                text: data.message || 'Data supir berhasil dihapus',
                                icon: 'success',
                                confirmButtonText: 'Tutup'
                            }).then(() => {
                                // Reload the page to see the changes
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Gagal menghapus data supir');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Terjadi kesalahan saat menghapus data supir',
                            icon: 'error',
                            confirmButtonText: 'Tutup'
                        });
                    });
                }
            });
        }

        // Tutup modal saat mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('driverModal');
            if (event.target === modal) {
                hideModal();
            }
        }

        // Format nomor telepon
        document.getElementById('no_telepon')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 12) value = value.substring(0, 12);
            e.target.value = value;
        });
    </script>
    </div>
</div>

<!-- Modal Tambah/Edit Supir -->
<div id="driverModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-5 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-2">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Tambah Supir</h3>
                <button type="button" onclick="hideModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="driverForm" action="" method="post" class="mt-6 space-y-6">
                <input type="hidden" name="action" id="formAction" value="tambah">
                <input type="hidden" name="id" id="driverId">
                
                <div class="space-y-6">
                    <!-- Nama Supir -->
                    <div>
                        <label for="nama_supir" class="block text-sm font-medium text-gray-700 mb-1">Nama Supir <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_supir" id="nama_supir" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    
                    <!-- No. Telepon -->
                    <div>
                        <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon <span class="text-red-500">*</span></label>
                        <input type="text" name="no_telepon" id="no_telepon" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    
                    <!-- Alamat -->
                    <div>
                        <label for="alamat" class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea name="alamat" id="alamat" rows="2"
                                 class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                    </div>
                    
                    <!-- No. SIM -->
                    <div>
                        <label for="no_sim" class="block text-sm font-medium text-gray-700 mb-1">No. SIM <span class="text-red-500">*</span></label>
                        <input type="text" name="no_sim" id="no_sim" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    
                    <!-- Armada -->
                    <div>
                        <label for="id_armada" class="block text-sm font-medium text-gray-700 mb-1">Armada</label>
                        <select name="id_armada" id="id_armada"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            <option value="">Pilih Armada (Opsional)</option>
                            <?php foreach ($fleets as $fleet): ?>
                                <option value="<?php echo $fleet['id']; ?>">
                                    <?php echo htmlspecialchars($fleet['nama'] . ' (' . $fleet['nomor_polisi'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Status -->
                    <div class="flex items-center">
                        <input type="checkbox" name="status" id="status" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="status" class="ml-2 block text-sm text-gray-700">Tersedia</label>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="pt-5 border-t border-gray-200">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideModal()" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            Simpan
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
    document.getElementById('modalTitle').textContent = 'Tambah Supir';
    document.getElementById('formAction').value = 'tambah';
    document.getElementById('driverForm').reset();
    document.getElementById('status').checked = true;
    document.getElementById('driverModal').classList.remove('hidden');
}

// Tampilkan modal edit
function editDriver(id, nama, telepon, alamat, noSim, armadaId, status) {
    document.getElementById('modalTitle').textContent = 'Edit Supir';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('driverId').value = id;
    document.getElementById('nama_supir').value = nama;
    document.getElementById('no_telepon').value = telepon;
    document.getElementById('alamat').value = alamat || '';
    document.getElementById('no_sim').value = noSim;
    document.getElementById('id_armada').value = armadaId || '';
    document.getElementById('status').checked = status === 'tersedia';
    document.getElementById('driverModal').classList.remove('hidden');
}

// Sembunyikan modal
function hideModal() {
    document.getElementById('driverModal').classList.add('hidden');
}

// Konfirmasi hapus
function confirmDelete(id, nama) {
    if (confirm(`Apakah Anda yakin ingin menghapus supir "${nama}"?`)) {
        // Kirim permintaan hapus
        fetch(`drivers.php?action=hapus&id=${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert(data.message || 'Gagal menghapus supir');
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat menghapus supir');
        });
    }
}

// Tutup modal saat mengklik di luar modal
window.onclick = function(event) {
    const modal = document.getElementById('driverModal');
    if (event.target === modal) {
        hideModal();
    }
}
</script>

<?php
// Get the buffered content and include the layout
$content = ob_get_clean();
include 'includes/layout.php';
?>

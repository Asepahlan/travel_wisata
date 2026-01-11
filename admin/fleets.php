<?php
require_once '../config/config.php';

// Cek koneksi database
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('Koneksi database gagal. Silakan periksa konfigurasi database.');
}

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
                    'kapasitas' => (int)$_POST['kapasitas'],
                    'deskripsi' => trim($_POST['keterangan']),
                    'status' => isset($_POST['status']) ? 'tersedia' : 'tidak_tersedia'
                ];

                // Validasi input
                if (empty($data['nama'])) {
                    throw new Exception('Nama armada harus diisi');
                }
                
                // Hanya validasi nomor polisi untuk data baru
                if ($_POST['action'] === 'tambah' && empty($data['nomor_polisi'])) {
                    throw new Exception('Nomor polisi harus diisi');
                }

                if ($_POST['action'] === 'tambah') {
                    // Tambah armada baru
                    $query = "INSERT INTO armada (nama, nomor_polisi, kapasitas, deskripsi, status) 
                              VALUES (:nama, :nomor_polisi, :kapasitas, :deskripsi, :status)";
                    $message = 'Armada berhasil ditambahkan';
                    
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':nama' => $data['nama'],
                        ':nomor_polisi' => $data['nomor_polisi'],
                        ':kapasitas' => $data['kapasitas'],
                        ':deskripsi' => $data['deskripsi'],
                        ':status' => $data['status']
                    ]);
                } else {
                    // Update armada yang ada
                    $query = "UPDATE armada SET 
                                nama = :nama, 
                                nomor_polisi = :nomor_polisi,
                                kapasitas = :kapasitas, 
                                deskripsi = :deskripsi, 
                                status = :status
                              WHERE id = :id";
                    $message = 'Armada berhasil diperbarui';
                    
                    $data['id'] = (int)$_POST['id'];
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([
                        ':nama' => $data['nama'],
                        ':nomor_polisi' => $data['nomor_polisi'],
                        ':kapasitas' => $data['kapasitas'],
                        ':deskripsi' => $data['deskripsi'],
                        ':status' => $data['status'],
                        ':id' => $data['id']
                    ]);
                }
                
                $_SESSION['success'] = $message;
                header('Location: fleets.php');
                exit();
            } elseif ($_POST['action'] === 'update_status' && isset($_POST['id'], $_POST['status'])) {
                // Validasi status yang diizinkan
                $allowed_statuses = ['tersedia', 'tidak_tersedia', 'dalam_perbaikan'];
                $status = $_POST['status'];
                
                if (!in_array($status, $allowed_statuses)) {
                    throw new Exception('Status tidak valid');
                }
                
                // Update status armada
                $stmt = $pdo->prepare("UPDATE armada SET status = :status WHERE id = :id");
                $stmt->execute([
                    ':status' => $status,
                    ':id' => (int)$_POST['id']
                ]);
                
                // Return JSON response
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
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
<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Manajemen Armada</h1>
        <!-- <button onclick="showAddModal()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-200 active:bg-blue-800 transition">
            <i class="fas fa-plus mr-2"></i> Tambah Armada
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

    <!-- Fleet Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="p-6">
            <!-- Tombol Tambah Armada -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Daftar Armada</h3>
                <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Tambah Armada
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nama Armada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Nomor Polisi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Kapasitas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
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
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo !empty($fleet['nomor_polisi']) ? htmlspecialchars($fleet['nomor_polisi']) : '-'; ?>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button onclick="showDetailModal(<?php echo htmlspecialchars(json_encode($fleet), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="text-blue-600 hover:text-blue-900" 
                                                title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editFleet(<?php echo htmlspecialchars(json_encode($fleet), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="text-blue-600 hover:text-blue-900"
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
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Belum ada data armada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        // Tampilkan modal detail armada
        function showDetailModal(fleet) {
            const statusText = {
                'tersedia': 'Tersedia',
                'tidak_tersedia': 'Tidak Tersedia',
                'dalam_perbaikan': 'Dalam Perbaikan'
            };
            
            Swal.fire({
                title: 'Detail Armada',
                html: `
                    <div class="text-left space-y-3">
                        <div class="border-b pb-2">
                            <h3 class="text-lg font-semibold text-gray-900">${fleet.nama}</h3>
                            <p class="text-sm text-gray-500">${fleet.nomor_polisi || 'Tidak ada nomor polisi'}</p>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="space-y-1">
                                <p class="font-medium text-gray-700">Kapasitas:</p>
                                <p>${fleet.kapasitas} orang</p>
                            </div>
                            <div class="space-y-1">
                                <p class="font-medium text-gray-700">Status:</p>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    ${fleet.status === 'tersedia' ? 'bg-green-100 text-green-800' : 
                                      fleet.status === 'dalam_perbaikan' ? 'bg-yellow-100 text-yellow-800' : 
                                      'bg-red-100 text-red-800'}">
                                    ${statusText[fleet.status] || fleet.status}
                                </span>
                            </div>
                        </div>
                        
                        ${fleet.deskripsi ? `
                        <div class="mt-2">
                            <p class="font-medium text-gray-700">Deskripsi:</p>
                            <p class="mt-1 text-gray-600 whitespace-pre-line">${fleet.deskripsi}</p>
                        </div>` : ''}
                    </div>
                `,
                showCloseButton: true,
                showConfirmButton: false,
                width: '500px',
                padding: '1.5rem',
                customClass: {
                    closeButton: 'text-gray-400 hover:text-gray-500 focus:outline-none',
                    container: 'text-left'
                }
            });
        }

        // Global state
        const fleetState = {
            currentFleet: null
        };

        // Initialize event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Format nomor polisi menjadi huruf besar
            const nomorPolisiInput = document.getElementById('nomor_polisi');
            if (nomorPolisiInput) {
                nomorPolisiInput.addEventListener('input', function(e) {
                    this.value = this.value.toUpperCase();
                });
            }
            
            // Set default capacity
            document.getElementById('kapasitas').value = '4';

            // Client-side form validation
            document.getElementById('fleetForm')?.addEventListener('submit', function(e) {
                const nomorPolisi = document.getElementById('nomor_polisi')?.value.trim() || '';
                const isEditMode = document.getElementById('formAction').value === 'edit';
                
                // Only validate nomor_polisi in add mode or if it has a value in edit mode
                if ((!isEditMode || nomorPolisi) && !/^[A-Za-z]\s?\d{1,4}\s?[A-Za-z]{1,3}$/.test(nomorPolisi)) {
                    e.preventDefault();
                    showError('Format Nomor Polisi Tidak Valid', 'Format yang benar contoh: B 1234 CD atau B1234CD');
                    return false;
                }
                
                // Show loading state
                Swal.fire({
                    title: 'Menyimpan...',
                    text: 'Sedang menyimpan data armada',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
                
                return true;
            });
        });

        // Show add fleet modal
        function showAddModal() {
            const modal = document.getElementById('fleetModal');
            document.getElementById('modalTitle').textContent = 'Tambah Armada';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('fleetForm').reset();
            document.getElementById('kapasitas').value = '4'; // Set default value
            document.getElementById('status').checked = true;
            document.getElementById('nomor_polisi').required = true;
            modal.classList.remove('hidden');
        }

        // Show edit fleet modal
        function editFleet(fleet) {
            fleetState.currentFleet = fleet;
            const modal = document.getElementById('fleetModal');
            document.getElementById('modalTitle').textContent = 'Edit Armada';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('fleetId').value = fleet.id;
            document.getElementById('nama_armada').value = fleet.nama || '';
            document.getElementById('nomor_polisi').value = fleet.nomor_polisi || '';
            document.getElementById('kapasitas').value = fleet.kapasitas || '4';
            document.getElementById('keterangan').value = fleet.deskripsi || '';
            document.getElementById('status').checked = fleet.status === 'tersedia';
            document.getElementById('nomor_polisi').required = false;
            modal.classList.remove('hidden');
        }

        // Hide modal
        function hideModal() {
            document.getElementById('fleetModal').classList.add('hidden');
            fleetState.currentFleet = null;
        }

        // Update fleet status
        async function updateFleetStatus(id, status) {
            try {
                const response = await fetch('fleets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=update_status&id=${id}&status=${status}`
                });

                const data = await response.json();
                
                if (data.success) {
                    await showSuccess('Berhasil!', 'Status armada berhasil diperbarui');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status armada');
                }
            } catch (error) {
                showError('Error!', error.message || 'Terjadi kesalahan saat memperbarui status armada');
            }
        }

        // Show fleet management options
        function manageFleet(id, name) {
            Swal.fire({
                title: 'Kelola Armada',
                html: `
                    <div class="text-left">
                        <p class="mb-4">Apa yang ingin Anda lakukan dengan armada <strong>${name}</strong>?</p>
                        <div class="space-y-3">
                            <button id="btn-edit" class="w-full text-left p-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-md border border-blue-200 flex items-center">
                                <i class="fas fa-edit mr-2"></i>
                                <div>
                                    <div class="font-medium">Edit Data</div>
                                    <div class="text-xs text-blue-500">Ubah detail armada</div>
                                </div>
                            </button>
                            <button id="btn-mark-unavailable" class="w-full text-left p-3 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 rounded-md border border-yellow-200 flex items-center">
                                <i class="fas fa-times-circle mr-2"></i>
                                <div>
                                    <div class="font-medium">Tandai Tidak Tersedia</div>
                                    <div class="text-xs text-yellow-600">Menonaktifkan armada sementara</div>
                                </div>
                            </button>
                            <button id="btn-delete" class="w-full text-left p-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-md border border-red-200 flex items-center">
                                <i class="fas fa-trash mr-2"></i>
                                <div>
                                    <div class="font-medium">Hapus Armada</div>
                                    <div class="text-xs text-red-500">Hanya jika tidak ada pemesanan aktif</div>
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
                    document.getElementById('btn-edit')?.addEventListener('click', () => {
                        Swal.close();
                        // Find fleet data from the table row
                        const fleet = {
                            id: id,
                            nama: name,
                            // Add other fleet properties as needed
                        };
                        editFleet(fleet);
                    });
                    
                    document.getElementById('btn-mark-unavailable')?.addEventListener('click', () => {
                        Swal.close();
                        confirmStatusUpdate(id, name, 'tidak_tersedia');
                    });
                    
                    document.getElementById('btn-delete')?.addEventListener('click', () => {
                        Swal.close();
                        confirmDelete(id, name);
                    });
                }
            });
        }

        // Confirm status update
        async function confirmStatusUpdate(id, name, status) {
            const statusText = status === 'tidak_tersedia' ? 'tidak tersedia' : 'tersedia';
            const { isConfirmed } = await Swal.fire({
                title: 'Konfirmasi Perubahan Status',
                html: `Apakah Anda yakin ingin mengubah status armada <strong>${name}</strong> menjadi <strong>${statusText}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ubah Status',
                cancelButtonText: 'Batal',
                reverseButtons: true
            });

            if (isConfirmed) {
                await updateFleetStatus(id, status);
            }
        }

        // Confirm delete fleet
        async function confirmDelete(id, name) {
            const { isConfirmed } = await Swal.fire({
                title: 'Hapus Armada',
                html: `Apakah Anda yakin ingin menghapus armada <strong>${name}</strong>?<br><span class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'px-4 py-2 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700',
                    cancelButton: 'px-4 py-2 mr-2 rounded-md text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
                },
                buttonsStyling: false
            });

            if (isConfirmed) {
                await deleteFleet(id, name);
            }
        }

        // Delete fleet function
        async function deleteFleet(id, name) {
            try {
                Swal.fire({
                    title: 'Menghapus...',
                    text: 'Sedang menghapus data armada',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const response = await fetch(`fleets.php?action=hapus&id=${id}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();

                if (data.success) {
                    await showSuccess('Berhasil!', 'Armada berhasil dihapus');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Gagal menghapus armada');
                }
            } catch (error) {
                await showDeleteError(error, id, name);
            }
        }

        // Show success message
        function showSuccess(title, message) {
            return Swal.fire({
                title: title,
                text: message,
                icon: 'success',
                confirmButtonText: 'Tutup'
            });
        }

        // Show error message
        function showError(title, message) {
            return Swal.fire({
                title: title,
                text: message,
                icon: 'error',
                confirmButtonText: 'Mengerti',
                confirmButtonColor: '#3b82f6'
            });
        }

        // Show delete error with options
        async function showDeleteError(error, id, name) {
            const { value } = await Swal.fire({
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
                showCancelButton: false,
                showCloseButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    document.getElementById('mark-unavailable')?.addEventListener('click', () => {
                        Swal.close();
                        updateFleetStatus(id, 'tidak_tersedia');
                    });
                    
                    document.getElementById('cancel-delete')?.addEventListener('click', () => {
                        Swal.close();
                    });
                }
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('fleetModal');
            if (event.target === modal) {
                hideModal();
            }
        };

    </script>
</div>

<?php
// Get the buffered content and include the layout
$content = ob_get_clean();
include 'includes/layout.php';
?>


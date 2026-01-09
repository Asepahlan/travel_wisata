<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Rute';

// Tangani aksi tambah/edit/hapus
// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status' && isset($_POST['id'])) {
    header('Content-Type: application/json');
    
    try {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] === 'aktif' ? 'aktif' : 'nonaktif';
        
        $stmt = $pdo->prepare("UPDATE rute SET status = ? WHERE id = ?");
        $updated = $stmt->execute([$status, $id]);
        
        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Status rute berhasil diperbarui'
            ]);
        } else {
            throw new Exception('Gagal memperbarui status rute');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}

// Handle other POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'tambah' || $_POST['action'] === 'edit') {
                $data = [
                    'asal' => trim($_POST['asal']),
                    'tujuan' => trim($_POST['tujuan']),
                    'jarak' => (float)str_replace(',', '.', $_POST['jarak']),
                    'durasi_jam' => (int)$_POST['waktu_tempuh'], // Map to durasi_jam
                    'rute_via' => !empty(trim($_POST['rute_via'])) ? trim($_POST['rute_via']) : NULL,
                    'keterangan' => !empty(trim($_POST['keterangan'])) ? trim($_POST['keterangan']) : NULL,
                    'status' => isset($_POST['status']) ? 'aktif' : 'nonaktif'
                ];

                // Validasi input
                if (empty($data['asal']) || empty($data['tujuan'])) {
                    throw new Exception('Kota asal dan tujuan harus diisi');
                }

                if ($_POST['action'] === 'tambah') {
                    // Add new route
                    $query = "INSERT INTO rute (
                                asal, tujuan, jarak, durasi_jam, rute_via, 
                                keterangan, status, created_at, updated_at
                              ) VALUES (
                                :asal, :tujuan, :jarak, :durasi_jam, :rute_via, 
                                :keterangan, :status, NOW(), NOW()
                              )";
                    $message = 'Rute berhasil ditambahkan';
                } else {
                    // Update existing route
                    $id = (int)$_POST['id'];
                    $query = "UPDATE rute SET 
                                asal = :asal, 
                                tujuan = :tujuan, 
                                jarak = :jarak, 
                                durasi_jam = :durasi_jam,
                                rute_via = :rute_via, 
                                keterangan = :keterangan, 
                                status = :status,
                                updated_at = NOW()
                              WHERE id = $id";
                    $message = 'Rute berhasil diperbarui';
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                $_SESSION['success'] = $message;
                header('Location: routes.php');
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
        
        // Cek apakah rute digunakan di paket
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM paket WHERE id_rute = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Tidak dapat menghapus rute karena sudah digunakan di paket wisata');
        }
        
        // Hapus data rute
        $stmt = $pdo->prepare("DELETE FROM rute WHERE id = ?");
        $deleted = $stmt->execute([$id]);
        
        if ($deleted) {
            $response['success'] = true;
            $response['message'] = 'Rute berhasil dihapus';
        } else {
            throw new Exception('Gagal menghapus data rute');
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

// Update all routes to be active by default if status is not set
$pdo->query("UPDATE rute SET status = 'aktif' WHERE status IS NULL OR status = ''");

// Get list of routes with proper field mapping
$query = "SELECT 
            id, 
            asal, 
            tujuan, 
            jarak, 
            durasi_jam as waktu_tempuh, 
            rute_via, 
            keterangan, 
            status,
            created_at,
            updated_at 
          FROM rute 
          ORDER BY asal, tujuan";
$routes = $pdo->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Travel Wisata'; ?> Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
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
                    <h2 class="text-xl font-semibold text-gray-800">Manajemen Rute Perjalanan</h2>
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

                <!-- Tombol Tambah Rute -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Rute Perjalanan</h3>
                    <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Tambah Rute
                    </button>
                </div>

                <!-- Tabel Rute -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asal - Tujuan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jarak (km)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Tempuh</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rute Via</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($routes) > 0): ?>
                                    <?php foreach ($routes as $route): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($route['asal'] . ' - ' . $route['tujuan']); ?>
                                                </div>
                                                <?php if (!empty($route['keterangan'])): ?>
                                                    <div class="text-sm text-gray-500 mt-1">
                                                        <?php echo htmlspecialchars($route['keterangan']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo number_format($route['jarak'], 1, ',', '.'); ?> km
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo !empty($route['waktu_tempuh']) ? htmlspecialchars($route['waktu_tempuh']) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo !empty($route['rute_via']) ? htmlspecialchars($route['rute_via']) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $status = $route['status'] ?? 'nonaktif';
                                                $statusClass = $status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                                $statusText = $status === 'aktif' ? 'Aktif' : 'Nonaktif';
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="editRoute(<?php echo htmlspecialchars(json_encode($route)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                                        title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button 
                                                   onclick="deleteRoute(<?php echo $route['id']; ?>, '<?php echo addslashes($route['asal']); ?>', '<?php echo addslashes($route['tujuan']); ?>')" 
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
                                            Belum ada data rute perjalanan
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

    <!-- Modal Tambah/Edit Rute -->
    <div id="routeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-5 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-2">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-semibold text-gray-800" id="modalTitle">Tambah Rute Perjalanan</h3>
                    <button type="button" onclick="hideModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="routeForm" action="" method="post" class="mt-6 space-y-6">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="id" id="routeId">
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Kota Asal -->
                            <div>
                                <label for="asal" class="block text-sm font-medium text-gray-700 mb-1">Kota Asal <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="asal" id="asal" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Contoh: Jakarta">
                                    <p class="mt-1 text-xs text-gray-500">Masukkan kota asal perjalanan</p>
                                </div>
                            </div>
                            
                            <!-- Kota Tujuan -->
                            <div>
                                <label for="tujuan" class="block text-sm font-medium text-gray-700 mb-1">Kota Tujuan <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="tujuan" id="tujuan" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Contoh: Bandung">
                                    <p class="mt-1 text-xs text-gray-500">Masukkan kota tujuan perjalanan</p>
                                </div>
                            </div>
                            
                            <!-- Jarak -->
                            <div>
                                <label for="jarak" class="block text-sm font-medium text-gray-700 mb-1">Jarak <span class="text-red-500">*</span></label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <input type="text" name="jarak" id="jarak" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 pl-3 pr-12 border"
                                           placeholder="0.0"
                                           oninput="formatNumber(this)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">km</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Masukkan jarak dalam kilometer</p>
                            </div>
                            
                            <!-- Waktu Tempuh -->
                            <div>
                                <label for="waktu_tempuh" class="block text-sm font-medium text-gray-700 mb-1">Waktu Tempuh <span class="text-red-500">*</span></label>
                                <div class="mt-1">
                                    <input type="text" name="waktu_tempuh" id="waktu_tempuh" required
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Contoh: 2 jam 30 menit">
                                    <p class="mt-1 text-xs text-gray-500">Perkiraan waktu tempuh perjalanan</p>
                                </div>
                            </div>
                            
                            <!-- Rute Via -->
                            <div class="md:col-span-2">
                                <label for="rute_via" class="block text-sm font-medium text-gray-700 mb-1">Rute Via</label>
                                <div class="mt-1">
                                    <input type="text" name="rute_via" id="rute_via"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                           placeholder="Contoh: Tol Cipularang, Puncak">
                                    <p class="mt-1 text-xs text-gray-500">Rute alternatif atau jalan yang dilalui (opsional)</p>
                                </div>
                            </div>
                            
                            <!-- Keterangan -->
                            <div class="md:col-span-2">
                                <label for="keterangan" class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                                <div class="mt-1">
                                    <textarea name="keterangan" id="keterangan" rows="3"
                                             class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2 px-3 border"
                                             placeholder="Informasi tambahan tentang rute"></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Catatan atau informasi penting lainnya</p>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="md:col-span-2 pt-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="status" id="status" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="status" class="ml-2 block text-sm text-gray-700">Aktif</label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Centang untuk mengaktifkan rute ini</p>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Toggle status rute
        function toggleRouteStatus(id, currentStatus) {
            const newStatus = currentStatus === 'aktif' ? 'nonaktif' : 'aktif';
            
            fetch('routes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_status&id=${id}&status=${newStatus}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Gagal memperbarui status rute');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Terjadi kesalahan saat memperbarui status rute',
                    icon: 'error',
                    confirmButtonText: 'Tutup'
                });
            });
        }
        
        // Format input angka
        function formatNumber(input) {
            // Hanya izinkan angka dan koma
            input.value = input.value.replace(/[^0-9,]/g, '');
            
            // Ganti koma dengan titik untuk format desimal
            input.value = input.value.replace(',', '.');
            
            // Pastikan hanya ada satu titik desimal
            if ((input.value.match(/\./g) || []).length > 1) {
                input.value = input.value.slice(0, -1);
            }
        }

        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Rute Perjalanan';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('routeForm').reset();
            document.getElementById('status').checked = true;
            document.getElementById('routeModal').classList.remove('hidden');
        }

        // Tampilkan modal edit
        function editRoute(route) {
            document.getElementById('modalTitle').textContent = 'Edit Rute Perjalanan';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('routeId').value = route.id;
            document.getElementById('asal').value = route.asal;
            document.getElementById('tujuan').value = route.tujuan;
            document.getElementById('jarak').value = route.jarak;
            document.getElementById('waktu_tempuh').value = route.waktu_tempuh;
            document.getElementById('rute_via').value = route.rute_via || '';
            document.getElementById('keterangan').value = route.keterangan || '';
            document.getElementById('status').checked = route.status === 'aktif';
            document.getElementById('routeModal').classList.remove('hidden');
        }

        // Sembunyikan modal
        function hideModal() {
            document.getElementById('routeModal').classList.add('hidden');
        }

        // Validasi form sebelum submit
        document.getElementById('routeForm')?.addEventListener('submit', function(e) {
            const jarak = document.getElementById('jarak').value.trim();
            if (jarak && !/^\d+(\.\d{1,2})?$/.test(jarak)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Format Jarak Tidak Valid',
                    text: 'Masukkan jarak yang valid (contoh: 12.5)',
                    icon: 'error',
                    confirmButtonText: 'Mengerti',
                    confirmButtonColor: '#3b82f6'
                });
            }
        });

        // Fungsi hapus dengan konfirmasi
        function deleteRoute(id, asal, tujuan) {
            Swal.fire({
                title: 'Hapus Rute',
                html: `Apakah Anda yakin ingin menghapus rute <strong>${asal} - ${tujuan}</strong>?<br><span class="text-sm text-gray-500">Tindakan ini tidak dapat dibatalkan.</span>`,
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
                        text: 'Sedang menghapus data rute',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit the delete request
                    fetch(`routes.php?action=hapus&id=${id}`, {
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
                                text: data.message || 'Rute berhasil dihapus',
                                icon: 'success',
                                confirmButtonText: 'Tutup'
                            }).then(() => {
                                // Reload the page to see the changes
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Gagal menghapus rute');
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Terjadi kesalahan saat menghapus rute',
                            icon: 'error',
                            confirmButtonText: 'Tutup'
                        });
                    });
                }
            });
        }

        // Tutup modal saat mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('routeModal');
            if (event.target === modal) {
                hideModal();
            }
        }
    </script>
</body>
</html>

<?php
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$page_title = 'Manajemen Pengguna';

// Tangani aksi tambah/edit/hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'tambah' || $_POST['action'] === 'edit') {
                $data = [
                    'username' => trim($_POST['username']),
                    'fullname' => trim($_POST['fullname']),
                    'email' => trim($_POST['email']),
                    'role' => trim($_POST['role']),
                    'status' => isset($_POST['status']) ? 'aktif' : 'nonaktif'
                ];

                // Validasi input
                if (empty($data['username']) || empty($data['fullname']) || empty($data['email'])) {
                    throw new Exception('Username, nama lengkap, dan email harus diisi');
                }

                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Format email tidak valid');
                }

                if ($_POST['action'] === 'tambah') {
                    // Validasi password untuk tambah pengguna baru
                    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
                        throw new Exception('Password minimal 6 karakter');
                    }
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    
                    // Cek username sudah ada atau belum
                    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
                    $stmt->execute([$data['username']]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception('Username sudah digunakan');
                    }

                    // Cek email sudah ada atau belum
                    $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
                    $stmt->execute([$data['email']]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception('Email sudah digunakan');
                    }

                    // Tambah pengguna baru
                    $query = "INSERT INTO admin (username, password, fullname, email, role, status, created_at) 
                              VALUES (:username, :password, :fullname, :email, :role, :status, NOW())";
                    $message = 'Pengguna berhasil ditambahkan';
                } else {
                    // Update pengguna yang ada
                    $id = (int)$_POST['id'];
                    
                    // Cek username sudah digunakan oleh pengguna lain
                    $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ? AND id != ?");
                    $stmt->execute([$data['username'], $id]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception('Username sudah digunakan oleh pengguna lain');
                    }

                    // Cek email sudah digunakan oleh pengguna lain
                    $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
                    $stmt->execute([$data['email'], $id]);
                    if ($stmt->rowCount() > 0) {
                        throw new Exception('Email sudah digunakan oleh pengguna lain');
                    }

                    // Update password jika diisi
                    if (!empty($_POST['password'])) {
                        if (strlen($_POST['password']) < 6) {
                            throw new Exception('Password minimal 6 karakter');
                        }
                        $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $query = "UPDATE admin SET 
                                    username = :username,
                                    password = :password,
                                    fullname = :fullname,
                                    email = :email,
                                    role = :role,
                                    status = :status,
                                    updated_at = NOW()
                                  WHERE id = $id";
                    } else {
                        $query = "UPDATE admin SET 
                                    username = :username,
                                    fullname = :fullname,
                                    email = :email,
                                    role = :role,
                                    status = :status,
                                    updated_at = NOW()
                                  WHERE id = $id";
                    }
                    $message = 'Pengguna berhasil diperbarui';
                }

                $stmt = $pdo->prepare($query);
                $stmt->execute($data);
                
                $_SESSION['success'] = $message;
                header('Location: users.php');
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
        
        // Cek apakah pengguna yang sedang login
        if ($id === $_SESSION['admin_id']) {
            throw new Exception('Tidak dapat menghapus akun yang sedang aktif');
        }
        
        $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = 'Pengguna berhasil dihapus';
        } else {
            $_SESSION['error'] = 'Pengguna tidak ditemukan';
        }
        
        header('Location: users.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Gagal menghapus pengguna: ' . $e->getMessage();
        header('Location: users.php');
        exit();
    }
}

// Ambil daftar pengguna (kecuali super admin jika perlu)
$query = "SELECT id, username, fullname, email, role, status, last_login FROM admin ORDER BY role, fullname";
$users = $pdo->query($query)->fetchAll();

// Daftar role yang tersedia
$role_list = [
    'admin' => 'Administrator',
    'staff' => 'Staff',
    'operator' => 'Operator'
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
    <style>
        .sidebar {
            min-height: calc(100vh - 4rem);
        }
        .status-active { 
            background-color: #DCFCE7; 
            color: #166534; 
        }
        .status-inactive { 
            background-color: #FEE2E2; 
            color: #991B1B; 
        }
        .role-admin { 
            background-color: #E0F2FE; 
            color: #075985;
        }
        .role-staff { 
            background-color: #F0F9FF; 
            color: #0C4A6E;
        }
        .role-operator { 
            background-color: #F0FDF4; 
            color: #166534;
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
                    <h2 class="text-xl font-semibold text-gray-800">Manajemen Pengguna</h2>
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

                <!-- Tombol Tambah Pengguna -->
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Daftar Pengguna</h3>
                    <button onclick="showAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-user-plus mr-2"></i> Tambah Pengguna
                    </button>
                </div>

                <!-- Tabel Pengguna -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($users) > 0): ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($user['fullname']); ?>
                                                    <?php if ($user['id'] === $_SESSION['admin_id']): ?>
                                                        <span class="ml-2 text-xs text-blue-600">(Anda)</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    Login terakhir: <?php echo $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Belum pernah login'; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $roleClass = 'role-' . strtolower($user['role']);
                                                $roleText = $role_list[$user['role']] ?? ucfirst($user['role']);
                                                ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $roleClass; ?>">
                                                    <?php echo $roleText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php 
                                                $statusClass = $user['status'] === 'aktif' ? 'status-active' : 'status-inactive';
                                                $statusText = $user['status'] === 'aktif' ? 'Aktif' : 'Nonaktif';
                                                ?>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                                        title="Edit"
                                                        <?php echo ($user['id'] === $_SESSION['admin_id']) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="#" 
                                                   onclick="return confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['fullname'])); ?>')" 
                                                   class="text-red-600 hover:text-red-900"
                                                   title="Hapus"
                                                   <?php echo ($user['id'] === $_SESSION['admin_id']) ? 'style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                            Belum ada data pengguna
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

    <!-- Modal Tambah/Edit Pengguna -->
    <div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center pb-3 border-b">
                    <h3 class="text-xl font-medium text-gray-900" id="modalTitle">Tambah Pengguna</h3>
                    <button onclick="hideModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="userForm" action="" method="post" class="mt-4 space-y-4">
                    <input type="hidden" name="action" id="formAction" value="tambah">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label for="username" class="block text-sm font-medium text-gray-700">Username <span class="text-red-500">*</span></label>
                            <input type="text" name="username" id="username" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Contoh: johndoe">
                        </div>
                        
                        <div class="col-span-2 md:col-span-1">
                            <label for="fullname" class="block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="fullname" id="fullname" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Contoh: John Doe">
                        </div>
                        
                        <div class="col-span-2">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" id="email" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="contoh@email.com">
                        </div>
                        
                        <div class="col-span-2 md:col-span-1">
                            <label for="password" class="block text-sm font-medium text-gray-700" id="passwordLabel">Password <span class="text-red-500">*</span></label>
                            <input type="password" name="password" id="password"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                   placeholder="Minimal 6 karakter">
                            <p class="mt-1 text-xs text-gray-500" id="passwordHelp">Biarkan kosong jika tidak ingin mengubah password</p>
                        </div>
                        
                        <div class="col-span-2 md:col-span-1">
                            <label for="role" class="block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                            <select name="role" id="role" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <?php foreach ($role_list as $key => $role): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $role; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="status" id="status" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" checked>
                                <label for="status" class="ml-2 block text-sm text-gray-700">Aktifkan akun</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Batal
                        </button>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tampilkan modal tambah
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Pengguna';
            document.getElementById('formAction').value = 'tambah';
            document.getElementById('userForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('passwordHelp').classList.add('hidden');
            document.getElementById('status').checked = true;
            document.getElementById('userModal').classList.remove('hidden');
            
            // Set focus ke field pertama
            document.getElementById('username').focus();
        }

        // Tampilkan modal edit
        function editUser(userData) {
            document.getElementById('modalTitle').textContent = 'Edit Pengguna';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = userData.id;
            document.getElementById('username').value = userData.username || '';
            document.getElementById('fullname').value = userData.fullname || '';
            document.getElementById('email').value = userData.email || '';
            document.getElementById('role').value = userData.role || 'staff';
            document.getElementById('status').checked = userData.status === 'aktif';
            
            // Atur field password
            document.getElementById('password').required = false;
            document.getElementById('password').placeholder = 'Biarkan kosong jika tidak ingin mengubah';
            document.getElementById('passwordHelp').classList.remove('hidden');
            
            // Tampilkan modal
            document.getElementById('userModal').classList.remove('hidden');
            
            // Set focus ke field pertama
            document.getElementById('fullname').focus();
        }

        // Sembunyikan modal
        function hideModal() {
            document.getElementById('userModal').classList.add('hidden');
        }

        // Konfirmasi hapus
        function confirmDelete(id, name) {
            if (id === <?php echo $_SESSION['admin_id']; ?>) {
                alert('Tidak dapat menghapus akun yang sedang aktif');
                return false;
            }
            
            if (confirm(`Apakah Anda yakin ingin menghapus pengguna "${name}"? Tindakan ini tidak dapat dibatalkan.`)) {
                window.location.href = `users.php?action=hapus&id=${id}`;
            }
            return false;
        }

        // Tutup modal saat mengklik di luar modal
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                hideModal();
            }
        }
    </script>
</body>
</html>

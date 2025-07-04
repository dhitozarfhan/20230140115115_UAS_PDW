<?php
$pageTitle = 'Kelola Pengguna';
$activePage = 'users';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $id_to_delete = (int)$_POST['id'];
    if ($id_to_delete === $_SESSION['user_id']) {
        $message = "Anda tidak dapat menghapus akun Anda sendiri!";
        $message_type = 'error';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            $message = "Pengguna berhasil dihapus!";
            $message_type = 'success';
        } else {
            $message = "Gagal menghapus pengguna. Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = trim($_POST['password']);
    $user_id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    // Validasi email unik (kecuali untuk user yang sedang diedit)
    $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check_email->bind_param("si", $email, $user_id);
    $stmt_check_email->execute();
    $stmt_check_email->store_result();

    if ($stmt_check_email->num_rows > 0) {
        $message = "Email sudah digunakan oleh pengguna lain.";
        $message_type = 'error';
    } else {
        if ($user_id > 0) {
            // UPDATE
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $nama, $email, $role, $hashed_password, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $nama, $email, $role, $user_id);
            }
            $action_message = "diperbarui";
        } else {
            // CREATE
            if (empty($password)) {
                $message = "Password wajib diisi untuk pengguna baru.";
                $message_type = 'error';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
                $action_message = "ditambahkan";
            }
        }
        
        if (empty($message) && isset($stmt)) {
            if ($stmt->execute()) {
                $message = "Pengguna berhasil " . $action_message . "!";
                $message_type = 'success';
            } else {
                $message = "Gagal. Error: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
    $stmt_check_email->close();
}

// --- LOGIKA PENGAMBILAN DATA ---

// Statistik
$total_users = $conn->query("SELECT COUNT(id) as total FROM users")->fetch_assoc()['total'];
$total_mahasiswa = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'mahasiswa'")->fetch_assoc()['total'];
$total_asisten = $conn->query("SELECT COUNT(id) as total FROM users WHERE role = 'asisten'")->fetch_assoc()['total'];

// Filter
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['role']) ? trim($_GET['role']) : '';

$sql = "SELECT id, nama, email, role, created_at FROM users";
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_search)) {
    $where_clauses[] = "(nama LIKE ? OR email LIKE ?)";
    $search_param = "%" . $filter_search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}
if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY created_at DESC";

$stmt_users = $conn->prepare($sql);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$users_list_result = $stmt_users->get_result();

function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    foreach ($words as $w) {
        $initials .= strtoupper($w[0]);
    }
    return $initials;
}
?>

<!-- Kartu Statistik -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4"><div class="bg-blue-100 p-3 rounded-full"><svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM10.5 18.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg></div><div><p class="text-sm text-gray-500">Total Pengguna</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></p></div></div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4"><div class="bg-indigo-100 p-3 rounded-full"><svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path d="M12 14l9-5-9-5-9 5 9 5z" /><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-5.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" /></svg></div><div><p class="text-sm text-gray-500">Total Mahasiswa</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_mahasiswa; ?></p></div></div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4"><div class="bg-green-100 p-3 rounded-full"><svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286zm0 13.036h.008v.008h-.008v-.008z" /></svg></div><div><p class="text-sm text-gray-500">Total Asisten</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_asisten; ?></p></div></div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" id="alert-box"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Filter dan Tabel Pengguna -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Daftar Pengguna</h2>
        <div class="flex items-center gap-2">
            <form action="manage_users.php" method="GET" class="flex items-center gap-2">
                <input type="text" name="search" placeholder="Cari nama atau email..." class="shadow-sm appearance-none border border-gray-300 rounded py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($filter_search); ?>">
                <select name="role" class="shadow-sm appearance-none border border-gray-300 rounded py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                    <option value="">Semua Peran</option>
                    <option value="mahasiswa" <?php echo ($filter_role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($filter_role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
            </form>
            <button onclick="openUserModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors flex items-center"><svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>Tambah</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-50">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                    <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Peran</th>
                    <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if ($users_list_result->num_rows > 0): ?>
                    <?php while($row = $users_list_result->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="font-bold text-gray-500"><?php echo getInitials($row['nama']); ?></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nama']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center"><span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $row['role'] == 'asisten' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>"><?php echo ucfirst($row['role']); ?></span></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick='openUserModal(<?php echo json_encode($row, JSON_HEX_APOS); ?>)' class="text-indigo-600 hover:text-indigo-900" title="Edit"><svg class="inline w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg></button>
                                <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                    <button onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900 ml-4" title="Hapus"><svg class="inline w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" class="text-center py-8 text-gray-500">Tidak ada pengguna ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Pengguna -->
<div id="user-modal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="manage_users.php" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="user-modal-title">Tambah Pengguna Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="id" id="user-id">
                        <div><label for="user-nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label><input type="text" id="user-nama" name="nama" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                        <div><label for="user-email" class="block text-sm font-medium text-gray-700">Email</label><input type="email" id="user-email" name="email" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700" required></div>
                        <div><label for="user-password" class="block text-sm font-medium text-gray-700">Password</label><input type="password" id="user-password" name="password" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700" placeholder="Kosongkan jika tidak diubah"></div>
                        <div><label for="user-role" class="block text-sm font-medium text-gray-700">Peran</label><select id="user-role" name="role" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700" required><option value="mahasiswa">Mahasiswa</option><option value="asisten">Asisten</option></select></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="save_user" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="closeUserModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="delete-modal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4"><div class="sm:flex sm:items-start"><div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div><div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"><h3 class="text-lg leading-6 font-medium text-gray-900">Hapus Pengguna</h3><div class="mt-2"><p class="text-sm text-gray-500">Anda yakin ingin menghapus pengguna <strong id="user-name-to-delete"></strong>? Tindakan ini tidak dapat dibatalkan.</p></div></div></div></div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="manage_users.php" method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="user-id-to-delete"><button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Hapus</button></form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    const userModal = document.getElementById('user-modal');
    const deleteModal = document.getElementById('delete-modal');

    function openUserModal(userData = null) {
        const form = userModal.querySelector('form');
        form.reset();
        if (userData) {
            document.getElementById('user-modal-title').textContent = 'Edit Pengguna';
            document.getElementById('user-id').value = userData.id;
            document.getElementById('user-nama').value = userData.nama;
            document.getElementById('user-email').value = userData.email;
            document.getElementById('user-role').value = userData.role;
            document.getElementById('user-password').placeholder = 'Kosongkan jika tidak diubah';
        } else {
            document.getElementById('user-modal-title').textContent = 'Tambah Pengguna Baru';
            document.getElementById('user-id').value = '';
            document.getElementById('user-password').placeholder = 'Wajib diisi';
        }
        userModal.classList.remove('hidden');
    }

    function closeUserModal() {
        userModal.classList.add('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('user-name-to-delete').textContent = name;
        document.getElementById('user-id-to-delete').value = id;
        deleteModal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
    }

    const alertBox = document.getElementById('alert-box');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.5s';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 500);
        }, 3000);
    }
</script>

<?php 
$conn->close();
include_once 'templates/footer.php'; 
?>

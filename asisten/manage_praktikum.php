<?php
$pageTitle = 'Kelola Praktikum';
$activePage = 'praktikum';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    $id_to_delete = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM mata_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    if ($stmt->execute()) {
        $message = "Mata praktikum berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus. Praktikum ini mungkin masih memiliki modul atau pendaftar. Error: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_praktikum'])) {
    $nama_praktikum = trim($_POST['nama_praktikum']);
    $deskripsi = trim($_POST['deskripsi']);
    $praktikum_id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($praktikum_id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE mata_praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nama_praktikum, $deskripsi, $praktikum_id);
        $action_message = "diperbarui";
    } else {
        // CREATE
        $stmt = $conn->prepare("INSERT INTO mata_praktikum (nama_praktikum, deskripsi) VALUES (?, ?)");
        $stmt->bind_param("ss", $nama_praktikum, $deskripsi);
        $action_message = "ditambahkan";
    }
    
    if ($stmt->execute()) {
        $message = "Mata praktikum berhasil " . $action_message . "!";
        $message_type = 'success';
    } else {
        $message = "Gagal. Error: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
}

// --- LOGIKA PENGAMBILAN DATA ---

// Statistik
$total_praktikum = $conn->query("SELECT COUNT(id) as total FROM mata_praktikum")->fetch_assoc()['total'];
$total_modul = $conn->query("SELECT COUNT(id) as total FROM modul_praktikum")->fetch_assoc()['total'];
$total_pendaftar = $conn->query("SELECT COUNT(id) as total FROM pendaftaran_praktikum")->fetch_assoc()['total'];

// Filter dan Pencarian
$filter_search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT mp.*, 
               (SELECT COUNT(id) FROM modul_praktikum WHERE praktikum_id = mp.id) as jumlah_modul,
               (SELECT COUNT(id) FROM pendaftaran_praktikum WHERE praktikum_id = mp.id) as jumlah_mahasiswa
        FROM mata_praktikum mp";

if (!empty($filter_search)) {
    $sql .= " WHERE mp.nama_praktikum LIKE ?";
}
$sql .= " ORDER BY mp.created_at DESC";

$stmt_praktikum = $conn->prepare($sql);
if (!empty($filter_search)) {
    $search_param = "%" . $filter_search . "%";
    $stmt_praktikum->bind_param("s", $search_param);
}
$stmt_praktikum->execute();
$praktikum_list_result = $stmt_praktikum->get_result();

?>

<!-- Kartu Statistik -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 p-3 rounded-full"><svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg></div>
        <div><p class="text-sm text-gray-500">Total Praktikum</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_praktikum; ?></p></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-indigo-100 p-3 rounded-full"><svg class="w-6 h-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 006 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg></div>
        <div><p class="text-sm text-gray-500">Total Modul</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_modul; ?></p></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 p-3 rounded-full"><svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m-7.5-2.962a3.75 3.75 0 100-7.5 3.75 3.75 0 000 7.5zM10.5 18.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" /></svg></div>
        <div><p class="text-sm text-gray-500">Total Pendaftar</p><p class="text-2xl font-bold text-gray-800"><?php echo $total_pendaftar; ?></p></div>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="mb-4 p-4 rounded-md <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" id="alert-box"><?php echo $message; ?></div>
<?php endif; ?>

<!-- Filter dan Tabel Praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Daftar Mata Praktikum</h2>
        <div class="flex items-center gap-2">
            <form action="manage_praktikum.php" method="GET" class="flex items-center gap-2">
                <input type="text" name="search" placeholder="Cari praktikum..." class="shadow-sm appearance-none border border-gray-300 rounded py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($filter_search); ?>">
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition-colors">Cari</button>
            </form>
            <button onclick="openPraktikumModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors flex items-center"><svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>Tambah</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500">
                <tr>
                    <th class="py-3 px-6 text-left text-xs font-bold text-white uppercase tracking-wider">Nama Praktikum</th>
                    <th class="py-3 px-6 text-center text-xs font-bold text-white uppercase tracking-wider">Jumlah Modul</th>
                    <th class="py-3 px-6 text-center text-xs font-bold text-white uppercase tracking-wider">Jumlah Mahasiswa</th>
                    <th class="py-3 px-6 text-center text-xs font-bold text-white uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-indigo-100">
                <?php if ($praktikum_list_result->num_rows > 0): ?>
                    <?php while($row = $praktikum_list_result->fetch_assoc()): ?>
                        <tr class="hover:bg-indigo-50 transition">
                            <td class="py-4 px-6 whitespace-nowrap">
                                <div class="text-base font-bold text-indigo-800"><?php echo htmlspecialchars($row['nama_praktikum']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 70)) . '...'; ?></div>
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-base font-semibold text-blue-700"><?php echo $row['jumlah_modul']; ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-base font-semibold text-purple-700"><?php echo $row['jumlah_mahasiswa']; ?></td>
                            <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                <button onclick='openPraktikumModal(<?php echo json_encode($row, JSON_HEX_APOS); ?>)' class="inline-flex items-center text-indigo-600 hover:text-white hover:bg-indigo-600 border border-indigo-200 px-3 py-1 rounded-lg font-bold transition" title="Edit">
                                    <svg class="inline w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    Edit
                                </button>
                                <button onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_praktikum'], ENT_QUOTES); ?>')" class="inline-flex items-center text-red-600 hover:text-white hover:bg-red-600 border border-red-200 px-3 py-1 rounded-lg font-bold transition ml-2" title="Hapus">
                                    <svg class="inline w-5 h-5 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-8 text-gray-500">Tidak ada praktikum ditemukan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah/Edit Praktikum -->
<div id="praktikum-modal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="manage_praktikum.php" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="praktikum-modal-title">Tambah Praktikum Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="id" id="praktikum-id">
                        <div><label for="praktikum-nama" class="block text-sm font-medium text-gray-700">Nama Praktikum</label><input type="text" id="praktikum-nama" name="nama_praktikum" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                        <div><label for="praktikum-deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label><textarea id="praktikum-deskripsi" name="deskripsi" rows="4" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700"></textarea></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="save_praktikum" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="closePraktikumModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
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
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4"><div class="sm:flex sm:items-start"><div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div><div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"><h3 class="text-lg leading-6 font-medium text-gray-900">Hapus Praktikum</h3><div class="mt-2"><p class="text-sm text-gray-500">Anda yakin ingin menghapus praktikum <strong id="praktikum-name-to-delete"></strong>? Semua modul dan data pendaftaran terkait akan ikut terhapus.</p></div></div></div></div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="manage_praktikum.php" method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" id="praktikum-id-to-delete"><button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Hapus</button></form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    const praktikumModal = document.getElementById('praktikum-modal');
    const deleteModal = document.getElementById('delete-modal');

    function openPraktikumModal(data = null) {
        const form = praktikumModal.querySelector('form');
        form.reset();
        if (data) {
            document.getElementById('praktikum-modal-title').textContent = 'Edit Praktikum';
            document.getElementById('praktikum-id').value = data.id;
            document.getElementById('praktikum-nama').value = data.nama_praktikum;
            document.getElementById('praktikum-deskripsi').value = data.deskripsi;
        } else {
            document.getElementById('praktikum-modal-title').textContent = 'Tambah Praktikum Baru';
            document.getElementById('praktikum-id').value = '';
        }
        praktikumModal.classList.remove('hidden');
    }

    function closePraktikumModal() {
        praktikumModal.classList.add('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('praktikum-name-to-delete').textContent = name;
        document.getElementById('praktikum-id-to-delete').value = id;
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

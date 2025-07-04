<?php
$pageTitle = 'Manajemen Modul';
$activePage = 'modul';
require_once '../config.php';
include_once 'templates/header.php';

$message = '';
$message_type = '';

// --- LOGIKA CRUD MODUL ---

// 1. Handle DELETE
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['modul_id'])) {
    $modul_id_to_delete = (int)$_POST['modul_id'];
    
    // Ambil nama file untuk dihapus dari server
    $stmt_file = $conn->prepare("SELECT file_materi FROM modul_praktikum WHERE id = ?");
    $stmt_file->bind_param("i", $modul_id_to_delete);
    $stmt_file->execute();
    $file_result = $stmt_file->get_result()->fetch_assoc();
    $stmt_file->close();
    
    // Hapus dari database
    $stmt_delete = $conn->prepare("DELETE FROM modul_praktikum WHERE id = ?");
    $stmt_delete->bind_param("i", $modul_id_to_delete);
    if ($stmt_delete->execute()) {
        // Jika berhasil, hapus file dari server
        if ($file_result && !empty($file_result['file_materi'])) {
            $file_path = '../uploads/materi/' . $file_result['file_materi'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $message = "Modul berhasil dihapus!";
        $message_type = 'success';
    } else {
        $message = "Gagal menghapus modul. Error: " . $stmt_delete->error;
        $message_type = 'error';
    }
    $stmt_delete->close();
}

// 2. Handle CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_modul'])) {
    $praktikum_id = (int)$_POST['praktikum_id'];
    $nama_modul = trim($_POST['nama_modul']);
    $deskripsi = trim($_POST['deskripsi']);
    $modul_id = isset($_POST['modul_id']) && !empty($_POST['modul_id']) ? (int)$_POST['modul_id'] : 0;
    
    $file_materi = $_POST['current_file'] ?? ''; // Ambil file yang sudah ada

    // Handle file upload
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] == 0) {
        $upload_dir = '../uploads/materi/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $original_name = basename($_FILES["file_materi"]["name"]);
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $unique_name = "materi_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $file_extension;
        $target_file = $upload_dir . $unique_name;
        
        if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
            // Hapus file lama jika ada file baru yang diupload saat update
            if ($modul_id > 0 && !empty($file_materi) && file_exists($upload_dir . $file_materi)) {
                unlink($upload_dir . $file_materi);
            }
            $file_materi = $unique_name;
        } else {
             $message = "Gagal mengunggah file materi.";
             $message_type = 'error';
        }
    }

    if (empty($message)) {
        if ($modul_id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE modul_praktikum SET nama_modul = ?, deskripsi = ?, file_materi = ? WHERE id = ?");
            $stmt->bind_param("sssi", $nama_modul, $deskripsi, $file_materi, $modul_id);
            $action_message = "diperbarui";
        } else {
            // CREATE
            $stmt = $conn->prepare("INSERT INTO modul_praktikum (praktikum_id, nama_modul, deskripsi, file_materi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $praktikum_id, $nama_modul, $deskripsi, $file_materi);
            $action_message = "ditambahkan";
        }
        
        if ($stmt->execute()) {
            $message = "Modul berhasil " . $action_message . "!";
            $message_type = 'success';
        } else {
            $message = "Gagal. Error: " . $stmt->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// --- LOGIKA PENGAMBILAN DATA ---

// Ambil daftar semua praktikum untuk dropdown
$praktikum_options = $conn->query("SELECT id, nama_praktikum FROM mata_praktikum ORDER BY nama_praktikum");

// Tentukan praktikum yang sedang dipilih
$selected_praktikum_id = isset($_GET['praktikum_id']) ? (int)$_GET['praktikum_id'] : 0;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_praktikum_id = (int)$_POST['praktikum_id'];
}

// Ambil modul untuk praktikum yang dipilih
$modul_list = [];
$nama_praktikum_terpilih = '';
if ($selected_praktikum_id > 0) {
    $stmt_nama = $conn->prepare("SELECT nama_praktikum FROM mata_praktikum WHERE id = ?");
    $stmt_nama->bind_param("i", $selected_praktikum_id);
    $stmt_nama->execute();
    $nama_praktikum_terpilih = $stmt_nama->get_result()->fetch_assoc()['nama_praktikum'] ?? '';
    $stmt_nama->close();

    $stmt_modul = $conn->prepare("SELECT * FROM modul_praktikum WHERE praktikum_id = ? ORDER BY created_at ASC");
    $stmt_modul->bind_param("i", $selected_praktikum_id);
    $stmt_modul->execute();
    $result = $stmt_modul->get_result();
    while ($row = $result->fetch_assoc()) {
        $modul_list[] = $row;
    }
    $stmt_modul->close();
}
?>

<!-- Filter Praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <form action="modul.php" method="GET">
        <label for="praktikum_id" class="block text-gray-700 text-sm font-bold mb-2">Pilih Mata Praktikum untuk Dikelola:</label>
        <div class="flex">
            <select name="praktikum_id" id="praktikum_id" class="block w-full bg-white border border-gray-300 rounded-l-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">-- Pilih Praktikum --</option>
                <?php mysqli_data_seek($praktikum_options, 0); while($p = $praktikum_options->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($selected_praktikum_id == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nama_praktikum']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-r-md">Tampilkan</button>
        </div>
    </form>
</div>

<?php if ($selected_praktikum_id > 0): ?>
    <?php if (!empty($message)): ?>
        <div class="mb-4 p-4 rounded-md <?php echo $message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" id="alert-box"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- Tabel Daftar Modul -->
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
            <h2 class="text-xl font-semibold text-gray-800">Daftar Modul untuk: <span class="text-blue-600"><?php echo htmlspecialchars($nama_praktikum_terpilih); ?></span></h2>
            <button onclick="openModulModal(<?php echo $selected_praktikum_id; ?>)" class="w-full md:w-auto bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors flex items-center justify-center"><svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>Tambah Modul</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Modul</th>
                        <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Materi</th>
                        <th class="py-3 px-6 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($modul_list)): ?>
                        <?php foreach($modul_list as $modul): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($modul['nama_modul']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($modul['deskripsi'], 0, 70)) . '...'; ?></div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-sm">
                                    <?php if (!empty($modul['file_materi'])): ?>
                                        <a href="../uploads/materi/<?php echo $modul['file_materi']; ?>" class="text-blue-500 hover:underline" target="_blank">Lihat File</a>
                                    <?php else: ?>
                                        <span class="text-gray-400">Tidak ada</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-center text-sm font-medium">
                                    <button onclick='openModulModal(<?php echo $selected_praktikum_id; ?>, <?php echo json_encode($modul, JSON_HEX_APOS); ?>)' class="text-indigo-600 hover:text-indigo-900" title="Edit"><svg class="inline w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg></button>
                                    <button onclick="openDeleteModal(<?php echo $modul['id']; ?>, '<?php echo htmlspecialchars($modul['nama_modul'], ENT_QUOTES); ?>')" class="text-red-600 hover:text-red-900 ml-4" title="Hapus"><svg class="inline w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center py-8 text-gray-500">Belum ada modul untuk praktikum ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Tambah/Edit Modul -->
<div id="modul-modal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="modul.php?praktikum_id=<?php echo $selected_praktikum_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modul-modal-title">Tambah Modul Baru</h3>
                    <div class="mt-4 space-y-4">
                        <input type="hidden" name="praktikum_id" id="modul-praktikum-id">
                        <input type="hidden" name="modul_id" id="modul-id">
                        <input type="hidden" name="current_file" id="modul-current-file">
                        <div><label for="modul-nama" class="block text-sm font-medium text-gray-700">Nama Modul</label><input type="text" id="modul-nama" name="nama_modul" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" required></div>
                        <div><label for="modul-deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label><textarea id="modul-deskripsi" name="deskripsi" rows="3" class="mt-1 shadow-sm appearance-none border border-gray-300 rounded w-full py-2 px-3 text-gray-700"></textarea></div>
                        <div><label for="modul-file" class="block text-sm font-medium text-gray-700">File Materi (Opsional)</label><div id="current-file-info" class="text-sm text-gray-500 mb-2"></div><input type="file" id="modul-file" name="file_materi" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"></div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="save_modul" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                    <button type="button" onclick="closeModulModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
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
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4"><div class="sm:flex sm:items-start"><div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10"><svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div><div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left"><h3 class="text-lg leading-6 font-medium text-gray-900">Hapus Modul</h3><div class="mt-2"><p class="text-sm text-gray-500">Anda yakin ingin menghapus modul <strong id="modul-name-to-delete"></strong>? Tindakan ini tidak dapat dibatalkan.</p></div></div></div></div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form action="modul.php?praktikum_id=<?php echo $selected_praktikum_id; ?>" method="POST"><input type="hidden" name="action" value="delete"><input type="hidden" name="modul_id" id="modul-id-to-delete"><button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Hapus</button></form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Batal</button>
            </div>
        </div>
    </div>
</div>

<script>
    const modulModal = document.getElementById('modul-modal');
    const deleteModal = document.getElementById('delete-modal');

    function openModulModal(praktikumId, data = null) {
        const form = modulModal.querySelector('form');
        form.reset();
        document.getElementById('modul-praktikum-id').value = praktikumId;
        const currentFileInfo = document.getElementById('current-file-info');
        currentFileInfo.innerHTML = '';

        if (data) {
            document.getElementById('modul-modal-title').textContent = 'Edit Modul';
            document.getElementById('modul-id').value = data.id;
            document.getElementById('modul-nama').value = data.nama_modul;
            document.getElementById('modul-deskripsi').value = data.deskripsi;
            if (data.file_materi) {
                document.getElementById('modul-current-file').value = data.file_materi;
                currentFileInfo.innerHTML = `File saat ini: <a href="../uploads/materi/${data.file_materi}" class="text-blue-500" target="_blank">${data.file_materi}</a>`;
            }
        } else {
            document.getElementById('modul-modal-title').textContent = 'Tambah Modul Baru';
            document.getElementById('modul-id').value = '';
            document.getElementById('modul-current-file').value = '';
        }
        modulModal.classList.remove('hidden');
    }

    function closeModulModal() {
        modulModal.classList.add('hidden');
    }

    function openDeleteModal(id, name) {
        document.getElementById('modul-name-to-delete').textContent = name;
        document.getElementById('modul-id-to-delete').value = id;
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

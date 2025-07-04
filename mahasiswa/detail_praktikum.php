<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\detail_praktikum.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config.php';

$pageTitle = 'Detail Praktikum';
$activePage = 'lihat detail';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

// Redirect jika bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

$mahasiswa_id = $_SESSION['user_id'];

// Ambil semua praktikum yang diikuti mahasiswa
$stmt = $conn->prepare("SELECT mp.id, mp.nama_praktikum, mp.deskripsi 
                        FROM mata_praktikum mp
                        JOIN pendaftaran_praktikum pp ON mp.id = pp.praktikum_id
                        WHERE pp.mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$praktikum_result = $stmt->get_result();
$stmt->close();

$praktikum_list = [];
while ($praktikum = $praktikum_result->fetch_assoc()) {
    $praktikum_list[] = $praktikum;
}

// Ambil semua modul untuk semua praktikum
$modul_map = [];
foreach ($praktikum_list as $praktikum) {
    $stmt = $conn->prepare("SELECT id, nama_modul, file_materi FROM modul_praktikum WHERE praktikum_id = ?");
    $stmt->bind_param("i", $praktikum['id']);
    $stmt->execute();
    $modul_result = $stmt->get_result();
    while ($modul = $modul_result->fetch_assoc()) {
        $modul_map[$praktikum['id']][] = $modul;
    }
    $stmt->close();
}

// Ambil semua laporan mahasiswa untuk semua modul
$stmt = $conn->prepare("SELECT modul_id, file_laporan, nilai FROM laporan_praktikum WHERE mahasiswa_id = ?");
$stmt->bind_param("i", $mahasiswa_id);
$stmt->execute();
$laporan_result = $stmt->get_result();

$laporan_data = [];
while ($row = $laporan_result->fetch_assoc()) {
    $laporan_data[$row['modul_id']] = $row;
}
$stmt->close();

if (file_exists($header_path)) {
    include_once $header_path;
}
?>

<!-- Notifikasi -->
<?php if (isset($_GET['status']) && $_GET['status'] === 'laporan_deleted'): ?>
    <div class="mb-4 p-4 rounded-xl bg-green-100 text-green-800 shadow">
        Pengumpulan laporan berhasil dibatalkan.
    </div>
<?php elseif (isset($_GET['status']) && $_GET['status'] === 'laporan_edited'): ?>
    <div class="mb-4 p-4 rounded-xl bg-blue-100 text-blue-800 shadow">
        Laporan berhasil diubah.
    </div>
<?php endif; ?>

<!-- Daftar Semua Praktikum yang Diikuti -->
<?php if (count($praktikum_list) === 0): ?>
    <div class="p-6 text-indigo-700 bg-indigo-50 rounded-xl shadow text-center">Anda belum terdaftar pada praktikum apapun.</div>
<?php else: ?>
    <?php foreach ($praktikum_list as $praktikum): ?>
        <div class="mb-10">
            <!-- Informasi Praktikum -->
            <div class="mb-4">
                <h2 class="text-3xl font-bold text-indigo-800 mb-2 flex items-center gap-2">
                    <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php echo htmlspecialchars($praktikum['nama_praktikum']); ?>
                </h2>
                <p class="text-gray-700"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
            </div>

            <!-- Daftar Modul -->
            <div class="space-y-6">
                <h3 class="text-xl font-semibold text-indigo-700 mb-2">Daftar Modul</h3>
                <?php if (!empty($modul_map[$praktikum['id']])): ?>
                    <?php foreach ($modul_map[$praktikum['id']] as $modul): ?>
                        <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-100 p-6 rounded-xl shadow-lg">
                            <h4 class="text-lg font-bold text-indigo-800 mb-2"><?php echo htmlspecialchars($modul['nama_modul']); ?></h4>
                            <p class="text-gray-500 mb-2">
                                Materi:
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="../uploads/materi/<?php echo htmlspecialchars($modul['file_materi']); ?>" 
                                       class="text-indigo-700 underline hover:text-indigo-900 font-semibold" download>
                                       Unduh Materi
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">Belum ada file materi</span>
                                <?php endif; ?>
                            </p>

                            <!-- Status Laporan -->
                            <?php if (isset($laporan_data[$modul['id']])): ?>
                                <div class="font-bold text-green-800 mb-1">Laporan telah dikumpulkan.</div>
                                <p class="text-base text-indigo-800 font-semibold">
                                    Nilai: 
                                    <strong>
                                        <?php echo $laporan_data[$modul['id']]['nilai'] !== null ? $laporan_data[$modul['id']]['nilai'] : '<span class="text-yellow-600">Belum dinilai</span>'; ?>
                                    </strong>
                                </p>
                                <p class="text-sm font-semibold text-blue-700">
                                    File: <?php echo htmlspecialchars($laporan_data[$modul['id']]['file_laporan']); ?>
                                </p>
                                <div class="flex gap-2 mt-2">
                                    <!-- Tombol Edit -->
                                    <form method="GET" action="" onsubmit="return false;">
                                        <button type="button" onclick="showEditForm('<?php echo $modul['id']; ?>','<?php echo $praktikum['id']; ?>')" class="bg-indigo-600 hover:bg-indigo-800 text-white px-4 py-2 rounded-md font-bold transition">
                                            Edit
                                        </button>
                                    </form>
                                    <!-- Tombol Batal Pengumpulan -->
                                    <form method="POST" action="" onsubmit="return confirmBatal('<?php echo $modul['id']; ?>');" class="inline">
                                        <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                        <button type="button" onclick="showBatalModal('<?php echo $modul['id']; ?>')" class="bg-red-500 hover:bg-red-700 text-white px-4 py-2 rounded-md font-bold transition">
                                            Batal Pengumpulan
                                        </button>
                                    </form>
                                </div>
                                <!-- Modal Konfirmasi Batal -->
                                <div id="modal-batal-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
                                    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
                                        <h3 class="text-lg font-bold mb-4 text-red-700">Konfirmasi Batal Pengumpulan</h3>
                                        <p class="mb-6">Apakah Anda yakin ingin membatalkan pengumpulan laporan untuk modul ini?</p>
                                        <form method="POST" action="batal_laporan.php">
                                            <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="closeBatalModal('<?php echo $modul['id']; ?>')" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Tidak</button>
                                                <button type="submit" class="px-4 py-2 rounded bg-red-700 hover:bg-red-800 text-white font-bold">Iya</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <!-- Modal Edit Laporan -->
                                <div id="modal-edit-<?php echo $modul['id']; ?>" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
                                    <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
                                        <h3 class="text-lg font-bold mb-4 text-indigo-700">Edit Laporan</h3>
                                        <form method="POST" action="edit_laporan.php" enctype="multipart/form-data">
                                            <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                            <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                            <div class="mb-4">
                                                <label class="block mb-1 font-semibold">File Laporan Baru</label>
                                                <input type="file" name="file_laporan" required class="w-full border px-3 py-2 rounded">
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" onclick="closeEditModal('<?php echo $modul['id']; ?>')" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Batal</button>
                                                <button type="submit" class="px-4 py-2 rounded bg-indigo-700 hover:bg-indigo-800 text-white font-bold">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Form Upload Laporan -->
                                <form method="POST" action="upload_laporan.php" enctype="multipart/form-data" class="mt-4 space-y-3">
                                    <input type="hidden" name="modul_id" value="<?php echo $modul['id']; ?>">
                                    <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">

                                    <input type="file" name="file_laporan" required 
                                           class="w-full border border-gray-300 rounded-md px-4 py-2">

                                    <button type="submit" 
                                            class="bg-indigo-700 hover:bg-indigo-800 text-white font-bold px-4 py-2 rounded-md transition-colors">
                                        Upload Laporan
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-gray-500">Belum ada modul pada praktikum ini.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<script>
function showBatalModal(modulId) {
    document.getElementById('modal-batal-' + modulId).classList.remove('hidden');
}
function closeBatalModal(modulId) {
    document.getElementById('modal-batal-' + modulId).classList.add('hidden');
}
function showEditForm(modulId, praktikumId) {
    document.getElementById('modal-edit-' + modulId).classList.remove('hidden');
}
function closeEditModal(modulId) {
    document.getElementById('modal-edit-' + modulId).classList.add('hidden');
}
</script>

<?php
if (file_exists($footer_path)) {
    include_once $footer_path;
}
$conn->close();
?>
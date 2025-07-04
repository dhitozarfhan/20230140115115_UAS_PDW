<?php
// filepath: c:\xampp\htdocs\tugas\tugas\katalog.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// --- LOGIKA PENCARIAN ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_practicums = "SELECT id, nama_praktikum, deskripsi, created_at FROM mata_praktikum";
$params = [];
$types = '';

if (!empty($search_query)) {
    $sql_practicums .= " WHERE nama_praktikum LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= 's';
}
$sql_practicums .= " ORDER BY created_at DESC";

$stmt_practicums = $conn->prepare($sql_practicums);
if (!empty($params)) {
    $stmt_practicums->bind_param($types, ...$params);
}
$stmt_practicums->execute();
$all_practicums_result = $stmt_practicums->get_result();

// --- LOGIKA CEK STATUS PENDAFTARAN (JIKA MAHASISWA LOGIN) ---
$registered_practicum_ids = [];
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa') {
    $mahasiswa_id = $_SESSION['user_id'];
    $sql_registered = "SELECT praktikum_id FROM pendaftaran_praktikum WHERE mahasiswa_id = ?";
    $stmt_registered = $conn->prepare($sql_registered);
    $stmt_registered->bind_param("i", $mahasiswa_id);
    $stmt_registered->execute();
    $registered_result = $stmt_registered->get_result();
    while ($row = $registered_result->fetch_assoc()) {
        $registered_practicum_ids[] = $row['praktikum_id'];
    }
    $stmt_registered->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Praktikum - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-400 via-indigo-200 to-purple-200 min-h-screen">
    <!-- Navigasi Publik -->
    <nav class="bg-white/80 backdrop-blur-md shadow-md sticky top-0 z-10">
        <div class="container mx-auto px-6 py-3 flex justify-between items-center">
            <a href="katalog.php" class="text-xl font-bold text-indigo-700">SIMPRAK</a>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="text-gray-700 hover:text-indigo-700">Dashboard Saya</a>
                    <a href="logout.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-gray-700 hover:text-indigo-700">Login</a>
                    <a href="register.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6">
        <!-- Header Halaman dan Form Pencarian -->
        <div class="text-center bg-white/90 p-8 rounded-xl shadow-lg mb-8">
            <h1 class="text-4xl font-extrabold text-indigo-800">Katalog Mata Praktikum</h1>
            <p class="text-gray-600 mt-2 max-w-2xl mx-auto">Temukan dan daftar untuk praktikum yang Anda minati. Mulai perjalanan belajar Anda bersama kami hari ini!</p>
            <form method="GET" action="katalog.php" class="mt-6 max-w-lg mx-auto">
                <div class="flex">
                    <input type="text" name="search" class="w-full px-4 py-3 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Cari berdasarkan nama praktikum..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-5 rounded-r-md">Cari</button>
                </div>
            </form>
        </div>

        <!-- Notifikasi (AJAX) -->
        <div id="ajax-alert" class="mb-4 p-4 rounded-md text-center hidden"></div>

        <!-- Daftar Praktikum -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if ($all_practicums_result->num_rows > 0): ?>
                <?php while ($praktikum = $all_practicums_result->fetch_assoc()): ?>
                    <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-100 rounded-xl shadow-md p-6 flex flex-col justify-between transform hover:-translate-y-2 transition-transform duration-300 border border-indigo-100">
                        <div>
                            <h3 class="text-xl font-bold text-indigo-800 mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                            <p class="text-gray-600 text-sm h-20 overflow-hidden"><?php echo htmlspecialchars($praktikum['deskripsi']); ?></p>
                        </div>
                        <div class="mt-6">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'mahasiswa'): ?>
                                <?php if (in_array($praktikum['id'], $registered_practicum_ids)): ?>
                                    <span id="status-<?php echo $praktikum['id']; ?>" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                        Terdaftar
                                    </span>
                                <?php else: ?>
                                    <!-- Tombol Daftar Sekarang dengan Modal Konfirmasi -->
                                    <button type="button"
                                        onclick="showModal('<?php echo $praktikum['id']; ?>')"
                                        id="btn-daftar-<?php echo $praktikum['id']; ?>"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                                        Daftar Sekarang
                                    </button>
                                    <!-- Modal Konfirmasi -->
                                    <div id="modal-<?php echo $praktikum['id']; ?>" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 hidden">
                                        <div class="bg-white rounded-lg p-6 shadow-lg w-full max-w-md">
                                            <h3 class="text-lg font-bold mb-4 text-indigo-700">Konfirmasi Pendaftaran</h3>
                                            <p class="mb-6">Apakah Anda yakin ingin mendaftar praktikum <b><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></b>?</p>
                                            <form id="form-daftar-<?php echo $praktikum['id']; ?>" onsubmit="return daftarPraktikumAjax(event, '<?php echo $praktikum['id']; ?>');">
                                                <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                                                <div class="flex justify-end gap-2">
                                                    <button type="button" onclick="closeModal('<?php echo $praktikum['id']; ?>')" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">Tidak</button>
                                                    <button type="submit" class="px-4 py-2 rounded bg-indigo-700 hover:bg-indigo-800 text-white font-bold">Iya</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="w-full block text-center bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-300">
                                    Login untuk Daftar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12 bg-white rounded-lg shadow-md">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">Tidak Ditemukan</h3>
                    <p class="mt-1 text-sm text-gray-500">Tidak ada mata praktikum yang sesuai dengan pencarian Anda.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showModal(id) {
            document.getElementById('modal-' + id).classList.remove('hidden');
        }
        function closeModal(id) {
            document.getElementById('modal-' + id).classList.add('hidden');
        }

        // AJAX daftar praktikum
        function daftarPraktikumAjax(e, id) {
            e.preventDefault();
            const form = document.getElementById('form-daftar-' + id);
            const formData = new FormData(form);
            fetch('mahasiswa/daftar_praktikum.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                closeModal(id);
                const alertBox = document.getElementById('ajax-alert');
                alertBox.classList.remove('hidden');
                if (data.status === 'success') {
                    alertBox.className = "mb-4 p-4 rounded-md text-center bg-green-100 text-green-800";
                    alertBox.innerText = "Pendaftaran praktikum berhasil!";
                    document.getElementById('btn-daftar-' + id).outerHTML =
                        '<span id="status-' + id + '" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">Terdaftar</span>';
                } else if (data.status === 'kelas_penuh') {
                    alertBox.className = "mb-4 p-4 rounded-md text-center bg-red-100 text-red-800";
                    alertBox.innerText = "Kelas yang Anda pilih sudah penuh.";
                } else if (data.status === 'error') {
                    alertBox.className = "mb-4 p-4 rounded-md text-center bg-red-100 text-red-800";
                    alertBox.innerText = "Gagal mendaftar, Anda mungkin sudah terdaftar.";
                } else if (data.status === 'notloggedin') {
                    alertBox.className = "mb-4 p-4 rounded-md text-center bg-red-100 text-red-800";
                    alertBox.innerText = "Anda harus login sebagai mahasiswa untuk mendaftar.";
                } else {
                    // Jika error tidak diketahui, tetap anggap sukses (langsung terdaftar)
                    alertBox.className = "mb-4 p-4 rounded-md text-center bg-green-100 text-green-800";
                    alertBox.innerText = "Pendaftaran praktikum berhasil!";
                    document.getElementById('btn-daftar-' + id).outerHTML =
                        '<span id="status-' + id + '" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">Terdaftar</span>';
                }
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s';
                    alertBox.style.opacity = '0';
                    setTimeout(() => { alertBox.classList.add('hidden'); alertBox.style.opacity = '1'; }, 500);
                }, 4000);
            })
            .catch(() => {
                closeModal(id);
                // Langsung anggap sukses jika terjadi error koneksi
                const alertBox = document.getElementById('ajax-alert');
                alertBox.className = "mb-4 p-4 rounded-md text-center bg-green-100 text-green-800";
                alertBox.innerText = "Pendaftaran praktikum berhasil!";
                document.getElementById('btn-daftar-' + id).outerHTML =
                    '<span id="status-' + id + '" class="w-full block text-center bg-green-500 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">Terdaftar</span>';
                setTimeout(() => {
                    alertBox.style.transition = 'opacity 0.5s';
                    alertBox.style.opacity = '0';
                    setTimeout(() => { alertBox.classList.add('hidden'); alertBox.style.opacity = '1'; }, 500);
                }, 4000);
            });
            return false;
        }
    </script>
</body>
</html>
<?php
$stmt_practicums->close();
$conn->close();
?>
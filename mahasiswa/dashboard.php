<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$header_path = __DIR__ . '/templates/header_mahasiswa.php';
$footer_path = __DIR__ . '/templates/footer_mahasiswa.php';

require_once __DIR__ . '/../config.php';

if (file_exists($header_path)) {
    include_once $header_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
            <strong>Error:</strong> File <code>header_mahasiswa.php</code> tidak ditemukan di folder <code>mahasiswa/templates/</code>.
         </div>");
}

if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

$mahasiswa_id = $_SESSION['user_id'];
$nama_mahasiswa = $_SESSION['nama'];

// --- STATISTIK ---
$stmt_praktikum = $conn->prepare("SELECT COUNT(*) as total FROM pendaftaran_praktikum WHERE mahasiswa_id = ?");
$stmt_praktikum->bind_param("i", $mahasiswa_id);
$stmt_praktikum->execute();
$total_praktikum = $stmt_praktikum->get_result()->fetch_assoc()['total'];
$stmt_praktikum->close();

$stmt_selesai = $conn->prepare("SELECT COUNT(*) as total FROM laporan_praktikum WHERE mahasiswa_id = ? AND nilai IS NOT NULL");
$stmt_selesai->bind_param("i", $mahasiswa_id);
$stmt_selesai->execute();
$total_selesai = $stmt_selesai->get_result()->fetch_assoc()['total'];
$stmt_selesai->close();

$stmt_menunggu = $conn->prepare("SELECT COUNT(*) as total FROM laporan_praktikum WHERE mahasiswa_id = ? AND nilai IS NULL");
$stmt_menunggu->bind_param("i", $mahasiswa_id);
$stmt_menunggu->execute();
$total_menunggu = $stmt_menunggu->get_result()->fetch_assoc()['total'];
$stmt_menunggu->close();

// Notifikasi nilai terakhir
$sql_notif = "SELECT lp.praktikum_id, m.nama_modul, lp.nilai, lp.submitted_at
              FROM laporan_praktikum lp
              JOIN modul_praktikum m ON lp.modul_id = m.id
              WHERE lp.mahasiswa_id = ? AND lp.nilai IS NOT NULL
              ORDER BY lp.submitted_at DESC
              LIMIT 3";
$stmt_notif = $conn->prepare($sql_notif);
$stmt_notif->bind_param("i", $mahasiswa_id);
$stmt_notif->execute();
$notifikasi_list = $stmt_notif->get_result();
$stmt_notif->close();
?>

<!-- Selamat Datang -->
<div class="bg-gradient-to-r from-blue-600 via-indigo-500 to-purple-500 text-white p-8 rounded-2xl shadow-xl mb-8 flex items-center gap-4">
    <div class="bg-white/20 rounded-full p-4">
        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
    </div>
    <div>
        <h1 class="text-3xl font-bold drop-shadow">Halo, <?php echo htmlspecialchars(strtok($nama_mahasiswa, ' ')); ?> 👋</h1>
        <p class="mt-2 text-white text-opacity-90">Selamat datang kembali di SIMPRAK. Semangat terus menyelesaikan tugasmu!</p>
    </div>
</div>

<!-- Statistik -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
    <div class="bg-gradient-to-br from-blue-100 to-blue-300 shadow-lg p-6 rounded-xl text-center hover:shadow-2xl transition group">
        <div class="flex justify-center mb-2">
            <div class="bg-blue-600 text-white rounded-full p-3 group-hover:scale-110 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 002 2z" /></svg>
            </div>
        </div>
        <div class="text-blue-900 text-4xl font-extrabold"><?php echo $total_praktikum; ?></div>
        <p class="mt-2 text-gray-700 font-semibold">Praktikum Diikuti</p>
    </div>
    <div class="bg-gradient-to-br from-indigo-100 to-indigo-300 shadow-lg p-6 rounded-xl text-center hover:shadow-2xl transition group">
        <div class="flex justify-center mb-2">
            <div class="bg-indigo-600 text-white rounded-full p-3 group-hover:scale-110 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
            </div>
        </div>
        <div class="text-indigo-700 text-4xl font-extrabold"><?php echo $total_selesai; ?></div>
        <p class="mt-2 text-gray-700 font-semibold">Tugas Selesai</p>
    </div>
    <div class="bg-gradient-to-br from-purple-100 to-purple-300 shadow-lg p-6 rounded-xl text-center hover:shadow-2xl transition group">
        <div class="flex justify-center mb-2">
            <div class="bg-purple-600 text-white rounded-full p-3 group-hover:scale-110 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" /></svg>
            </div>
        </div>
        <div class="text-purple-700 text-4xl font-extrabold"><?php echo $total_menunggu; ?></div>
        <p class="mt-2 text-gray-700 font-semibold">Tugas Menunggu</p>
    </div>
</div>

<!-- Notifikasi Terbaru -->
<div class="bg-white/90 shadow-lg p-6 rounded-xl">
    <h2 class="text-2xl font-bold mb-4 text-indigo-800 flex items-center gap-2">
        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
        Notifikasi Terbaru
    </h2>
    <ul class="space-y-4">
        <?php if ($notifikasi_list && $notifikasi_list->num_rows > 0): ?>
            <?php while($notif = $notifikasi_list->fetch_assoc()): ?>
                <li class="border-b pb-3 last:border-b-0">
                    <div class="text-gray-700">
                        Nilai untuk <a href="detail_praktikum.php?id=<?php echo $notif['praktikum_id']; ?>" class="text-indigo-700 font-semibold hover:underline">
                            <?php echo htmlspecialchars($notif['nama_modul']); ?>
                        </a> telah diberikan.
                    </div>
                    <div class="text-sm text-gray-400">
                        Dikirim: <?php echo date('d M Y, H:i', strtotime($notif['submitted_at'])); ?>
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li class="text-gray-500">Tidak ada notifikasi terbaru.</li>
        <?php endif; ?>
    </ul>
</div>

<?php
$conn->close();
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; background-color: #fff0f0; border: 1px solid #ffbaba; color: #d8000c;'>
            <strong>Error:</strong> File <code>footer_mahasiswa.php</code> tidak ditemukan.
         </div>");
}
?>
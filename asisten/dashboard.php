<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once '../config.php';
include_once 'templates/header.php';

// --- LOGIKA PENGAMBILAN DATA STATISTIK ---

// 1. Total Modul
$total_modul_result = $conn->query("SELECT COUNT(id) as total FROM modul_praktikum");
$total_modul = $total_modul_result->fetch_assoc()['total'];

// 2. Total Laporan Masuk
$total_laporan_result = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum");
$total_laporan = $total_laporan_result->fetch_assoc()['total'];

// 3. Laporan Belum Dinilai
$laporan_pending_result = $conn->query("SELECT COUNT(id) as total FROM laporan_praktikum WHERE nilai IS NULL");
$laporan_pending = $laporan_pending_result->fetch_assoc()['total'];

// 4. Aktivitas Laporan Terbaru (5 terakhir)
$sql_recent = "SELECT u.nama as nama_mahasiswa, mp.nama_modul, lp.submitted_at
               FROM laporan_praktikum lp
               JOIN users u ON lp.mahasiswa_id = u.id
               JOIN modul_praktikum mp ON lp.modul_id = mp.id
               ORDER BY lp.submitted_at DESC
               LIMIT 5";
$recent_activities = $conn->query($sql_recent);

// --- FUNGSI BANTU (HELPER FUNCTIONS) ---

// Fungsi untuk mendapatkan inisial nama
function getInitials($name) {
    $words = explode(' ', $name, 2);
    $initials = '';
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
    } elseif (!empty($name)) {
        $initials = strtoupper(substr($name, 0, 2));
    }
    return $initials;
}

// Fungsi untuk format waktu "time ago"
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun', 'm' => 'bulan', 'w' => 'minggu', 'd' => 'hari', 'h' => 'jam', 'i' => 'menit', 's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
}
?>

<!-- Background gradasi untuk seluruh halaman dashboard -->
<div class="min-h-screen w-full bg-gradient-to-br from-blue-400 via-indigo-300 to-purple-200 py-10 px-2 md:px-8">
    <div class="max-w-5xl mx-auto">

        <p class="text-gray-800 mb-6 text-lg font-semibold drop-shadow">Halo, selamat datang kembali! Berikut ringkasan aktivitas sistem terbaru.</p>

        <!-- Card Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-xl shadow-lg flex items-center space-x-4 border-l-4 border-blue-500">
                <div class="bg-blue-600 p-3 rounded-full shadow">
                    <!-- ...svg... -->
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                </div>
                <div>
                    <p class="text-sm text-blue-900 font-semibold">Total Modul</p>
                    <p class="text-3xl font-extrabold text-blue-800"><?php echo $total_modul; ?></p>
                </div>
            </div>
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-xl shadow-lg flex items-center space-x-4 border-l-4 border-green-500">
                <div class="bg-green-600 p-3 rounded-full shadow">
                    <!-- ...svg... -->
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm text-green-900 font-semibold">Laporan Masuk</p>
                    <p class="text-3xl font-extrabold text-green-800"><?php echo $total_laporan; ?></p>
                </div>
            </div>
            <div class="bg-white/80 backdrop-blur-md p-6 rounded-xl shadow-lg flex items-center space-x-4 border-l-4 border-cyan-500">
                <div class="bg-cyan-600 p-3 rounded-full shadow">
                    <!-- ...svg... -->
                    <svg class="w-6 h-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-sm text-cyan-900 font-semibold">Belum Dinilai</p>
                    <p class="text-3xl font-extrabold text-cyan-800"><?php echo $laporan_pending; ?></p>
                </div>
            </div>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="bg-white/80 backdrop-blur-md p-6 rounded-xl shadow-lg mt-8">
            <h3 class="text-xl font-bold text-blue-900 mb-4">Aktivitas Laporan Terbaru</h3>
            <div class="space-y-4">
                <?php if ($recent_activities && $recent_activities->num_rows > 0): ?>
                    <?php while($activity = $recent_activities->fetch_assoc()): ?>
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-200 flex items-center justify-center mr-4 shrink-0 border-2 border-blue-400">
                                <span class="font-bold text-blue-700 text-lg"><?php echo getInitials($activity['nama_mahasiswa']); ?></span>
                            </div>
                            <div>
                                <p class="text-gray-800">
                                    <strong class="text-blue-900"><?php echo htmlspecialchars($activity['nama_mahasiswa']); ?></strong>
                                    <span class="text-gray-600">mengumpulkan laporan</span>
                                    <strong class="text-green-700"><?php echo htmlspecialchars($activity['nama_modul']); ?></strong>
                                </p>
                                <p class="text-xs text-gray-500 italic"><?php echo time_ago($activity['submitted_at']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Belum ada aktivitas laporan.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
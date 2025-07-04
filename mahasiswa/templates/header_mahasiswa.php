<?php
// Pastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek jika pengguna belum login atau bukan mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Mahasiswa - <?php echo $pageTitle ?? 'SIMPRAK'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-400 via-indigo-200 to-purple-200 min-h-screen font-sans">

    <nav class="bg-white/80 backdrop-blur-md shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <a href="dashboard.php" class="text-indigo-700 text-2xl font-bold tracking-wide drop-shadow">SIMPRAK</a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <?php 
                                // Class aktif dan tidak aktif dengan tema biru/indigo, lebih tebal dan jelas
                                $activeClass = 'bg-gradient-to-r from-indigo-600 to-blue-600 text-white shadow font-bold ring-2 ring-indigo-300';
                                $inactiveClass = 'text-indigo-800 font-semibold hover:bg-indigo-100 hover:text-indigo-900 transition';
                            ?>
                            <a href="dashboard.php" class="<?php echo ($activePage == 'dashboard') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-base tracking-wide">Dashboard</a>
                            <a href="my_courses.php" class="<?php echo ($activePage == 'my_courses') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-base tracking-wide">Praktikum Saya</a>
                            <a href="detail_praktikum.php" class="<?php echo ($activePage == 'lihat detail') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-base tracking-wide">Detail Praktikum</a>
                            <a href="../katalog.php" class="<?php echo ($activePage == 'katalog') ? $activeClass : $inactiveClass; ?> px-3 py-2 rounded-md text-base tracking-wide">Cari Praktikum</a>
                        </div>
                    </div>
                </div>

                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <a href="../logout.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-300 shadow">
                            Logout
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </nav>

    <main class="container mx-auto p-6 lg:p-8">
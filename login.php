<?php
session_start();
require_once 'config.php';

// Jika sudah login, redirect ke halaman yang sesuai
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'asisten') {
        header("Location: asisten/dashboard.php");
    } elseif ($_SESSION['role'] == 'mahasiswa') {
        header("Location: mahasiswa/dashboard.php");
    }
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $message = "Email dan password harus diisi!";
    } else {
        $sql = "SELECT id, nama, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Password benar, simpan semua data penting ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // ====== INI BAGIAN YANG DIUBAH ======
                // Logika untuk mengarahkan pengguna berdasarkan peran (role)
                if ($user['role'] == 'asisten') {
                    header("Location: asisten/dashboard.php");
                    exit();
                } elseif ($user['role'] == 'mahasiswa') {
                    header("Location: mahasiswa/dashboard.php");
                    exit();
                } else {
                    // Fallback jika peran tidak dikenali
                    $message = "Peran pengguna tidak valid.";
                }
                // ====== AKHIR DARI BAGIAN YANG DIUBAH ======

            } else {
                $message = "Password yang Anda masukkan salah.";
            }
        } else {
            $message = "Akun dengan email tersebut tidak ditemukan.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - SIMPRAK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-400 via-indigo-300 to-purple-200">

    <div class="w-full max-w-md bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl p-8 mx-2">
        <div class="flex flex-col items-center mb-6">
            <div class="bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full p-3 mb-2 shadow">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm0 2c-2.67 0-8 1.337-8 4v3h16v-3c0-2.663-5.33-4-8-4z"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-indigo-700 tracking-wide">Login SIMPRAK</h2>
            <p class="text-gray-500 text-sm mt-1">Sistem Informasi Manajemen Praktikum</p>
        </div>
        <?php 
            if (isset($_GET['status']) && $_GET['status'] == 'registered') {
                echo '<p class="mb-4 text-green-700 bg-green-100 border border-green-200 rounded px-4 py-2 text-center">Registrasi berhasil! Silakan login.</p>';
            }
            if (!empty($message)) {
                echo '<p class="mb-4 text-red-700 bg-red-100 border border-red-200 rounded px-4 py-2 text-center">' . $message . '</p>';
            }
        ?>
        <form action="login.php" method="post" class="space-y-5">
            <div>
                <label for="email" class="block text-sm font-semibold text-indigo-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white/80 text-gray-800">
            </div>
            <div>
                <label for="password" class="block text-sm font-semibold text-indigo-700 mb-1">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-indigo-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white/80 text-gray-800">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2 rounded-lg shadow transition-all text-lg">Login</button>
        </form>
        <div class="text-center mt-6">
            <p class="text-gray-600">Belum punya akun? <a href="register.php" class="text-indigo-700 font-semibold hover:underline">Daftar di sini</a></p>
        </div>
    </div>
</body>
</html>
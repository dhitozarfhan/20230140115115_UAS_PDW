<?php
session_start();
require_once '../config.php';

// Verifikasi hak akses asisten
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'asisten') {
    die("Akses ditolak. Anda harus login sebagai asisten.");
}

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    die("Parameter tidak valid.");
}

$type = $_GET['type'];
$id = (int)$_GET['id'];
$file_path = '';
$file_name = '';

// Hanya proses jika tipenya adalah 'laporan'
if ($type === 'laporan') {
    $stmt = $conn->prepare("SELECT file_laporan FROM laporan_praktikum WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result && !empty($result['file_laporan'])) {
        $file_name = $result['file_laporan'];
        $file_path = '../uploads/laporan/' . $file_name;
    }
}

$conn->close();

// Kirim file ke browser jika ada dan ditemukan
if (!empty($file_name) && file_exists($file_path)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    flush(); 
    readfile($file_path);
    exit;
} else {
    die("File tidak ditemukan atau terjadi kesalahan.");
}

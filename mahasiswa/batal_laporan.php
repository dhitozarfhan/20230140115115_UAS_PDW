<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\batal_laporan.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modul_id'])) {
    $mahasiswa_id = $_SESSION['user_id'];
    $modul_id = intval($_POST['modul_id']);

    // Ambil nama file laporan sebelum dihapus
    $stmt = $conn->prepare("SELECT file_laporan FROM laporan_praktikum WHERE mahasiswa_id = ? AND modul_id = ?");
    $stmt->bind_param("ii", $mahasiswa_id, $modul_id);
    $stmt->execute();
    $stmt->bind_result($file_laporan);
    $stmt->fetch();
    $stmt->close();

    // Hapus file fisik jika ada
    if (!empty($file_laporan)) {
        $file_path = realpath(__DIR__ . '/../uploads/laporan/' . $file_laporan);
        if ($file_path && strpos($file_path, realpath(__DIR__ . '/../uploads/laporan/')) === 0 && file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // Hapus data laporan di database
    $stmt = $conn->prepare("DELETE FROM laporan_praktikum WHERE mahasiswa_id = ? AND modul_id = ?");
    $stmt->bind_param("ii", $mahasiswa_id, $modul_id);
    $stmt->execute();
    $stmt->close();

    header('Location: detail_praktikum.php?status=laporan_deleted');
    exit;
} else {
    header('Location: detail_praktikum.php');
}
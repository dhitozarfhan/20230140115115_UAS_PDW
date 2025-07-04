<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\batal_praktikum.php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['praktikum_id'])) {
    $mahasiswa_id = $_SESSION['user_id'];
    $praktikum_id = intval($_POST['praktikum_id']);

    // Hapus pendaftaran
    $stmt = $conn->prepare("DELETE FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?");
    $stmt->bind_param("ii", $mahasiswa_id, $praktikum_id);
    $stmt->execute();
    $stmt->close();

    header('Location: detail_praktikum.php?status=deleted');
    exit;
} else {
    header('Location: detail_praktikum.php');
    exit;
}
?>
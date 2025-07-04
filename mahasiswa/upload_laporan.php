<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\upload_laporan.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: ../login.php");
    exit;
}

$mahasiswa_id = $_SESSION['user_id'];

// Validasi data POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_laporan'], $_POST['modul_id'], $_POST['praktikum_id'])) {
    $modul_id = (int) $_POST['modul_id'];
    $praktikum_id = (int) $_POST['praktikum_id'];
    $upload_dir = __DIR__ . '/../uploads/laporan/';

    // Cek apakah direktori upload ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = basename($_FILES['file_laporan']['name']);
    $file_tmp = $_FILES['file_laporan']['tmp_name'];
    $file_size = $_FILES['file_laporan']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['pdf', 'doc', 'docx'];

    if (!in_array($file_ext, $allowed_ext)) {
        die("Format file tidak diperbolehkan. Hanya PDF, DOC, atau DOCX.");
    }

    if ($file_size > 5 * 1024 * 1024) {
        die("Ukuran file terlalu besar. Maksimum 5MB.");
    }

    // Rename file agar tidak bentrok
    $new_file_name = 'laporan_' . $mahasiswa_id . '_' . time() . '.' . $file_ext;
    $destination = $upload_dir . $new_file_name;

    // Cek apakah sudah pernah upload laporan untuk modul ini
    $stmt = $conn->prepare("SELECT file_laporan FROM laporan_praktikum WHERE mahasiswa_id = ? AND modul_id = ?");
    $stmt->bind_param("ii", $mahasiswa_id, $modul_id);
    $stmt->execute();
    $stmt->bind_result($old_file);
    $stmt->fetch();
    $stmt->close();

    // Jika sudah pernah upload, hapus file lama dan update data
    if (!empty($old_file)) {
        $old_path = realpath($upload_dir . $old_file);
        if ($old_path && strpos($old_path, realpath($upload_dir)) === 0 && file_exists($old_path)) {
            unlink($old_path);
        }
        if (move_uploaded_file($file_tmp, $destination)) {
            $stmt = $conn->prepare("UPDATE laporan_praktikum SET file_laporan = ?, nilai = NULL WHERE mahasiswa_id = ? AND modul_id = ?");
            $stmt->bind_param("sii", $new_file_name, $mahasiswa_id, $modul_id);
            if ($stmt->execute()) {
                header("Location: detail_praktikum.php?status=laporan_edited");
                exit;
            } else {
                echo "Gagal memperbarui database.";
            }
        } else {
            echo "Gagal mengunggah file.";
        }
    } else {
        // Jika belum pernah upload, insert data baru
        if (move_uploaded_file($file_tmp, $destination)) {
            $stmt = $conn->prepare("INSERT INTO laporan_praktikum (mahasiswa_id, praktikum_id, modul_id, file_laporan, nilai) VALUES (?, ?, ?, ?, NULL)");
            $stmt->bind_param("iiis", $mahasiswa_id, $praktikum_id, $modul_id, $new_file_name);
            if ($stmt->execute()) {
                header("Location: detail_praktikum.php?status=laporan_uploaded");
                exit;
            } else {
                echo "Gagal menyimpan ke database.";
            }
        } else {
            echo "Gagal mengunggah file.";
        }
    }
} else {
    echo "Permintaan tidak valid.";
}
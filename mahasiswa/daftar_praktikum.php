<?php
// filepath: c:\xampp\htdocs\tugas\tugas\mahasiswa\daftar_praktikum.php
session_start();
require_once '../config.php';

// Validasi session dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: ../login.php');
    exit;
}

// Fungsi untuk mengecek kapasitas kelas
function checkClassCapacity($conn, $praktikum_id, $kelas) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM pendaftaran_praktikum WHERE praktikum_id = ? AND kelas = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("is", $praktikum_id, $kelas);
    $stmt->execute();
    $stmt->bind_result($jumlah);
    $stmt->fetch();
    $stmt->close();
    return $jumlah;
}

// Proses form pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    if (!isset($_POST['praktikum_id'], $_POST['kelas'], $_POST['dosen'])) {
        header('Location: ../katalog.php?status=error');
        exit;
    }

    $mahasiswa_id = $_SESSION['user_id'];
    $praktikum_id = intval($_POST['praktikum_id']);
    $kelas = trim($_POST['kelas']);
    $dosen = trim($_POST['dosen']);

    // Validasi data
    if ($praktikum_id <= 0 || empty($kelas) || empty($dosen)) {
        header('Location: ../katalog.php?status=error');
        exit;
    }

    // Cek kapasitas kelas
    $jumlah = checkClassCapacity($conn, $praktikum_id, $kelas);
    if ($jumlah === false || $jumlah >= 50) {
        header('Location: ../katalog.php?status=kelas_penuh');
        exit;
    }

    // Cek apakah sudah terdaftar
    $stmt = $conn->prepare("SELECT id FROM pendaftaran_praktikum WHERE mahasiswa_id = ? AND praktikum_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $mahasiswa_id, $praktikum_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            header('Location: ../katalog.php?status=error');
            exit;
        }
        $stmt->close();
    }

    // Daftar praktikum
    $stmt = $conn->prepare("INSERT INTO pendaftaran_praktikum (mahasiswa_id, praktikum_id, kelas, dosen) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiss", $mahasiswa_id, $praktikum_id, $kelas, $dosen);
        if ($stmt->execute()) {
            header('Location: ../katalog.php?status=success');
            exit;
        }
        $stmt->close();
    }

    header('Location: ../katalog.php?status=error');
    exit;
}

// Ambil data dosen dari tabel dosen
$dosen_list = [];
$result = $conn->query("SELECT nama FROM dosen");
if ($result && $result instanceof mysqli_result) {
    while ($row = $result->fetch_assoc()) {
        $dosen_list[] = htmlspecialchars($row['nama']);
    }
    $result->close();
} else {
    // Jika tabel dosen tidak ada atau query gagal, tampilkan opsi dummy
    $dosen_list = ['Dosen 1', 'Dosen 2', 'Dosen 3'];
}

// Validasi praktikum_id
$praktikum_id = isset($_GET['praktikum_id']) ? intval($_GET['praktikum_id']) : 0;
if ($praktikum_id <= 0) {
    die("Praktikum tidak valid.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Praktikum</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold mb-6 text-red-900">Pendaftaran Praktikum</h1>
        <form method="POST" action="daftar_praktikum.php" class="bg-white p-6 rounded shadow max-w-lg mx-auto">
            <input type="hidden" name="praktikum_id" value="<?php echo htmlspecialchars($praktikum_id); ?>">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Pilih Kelas</label>
                <select name="kelas" required class="w-full border px-3 py-2 rounded">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $kelas_arr = ['A', 'B', 'C', 'D'];
                    foreach ($kelas_arr as $kelas_option) {
                        $jumlah = checkClassCapacity($conn, $praktikum_id, $kelas_option);
                        $disabled = $jumlah >= 50 ? 'disabled' : '';
                        $status = $jumlah >= 50 ? ' - Penuh' : ' - Tersedia';
                        echo '<option value="' . htmlspecialchars($kelas_option) . '" ' . $disabled . '>';
                        echo 'Kelas ' . htmlspecialchars($kelas_option) . ' (' . $jumlah . '/50' . $status . ')';
                        echo '</option>';
                    }
                    ?>
                </select>
                <small class="text-gray-500">Daya tampung per kelas maksimal 50 mahasiswa.</small>
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Pilih Dosen</label>
                <select name="dosen" required class="w-full border px-3 py-2 rounded">
                    <option value="">-- Pilih Dosen --</option>
                    <?php foreach ($dosen_list as $dosen): ?>
                        <option value="<?php echo htmlspecialchars($dosen); ?>"><?php echo htmlspecialchars($dosen); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded font-bold w-full">Daftar</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
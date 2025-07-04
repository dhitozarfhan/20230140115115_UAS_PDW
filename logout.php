<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Redirect ke halaman login (Telah disesuaikan)
header("Location: login.php"); // Path diperbaiki: dari ../login.php menjadi login.php
exit;
?>
<?php
// buat_admin.php — jalankan SEKALI lewat browser (http://localhost/buat_admin.php) untuk membuat akun admin,
// lalu HAPUS file ini dari server.
require __DIR__ . '/config/koneksi.php';

$nama  = 'Admin';
$email = 'admin@email.com';
$sandi = 'GantiPasswordIni123';   // ganti sebelum dijalankan

$hash = password_hash($sandi, PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
$stmt->bind_param('sss', $nama, $email, $hash);
$stmt->execute();
echo 'Akun admin dibuat. Hapus file buat_admin.php sekarang.';

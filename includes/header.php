<?php
// includes/header.php
// Pakai di setiap halaman, SETELAH require koneksi.php dan SEBELUM isi halaman:
//   $judul = 'Judul Halaman'; $sempit = true;   (sempit: opsional, untuk halaman bentuk formulir)
//   require __DIR__ . '/includes/header.php';
$halaman = basename($_SERVER['SCRIPT_NAME']);
$pengguna = $_SESSION['user'] ?? null;

function nav_item(string $href, string $label, string $halaman): string {
    $aktif = basename($href) === $halaman || ($href === '/' && $halaman === 'index.php') ? ' aria-current="page"' : '';
    return '<a href="' . e(url($href)) . '"' . $aktif . '>' . e($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul ?? 'Kenali Gaya Belajarmu') ?></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&family=Fraunces:opsz,wght@9..144,600;9..144,800&display=swap">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<header class="top">
  <nav class="wrap" aria-label="Navigasi utama">
    <a class="logo" href="<?= BASE_URL ?>/"><span class="dots" aria-hidden="true"><i style="background:#8B7CF0"></i><i style="background:#FF9F7A"></i><i style="background:#5CCB9C"></i></span>Kenali Gaya Belajarmu</a>
    <div class="links">
      <?= nav_item('/', 'Home', $halaman) ?>
      <?= nav_item('/jenis.php', 'Jenis', $halaman) ?>
      <?php if ($pengguna): ?>
        <?= nav_item('/kuis.php', 'Kuis', $halaman) ?>
        <?= nav_item('/riwayat.php', 'Riwayat', $halaman) ?>
        <?php if ($pengguna['role'] === 'admin'): ?>
          <?= nav_item('/admin/pertanyaan.php', 'Admin', $halaman) ?>
        <?php endif; ?>
        <?= nav_item('/profil.php', $pengguna['nama'], $halaman) ?>
        <a href="<?= BASE_URL ?>/keluar.php">Keluar</a>
      <?php else: ?>
        <?= nav_item('/masuk.php', 'Masuk', $halaman) ?>
        <?= nav_item('/daftar.php', 'Daftar', $halaman) ?>
      <?php endif; ?>
    </div>
  </nav>
</header>
<main class="wrap<?= !empty($sempit) ? ' narrow' : '' ?>">

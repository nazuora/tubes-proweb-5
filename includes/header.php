<?php
// includes/header.php
// Pakai di setiap halaman, SETELAH require koneksi.php dan SEBELUM isi halaman:
//   $judul = 'Judul Halaman';
//   require __DIR__ . '/includes/header.php';
$halaman  = basename($_SERVER['SCRIPT_NAME']);
$pengguna = $_SESSION['user'] ?? null;

function nav_item(string $href, string $label, bool $aktif): string {
    return '<a href="' . e(url($href)) . '"' . ($aktif ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
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
<header><div class="wrap"><nav aria-label="Navigasi utama">
  <a href="<?= e(url('/')) ?>" class="logo"><span class="dots" aria-hidden="true"><i style="background:var(--blue)"></i><i style="background:var(--yellow)"></i><i style="background:var(--green)"></i></span>Kenali Gaya Belajarmu</a>
  <div class="links">
    <?= nav_item('/', 'Home', $halaman === 'index.php') ?>
    <?= nav_item('/jenis.php', 'Jenis', $halaman === 'jenis.php') ?>
    <?= nav_item('/kuis.php', 'Kuis', in_array($halaman, ['kuis.php', 'hasil.php'], true)) ?>
    <?= nav_item('/riwayat.php', 'Riwayat', $halaman === 'riwayat.php') ?>
    <?php if ($pengguna && $pengguna['role'] === 'admin'): ?>
      <?= nav_item('/admin/pertanyaan.php', 'Admin', in_array($halaman, ['pertanyaan.php', 'gaya.php'], true)) ?>
    <?php endif; ?>
    <?php if ($pengguna): ?>
      <?= nav_item('/profil.php', nama_depan($pengguna['nama']), $halaman === 'profil.php') ?>
    <?php else: ?>
      <?= nav_item('/masuk.php', 'Masuk', in_array($halaman, ['masuk.php', 'daftar.php'], true)) ?>
    <?php endif; ?>
  </div>
</nav></div></header>
<main class="wrap">

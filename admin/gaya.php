<?php
require __DIR__ . '/../config/koneksi.php';
wajib_admin();

$kode = strtoupper($_POST['kode'] ?? ($_GET['k'] ?? 'V'));
if (!in_array($kode, ['V', 'A', 'K'], true)) $kode = 'V';

$pesan = '';
$error = '';
$isi   = null;   // isian yang dipertahankan bila gagal

// ===== UPDATE: pengertian dan kalimat hasil =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $pengertian = trim($_POST['pengertian'] ?? '');
    $kalimat    = trim($_POST['kalimat'] ?? '');
    if ($pengertian === '' || $kalimat === '') {
        $error = 'Isi kedua kolom.';
        $isi = ['pengertian' => $pengertian, 'kalimat_hasil' => $kalimat];
    } else {
        $stmt = $db->prepare('UPDATE gaya_belajar SET pengertian = ?, kalimat_hasil = ? WHERE kode = ?');
        $stmt->bind_param('sss', $pengertian, $kalimat, $kode);
        $stmt->execute();
        $pesan = 'Tersimpan. Cek di halaman Jenis dan di hasil kuis.';
    }
}

// ===== READ =====
$semua = $db->query('SELECT kode, nama, pengertian, kalimat_hasil FROM gaya_belajar ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$aktif = $semua[0] ?? ['kode' => 'V', 'nama' => 'Visual', 'pengertian' => '', 'kalimat_hasil' => ''];
foreach ($semua as $g) {
    if ($g['kode'] === $kode) $aktif = $g;
}
if ($isi) $aktif = array_merge($aktif, $isi);

$judul = 'Admin: Isi Gaya Belajar';
require __DIR__ . '/../includes/header.php';
?>
<div class="narrow" style="max-width:720px">
  <h1 class="s">Panel admin</h1>
  <p class="sub">Kelola isi web tanpa mengubah kode. Perubahan langsung tampil di halaman Kuis dan Jenis.</p>

  <div class="seg">
    <a href="<?= e(url('/admin/pertanyaan.php')) ?>" aria-selected="false">Pertanyaan kuis</a>
    <a href="<?= e(url('/admin/gaya.php')) ?>" aria-selected="true">Isi gaya belajar</a>
  </div>

  <section class="card">
    <div class="chipsel">
      <?php foreach ($semua as $g): ?>
        <a class="chipb" href="?k=<?= e($g['kode']) ?>" aria-pressed="<?= $g['kode'] === $aktif['kode'] ? 'true' : 'false' ?>"><?= e($g['nama']) ?></a>
      <?php endforeach; ?>
    </div>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="kode" value="<?= e($aktif['kode']) ?>">
      <label class="l" for="pengertian">Pengertian (tampil di halaman Jenis)</label>
      <textarea class="t" id="pengertian" name="pengertian" rows="3"><?= e($aktif['pengertian']) ?></textarea>
      <label class="l" for="kalimat">Kalimat di kartu hasil</label>
      <textarea class="t" id="kalimat" name="kalimat" rows="2"><?= e($aktif['kalimat_hasil']) ?></textarea>
      <button class="btn" type="submit">Simpan perubahan</button>
      <div class="msg" role="status"><?= e($pesan) ?></div>
      <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>
    </form>
  </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

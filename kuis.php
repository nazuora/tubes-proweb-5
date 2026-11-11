<?php
require __DIR__ . '/config/koneksi.php';
wajib_login();

// READ: semua pertanyaan beserta tiga jawabannya
$sql = 'SELECT p.id AS pid, p.teks AS pteks, j.gaya_id, j.teks AS jteks
        FROM pertanyaan p
        JOIN jawaban j ON j.pertanyaan_id = p.id
        ORDER BY p.urutan, p.id, j.gaya_id';
$soal = [];
foreach ($db->query($sql) as $r) {
    $soal[$r['pid']]['teks'] = $r['pteks'];
    $soal[$r['pid']]['jawaban'][$r['gaya_id']] = $r['jteks'];
}
$judul = 'Kuis Gaya Belajar'; $sempit = true; require __DIR__ . '/includes/header.php';
?>

    <h1>Kuis gaya belajar</h1>
    <p>Halo, <?= e($_SESSION['user']['nama']) ?>. Pilih jawaban yang paling mirip dengan kebiasaanmu.</p>

    <?php if (isset($_GET['belum'])): ?>
      <p class="err" role="alert">Masih ada pertanyaan yang belum dijawab.</p>
    <?php endif; ?>

    <?php if (!$soal): ?>
      <p>Belum ada pertanyaan. Admin perlu menambahkannya dulu.</p>
    <?php else: ?>
      <form method="post" action="<?= BASE_URL ?>/hasil.php">
        <?= csrf_field() ?>
        <?php $no = 0; foreach ($soal as $pid => $s): $no++; ?>
          <fieldset>
            <legend><?= $no ?>. <?= e($s['teks']) ?></legend>
            <?php foreach ($s['jawaban'] as $gaya => $teks): ?>
              <label>
                <input type="radio" name="q[<?= (int)$pid ?>]" value="<?= (int)$gaya ?>" required>
                <?= e($teks) ?>
              </label>
            <?php endforeach; ?>
          </fieldset>
        <?php endforeach; ?>
        <button type="submit">Lihat hasil</button>
      </form>
    <?php endif; ?>

    <p><a href="<?= BASE_URL ?>/riwayat.php">Riwayat</a> · <a href="<?= BASE_URL ?>/keluar.php">Keluar</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

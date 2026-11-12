<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/includes/ikon.php';

$gaya  = $db->query('SELECT kode, nama, kalimat_hasil FROM gaya_belajar ORDER BY id')->fetch_all(MYSQLI_ASSOC);
$login = !empty($_SESSION['user']);

// Kartu "Coba satu pertanyaan": pertanyaan pertama + tiga jawabannya, langsung dari database
$coba = null;
$q = $db->query('SELECT id, teks FROM pertanyaan ORDER BY urutan, id LIMIT 1')->fetch_assoc();
if ($q) {
    $stmt = $db->prepare('SELECT j.teks, g.kode FROM jawaban j JOIN gaya_belajar g ON g.id = j.gaya_id WHERE j.pertanyaan_id = ? ORDER BY j.gaya_id');
    $stmt->bind_param('i', $q['id']);
    $stmt->execute();
    $coba = ['teks' => $q['teks'], 'jawaban' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)];
}
$cobaInfo = [];
foreach ($gaya as $g) {
    $cobaInfo[$g['kode']] = ['nama' => $g['nama'], 'kalimat' => $g['kalimat_hasil']];
}

$judul = 'Kenali Gaya Belajarmu';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div>
    <h1>Temukan cara belajar yang paling pas untukmu.</h1>
    <p class="lead">Jawab pertanyaan seputar situasi sehari-hari. Kamu akan dapat profil gaya belajar dan tips yang bisa langsung dipraktikkan.</p>
    <p>
      <a class="btn" href="<?= url('/kuis.php') ?>">Mulai kuis</a>
      <a class="btn ghost" href="<?= BASE_URL ?>/jenis.php">Lihat jenis gaya belajar</a>
    </p>
  </div>
  <?php if ($coba): ?>
    <div class="cobacard" aria-labelledby="judulcoba">
      <h2 id="judulcoba">Coba satu pertanyaan</h2>
      <p><?= e($coba['teks']) ?></p>
      <?php foreach ($coba['jawaban'] as $j): ?>
        <button type="button" class="opt <?= e(strtolower($j['kode'])) ?>" data-kode="<?= e($j['kode']) ?>" aria-pressed="false"><?= e($j['teks']) ?></button>
      <?php endforeach; ?>
      <div id="dres" aria-live="polite"></div>
    </div>
  <?php endif; ?>
</section>

<h2 class="tengah">Kenalan dengan tiga maskot</h2>
<div class="trio">
  <?php foreach ($gaya as $g): ?>
    <button type="button" class="mas <?= e(strtolower($g['kode'])) ?>" data-kode="<?= e($g['kode']) ?>" aria-pressed="false">
      <span class="mi"><?= ikon($g['kode']) ?></span><?= e($g['nama']) ?>
    </button>
  <?php endforeach; ?>
</div>
<div class="trioNote" id="trioNote" aria-live="polite">Ketuk satu maskot untuk berkenalan.</div>

<h2 class="tengah">Cara kerjanya</h2>
<ol class="steps">
  <li><strong>Buat akun</strong><span>Supaya hasilmu tersimpan.</span></li>
  <li><strong>Jawab kuis</strong><span>Pilih jawaban yang paling mirip kebiasaanmu.</span></li>
  <li><strong>Lihat hasil</strong><span>Dapatkan persentase dan rekomendasi.</span></li>
  <li><strong>Pantau riwayat</strong><span>Bandingkan hasilmu dari waktu ke waktu.</span></li>
</ol>

<script>
  // Teks diambil dari database (diubah admin), jadi dikirim ke JS lewat json_encode
  const info = <?= json_encode(array_column($gaya, 'kalimat_hasil', 'kode'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const cobaInfo = <?= json_encode($cobaInfo, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  document.querySelectorAll('.opt').forEach(b => b.onclick = () => {
    document.querySelectorAll('.opt').forEach(o => o.setAttribute('aria-pressed', o === b));
    const d = cobaInfo[b.dataset.kode], res = document.getElementById('dres');
    res.textContent = '';
    const tag = document.createElement('b');
    tag.className = b.dataset.kode.toLowerCase();
    tag.textContent = d.nama;
    res.append(tag, d.kalimat + ' Kuis lengkap akan mengungkap profilmu.');
  });
  document.querySelectorAll('.mas').forEach(b => b.onclick = () => {
    document.querySelectorAll('.mas').forEach(x => x.setAttribute('aria-pressed', x === b));
    document.getElementById('trioNote').className = 'trioNote ' + b.dataset.kode.toLowerCase();
    document.getElementById('trioNote').textContent = info[b.dataset.kode];
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

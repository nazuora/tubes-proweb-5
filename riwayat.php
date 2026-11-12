<?php
require __DIR__ . '/config/koneksi.php';

$login = !empty($_SESSION['user']);
$uid   = $login ? (int)$_SESSION['user']['id'] : 0;

// ===== DELETE: hapus satu hasil milik pengguna ini =====
if ($login && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_periksa();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?'); // user_id mencegah menghapus data orang lain
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    header('Location: ' . url('/riwayat.php'));
    exit;
}

// ===== READ: semua hasil, dari yang paling lama =====
$rows = [];
if ($login) {
    $stmt = $db->prepare('SELECT h.id, h.persen_visual, h.persen_auditori, h.persen_kinestetik, h.dibuat_pada, g.kode, g.nama AS gaya
                          FROM hasil_kuis h
                          JOIN gaya_belajar g ON g.id = h.gaya_utama_id
                          WHERE h.user_id = ?
                          ORDER BY h.dibuat_pada, h.id');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$n = count($rows);

// Grafik garis (SVG dibuat di server)
$garis = [
    'persen_visual'     => ['Visual',     WARNA_GAYA['V'][0]],
    'persen_auditori'   => ['Auditori',   WARNA_GAYA['A'][0]],
    'persen_kinestetik' => ['Kinestetik', WARNA_GAYA['K'][0]],
];
$W = 600; $H = 220; $pad = 34;
$px = fn(int $i): float => $pad + $i * ($W - 2 * $pad) / max(1, $n - 1);
$py = fn(int $v): float => $H - $pad - ($v / 100) * ($H - 2 * $pad);

$judul = 'Riwayat Kuis';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow" style="max-width:720px">
  <h1 class="s">Riwayat kuismu</h1>
  <p class="sub">Lihat perubahan gaya belajarmu dari waktu ke waktu.</p>

  <?php if (!$login): ?>
    <div class="empty">
      <strong>Masuk dulu untuk melihat riwayat.</strong>
      <p>Hasil kuis tersimpan di akunmu.</p>
      <a class="btn" href="<?= e(url('/masuk.php')) ?>">Masuk</a>
    </div>

  <?php elseif ($n === 0): ?>
    <div class="empty">
      <strong>Belum ada hasil kuis.</strong>
      <p>Kerjakan kuis pertamamu untuk mulai melacak gaya belajar.</p>
      <a class="btn" href="<?= e(url('/kuis.php')) ?>">Mulai kuis</a>
    </div>

  <?php else: ?>
    <?php if ($n >= 2): ?>
      <section class="card" aria-label="Grafik perkembangan">
        <h2 style="margin-top:0">Perkembangan persentase</h2>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" style="width:100%;height:auto;display:block" role="img" aria-label="Grafik garis persentase tiga gaya belajar">
          <?php foreach ([0, 50, 100] as $t): ?>
            <line x1="<?= $pad ?>" x2="<?= $W - $pad ?>" y1="<?= $py($t) ?>" y2="<?= $py($t) ?>" stroke="var(--line)"/>
            <text x="4" y="<?= $py($t) + 4 ?>" font-size="12" fill="var(--muted)"><?= $t ?>%</text>
          <?php endforeach; ?>
          <?php foreach ($garis as $kolom => [$nama, $warna]): ?>
            <?php
              $titik = [];
              foreach ($rows as $i => $r) $titik[] = round($px($i), 1) . ',' . round($py((int)$r[$kolom]), 1);
            ?>
            <polyline points="<?= e(implode(' ', $titik)) ?>" fill="none" stroke="<?= $warna ?>" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
            <?php foreach ($rows as $i => $r): ?>
              <circle cx="<?= round($px($i), 1) ?>" cy="<?= round($py((int)$r[$kolom]), 1) ?>" r="5" fill="<?= $warna ?>"/>
            <?php endforeach; ?>
          <?php endforeach; ?>
          <?php foreach ($rows as $i => $r): ?>
            <text x="<?= round($px($i), 1) ?>" y="<?= $H - 8 ?>" text-anchor="middle" font-size="12" fill="var(--muted)"><?= e(implode(' ', array_slice(explode(' ', tanggal_id($r['dibuat_pada'])), 0, 2))) ?></text>
          <?php endforeach; ?>
        </svg>
        <div class="legend">
          <span><i style="background:<?= WARNA_GAYA['V'][0] ?>"></i>Visual</span>
          <span><i style="background:<?= WARNA_GAYA['A'][0] ?>"></i>Auditori</span>
          <span><i style="background:<?= WARNA_GAYA['K'][0] ?>"></i>Kinestetik</span>
        </div>
      </section>
    <?php endif; ?>

    <?php foreach (array_reverse($rows) as $r): ?>
      <div class="item">
        <button type="button" class="head" aria-expanded="false">
          <span class="pill" style="background:<?= WARNA_GAYA[$r['kode']][0] ?>"><?= e($r['gaya']) ?></span>
          <span class="date"><?= e(tanggal_id($r['dibuat_pada'])) ?></span>
          <span class="chev" aria-hidden="true">&#9662;</span>
        </button>
        <div class="body hidden">
          <?php foreach ([['Visual', 'persen_visual', 'V'], ['Auditori', 'persen_auditori', 'A'], ['Kinestetik', 'persen_kinestetik', 'K']] as [$nama, $kolom, $k]): ?>
            <div class="row">
              <div class="lab"><span><?= $nama ?></span><span><?= (int)$r[$kolom] ?>%</span></div>
              <div class="track"><div style="width:<?= (int)$r[$kolom] ?>%;background:<?= WARNA_GAYA[$k][0] ?>"></div></div>
            </div>
          <?php endforeach; ?>
          <div class="actions" style="justify-content:flex-start;align-items:center">
            <a class="btn ghost" style="padding:8px 18px" href="<?= e(url('/hasil.php?id=' . (int)$r['id'])) ?>">Lihat detail</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="button" class="btn red del" style="padding:8px 18px">Hapus hasil ini</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script>
  document.querySelectorAll('.item').forEach(item => {
    const head = item.querySelector('.head'), body = item.querySelector('.body'), del = item.querySelector('.del');
    head.onclick = () => {
      const buka = head.getAttribute('aria-expanded') === 'true';
      head.setAttribute('aria-expanded', !buka);
      body.classList.toggle('hidden', buka);
    };
    del.onclick = () => {
      if (del.dataset.sure) { del.closest('form').submit(); }
      else { del.dataset.sure = 1; del.textContent = 'Klik lagi untuk menghapus'; }
    };
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

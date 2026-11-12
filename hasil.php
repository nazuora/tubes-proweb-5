<?php
require __DIR__ . '/config/koneksi.php';

$login = !empty($_SESSION['user']);
$uid   = $login ? (int)$_SESSION['user']['id'] : 0;
$h     = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ===== Terima jawaban dari kuis, hitung persentase =====
    csrf_periksa();
    $jawab = $_POST['q'] ?? [];
    $ids   = array_column($db->query('SELECT id FROM pertanyaan')->fetch_all(MYSQLI_ASSOC), 'id');
    $total = count($ids);
    if ($total === 0) {
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $hitung = [1 => 0, 2 => 0, 3 => 0];            // 1 = Visual, 2 = Auditori, 3 = Kinestetik
    foreach ($ids as $pid) {
        $g = (int)($jawab[$pid] ?? 0);
        if ($g < 1 || $g > 3) {                    // ada soal yang belum dijawab
            header('Location: ' . url('/kuis.php'));
            exit;
        }
        $hitung[$g]++;
    }
    $pv = (int)round($hitung[1] / $total * 100);
    $pa = (int)round($hitung[2] / $total * 100);
    $pk = (int)round($hitung[3] / $total * 100);
    $utama = array_keys($hitung, max($hitung))[0]; // bila seri, ambil yang id-nya paling kecil

    if (!$login) {
        // Tamu: simpan sementara di session, dipindahkan ke riwayat setelah masuk atau daftar
        $_SESSION['hasil_tertunda'] = ['v' => $pv, 'a' => $pa, 'k' => $pk, 'g' => (int)$utama, 't' => time()];
    }

    if ($login) {
        // CREATE: simpan ke riwayat, lalu alihkan agar refresh tidak menyimpan dua kali
        $stmt = $db->prepare('INSERT INTO hasil_kuis (user_id, persen_visual, persen_auditori, persen_kinestetik, gaya_utama_id) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iiiii', $uid, $pv, $pa, $pk, $utama);
        $stmt->execute();
        header('Location: ' . url('/hasil.php?id=' . $db->insert_id));
        exit;
    }

    // Tamu: hasil hanya ditampilkan, tidak disimpan
    $stmt = $db->prepare('SELECT kode, nama, julukan, kalimat_hasil, tips FROM gaya_belajar WHERE id = ?');
    $stmt->bind_param('i', $utama);
    $stmt->execute();
    $h = $stmt->get_result()->fetch_assoc();
    if (!$h) {
        http_response_code(500);
        exit('Data gaya belajar belum ada. Impor ulang database.');
    }
    $h += ['persen_visual' => $pv, 'persen_auditori' => $pa, 'persen_kinestetik' => $pk];
} else {
    // ===== READ: tampilkan satu hasil milik pengguna ini =====
    if (!$login) {
        header('Location: ' . url('/masuk.php'));
        exit;
    }
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('SELECT h.persen_visual, h.persen_auditori, h.persen_kinestetik,
                                 g.kode, g.nama, g.julukan, g.kalimat_hasil, g.tips
                          FROM hasil_kuis h
                          JOIN gaya_belajar g ON g.id = h.gaya_utama_id
                          WHERE h.id = ? AND h.user_id = ?');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $h = $stmt->get_result()->fetch_assoc();
    if (!$h) {
        http_response_code(404);
        exit('Hasil tidak ditemukan.');
    }
}

$tips  = array_values(array_filter(array_map('trim', explode("\n", (string)$h['tips']))));
$kode  = $h['kode'];
$latar = ['V' => 'var(--blue-soft)', 'A' => 'var(--yellow-soft)', 'K' => 'var(--green-soft)'][$kode] ?? 'var(--blue-soft)';
$bar   = [
    ['Visual',     (int)$h['persen_visual'],     WARNA_GAYA['V'][0]],
    ['Auditori',   (int)$h['persen_auditori'],   WARNA_GAYA['A'][0]],
    ['Kinestetik', (int)$h['persen_kinestetik'], WARNA_GAYA['K'][0]],
];
$dataKartu = [
    'kode'    => $kode,
    'julukan' => $h['julukan'],
    'warna'   => WARNA_GAYA[$kode][0],
    'latar'   => WARNA_GAYA[$kode][1],
    'pct'     => ['V' => (int)$h['persen_visual'], 'A' => (int)$h['persen_auditori'], 'K' => (int)$h['persen_kinestetik']],
    'tips'    => array_slice($tips, 0, 2),
];
$namaDepan = $login ? nama_depan($_SESSION['user']['nama']) : '';

$judul = 'Hasil Kuis';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow">
  <div class="qtop"><span>Kuis</span><span>Selesai</span></div>
  <div class="bar"><div style="width:100%"></div></div>

  <section class="card" id="result" style="margin-top:18px">
    <div class="badge" style="background:<?= $latar ?>">
      <p>Gaya belajar utamamu</p>
      <h1><?= e($h['julukan']) ?> (<?= e($h['nama']) ?>)</h1>
      <p><?= e($h['kalimat_hasil']) ?></p>
    </div>

    <?php foreach ($bar as [$nama, $p, $warna]): ?>
      <div class="row">
        <div class="lab"><span><?= e($nama) ?></span><span><?= $p ?>%</span></div>
        <div class="track"><div style="width:<?= $p ?>%;background:<?= $warna ?>"></div></div>
      </div>
    <?php endforeach; ?>

    <h2>Tips belajar untukmu</h2>
    <ul class="tips"><?php foreach ($tips as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>

    <?php if ($login): ?>
      <div class="msg">Hasil tersimpan di Riwayat.</div>
    <?php else: ?>
      <p class="hint" style="margin-top:12px">Masuk atau daftar, hasil ini otomatis tersimpan di Riwayat. <a href="<?= e(url('/masuk.php')) ?>" style="color:var(--blue);font-weight:900">Masuk sekarang</a></p>
    <?php endif; ?>

    <div class="actions">
      <a class="btn ghost" href="<?= e(url('/kuis.php')) ?>">Ulangi kuis</a>
      <button type="button" class="btn" id="mk">Buat kartu</button>
    </div>
  </section>

  <section class="card hidden" id="cardv" style="margin-top:18px">
    <h1 class="s">Kartu hasilmu</h1>
    <label class="l" for="nm">Nama di kartu</label>
    <input class="t" id="nm" maxlength="20" autocomplete="off" placeholder="Nama kamu" value="<?= e($namaDepan) ?>" style="margin-bottom:14px">
    <img id="out" alt="Kartu hasil gaya belajar">
    <p class="hint">Tekan lama gambar (atau klik kanan) untuk menyimpan.</p>
    <div class="actions">
      <button type="button" class="btn ghost" id="back">Kembali ke hasil</button>
      <a class="btn" href="<?= e(url('/kuis.php')) ?>">Ulangi kuis</a>
    </div>
  </section>
</div>
<canvas id="cv" width="1080" height="1350" hidden></canvas>

<script>
  const D = <?= json_encode($dataKartu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
  const NAMA = { V: 'Visual', A: 'Auditori', K: 'Kinestetik' };
  const WARNA = { V: '#8B7CF0', A: '#FF9F7A', K: '#5CCB9C' };
  const KEYS = ['V', 'A', 'K'];
  const INK = '#3A2F52', FF = "'Nunito',system-ui,sans-serif";
  const $ = id => document.getElementById(id);
  const cv = $('cv'), ctx = cv.getContext('2d');

  function rr(x, y, w, h, r) { ctx.beginPath(); ctx.roundRect(x, y, w, h, r); }
  function ikon(k, cx, cy) {
    ctx.save(); ctx.translate(cx - 128, cy - 128); ctx.scale(4, 4);
    ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = INK; ctx.fillStyle = '#fff';
    if (k === 'V') {
      ctx.beginPath(); ctx.ellipse(32, 32, 28, 18, 0, 0, 7); ctx.fill();
      ctx.fillStyle = INK; ctx.beginPath(); ctx.arc(32, 32, 12, 0, 7); ctx.fill();
      ctx.fillStyle = '#fff'; ctx.beginPath(); ctx.arc(36, 28, 3.5, 0, 7); ctx.fill();
    } else if (k === 'A') {
      const p = new Path2D('M20 30c0-9 5-16 13-16s13 7 13 15c0 9-8 10-8 17a7 7 0 0 1-14 0');
      ctx.fill(p); ctx.stroke(p); ctx.stroke(new Path2D('M29 30c0-4 2-6 5-6'));
    } else {
      const p = new Path2D('M18 34V18a4 4 0 0 1 8 0v10m0-12a4 4 0 0 1 8 0v12m0-10a4 4 0 0 1 8 0v14m0-6a4 4 0 0 1 8 0v14c0 10-7 16-16 16-10 0-16-6-20-14l-4-8a4 4 0 0 1 7-3z');
      ctx.fill(p); ctx.stroke(p);
    }
    ctx.restore();
  }
  function gambarKartu() {
    const name = $('nm').value.trim();
    ctx.clearRect(0, 0, 1080, 1350);
    ctx.fillStyle = D.latar; ctx.fillRect(0, 0, 1080, 1350);
    ctx.textAlign = 'left'; ctx.fillStyle = INK; ctx.font = '900 34px ' + FF; ctx.fillText('Kenali Gaya Belajarmu', 80, 100);
    [['#8B7CF0', 900], ['#FF9F7A', 936], ['#5CCB9C', 972]].forEach(([c, x]) => { ctx.fillStyle = c; ctx.beginPath(); ctx.arc(x + 10, 88, 13, 0, 7); ctx.fill(); });
    ctx.fillStyle = D.warna; ctx.beginPath(); ctx.arc(540, 330, 170, 0, 7); ctx.fill(); ikon(D.kode, 540, 330);
    ctx.textAlign = 'center'; ctx.fillStyle = INK;
    ctx.font = '700 34px ' + FF; ctx.fillText('Gaya belajar utama' + (name ? ' ' + name : ''), 540, 590);
    ctx.font = '900 74px ' + FF; ctx.fillText(D.julukan, 540, 680);
    ctx.textAlign = 'left';
    KEYS.forEach((k, n) => {
      const y = 760 + n * 84;
      ctx.fillStyle = INK; ctx.font = '700 30px ' + FF; ctx.fillText(NAMA[k], 80, y);
      ctx.textAlign = 'right'; ctx.fillText(D.pct[k] + '%', 1000, y); ctx.textAlign = 'left';
      ctx.fillStyle = 'rgba(45,49,66,.12)'; rr(80, y + 14, 920, 22, 11); ctx.fill();
      ctx.fillStyle = WARNA[k]; rr(80, y + 14, Math.max(22, 920 * D.pct[k] / 100), 22, 11); ctx.fill();
    });
    ctx.fillStyle = INK; ctx.font = '900 34px ' + FF; ctx.fillText('Coba ini:', 80, 1068);
    ctx.font = '700 32px ' + FF; D.tips.forEach((t, n) => ctx.fillText('\u2022 ' + t, 80, 1120 + n * 50));
    ctx.textAlign = 'center'; ctx.fillStyle = 'rgba(45,49,66,.65)'; ctx.font = '700 28px ' + FF; ctx.fillText('Cari tahu gaya belajarmu juga!', 540, 1290);
    $('out').src = cv.toDataURL('image/png');
  }
  $('mk').onclick = () => { $('result').classList.add('hidden'); $('cardv').classList.remove('hidden'); gambarKartu(); };
  $('back').onclick = () => { $('cardv').classList.add('hidden'); $('result').classList.remove('hidden'); };
  $('nm').oninput = gambarKartu;
  if (document.fonts) Promise.all([document.fonts.load('900 20px Nunito'), document.fonts.load('700 20px Nunito')]).catch(() => {});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

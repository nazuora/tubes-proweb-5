<?php
require __DIR__ . '/config/koneksi.php';
wajib_login();
$uid = $_SESSION['user']['id'];

// ===== DELETE: hapus satu hasil milik pengguna ini =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    csrf_periksa();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM hasil_kuis WHERE id = ? AND user_id = ?'); // user_id mencegah menghapus data orang lain
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    header('Location: ' . url('/riwayat.php'));
    exit;
}

// ===== READ: semua hasil, dari yang paling lama =====
$stmt = $db->prepare('SELECT h.id, h.persen_visual, h.persen_auditori, h.persen_kinestetik, h.dibuat_pada, g.nama AS gaya
                      FROM hasil_kuis h
                      JOIN gaya_belajar g ON g.id = h.gaya_utama_id
                      WHERE h.user_id = ?
                      ORDER BY h.dibuat_pada, h.id');
$stmt->bind_param('i', $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function tanggal_id(string $waktu): string {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($waktu);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

// ===== Grafik garis (SVG dibuat di server) =====
$n = count($rows);
$garis = [
    'persen_visual'     => ['Visual',     '#8B7CF0'],
    'persen_auditori'   => ['Auditori',   '#FF9F7A'],
    'persen_kinestetik' => ['Kinestetik', '#5CCB9C'],
];
$W = 600; $H = 220; $pad = 34;
$px = fn(int $i): float => $pad + $i * ($W - 2 * $pad) / max(1, $n - 1);
$py = fn(int $v): float => $H - $pad - ($v / 100) * ($H - 2 * $pad);
$judul = 'Riwayat Kuis'; require __DIR__ . '/includes/header.php';
?>

    <h1>Riwayat kuismu</h1>

    <?php if ($n === 0): ?>
      <p>Belum ada hasil kuis. <a href="<?= BASE_URL ?>/kuis.php">Kerjakan kuis pertamamu</a>.</p>
    <?php else: ?>

      <?php if ($n >= 2): ?>
        <h2>Perkembangan persentase</h2>
        <svg viewBox="0 0 <?= $W ?> <?= $H ?>" style="width:100%;height:auto" role="img" aria-label="Grafik garis persentase tiga gaya belajar">
          <?php foreach ([0, 50, 100] as $t): ?>
            <line x1="<?= $pad ?>" x2="<?= $W - $pad ?>" y1="<?= $py($t) ?>" y2="<?= $py($t) ?>" stroke="#ddd"/>
            <text x="4" y="<?= $py($t) + 4 ?>" font-size="12" fill="#666"><?= $t ?>%</text>
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
            <text x="<?= round($px($i), 1) ?>" y="<?= $H - 8 ?>" text-anchor="middle" font-size="12" fill="#666"><?= e(date('d/m', strtotime($r['dibuat_pada']))) ?></text>
          <?php endforeach; ?>
        </svg>
        <p>
          <span style="color:#8B7CF0">●</span> Visual
          <span style="color:#FF9F7A">●</span> Auditori
          <span style="color:#5CCB9C">●</span> Kinestetik
        </p>
      <?php endif; ?>

      <h2>Semua hasil</h2>
      <?php foreach (array_reverse($rows) as $r): ?>
        <details>
          <summary><b><?= e($r['gaya']) ?></b> · <?= e(tanggal_id($r['dibuat_pada'])) ?></summary>
          <p>Visual <?= (int)$r['persen_visual'] ?>% · Auditori <?= (int)$r['persen_auditori'] ?>% · Kinestetik <?= (int)$r['persen_kinestetik'] ?>%</p>
          <a href="<?= BASE_URL ?>/hasil.php?id=<?= (int)$r['id'] ?>">Lihat detail</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Hapus hasil ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit">Hapus hasil ini</button>
          </form>
        </details>
      <?php endforeach; ?>
    <?php endif; ?>

    <p><a href="<?= BASE_URL ?>/kuis.php">Kuis</a> · <a href="<?= BASE_URL ?>/profil.php">Profil</a> · <a href="<?= BASE_URL ?>/keluar.php">Keluar</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

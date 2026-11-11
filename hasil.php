<?php
require __DIR__ . '/config/koneksi.php';
wajib_login();
$uid = $_SESSION['user']['id'];

// ===== CREATE: terima jawaban, hitung, simpan, lalu alihkan =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $jawab = $_POST['q'] ?? [];
    $ids = array_column($db->query('SELECT id FROM pertanyaan')->fetch_all(MYSQLI_ASSOC), 'id');
    $total = count($ids);
    if ($total === 0) {
        header('Location: ' . url('/kuis.php'));
        exit;
    }

    $hitung = [1 => 0, 2 => 0, 3 => 0];            // 1 = Visual, 2 = Auditori, 3 = Kinestetik
    foreach ($ids as $pid) {
        $g = (int)($jawab[$pid] ?? 0);
        if ($g < 1 || $g > 3) {                    // ada soal yang belum dijawab
            header('Location: ' . url('/kuis.php?belum=1'));
            exit;
        }
        $hitung[$g]++;
    }

    $pv = (int)round($hitung[1] / $total * 100);
    $pa = (int)round($hitung[2] / $total * 100);
    $pk = (int)round($hitung[3] / $total * 100);
    $utama = array_keys($hitung, max($hitung))[0]; // bila seri, ambil yang id-nya paling kecil

    $stmt = $db->prepare('INSERT INTO hasil_kuis (user_id, persen_visual, persen_auditori, persen_kinestetik, gaya_utama_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiiii', $uid, $pv, $pa, $pk, $utama);
    $stmt->execute();

    // Post-Redirect-Get: refresh halaman tidak menyimpan hasil dua kali
    header('Location: ' . url('/hasil.php?id=' . $db->insert_id));
    exit;
}

// ===== READ: tampilkan satu hasil milik pengguna ini =====
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT h.persen_visual, h.persen_auditori, h.persen_kinestetik, h.dibuat_pada,
                             g.nama, g.julukan, g.kalimat_hasil, g.tips
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

$tips = array_filter(array_map('trim', explode("\n", (string)$h['tips'])));
$bar  = ['Visual' => [$h['persen_visual'], '#8B7CF0'], 'Auditori' => [$h['persen_auditori'], '#FF9F7A'], 'Kinestetik' => [$h['persen_kinestetik'], '#5CCB9C']];
$judul = 'Hasil Kuis'; $sempit = true; require __DIR__ . '/includes/header.php';
?>

    <p>Gaya belajar utamamu</p>
    <h1><?= e($h['julukan']) ?> (<?= e($h['nama']) ?>)</h1>
    <p><?= e($h['kalimat_hasil']) ?></p>

    <?php foreach ($bar as $nama => [$p, $warna]): ?>
      <div>
        <div><?= e($nama) ?> <b><?= (int)$p ?>%</b></div>
        <div class="track"><div style="width:<?= (int)$p ?>%;background:<?= $warna ?>"></div></div>
      </div>
    <?php endforeach; ?>

    <h2>Tips belajar untukmu</h2>
    <ul>
      <?php foreach ($tips as $t): ?><li><?= e($t) ?></li><?php endforeach; ?>
    </ul>

    <p><a href="<?= BASE_URL ?>/kuis.php">Ulangi kuis</a> · <a href="<?= BASE_URL ?>/riwayat.php">Lihat riwayat</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

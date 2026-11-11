<?php
require __DIR__ . '/../config/koneksi.php';
wajib_admin();

$pesan = '';
$error = '';

// ===== CREATE, UPDATE, DELETE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM pertanyaan WHERE id = ?'); // jawaban ikut terhapus (ON DELETE CASCADE)
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $pesan = 'Pertanyaan dihapus.';
    }

    if ($aksi === 'simpan') {
        $id   = (int)($_POST['id'] ?? 0);
        $teks = trim($_POST['teks'] ?? '');
        // gaya_id: 1 = Visual, 2 = Auditori, 3 = Kinestetik
        $jwb  = [1 => trim($_POST['j1'] ?? ''), 2 => trim($_POST['j2'] ?? ''), 3 => trim($_POST['j3'] ?? '')];

        if ($teks === '' || in_array('', $jwb, true)) {
            $error = 'Isi pertanyaan dan ketiga jawabannya.';
        } else {
            $db->begin_transaction();
            try {
                if ($id > 0) {
                    // UPDATE
                    $stmt = $db->prepare('UPDATE pertanyaan SET teks = ? WHERE id = ?');
                    $stmt->bind_param('si', $teks, $id);
                    $stmt->execute();
                    $stmt = $db->prepare('UPDATE jawaban SET teks = ? WHERE pertanyaan_id = ? AND gaya_id = ?');
                    foreach ($jwb as $gaya => $t) {
                        $stmt->bind_param('sii', $t, $id, $gaya);
                        $stmt->execute();
                    }
                    $pesan = 'Pertanyaan diperbarui.';
                } else {
                    // CREATE
                    $urutan = (int)$db->query('SELECT COALESCE(MAX(urutan), 0) + 1 FROM pertanyaan')->fetch_row()[0];
                    $stmt = $db->prepare('INSERT INTO pertanyaan (teks, urutan) VALUES (?, ?)');
                    $stmt->bind_param('si', $teks, $urutan);
                    $stmt->execute();
                    $baru = $db->insert_id;
                    $stmt = $db->prepare('INSERT INTO jawaban (pertanyaan_id, gaya_id, teks) VALUES (?, ?, ?)');
                    foreach ($jwb as $gaya => $t) {
                        $stmt->bind_param('iis', $baru, $gaya, $t);
                        $stmt->execute();
                    }
                    $pesan = 'Pertanyaan ditambahkan.';
                }
                $db->commit();
            } catch (mysqli_sql_exception $ex) {
                $db->rollback();
                $error = 'Gagal menyimpan. Coba lagi.';
            }
        }
    }
}

// ===== Data untuk formulir ubah =====
$edit = ['id' => 0, 'teks' => '', 'j' => [1 => '', 2 => '', 3 => '']];
if (isset($_GET['ubah'])) {
    $id = (int)$_GET['ubah'];
    $stmt = $db->prepare('SELECT id, teks FROM pertanyaan WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $edit['id'] = (int)$row['id'];
        $edit['teks'] = $row['teks'];
        $stmt = $db->prepare('SELECT gaya_id, teks FROM jawaban WHERE pertanyaan_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        foreach ($stmt->get_result() as $j) {
            $edit['j'][(int)$j['gaya_id']] = $j['teks'];
        }
    }
}

// ===== READ: daftar pertanyaan =====
$daftar = $db->query('SELECT id, teks FROM pertanyaan ORDER BY urutan, id')->fetch_all(MYSQLI_ASSOC);
$judul = 'Admin: Pertanyaan Kuis'; require __DIR__ . '/../includes/header.php';
?>

    <h1>Pertanyaan kuis</h1>
    <?php if ($pesan): ?><p class="msg" role="status"><?= e($pesan) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>

    <p><b><?= count($daftar) ?> pertanyaan</b></p>
    <ol>
      <?php foreach ($daftar as $p): ?>
        <li>
          <?= e($p['teks']) ?>
          <a href="?ubah=<?= (int)$p['id'] ?>">Ubah</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Hapus pertanyaan ini?')">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button type="submit">Hapus</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ol>

    <h2><?= $edit['id'] ? 'Ubah pertanyaan' : 'Tambah pertanyaan' ?></h2>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">
      <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">

      <label for="teks">Pertanyaan</label>
      <input id="teks" name="teks" value="<?= e($edit['teks']) ?>" required>

      <label for="j1">Jawaban gaya Visual</label>
      <input id="j1" name="j1" value="<?= e($edit['j'][1]) ?>" required>

      <label for="j2">Jawaban gaya Auditori</label>
      <input id="j2" name="j2" value="<?= e($edit['j'][2]) ?>" required>

      <label for="j3">Jawaban gaya Kinestetik</label>
      <input id="j3" name="j3" value="<?= e($edit['j'][3]) ?>" required>

      <button type="submit">Simpan</button>
      <?php if ($edit['id']): ?><a href="pertanyaan.php">Batal</a><?php endif; ?>
    </form>
<?php require __DIR__ . '/../includes/footer.php'; ?>

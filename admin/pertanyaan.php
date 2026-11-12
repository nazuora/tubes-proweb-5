<?php
require __DIR__ . '/../config/koneksi.php';
wajib_admin();

$pesan = '';
$error = '';
$edit  = ['id' => 0, 'teks' => '', 'j' => [1 => '', 2 => '', 3 => '']];

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
            $edit  = ['id' => $id, 'teks' => $teks, 'j' => $jwb];   // pertahankan isian
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['ubah'])) {
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
$tampilForm = isset($_GET['tambah']) || ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['ubah'])) || $error !== '';

// ===== READ: daftar pertanyaan =====
$daftar = $db->query('SELECT id, teks FROM pertanyaan ORDER BY urutan, id')->fetch_all(MYSQLI_ASSOC);

$judul = 'Admin: Pertanyaan Kuis';
require __DIR__ . '/../includes/header.php';
?>
<div class="narrow" style="max-width:720px">
  <h1 class="s">Panel admin</h1>
  <p class="sub">Kelola isi web tanpa mengubah kode. Perubahan langsung tampil di halaman Kuis dan Jenis.</p>

  <div class="seg">
    <a href="<?= e(url('/admin/pertanyaan.php')) ?>" aria-selected="true">Pertanyaan kuis</a>
    <a href="<?= e(url('/admin/gaya.php')) ?>" aria-selected="false">Isi gaya belajar</a>
  </div>

  <div class="actions" style="align-items:center;margin:0 0 12px">
    <b><?= count($daftar) ?> pertanyaan</b>
    <a class="btn" href="?tambah=1">+ Tambah pertanyaan</a>
  </div>

  <?php if ($pesan): ?><div class="msg" role="status"><?= e($pesan) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>

  <?php foreach ($daftar as $no => $p): ?>
    <div class="arow">
      <div class="at"><b><?= $no + 1 ?>.</b> <?= e($p['teks']) ?></div>
      <div class="ab">
        <a class="btn ghost sm" href="?ubah=<?= (int)$p['id'] ?>">Ubah</a>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="aksi" value="hapus">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="button" class="btn red sm del">Hapus</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($tampilForm): ?>
    <section class="card">
      <h2 style="margin-top:0"><?= $edit['id'] ? 'Ubah pertanyaan' : 'Tambah pertanyaan' ?></h2>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
        <label class="l" for="teks">Pertanyaan</label>
        <input class="t" id="teks" name="teks" value="<?= e($edit['teks']) ?>" autocomplete="off">
        <label class="l" for="j1" style="margin-top:8px">Jawaban gaya Visual</label>
        <input class="t" id="j1" name="j1" value="<?= e($edit['j'][1]) ?>" autocomplete="off">
        <label class="l" for="j2" style="margin-top:8px">Jawaban gaya Auditori</label>
        <input class="t" id="j2" name="j2" value="<?= e($edit['j'][2]) ?>" autocomplete="off">
        <label class="l" for="j3" style="margin-top:8px">Jawaban gaya Kinestetik</label>
        <input class="t" id="j3" name="j3" value="<?= e($edit['j'][3]) ?>" autocomplete="off">
        <div class="actions" style="justify-content:flex-start">
          <button class="btn" type="submit">Simpan</button>
          <a class="btn ghost" href="<?= e(url('/admin/pertanyaan.php')) ?>">Batal</a>
        </div>
      </form>
    </section>
  <?php endif; ?>
</div>
<script>
  // Hapus: klik dua kali agar tidak terhapus tanpa sengaja
  document.querySelectorAll('.del').forEach(b => b.onclick = () => {
    if (b.dataset.sure) { b.closest('form').submit(); }
    else { b.dataset.sure = 1; b.textContent = 'Klik lagi'; }
  });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

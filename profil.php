<?php
require __DIR__ . '/config/koneksi.php';
wajib_login();
$uid = $_SESSION['user']['id'];

const FOLDER_FOTO = __DIR__ . '/uploads/';   // folder ini harus ada dan bisa ditulis
const MAKS_FOTO   = 2 * 1024 * 1024;         // 2 MB

function ambil_user(mysqli $db, int $uid): array {
    $stmt = $db->prepare('SELECT nama, email, password_hash, foto FROM users WHERE id = ?');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$user  = ambil_user($db, $uid);
$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi = $_POST['aksi'] ?? '';

    // ===== UPDATE: nama dan foto =====
    if ($aksi === 'simpan') {
        $nama = trim($_POST['nama'] ?? '');
        $fotoBaru = $user['foto'];

        if (mb_strlen($nama) < 2) {
            $error = 'Isi nama minimal 2 huruf.';
        }

        if (!$error && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['foto'];
            $izin = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';

            if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] > MAKS_FOTO) {
                $error = 'Foto gagal diunggah atau lebih dari 2 MB.';
            } elseif (!isset($izin[$mime])) {
                $error = 'Foto harus berformat JPG, PNG, atau WebP.';
            } else {
                $namaFile = 'foto_' . $uid . '_' . bin2hex(random_bytes(6)) . '.' . $izin[$mime];
                if (move_uploaded_file($f['tmp_name'], FOLDER_FOTO . $namaFile)) {
                    if ($user['foto']) @unlink(FOLDER_FOTO . basename($user['foto'])); // hapus foto lama
                    $fotoBaru = $namaFile;
                } else {
                    $error = 'Foto gagal disimpan. Pastikan folder uploads bisa ditulis.';
                }
            }
        }

        if (!$error) {
            $stmt = $db->prepare('UPDATE users SET nama = ?, foto = ? WHERE id = ?');
            $stmt->bind_param('ssi', $nama, $fotoBaru, $uid);
            $stmt->execute();
            $_SESSION['user']['nama'] = $nama;
            $user = ambil_user($db, $uid);
            $pesan = 'Perubahan tersimpan.';
        }
    }

    // ===== DELETE: hapus akun (wajib kata sandi) =====
    if ($aksi === 'hapus_akun') {
        if (!password_verify($_POST['sandi'] ?? '', $user['password_hash'])) {
            $error = 'Kata sandi salah, akun tidak dihapus.';
        } else {
            if ($user['foto']) @unlink(FOLDER_FOTO . basename($user['foto']));
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?'); // riwayat ikut terhapus (ON DELETE CASCADE)
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $_SESSION = [];
            session_destroy();
            header('Location: ' . url('/'));
            exit;
        }
    }
}

$judul = 'Profil';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow" style="max-width:520px">
  <h1 class="s">Profilmu</h1>
  <?php if ($pesan): ?><div class="msg" role="status"><?= e($pesan) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="err" role="alert"><?= e($error) ?></div><?php endif; ?>

  <form class="card" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="simpan">
    <div class="who">
      <div class="ava" id="ava" aria-hidden="true">
        <?php if ($user['foto']): ?>
          <img alt="" src="<?= BASE_URL ?>/uploads/<?= e(basename($user['foto'])) ?>">
        <?php else: ?>
          <?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
        <?php endif; ?>
      </div>
      <div>
        <strong><?= e($user['nama']) ?></strong><br>
        <label class="file">Ganti foto<input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"></label>
      </div>
    </div>

    <label class="l" for="nama">Nama</label>
    <input class="t" id="nama" name="nama" value="<?= e($user['nama']) ?>" autocomplete="name">
    <div class="err"></div>
    <label class="l" for="email">Email</label>
    <input class="t" id="email" value="<?= e($user['email']) ?>" readonly aria-describedby="petunjuk">
    <div class="hint" id="petunjuk" style="margin-bottom:12px">Email tidak bisa diubah. Foto: JPG, PNG, atau WebP, maks. 2 MB.</div>
    <div class="actions" style="justify-content:flex-start;align-items:center">
      <button class="btn" type="submit">Simpan perubahan</button>
      <a class="btn ghost" href="<?= e(url('/keluar.php')) ?>">Keluar</a>
    </div>
  </form>

  <form class="card danger" method="post" onsubmit="return confirm('Yakin hapus akun? Ini tidak bisa dibatalkan.')">
    <?= csrf_field() ?>
    <input type="hidden" name="aksi" value="hapus_akun">
    <h2>Hapus akun</h2>
    <p>Akun dan seluruh riwayat kuismu akan dihapus permanen dan tidak bisa dikembalikan.</p>
    <label class="l" for="sandi">Masukkan kata sandi untuk konfirmasi</label>
    <input class="t" id="sandi" name="sandi" type="password" required autocomplete="current-password" style="margin-bottom:12px">
    <button class="btn red" type="submit">Hapus akun saya</button>
  </form>
</div>
<script>
  // Pratinjau foto sebelum disimpan
  document.getElementById('foto').onchange = e => {
    const f = e.target.files[0];
    if (!f) return;
    const r = new FileReader();
    r.onload = () => { document.getElementById('ava').innerHTML = '<img alt="" src="' + r.result + '">'; };
    r.readAsDataURL(f);
  };
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

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
            header('Location: ' . url('/masuk.php'));
            exit;
        }
    }
}
$judul = 'Profil'; $sempit = true; require __DIR__ . '/includes/header.php';
?>

    <h1>Profilmu</h1>
    <?php if ($pesan): ?><p class="msg" role="status"><?= e($pesan) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>

    <?php if ($user['foto']): ?>
      <img src="<?= BASE_URL ?>/uploads/<?= e(basename($user['foto'])) ?>" alt="Foto profil" width="96" height="96" style="border-radius:50%;object-fit:cover">
    <?php else: ?>
      <div aria-hidden="true" style="width:96px;height:96px;border-radius:50%;background:#ECE8FF;display:grid;place-items:center;font-size:2rem">
        <?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="simpan">

      <label for="nama">Nama</label>
      <input id="nama" name="nama" value="<?= e($user['nama']) ?>" autocomplete="name">

      <label for="email">Email</label>
      <input id="email" value="<?= e($user['email']) ?>" readonly aria-describedby="petunjuk">
      <small id="petunjuk">Email tidak bisa diubah.</small>

      <label for="foto">Ganti foto (JPG, PNG, WebP, maks. 2 MB)</label>
      <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">

      <button type="submit">Simpan perubahan</button>
    </form>

    <h2>Hapus akun</h2>
    <p>Akun dan seluruh riwayat kuismu akan dihapus permanen dan tidak bisa dikembalikan.</p>
    <form method="post" onsubmit="return confirm('Yakin hapus akun? Ini tidak bisa dibatalkan.')">
      <?= csrf_field() ?>
      <input type="hidden" name="aksi" value="hapus_akun">
      <label for="sandi">Masukkan kata sandi untuk konfirmasi</label>
      <input id="sandi" name="sandi" type="password" required autocomplete="current-password">
      <button type="submit">Hapus akun saya</button>
    </form>

    <p><a href="<?= BASE_URL ?>/kuis.php">Kuis</a> · <a href="<?= BASE_URL ?>/riwayat.php">Riwayat</a> · <a href="<?= BASE_URL ?>/keluar.php">Keluar</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
require __DIR__ . '/config/koneksi.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

$errors = [];
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $nama  = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sandi = $_POST['sandi'] ?? '';
    $ulang = $_POST['ulang'] ?? '';

    if (mb_strlen($nama) < 2)                        $errors['nama']  = 'Isi nama minimal 2 huruf.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors['email'] = 'Format email belum benar, contoh: nama@email.com.';
    if (strlen($sandi) < 8)                          $errors['sandi'] = 'Kata sandi minimal 8 karakter.';
    if ($ulang !== $sandi)                           $errors['ulang'] = 'Kata sandi tidak sama.';

    if (!$errors) {
        try {
            $hash = password_hash($sandi, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param('sss', $nama, $email, $hash);
            $stmt->execute();
            header('Location: ' . url('/masuk.php?baru=1'));
            exit;
        } catch (mysqli_sql_exception $ex) {
            // 1062 = email sudah terdaftar (kolom email UNIQUE)
            $errors['email'] = ($ex->getCode() === 1062)
                ? 'Email ini sudah terdaftar. Silakan masuk.'
                : 'Gagal membuat akun. Coba lagi.';
        }
    }
}
$judul = 'Daftar'; $sempit = true; require __DIR__ . '/includes/header.php';
?>

    <h1>Buat akun baru</h1>
    <form method="post" novalidate>
      <?= csrf_field() ?>

      <label for="nama">Nama</label>
      <input id="nama" name="nama" value="<?= e($nama) ?>" autocomplete="name">
      <?php if (isset($errors['nama'])): ?><p class="err" role="alert"><?= e($errors['nama']) ?></p><?php endif; ?>

      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email">
      <?php if (isset($errors['email'])): ?><p class="err" role="alert"><?= e($errors['email']) ?></p><?php endif; ?>

      <label for="sandi">Kata sandi</label>
      <input id="sandi" name="sandi" type="password" autocomplete="new-password">
      <?php if (isset($errors['sandi'])): ?><p class="err" role="alert"><?= e($errors['sandi']) ?></p><?php endif; ?>

      <label for="ulang">Ulangi kata sandi</label>
      <input id="ulang" name="ulang" type="password" autocomplete="new-password">
      <?php if (isset($errors['ulang'])): ?><p class="err" role="alert"><?= e($errors['ulang']) ?></p><?php endif; ?>

      <button type="submit">Daftar</button>
    </form>
    <p>Sudah punya akun? <a href="<?= BASE_URL ?>/masuk.php">Masuk</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

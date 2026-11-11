<?php
require __DIR__ . '/config/koneksi.php';

// Sudah login? langsung ke kuis
if (!empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $email = trim($_POST['email'] ?? '');
    $sandi = $_POST['sandi'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email belum benar, contoh: nama@email.com.';
    } elseif (strlen($sandi) < 8) {
        $error = 'Kata sandi minimal 8 karakter.';
    } else {
        // Prepared statement: aman dari SQL injection
        $stmt = $db->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($sandi, $user['password_hash'])) {
            session_regenerate_id(true); // cegah session fixation
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            header('Location: ' . url($user['role'] === 'admin' ? '/admin/pertanyaan.php' : '/kuis.php'));
            exit;
        }
        // Pesan sengaja sama untuk email salah atau sandi salah
        $error = 'Email atau kata sandi salah.';
    }
}
$judul = 'Masuk'; $sempit = true; require __DIR__ . '/includes/header.php';
?>

    <h1>Selamat datang kembali</h1>
    <?php if ($error): ?>
      <p class="err" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="email">
      <label for="sandi">Kata sandi</label>
      <input id="sandi" name="sandi" type="password" required autocomplete="current-password">
      <button type="submit">Masuk</button>
    </form>
    <p>Belum punya akun? <a href="<?= BASE_URL ?>/daftar.php">Daftar</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

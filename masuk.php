<?php
require __DIR__ . '/config/koneksi.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . url('/kuis.php'));
    exit;
}

$tab   = (($_GET['tab'] ?? '') === 'daftar') ? 'daftar' : 'masuk';
$err   = [];
$nilai = ['nama' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_periksa();
    $aksi  = $_POST['aksi'] ?? '';
    $nilai['email'] = trim($_POST['email'] ?? '');
    $sandi = $_POST['sandi'] ?? '';

    if ($aksi === 'masuk') {
        $tab = 'masuk';
        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        if (strlen($sandi) < 8)                                  $err['sandi'] = 'Kata sandi minimal 8 karakter.';

        if (!$err) {
            // Prepared statement: aman dari SQL injection
            $stmt = $db->prepare('SELECT id, nama, email, password_hash, role FROM users WHERE email = ?');
            $stmt->bind_param('s', $nilai['email']);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if ($user && password_verify($sandi, $user['password_hash'])) {
                masukkan_sesi($user);
                $hasilId = simpan_hasil_tertunda($db, (int)$user['id']);
                header('Location: ' . url($hasilId ? '/hasil.php?id=' . $hasilId : '/kuis.php'));
                exit;
            }
            // Pesan sengaja sama untuk email salah atau sandi salah
            $err['sandi'] = 'Email atau kata sandi salah.';
        }
    }

    if ($aksi === 'daftar') {
        $tab = 'daftar';
        $nilai['nama'] = trim($_POST['nama'] ?? '');
        $ulang = $_POST['ulang'] ?? '';

        if (mb_strlen($nilai['nama']) < 2)                       $err['nama']  = 'Isi nama minimal 2 huruf.';
        if (!filter_var($nilai['email'], FILTER_VALIDATE_EMAIL)) $err['email'] = 'Format email belum benar, contoh: nama@email.com.';
        if (strlen($sandi) < 8)                                  $err['sandi'] = 'Kata sandi minimal 8 karakter.';
        if ($ulang !== $sandi)                                   $err['ulang'] = 'Kata sandi tidak sama.';

        if (!$err) {
            try {
                $hash = password_hash($sandi, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (nama, email, password_hash, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param('sss', $nilai['nama'], $nilai['email'], $hash);
                $stmt->execute();
                $idBaru = (int)$db->insert_id;
                // Langsung masuk setelah daftar, seperti di demo
                masukkan_sesi(['id' => $idBaru, 'nama' => $nilai['nama'], 'email' => $nilai['email'], 'role' => 'user']);
                $hasilId = simpan_hasil_tertunda($db, $idBaru);
                header('Location: ' . url($hasilId ? '/hasil.php?id=' . $hasilId : '/kuis.php'));
                exit;
            } catch (mysqli_sql_exception $ex) {
                // 1062 = email sudah terdaftar (kolom email UNIQUE)
                $err['email'] = ($ex->getCode() === 1062) ? 'Email ini sudah terdaftar. Silakan masuk.' : 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
}

$judul = 'Masuk';
require __DIR__ . '/includes/header.php';
?>
<div class="narrow" style="max-width:440px">
  <?php if (!empty($_SESSION['hasil_tertunda'])): ?>
    <div class="msg" style="margin:0 0 12px">Hasil kuismu akan otomatis tersimpan setelah kamu masuk atau daftar.</div>
  <?php endif; ?>
  <div class="card">
    <div class="seg" role="tablist">
      <button type="button" role="tab" id="t-in" aria-selected="<?= $tab === 'masuk' ? 'true' : 'false' ?>">Masuk</button>
      <button type="button" role="tab" id="t-up" aria-selected="<?= $tab === 'daftar' ? 'true' : 'false' ?>">Daftar</button>
    </div>

    <div id="p-in" class="<?= $tab === 'masuk' ? '' : 'hidden' ?>">
      <h1 class="s">Selamat datang kembali</h1>
      <p class="sub">Masuk untuk menyimpan dan melihat riwayat hasil kuismu.</p>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="masuk">
        <label class="l" for="in-email">Email</label>
        <input class="t" id="in-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= $tab === 'masuk' ? e($nilai['email']) : '' ?>" aria-invalid="<?= isset($err['email']) && $tab === 'masuk' ? 'true' : 'false' ?>">
        <div class="err"><?= $tab === 'masuk' ? e($err['email'] ?? '') : '' ?></div>
        <label class="l" for="in-sandi">Kata sandi</label>
        <input class="t" id="in-sandi" name="sandi" type="password" autocomplete="current-password" aria-invalid="<?= isset($err['sandi']) && $tab === 'masuk' ? 'true' : 'false' ?>">
        <div class="err"><?= $tab === 'masuk' ? e($err['sandi'] ?? '') : '' ?></div>
        <button class="btn" type="submit" style="width:100%">Masuk</button>
      </form>
    </div>

    <div id="p-up" class="<?= $tab === 'daftar' ? '' : 'hidden' ?>">
      <h1 class="s">Buat akun baru</h1>
      <p class="sub">Simpan hasil kuismu dan pantau perkembangannya.</p>
      <form method="post" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="aksi" value="daftar">
        <label class="l" for="up-nama">Nama</label>
        <input class="t" id="up-nama" name="nama" autocomplete="name" value="<?= e($nilai['nama']) ?>" aria-invalid="<?= isset($err['nama']) ? 'true' : 'false' ?>">
        <div class="err"><?= e($err['nama'] ?? '') ?></div>
        <label class="l" for="up-email">Email</label>
        <input class="t" id="up-email" name="email" type="email" autocomplete="email" placeholder="nama@email.com" value="<?= $tab === 'daftar' ? e($nilai['email']) : '' ?>" aria-invalid="<?= isset($err['email']) && $tab === 'daftar' ? 'true' : 'false' ?>">
        <div class="err"><?= $tab === 'daftar' ? e($err['email'] ?? '') : '' ?></div>
        <label class="l" for="up-sandi">Kata sandi</label>
        <input class="t" id="up-sandi" name="sandi" type="password" autocomplete="new-password" aria-invalid="<?= isset($err['sandi']) && $tab === 'daftar' ? 'true' : 'false' ?>">
        <div class="err"><?= $tab === 'daftar' ? e($err['sandi'] ?? '') : '' ?></div>
        <label class="l" for="up-ulang">Ulangi kata sandi</label>
        <input class="t" id="up-ulang" name="ulang" type="password" autocomplete="new-password" aria-invalid="<?= isset($err['ulang']) ? 'true' : 'false' ?>">
        <div class="err"><?= e($err['ulang'] ?? '') ?></div>
        <button class="btn" type="submit" style="width:100%">Daftar</button>
      </form>
    </div>
  </div>
</div>
<script>
  function tab(daftar) {
    document.getElementById('t-in').setAttribute('aria-selected', !daftar);
    document.getElementById('t-up').setAttribute('aria-selected', daftar);
    document.getElementById('p-in').classList.toggle('hidden', daftar);
    document.getElementById('p-up').classList.toggle('hidden', !daftar);
  }
  document.getElementById('t-in').onclick = () => tab(false);
  document.getElementById('t-up').onclick = () => tab(true);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

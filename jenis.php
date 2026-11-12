<?php
require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/includes/ikon.php';

$gaya = $db->query('SELECT kode, nama, pengertian, ciri, tips, bidang FROM gaya_belajar ORDER BY id')->fetch_all(MYSQLI_ASSOC);

// Ubah teks bertanda baris baru menjadi array
function baris(?string $teks): array {
    return array_values(array_filter(array_map('trim', explode("\n", (string)$teks))));
}

$judul = 'Jenis Gaya Belajar';
require __DIR__ . '/includes/header.php';
?>
<h1>Jenis gaya belajar</h1>
<p class="lead">Gaya belajar adalah cara yang paling nyaman bagi seseorang untuk menerima dan mengingat informasi. Pilih satu maskot untuk mengenalnya.</p>

<div class="tabs" role="tablist">
  <?php foreach ($gaya as $i => $g): ?>
    <button type="button" role="tab" class="<?= e(strtolower($g['kode'])) ?>" data-kode="<?= e($g['kode']) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
      <?= ikon($g['kode']) ?><?= e($g['nama']) ?>
    </button>
  <?php endforeach; ?>
</div>

<?php foreach ($gaya as $i => $g): ?>
  <article class="panel <?= e(strtolower($g['kode'])) ?>" id="p-<?= e($g['kode']) ?>" role="tabpanel" <?= $i === 0 ? '' : 'hidden' ?>>
    <div class="bubble">
      <h2><?= e($g['nama']) ?></h2>
      <p><?= e($g['pengertian']) ?></p>
    </div>

    <h3>Ciri-cirimu</h3>
    <div class="chips">
      <?php foreach (baris($g['ciri']) as $c): ?><span><?= e($c) ?></span><?php endforeach; ?>
    </div>

    <h3>Coba cara ini</h3>
    <div class="notes">
      <?php foreach (baris($g['tips']) as $t): ?><div class="note"><?= e($t) ?></div><?php endforeach; ?>
    </div>

    <?php if ($g['bidang']): ?><p class="bd">Cocok untuk: <?= e($g['bidang']) ?></p><?php endif; ?>
  </article>
<?php endforeach; ?>

<p><a class="btn" href="<?= url('/kuis.php') ?>">Cari tahu gaya belajarmu</a></p>

<script>
  document.querySelectorAll('.tabs button').forEach(b => b.onclick = () => {
    document.querySelectorAll('.tabs button').forEach(x => x.setAttribute('aria-selected', x === b));
    document.querySelectorAll('.panel').forEach(p => p.hidden = p.id !== 'p-' + b.dataset.kode);
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

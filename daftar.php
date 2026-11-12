<?php
// Pendaftaran sekarang ada di halaman masuk (tab Daftar). File ini hanya mengalihkan.
require __DIR__ . '/config/koneksi.php';
header('Location: ' . url('/masuk.php?tab=daftar'));
exit;

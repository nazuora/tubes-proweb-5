<?php
// config/koneksi.php — dipanggil di awal SETIAP halaman PHP:
//   require __DIR__ . '/config/koneksi.php';      (dari folder admin: '/../config/koneksi.php')

// Sesuaikan dengan pengaturan XAMPP/Laragon kamu
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'kenali_gaya_belajar';

// Awalan URL otomatis, supaya tautan tetap benar di akar server maupun di subfolder.
//   http://localhost/          -> ''
//   http://localhost/kenali/   -> '/kenali'
$akarProyek = str_replace('\\', '/', (string)realpath(__DIR__ . '/..'));
$dr = $_SERVER['DOCUMENT_ROOT'] ?? '';
$akarServer = $dr !== '' ? str_replace('\\', '/', rtrim((string)realpath($dr), '/\\')) : '';
define('BASE_URL', ($akarServer !== '' && stripos($akarProyek, $akarServer) === 0) ? (string)substr($akarProyek, strlen($akarServer)) : '');

// Buat alamat dari path yang diawali "/" , contoh: url('/masuk.php')
function url(string $path): string {
    return BASE_URL . $path;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa pengaturan di config/koneksi.php.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape output supaya aman dari XSS. Pakai fungsi e() setiap menampilkan data ke HTML.
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Wajib login
function wajib_login(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ' . url('/masuk.php'));
        exit;
    }
}

// Wajib admin
function wajib_admin(): void {
    wajib_login();
    if ($_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        exit('Halaman ini hanya untuk admin.');
    }
}

// Perlindungan CSRF untuk form POST
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_periksa(): void {
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(400);
        exit('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.');
    }
}


// Warna tiap gaya belajar: [warna utama, warna latar lembut]
const WARNA_GAYA = [
    'V' => ['#8B7CF0', '#ECE8FF'],
    'A' => ['#FF9F7A', '#FFE9DE'],
    'K' => ['#5CCB9C', '#DDF6EA'],
];

// Simpan data pengguna ke session setelah daftar atau masuk
function masukkan_sesi(array $u): void {
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['user'] = [
        'id'    => (int)$u['id'],
        'nama'  => $u['nama'],
        'email' => $u['email'],
        'role'  => $u['role'],
    ];
}

function nama_depan(string $nama): string {
    return explode(' ', trim($nama))[0];
}

function tanggal_id(string $waktu): string {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($waktu);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}


// Hasil kuis yang dikerjakan tamu disimpan sementara di session.
// Setelah tamu masuk atau daftar, hasil itu dipindahkan ke riwayat. Mengembalikan id hasil, atau null.
function simpan_hasil_tertunda(mysqli $db, int $uid): ?int {
    $t = $_SESSION['hasil_tertunda'] ?? null;
    unset($_SESSION['hasil_tertunda']);
    if (!$t || time() - (int)$t['t'] > 3600) {      // kedaluwarsa setelah 1 jam
        return null;
    }
    $v = (int)$t['v']; $a = (int)$t['a']; $k = (int)$t['k']; $g = (int)$t['g'];
    if ($g < 1 || $g > 3 || min($v, $a, $k) < 0 || max($v, $a, $k) > 100) {
        return null;
    }
    $stmt = $db->prepare('INSERT INTO hasil_kuis (user_id, persen_visual, persen_auditori, persen_kinestetik, gaya_utama_id) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiiii', $uid, $v, $a, $k, $g);
    $stmt->execute();
    return $db->insert_id;
}

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

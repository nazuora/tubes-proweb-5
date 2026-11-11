-- Database: kenali_gaya_belajar
-- Cara pakai: buka phpMyAdmin > Import, pilih file ini.
-- Untuk pengumpulan, ekspor ulang (dumping) database dari phpMyAdmin.

CREATE DATABASE IF NOT EXISTS kenali_gaya_belajar
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kenali_gaya_belajar;

-- 1. Akun pengguna dan admin
CREATE TABLE users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(60)  NOT NULL,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- isi dengan password_hash() di PHP
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  foto          VARCHAR(255) NULL,
  dibuat_pada   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tiga gaya belajar (dikelola admin)
CREATE TABLE gaya_belajar (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kode          CHAR(1)      NOT NULL UNIQUE,   -- V, A, K
  nama          VARCHAR(30)  NOT NULL,
  julukan       VARCHAR(60)  NOT NULL,
  pengertian    TEXT         NOT NULL,
  kalimat_hasil VARCHAR(200) NOT NULL,
  ciri          TEXT         NULL,              -- satu ciri per baris
  tips          TEXT         NULL,              -- satu tips per baris
  bidang        VARCHAR(200) NULL
) ENGINE=InnoDB;

-- 3. Pertanyaan kuis (dikelola admin)
CREATE TABLE pertanyaan (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  teks   VARCHAR(255) NOT NULL,
  urutan INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- 4. Tiga pilihan jawaban tiap pertanyaan, masing-masing mewakili satu gaya
CREATE TABLE jawaban (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  pertanyaan_id INT NOT NULL,
  gaya_id       INT NOT NULL,
  teks          VARCHAR(255) NOT NULL,
  FOREIGN KEY (pertanyaan_id) REFERENCES pertanyaan(id) ON DELETE CASCADE,
  FOREIGN KEY (gaya_id)       REFERENCES gaya_belajar(id)
) ENGINE=InnoDB;

-- 5. Riwayat hasil kuis tiap pengguna
CREATE TABLE hasil_kuis (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  persen_visual     TINYINT UNSIGNED NOT NULL,
  persen_auditori   TINYINT UNSIGNED NOT NULL,
  persen_kinestetik TINYINT UNSIGNED NOT NULL,
  gaya_utama_id   INT NOT NULL,
  dibuat_pada     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)       REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (gaya_utama_id) REFERENCES gaya_belajar(id)
) ENGINE=InnoDB;

-- ===== DATA AWAL =====

INSERT INTO gaya_belajar (kode, nama, julukan, pengertian, kalimat_hasil, ciri, tips, bidang) VALUES
('V','Visual','Si Pemetaan Visual',
 'Orang visual menyerap informasi paling baik lewat penglihatan: gambar, warna, diagram, dan tata letak yang rapi.',
 'Kamu paling cepat paham lewat gambar dan struktur yang jelas.',
 'Mudah mengingat wajah, bentuk, dan warna\nSuka membuat catatan berwarna dan bergambar\nLebih paham saat melihat peta, grafik, atau slide\nSulit fokus kalau hanya mendengar ceramah panjang',
 'Buat mind map dan diagram alur\nPakai stabilo dengan kode warna\nTonton video penjelasan\nUbah angka dan teks menjadi tabel atau grafik',
 'Desainer, arsitek, dan pekerjaan yang banyak memakai visual.'),
('A','Auditori','Sang Pendengar Ulung',
 'Orang auditori menyerap informasi paling baik lewat pendengaran: penjelasan lisan, diskusi, dan irama.',
 'Kamu paling cepat paham lewat suara dan percakapan.',
 'Mudah mengingat percakapan dan penjelasan\nSuka berdiskusi dan bertanya\nSering membaca sambil bersuara\nMudah terganggu oleh suara bising',
 'Jelaskan ulang materi dengan suara\nRekam rangkuman, lalu dengarkan ulang\nBelajar dalam kelompok diskusi\nDengarkan podcast atau materi audio',
 'Pembicara, pengajar, dan pekerjaan yang banyak berkomunikasi lisan.'),
('K','Kinestetik','Si Pencoba Langsung',
 'Orang kinestetik menyerap informasi paling baik lewat gerakan dan pengalaman langsung.',
 'Kamu paling cepat paham lewat praktik dan gerakan.',
 'Sulit duduk diam dalam waktu lama\nLebih paham setelah mencoba sendiri\nSuka praktik, eksperimen, dan simulasi\nSering bergerak atau memegang benda saat berpikir',
 'Perbanyak latihan soal dan praktik\nBelajar sambil berjalan atau berdiri\nPakai benda nyata sebagai alat peraga\nIstirahat singkat untuk bergerak tiap 25 menit',
 'Teknisi, atlet, dan pekerjaan yang banyak praktik lapangan.');

INSERT INTO pertanyaan (teks, urutan) VALUES
('Kamu harus merakit lemari baru. Apa yang kamu lakukan pertama kali?',1),
('Kamu ingin tahu jalan ke tempat baru. Cara yang paling nyaman?',2),
('Kamu belajar untuk ujian besok. Kamu paling mungkin…',3),
('Dosen menjelaskan materi baru. Kamu paling mengingat…',4),
('Kamu sedang menunggu antrean panjang. Kamu biasanya…',5),
('Kamu belajar memakai aplikasi baru. Kamu memilih…',6);

-- gaya_id: 1 = Visual, 2 = Auditori, 3 = Kinestetik
INSERT INTO jawaban (pertanyaan_id, gaya_id, teks) VALUES
(1,1,'Mempelajari gambar petunjuknya'),
(1,2,'Minta teman membacakan petunjuknya'),
(1,3,'Langsung memasang sambil mencoba'),
(2,1,'Melihat peta dan rute di layar'),
(2,2,'Bertanya atau mendengarkan arahan suara'),
(2,3,'Berjalan dulu, lalu menyesuaikan di jalan'),
(3,1,'Membuat mind map dan menandai dengan warna'),
(3,2,'Membaca keras-keras atau berdiskusi dengan teman'),
(3,3,'Mengerjakan banyak latihan soal'),
(4,1,'Slide, grafik, dan tulisan di papan'),
(4,2,'Cara dosen menjelaskan dan contoh ceritanya'),
(4,3,'Saat ikut praktik atau demonstrasi'),
(5,1,'Mengamati sekitar atau membaca poster'),
(5,2,'Mengobrol atau mendengarkan musik'),
(5,3,'Bergerak, berdiri, atau memainkan sesuatu di tangan'),
(6,1,'Menonton video tutorial'),
(6,2,'Mendengarkan penjelasan teman'),
(6,3,'Mengutak-atik sendiri sampai bisa');

-- Akun admin: buat lewat PHP agar password-nya di-hash, misalnya sekali jalan:
--   echo password_hash('GantiPasswordIni123', PASSWORD_DEFAULT);
-- lalu tempel hasilnya di sini:
-- INSERT INTO users (nama, email, password_hash, role)
-- VALUES ('Admin', 'admin@email.com', '<hasil_hash>', 'admin');

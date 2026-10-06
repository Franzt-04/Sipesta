-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Okt 2026 pada 04.06
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sipesta`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `pesanan_penjual_id` int(11) DEFAULT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `pesanan_penjual_id`, `produk_id`, `jumlah`, `harga`, `subtotal`) VALUES
(1, 1, 1, 3, 3.00, 35000.00, 105000.00),
(2, 1, 1, 5, 2.00, 75000.00, 150000.00),
(3, 1, 1, 4, 6.00, 5000.00, 30000.00),
(4, 2, 2, 2, 1.00, 50000.00, 50000.00),
(5, 3, 3, 4, 2.00, 5000.00, 10000.00),
(6, 4, 4, 4, 5.00, 5000.00, 25000.00),
(7, 5, 5, 4, 1.00, 5000.00, 5000.00),
(8, 6, 6, 4, 1.00, 5000.00, 5000.00),
(9, 7, 7, 1, 1.00, 50000.00, 50000.00),
(10, 7, 7, 3, 2.00, 38000.00, 76000.00),
(11, 8, 8, 2, 1.00, 50000.00, 50000.00),
(12, 9, 9, 2, 2.00, 50000.00, 100000.00),
(13, 10, 10, 4, 1.00, 5000.00, 5000.00),
(14, 11, 16, 4, 1.00, 5000.00, 5000.00),
(15, 11, 16, 2, 13.00, 50000.00, 650000.00),
(16, 11, 16, 5, 7.00, 75000.00, 525000.00),
(17, 12, 17, 4, 1.00, 5000.00, 5000.00),
(18, 12, 17, 3, 2.00, 38000.00, 76000.00),
(19, 13, NULL, 3, 1.00, 38000.00, 38000.00),
(20, 13, NULL, 4, 2.00, 5000.00, 10000.00),
(21, 13, NULL, 5, 2.00, 75000.00, 150000.00),
(22, 13, NULL, 2, 1.00, 50000.00, 50000.00),
(23, 14, NULL, 4, 1.00, 5000.00, 5000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `deskripsi`, `created_at`) VALUES
(1, 'Telur', 'Produk telur ayam dan telur bebek', '2026-09-15 13:30:43'),
(2, 'Ikan', 'Produk ikan konsumsi', '2026-09-15 13:30:43'),
(3, 'Sayuran', 'Produk sayuran segar', '2026-09-15 13:30:43'),
(4, 'Ayam', 'Produk ayam kampung', '2026-09-15 13:30:43');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang`
--

CREATE TABLE `keranjang` (
  `id` int(11) NOT NULL,
  `pembeli_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `keranjang`
--

INSERT INTO `keranjang` (`id`, `pembeli_id`, `created_at`) VALUES
(1, 1, '2026-09-15 13:47:29'),
(4, 3, '2026-10-05 02:28:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang_detail`
--

CREATE TABLE `keranjang_detail` (
  `id` int(11) NOT NULL,
  `keranjang_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 1.00,
  `harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `keranjang_detail`
--

INSERT INTO `keranjang_detail` (`id`, `keranjang_id`, `produk_id`, `jumlah`, `harga`, `subtotal`) VALUES
(24, 4, 5, 1.00, 75000.00, 75000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` varchar(50) NOT NULL DEFAULT 'info',
  `link` varchar(255) DEFAULT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `judul`, `pesan`, `tipe`, `link`, `dibaca`, `created_at`) VALUES
(1, 2, 'Selamat Datang di SIPESTA', 'Terima kasih telah menggunakan sistem SIPESTA.', 'info', 'index.php', 0, '2026-09-17 03:04:54'),
(2, 2, 'Pesanan Berhasil', 'Pesanan PS20260922085513176 berhasil dibuat dengan total Rp 1.180.000 dan sedang menunggu diproses.', 'pesanan', 'detail_pesanan.php?id=11', 0, '2026-09-22 06:55:14'),
(3, 2, 'Pesanan Berhasil Dibuat', 'Pesanan dengan kode PS20260922085513176 berhasil dibuat dan sedang menunggu proses penjual.', 'pesanan', 'detail_pesanan.php?id=11', 0, '2026-09-22 06:55:14'),
(4, 3, 'Pesanan Baru', 'Anda menerima pesanan baru dengan kode PS20260922085513176.', 'pesanan', '../penjual/detail_pesanan.php?id=11', 0, '2026-09-22 06:55:14'),
(5, 2, 'Pesanan Berhasil', 'Pesanan PS20260929041011546 berhasil dibuat dengan total Rp 81.000 dan sedang menunggu diproses.', 'pesanan', 'detail_pesanan.php?id=12', 0, '2026-09-29 02:10:12'),
(6, 2, 'Pesanan Berhasil Dibuat', 'Pesanan dengan kode PS20260929041011546 berhasil dibuat dan sedang menunggu proses penjual.', 'pesanan', 'detail_pesanan.php?id=12', 0, '2026-09-29 02:10:12'),
(7, 3, 'Pesanan Baru', 'Anda menerima pesanan baru dengan kode PS20260929041011546.', 'pesanan', '../penjual/detail_pesanan.php?id=12', 0, '2026-09-29 02:10:12'),
(8, 2, 'Pesanan Berhasil Dibuat', 'Pesanan PSN-20261003121130-F68FFF berhasil dibuat dengan total Rp 253.000.', 'pesanan', 'detail_pesanan.php?id=13', 0, '2026-10-03 10:11:30'),
(9, 2, 'Pesanan Berhasil Dibuat', 'Pesanan PSN-20261003121315-66F235 berhasil dibuat dengan total Rp 10.000.', 'pesanan', 'detail_pesanan.php?id=14', 0, '2026-10-03 10:13:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `tanggal_pembayaran` datetime DEFAULT NULL,
  `jumlah` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metode` varchar(50) DEFAULT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `STATUS` enum('Belum Bayar','Menunggu Verifikasi','Diterima','Ditolak') DEFAULT 'Belum Bayar',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembeli`
--

CREATE TABLE `pembeli` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_toko` varchar(150) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `lama_usaha` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pembeli`
--

INSERT INTO `pembeli` (`id`, `user_id`, `nama`, `tanggal_lahir`, `no_hp`, `alamat`, `nama_toko`, `jenis_kelamin`, `lama_usaha`, `created_at`, `latitude`, `longitude`) VALUES
(1, 2, 'Muh Farhan hasnawing', '2004-02-05', '081356397538', 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', 'makmur harapan', 'Laki-laki', 3, '2026-09-15 13:47:29', NULL, NULL),
(2, 6, 'meilong', '2004-07-15', '0828473024653', 'makassar', 'toko makmur', 'Laki-laki', 3, '2026-09-19 14:04:01', NULL, NULL),
(3, 8, 'muh fahrul', '2004-01-01', '083948572645', 'maros', 'makmur sentosa', 'Laki-laki', 2, '2026-10-05 02:28:36', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaturan_ongkir`
--

CREATE TABLE `pengaturan_ongkir` (
  `id` int(11) NOT NULL,
  `nama_pengaturan` varchar(100) NOT NULL,
  `tarif_dasar` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tarif_per_km` decimal(15,2) NOT NULL DEFAULT 0.00,
  `minimal_gratis` decimal(15,2) NOT NULL DEFAULT 0.00,
  `maksimal_jarak_km` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengaturan_ongkir`
--

INSERT INTO `pengaturan_ongkir` (`id`, `nama_pengaturan`, `tarif_dasar`, `tarif_per_km`, `minimal_gratis`, `maksimal_jarak_km`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ongkir Default', 5000.00, 3000.00, 500000.00, 30.00, 'aktif', '2026-09-30 08:20:36', '2026-09-30 08:20:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengiriman`
--

CREATE TABLE `pengiriman` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `alamat` text NOT NULL,
  `kurir` varchar(100) DEFAULT NULL,
  `no_resi` varchar(100) DEFAULT NULL,
  `tanggal_kirim` datetime DEFAULT NULL,
  `tanggal_sampai` datetime DEFAULT NULL,
  `STATUS` enum('Belum Dikirim','Diproses','Dikirim','Sampai') DEFAULT 'Belum Dikirim',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjual`
--

CREATE TABLE `penjual` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_usaha` varchar(150) DEFAULT NULL,
  `jenis_usaha` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penjual`
--

INSERT INTO `penjual` (`id`, `user_id`, `nama`, `no_hp`, `alamat`, `nama_usaha`, `jenis_usaha`, `created_at`, `latitude`, `longitude`) VALUES
(1, 3, 'Muh Azhar', '082375840285', 'takalar', 'Pt ikan terbang', 'ikan', '2026-09-15 13:56:40', -4.94458326, 119.58119689),
(2, 4, 'Muh Nur Isnaeni', '08472819203', 'patte\'ne', 'Pt ikan Kuyang', 'ikan', '2026-09-16 00:48:31', NULL, NULL),
(3, 5, '', NULL, 'marannu', 'warung', NULL, '2026-09-18 14:47:01', NULL, NULL),
(4, 7, '', NULL, 'Pangkep, bambu runcung', 'Toko ikan nia', NULL, '2026-09-22 06:13:57', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `kode_pesanan` varchar(30) NOT NULL,
  `pembeli_id` int(11) NOT NULL,
  `nama_penerima` varchar(150) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `tanggal_pesanan` datetime DEFAULT current_timestamp(),
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `alamat_pengiriman` text NOT NULL,
  `catatan` text DEFAULT NULL,
  `metode_pembayaran` enum('COD','Transfer') DEFAULT 'COD',
  `STATUS` varchar(20) NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitude_pengiriman` decimal(10,8) DEFAULT NULL,
  `longitude_pengiriman` decimal(11,8) DEFAULT NULL,
  `jarak_km` decimal(10,2) DEFAULT NULL,
  `ongkos_kirim` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `kode_pesanan`, `pembeli_id`, `nama_penerima`, `no_hp`, `tanggal_pesanan`, `total_harga`, `alamat_pengiriman`, `catatan`, `metode_pembayaran`, `STATUS`, `created_at`, `updated_at`, `latitude_pengiriman`, `longitude_pengiriman`, `jarak_km`, `ongkos_kirim`) VALUES
(1, 'SP-20260916074615-818', 1, 'Muh Farhan', '081356397538', '2026-09-16 13:46:15', 285000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-16 05:46:15', '2026-09-16 07:01:01', NULL, NULL, NULL, 0.00),
(2, 'SP-20260916074635-931', 1, 'Muh Farhan', '081356397538', '2026-09-16 13:46:35', 50000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-16 05:46:35', '2026-09-16 07:01:01', NULL, NULL, NULL, 0.00),
(3, 'SP-20260916075128-639', 1, 'Muh Farhan', '081356397538', '2026-09-16 13:51:28', 10000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'diproses', '2026-09-16 05:51:28', '2026-09-16 11:27:36', NULL, NULL, NULL, 0.00),
(4, 'SP-20260916080559-903', 1, 'Muh Farhan', '081356397538', '2026-09-16 14:05:59', 25000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'selesai', '2026-09-16 06:05:59', '2026-09-16 11:27:27', NULL, NULL, NULL, 0.00),
(5, 'SP-20260916080801-980', 1, 'Muh Farhan', '081356397538', '2026-09-16 14:08:01', 5000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'dikirim', '2026-09-16 06:08:01', '2026-09-16 11:26:59', NULL, NULL, NULL, 0.00),
(6, 'SP-20260916084101-208', 1, 'Muh Farhan', '081356397538', '2026-09-16 14:41:01', 5000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'dibatalkan', '2026-09-16 06:41:01', '2026-09-16 07:01:56', NULL, NULL, NULL, 0.00),
(7, 'SP-20260917024449-526', 1, 'Muh Farhan', '081356397538', '2026-09-17 08:44:49', 126000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-17 00:44:49', '2026-09-17 00:44:49', NULL, NULL, NULL, 0.00),
(8, 'SP-20260917024558-643', 1, 'Muh Farhan', '081356397538', '2026-09-17 08:45:58', 50000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-17 00:45:58', '2026-09-17 00:45:58', NULL, NULL, NULL, 0.00),
(9, 'SP-20260917040809-646', 1, 'Muh Farhan', '081356397538', '2026-09-17 10:08:09', 100000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', 'yang besar2 telurnya', 'COD', 'menunggu', '2026-09-17 02:08:09', '2026-09-17 02:08:09', NULL, NULL, NULL, 0.00),
(10, 'SP-20260917075148-934', 1, 'Muh Farhan', '081356397538', '2026-09-17 13:51:48', 5000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-17 05:51:48', '2026-09-17 05:51:48', NULL, NULL, NULL, 0.00),
(11, 'PS20260922085513176', 1, 'Muh Farhan hasnawing', '081356397538', '2026-09-22 14:55:13', 1180000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-22 06:55:13', '2026-09-22 06:55:13', NULL, NULL, NULL, 0.00),
(12, 'PS20260929041011546', 1, 'Muh Farhan hasnawing', '081356397538', '2026-09-29 10:10:11', 81000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-09-29 02:10:11', '2026-09-29 02:10:11', NULL, NULL, NULL, 0.00),
(13, 'PSN-20261003121130-F68FFF', 1, 'Muh Farhan hasnawing', '081356397538', '2026-10-03 18:11:30', 253000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'menunggu', '2026-10-03 10:11:30', '2026-10-03 10:11:30', -4.94458326, 119.58119689, 0.00, 5000.00),
(14, 'PSN-20261003121315-66F235', 1, 'Muh Farhan hasnawing', '081356397538', '2026-10-03 18:13:15', 10000.00, 'jl. Pendidikan No. 2 Marannu, Sulawesi Selatan, Indonesia', '', 'COD', 'diproses', '2026-10-03 10:13:15', '2026-10-03 10:14:25', -4.94458326, 119.58119689, 0.00, 5000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan_penjual`
--

CREATE TABLE `pesanan_penjual` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `penjual_id` int(11) NOT NULL,
  `kode_sub_pesanan` varchar(50) NOT NULL,
  `tanggal_pesanan` datetime NOT NULL DEFAULT current_timestamp(),
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('menunggu','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pesanan_penjual`
--

INSERT INTO `pesanan_penjual` (`id`, `pesanan_id`, `penjual_id`, `kode_sub_pesanan`, `tanggal_pesanan`, `total_harga`, `status`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'SP-20260916074615-818-P01', '2026-09-16 13:46:15', 285000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(2, 2, 1, 'SP-20260916074635-931-P01', '2026-09-16 13:46:35', 50000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(3, 3, 1, 'SP-20260916075128-639-P01', '2026-09-16 13:51:28', 10000.00, 'diproses', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(4, 4, 1, 'SP-20260916080559-903-P01', '2026-09-16 14:05:59', 25000.00, 'selesai', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(5, 5, 1, 'SP-20260916080801-980-P01', '2026-09-16 14:08:01', 5000.00, 'dikirim', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(6, 6, 1, 'SP-20260916084101-208-P01', '2026-09-16 14:41:01', 5000.00, 'dibatalkan', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(7, 7, 1, 'SP-20260917024449-526-P01', '2026-09-17 08:44:49', 126000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(8, 8, 1, 'SP-20260917024558-643-P01', '2026-09-17 08:45:58', 50000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(9, 9, 1, 'SP-20260917040809-646-P01', '2026-09-17 10:08:09', 100000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(10, 10, 1, 'SP-20260917075148-934-P01', '2026-09-17 13:51:48', 5000.00, 'menunggu', NULL, '2026-09-18 08:27:33', '2026-09-18 08:27:33'),
(16, 11, 1, 'PS20260922085513176-P01', '2026-09-22 14:55:13', 1180000.00, 'menunggu', NULL, '2026-09-22 06:55:13', '2026-09-22 06:55:13'),
(17, 12, 1, 'PS20260929041011546-P01', '2026-09-29 10:10:11', 81000.00, 'menunggu', NULL, '2026-09-29 02:10:11', '2026-09-29 02:10:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `penjual_id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `satuan` varchar(50) NOT NULL,
  `stok` decimal(15,2) NOT NULL DEFAULT 0.00,
  `foto` varchar(255) DEFAULT NULL,
  `STATUS` enum('tersedia','habis','nonaktif') DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `penjual_id`, `kategori_id`, `nama_produk`, `deskripsi`, `harga`, `satuan`, `stok`, `foto`, `STATUS`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Telur Ayam', 'Telur yang baik', 50000.00, 'Rak', 99.00, 'Telur_ayam.jpg', 'tersedia', '2026-09-15 14:18:41', '2026-09-21 06:18:24'),
(2, 1, 1, 'Telur Bebek', 'Telur sehat dan besar', 50000.00, 'Rak', 82.00, 'Telur_bebek.jpg', 'tersedia', '2026-09-15 14:21:51', '2026-10-03 10:11:30'),
(3, 1, 2, 'Ikan Nila', 'Ikan Nila fresh', 38000.00, 'kg', 42.00, 'Ikan_nila.jpg', 'tersedia', '2026-09-15 14:23:14', '2026-10-03 10:11:30'),
(4, 1, 3, 'Wortel', 'Wortel dari petani langsung', 5000.00, 'Kg', 60.00, 'wortel.jpg', 'tersedia', '2026-09-15 14:25:36', '2026-10-03 10:13:15'),
(5, 1, 4, 'Ayam Kampung', 'Ayam Fresh dari peternakan', 75000.00, 'Ekor', 19.00, 'Ayam_kampung.jpg', 'tersedia', '2026-09-15 14:27:13', '2026-10-03 10:11:30'),
(6, 1, 3, 'timun', 'Timun segar', 7000.00, 'pcs', 0.00, '', 'habis', '2026-09-17 01:23:15', '2026-09-17 02:06:22');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulasan`
--

CREATE TABLE `ulasan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `produk_id` int(11) NOT NULL,
  `pembeli_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `ulasan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `role` enum('admin','penjual','pembeli') NOT NULL,
  `STATUS` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `PASSWORD`, `no_hp`, `role`, `STATUS`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$KHvNP1hfIuSEJCi5MsNVquYONODyOWhu6FvRjdRtnuWu01SEq85iC', '080000000000', 'admin', 'aktif', '2026-09-15 13:30:43'),
(2, 'Muh Farhan', 'farhan', '$2y$10$LGdoFH/rVXiUVzo12jUCf.W.VYSiV0CYV2bIZdSinKXtabOd0S7bG', '081356397538', 'pembeli', 'aktif', '2026-09-15 13:47:29'),
(3, 'Muh Azhar', 'Azhar', '$2y$10$fJDD1yg9qtG0gbjQz838E.N7V8.Rwg6vexpBTgBaa57/WyH.2dmSK', '082375840285', 'penjual', 'aktif', '2026-09-15 13:56:40'),
(4, 'Muh Nur Isnaeni', 'Is Radikal', '$2y$10$VbMwS9.okP1yCN4D9McyQ.wmlodmyF4wl.m4KFHk8pVfdHoIvNhJe', '08472819203', 'penjual', 'aktif', '2026-09-16 00:48:31'),
(5, '', 'Algazali', '$2y$10$yDeWf7choPtKrPxjhZHf0O87fbC7tUhDmhrzr1lc6rlGK8jv0vg2G', NULL, 'penjual', 'aktif', '2026-09-18 14:47:01'),
(6, '', 'Meiy', '$2y$10$JyNp23WmX3nhoUIBEkL.Web.Qadlw6ktnCUcFTRPabmaioMDu0voe', NULL, 'pembeli', 'aktif', '2026-09-19 14:04:01'),
(7, '', 'Nurul', '$2y$10$3kHc5c4arOW7QrQxEgIace1NiVQC9ioHC/.y7iRNSZCSjWhUsLsJG', NULL, 'penjual', 'aktif', '2026-09-22 06:13:57'),
(8, 'muh fahrul', 'fahrul', '$2y$10$dDvp7TlpnCvmYbwxj54tZ.eQRCxBVYNweLSdb8wq8qDDrypf0Pzry', '083948572645', 'pembeli', 'aktif', '2026-10-05 02:28:36');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_pesanan` (`pesanan_id`),
  ADD KEY `fk_detail_pesanan_produk` (`produk_id`),
  ADD KEY `idx_detail_pesanan_penjual` (`pesanan_penjual_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indeks untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_keranjang_pembeli` (`pembeli_id`);

--
-- Indeks untuk tabel `keranjang_detail`
--
ALTER TABLE `keranjang_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_keranjang` (`keranjang_id`),
  ADD KEY `fk_detail_produk` (`produk_id`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_dibaca` (`dibaca`),
  ADD KEY `idx_notifications_user_dibaca` (`user_id`,`dibaca`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pembayaran_pesanan` (`pesanan_id`);

--
-- Indeks untuk tabel `pembeli`
--
ALTER TABLE `pembeli`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pembeli_user` (`user_id`);

--
-- Indeks untuk tabel `pengaturan_ongkir`
--
ALTER TABLE `pengaturan_ongkir`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pengiriman_pesanan` (`pesanan_id`);

--
-- Indeks untuk tabel `penjual`
--
ALTER TABLE `penjual`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_penjual_user` (`user_id`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `fk_pesanan_pembeli` (`pembeli_id`);

--
-- Indeks untuk tabel `pesanan_penjual`
--
ALTER TABLE `pesanan_penjual`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_kode_sub_pesanan` (`kode_sub_pesanan`),
  ADD KEY `idx_pesanan_penjual_pesanan` (`pesanan_id`),
  ADD KEY `idx_pesanan_penjual_penjual` (`penjual_id`),
  ADD KEY `idx_pesanan_penjual_status` (`status`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_produk_penjual` (`penjual_id`),
  ADD KEY `fk_produk_kategori` (`kategori_id`);

--
-- Indeks untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_ulasan` (`pesanan_id`,`produk_id`),
  ADD KEY `idx_ulasan_produk` (`produk_id`),
  ADD KEY `idx_ulasan_pembeli` (`pembeli_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `keranjang_detail`
--
ALTER TABLE `keranjang_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pembeli`
--
ALTER TABLE `pembeli`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `pengaturan_ongkir`
--
ALTER TABLE `pengaturan_ongkir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pengiriman`
--
ALTER TABLE `pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `penjual`
--
ALTER TABLE `penjual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `pesanan_penjual`
--
ALTER TABLE `pesanan_penjual`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pesanan_penjual` FOREIGN KEY (`pesanan_penjual_id`) REFERENCES `pesanan_penjual` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_pesanan_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang`
--
ALTER TABLE `keranjang`
  ADD CONSTRAINT `fk_keranjang_pembeli` FOREIGN KEY (`pembeli_id`) REFERENCES `pembeli` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang_detail`
--
ALTER TABLE `keranjang_detail`
  ADD CONSTRAINT `fk_detail_keranjang` FOREIGN KEY (`keranjang_id`) REFERENCES `keranjang` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `fk_pembayaran_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembeli`
--
ALTER TABLE `pembeli`
  ADD CONSTRAINT `fk_pembeli_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengiriman`
--
ALTER TABLE `pengiriman`
  ADD CONSTRAINT `fk_pengiriman_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penjual`
--
ALTER TABLE `penjual`
  ADD CONSTRAINT `fk_penjual_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_pembeli` FOREIGN KEY (`pembeli_id`) REFERENCES `pembeli` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan_penjual`
--
ALTER TABLE `pesanan_penjual`
  ADD CONSTRAINT `fk_pesanan_penjual_penjual` FOREIGN KEY (`penjual_id`) REFERENCES `penjual` (`id`),
  ADD CONSTRAINT `fk_pesanan_penjual_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produk_penjual` FOREIGN KEY (`penjual_id`) REFERENCES `penjual` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ulasan`
--
ALTER TABLE `ulasan`
  ADD CONSTRAINT `fk_ulasan_pembeli` FOREIGN KEY (`pembeli_id`) REFERENCES `pembeli` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ulasan_pesanan` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ulasan_produk` FOREIGN KEY (`produk_id`) REFERENCES `produk` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

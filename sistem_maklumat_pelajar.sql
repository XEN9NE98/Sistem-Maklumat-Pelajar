-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 08:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_maklumat_pelajar`
--

-- --------------------------------------------------------

--
-- Table structure for table `akademik`
--

CREATE TABLE `akademik` (
  `id_akademik` int(11) NOT NULL,
  `ic_pelajar` char(12) NOT NULL,
  `tahun_penggal` year(4) NOT NULL,
  `kehadiran_penggal_satu` decimal(5,2) DEFAULT NULL,
  `keputusan_penggal_satu` decimal(5,2) DEFAULT NULL,
  `kehadiran_penggal_dua` decimal(5,2) DEFAULT NULL,
  `keputusan_penggal_dua` decimal(5,2) DEFAULT NULL,
  `upkk` varchar(10) DEFAULT NULL,
  `sdea` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akademik`
--

INSERT INTO `akademik` (`id_akademik`, `ic_pelajar`, `tahun_penggal`, `kehadiran_penggal_satu`, `keputusan_penggal_satu`, `kehadiran_penggal_dua`, `keputusan_penggal_dua`, `upkk`, `sdea`) VALUES
(2, '110505108901', '2025', 92.30, 75.90, 88.20, 78.60, '4A, 3B', 'Jayyid Jid'),
(3, '120312045678', '2025', 96.00, 82.40, 94.50, 85.20, '5A, 2B', 'Mumtaz'),
(4, '130208076543', '2025', 89.20, 68.60, 91.80, 72.30, '3A, 4B', 'Jayyid'),
(5, '120815034567', '2025', 93.80, 79.20, 90.40, 81.60, '4A, 3B', 'Jayyid Jid'),
(6, '111122105678', '2025', 87.60, 65.40, 89.20, 69.80, '2A, 5B', 'Jayyid'),
(7, '120704078901', '2025', 94.20, 88.60, 96.80, 91.40, '6A, 1B', 'Mumtaz'),
(8, '101209045612', '2025', 91.40, 73.20, 88.60, 76.80, '3A, 4B', 'Jayyid Jid'),
(9, '110518092345', '2025', 95.60, 84.40, 93.20, 87.40, '5A, 2B', 'Mumtaz'),
(10, '100925067890', '2025', 86.80, 71.60, 90.40, 74.20, '3A, 4B', 'Jayyid'),
(11, '110612054321', '2025', 92.20, 80.40, 89.60, 83.80, '4A, 3B', 'Jayyid Jid'),
(12, '100306081234', '2025', 88.40, 67.20, 91.80, 70.60, '2A, 5B', 'Jayyid'),
(13, '110927036789', '2025', 97.20, 86.80, 95.40, 89.20, '6A, 1B', 'Mumtaz'),
(14, '100730109876', '2025', 90.60, 72.40, 87.20, 75.80, '3A, 4B', 'Jayyid Jid'),
(15, '111215043210', '2025', 93.40, 81.60, 91.00, 84.40, '5A, 2B', 'Jayyid Jid'),
(16, '100403126543', '2025', 85.20, 64.80, 88.60, 68.20, '2A, 5B', 'Jayyid'),
(17, '111025078654', '2025', 96.80, 87.40, 94.20, 90.60, '6A, 1B', 'Mumtaz'),
(18, '100818095432', '2025', 89.60, 70.80, 92.40, 74.60, '3A, 4B', 'Jayyid'),
(19, '111130087651', '2025', 91.80, 78.20, 88.40, 81.60, '4A, 3B', 'Jayyid Jid'),
(20, '100507041987', '2025', 87.20, 66.40, 90.80, 69.80, '2A, 5B', 'Jayyid'),
(21, '110919058765', '2025', 95.40, 85.60, 93.80, 88.40, '5A, 2B', 'Mumtaz');

-- --------------------------------------------------------

--
-- Table structure for table `bantuan`
--

CREATE TABLE `bantuan` (
  `id_bantuan` int(11) NOT NULL,
  `ic_pelajar` char(12) NOT NULL,
  `tahun_penggal` year(4) NOT NULL,
  `anak_orang_asli_islam` tinyint(1) DEFAULT NULL,
  `anak_yatim` tinyint(1) DEFAULT NULL,
  `skim_pinjaman_kitab` tinyint(1) DEFAULT NULL,
  `skim_pinjaman_spbt` tinyint(1) DEFAULT NULL,
  `makanan_sihat` tinyint(1) DEFAULT NULL,
  `pakaian` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bantuan`
--

INSERT INTO `bantuan` (`id_bantuan`, `ic_pelajar`, `tahun_penggal`, `anak_orang_asli_islam`, `anak_yatim`, `skim_pinjaman_kitab`, `skim_pinjaman_spbt`, `makanan_sihat`, `pakaian`) VALUES
(2, '110505108901', '2025', 0, 0, 1, 1, 1, 0),
(3, '120312045678', '2025', 0, 0, 0, 1, 1, 1),
(4, '130208076543', '2025', 0, 0, 1, 0, 1, 0),
(5, '120815034567', '2025', 0, 1, 1, 1, 1, 1),
(6, '111122105678', '2025', 0, 0, 0, 1, 0, 0),
(7, '120704078901', '2025', 0, 1, 1, 1, 1, 1),
(8, '101209045612', '2025', 0, 0, 1, 1, 1, 0),
(9, '110518092345', '2025', 1, 1, 1, 1, 1, 1),
(10, '100925067890', '2025', 0, 0, 0, 1, 1, 0),
(11, '110612054321', '2025', 0, 1, 1, 1, 1, 1),
(12, '100306081234', '2025', 0, 0, 1, 0, 1, 0),
(13, '110927036789', '2025', 0, 1, 1, 1, 1, 1),
(14, '100730109876', '2025', 0, 0, 0, 1, 1, 0),
(15, '111215043210', '2025', 0, 1, 1, 1, 1, 1),
(16, '100403126543', '2025', 0, 0, 1, 1, 0, 0),
(17, '111025078654', '2025', 0, 1, 1, 1, 1, 1),
(18, '100818095432', '2025', 0, 0, 0, 1, 1, 0),
(19, '111130087651', '2025', 0, 1, 1, 1, 1, 1),
(20, '100507041987', '2025', 0, 0, 1, 0, 1, 0),
(21, '110919058765', '2025', 0, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `kod_sekolah` varchar(20) NOT NULL,
  `darjah_kelas` varchar(10) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `guru_kelas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `kod_sekolah`, `darjah_kelas`, `nama_kelas`, `guru_kelas`) VALUES
(6, 'SAKNJ/LDG/10018', '2', 'Ibnu Qayyum', 'Ustaz Ahmad Bin Abdullah'),
(7, 'SAKNJ/LDG/10018', '3', 'Ibnu Madjah', 'Ustaz Ibrahim Bin Omar'),
(8, 'SAKNJ/LDG/10018', '4', 'Ibnu Khaldun', 'Ustazah Fatimah Binti Hassan'),
(9, 'SAKNJ/LDG/10018', '5', 'Ibnu Qayyum', 'Ustaz Ahmad Bin Abdullah'),
(10, 'SAKNJ/LDG/10018', '6', 'Ibnu Madjah', 'Ustaz Ibrahim Bin Omar'),
(11, 'SAKNJ/LDG/10018', '1', 'Ibnu Sina', 'Mohd Nazaruddin Bin Taib');

-- --------------------------------------------------------

--
-- Table structure for table `kokurikulum`
--

CREATE TABLE `kokurikulum` (
  `id_kokurikulum` int(11) NOT NULL,
  `ic_pelajar` char(12) NOT NULL,
  `tahun_penggal` year(4) NOT NULL,
  `persatuan_kelab` varchar(100) NOT NULL,
  `jawatan_persatuan_kelab` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kokurikulum`
--

INSERT INTO `kokurikulum` (`id_kokurikulum`, `ic_pelajar`, `tahun_penggal`, `persatuan_kelab`, `jawatan_persatuan_kelab`) VALUES
(4, '130208076543', '2025', 'Kelab Nadi Ansar', 'Setiausaha'),
(5, '120815034567', '2025', 'Kelab Nadi Ansar', 'Naib Pengerusi'),
(6, '111122105678', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(7, '120704078901', '2025', 'Kelab Nadi Ansar', 'Bendahari'),
(8, '101209045612', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(10, '100925067890', '2025', 'Kelab Nadi Ansar', 'Kapten'),
(11, '110612054321', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(12, '100306081234', '2025', 'Kelab Nadi Ansar', 'Naib Setiausaha'),
(13, '110927036789', '2025', 'Kelab Nadi Ansar', 'Pengerusi'),
(16, '100403126543', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(18, '100818095432', '2025', 'Kelab Nadi Ansar', 'Naib Kapten'),
(19, '111130087651', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(20, '100507041987', '2025', 'Kelab Nadi Ansar', 'Pengerusi'),
(21, '110919058765', '2025', 'Kelab Nadi Ansar', 'Ahli Biasa'),
(22, '110518092345', '2026', 'Kelab Nadi Ansar', 'Setiausaha'),
(23, '111025078654', '2025', 'Kelab Nadi Ansar', 'Ahli');

-- --------------------------------------------------------

--
-- Table structure for table `pelajar`
--

CREATE TABLE `pelajar` (
  `ic_pelajar` char(12) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `ic_waris` bigint(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jantina` enum('lelaki','perempuan') NOT NULL,
  `kaum` varchar(50) NOT NULL,
  `status_pelajar` enum('kandung','tiri','angkat') NOT NULL,
  `status_penjaga` enum('ibu bapa','ibu tunggal','bapa tunggal','penjaga') NOT NULL,
  `sijil_lahir` varchar(50) DEFAULT NULL,
  `warganegara` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelajar`
--

INSERT INTO `pelajar` (`ic_pelajar`, `id_kelas`, `ic_waris`, `nama`, `jantina`, `kaum`, `status_pelajar`, `status_penjaga`, `sijil_lahir`, `warganegara`) VALUES
('100306081234', 10, 740206081234, 'Hafiz Muhamad bin Karim Abdullah', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A25678', 'Malaysia'),
('100403126543', 6, 820103126543, 'Rayyan Muaz bin Osman Daud', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A29890', 'Malaysia'),
('100507041987', 8, 660507041987, 'Hakim Luqman bin Nasir Hamid', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A33012', 'Malaysia'),
('100730109876', 11, 790830109876, 'Irfan Hakim bin Razak Othman', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A27234', 'Malaysia'),
('100818095432', 7, 770918095432, 'Amir Danial bin Rashid Yusof', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A31456', 'Malaysia'),
('100925067890', 9, 760425067890, 'Danial Hakeem bin Zainal Abidin', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A23012', 'Malaysia'),
('101209045612', 8, 660309045612, 'Muhammad Zikri bin Mohd Aziz Rahman', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A21456', 'Malaysia'),
('110505108901', 11, 720508146789, 'Ahmad Hakim bin Ahmad Sulaiman', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A15234', 'Malaysia'),
('110518092345', 8, 730718092345, 'Aina Sofea binti Ismail', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A22789', 'Malaysia'),
('110612054321', 9, 710812054321, 'Nurul Hidayah binti Hassan', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A24345', 'Malaysia'),
('110919058765', 8, 720819058765, 'Alya Medina binti Ibrahim', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A34345', 'Malaysia'),
('110927036789', 10, 681127036789, 'Aishah Maisarah binti Mohd', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A26901', 'Malaysia'),
('111025078654', 6, 691225078654, 'Irdina Zahra binti Yaacob', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A30123', 'Malaysia'),
('111122105678', 7, 780622105678, 'Arif Rahman bin Ibrahim Ali', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A19890', 'Malaysia'),
('111130087651', 7, 751030087651, 'Nur Qistina binti Razak', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A32789', 'Malaysia'),
('111215043210', 11, 700415043210, 'Maryam Sofea binti Said', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A28567', 'Malaysia'),
('120312045678', 11, 680912037845, 'Nur Aisyah binti Mohd Yusof', 'perempuan', 'Melayu', 'kandung', 'ibu bapa', 'A16789', 'Malaysia'),
('120704078901', 7, 721104078901, 'Fatimah Az-Zahra binti Omar', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A20123', 'Malaysia'),
('120815034567', 6, 690815024567, 'Siti Nurhaliza binti Ahmad', 'perempuan', 'Melayu', 'kandung', 'ibu tunggal', 'A18567', 'Malaysia'),
('130208076543', 6, 751203089456, 'Mohd Fikri bin Hassan Mahmud', 'lelaki', 'Melayu', 'kandung', 'ibu bapa', 'A17234', 'Malaysia');

-- --------------------------------------------------------

--
-- Table structure for table `pelajar_tahun`
--

CREATE TABLE `pelajar_tahun` (
  `ic_pelajar` char(12) NOT NULL,
  `tahun_penggal` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pelajar_tahun`
--

INSERT INTO `pelajar_tahun` (`ic_pelajar`, `tahun_penggal`) VALUES
('100306081234', '2025'),
('100403126543', '2025'),
('100507041987', '2025'),
('100730109876', '2025'),
('100818095432', '2025'),
('100925067890', '2025'),
('101209045612', '2025'),
('110505108901', '2025'),
('110518092345', '2025'),
('110518092345', '2026'),
('110612054321', '2025'),
('110919058765', '2025'),
('110927036789', '2025'),
('111025078654', '2025'),
('111122105678', '2025'),
('111130087651', '2025'),
('111215043210', '2025'),
('120312045678', '2025'),
('120704078901', '2025'),
('120815034567', '2025'),
('130208076543', '2025');

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` varchar(50) NOT NULL,
  `nama_pengguna` varchar(100) NOT NULL,
  `kata_laluan` varchar(255) NOT NULL,
  `jenis_pengguna` enum('admin','guru') NOT NULL,
  `kod_sekolah` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama_pengguna`, `kata_laluan`, `jenis_pengguna`, `kod_sekolah`) VALUES
('admin', 'Pentadbir Sistem', '$2y$10$/ekX7YAYZSsw1y25o3/eXeXAOzSiJL3AjheHm0noZqDo51eXVy25O', 'admin', 'SAKNJ/LDG/10018'),
('guru001', 'Ustaz Ahmad bin Abdullah', '$2y$10$gXFFPYBakqjvJ6WnW2uvWOnOtngx56yCIh4h9/tJt46c5ljHLF.cm', 'guru', 'SAKNJ/LDG/10018'),
('guru002', 'Ustazah Fatimah binti Hassan', '$2y$10$IlTJFBO.TFgPxgCMgTMb2.sGLS4EAbxv7kvkgNW9OdjwF3oVpFHu6', 'guru', 'SAKNJ/LDG/10018'),
('guru003', 'Ustaz Ibrahim bin Omar', '$2y$10$lTnjBBv39kjdp8ETPwTg3.vEn1RijZTUp9LvjihwWVwQPTttDTv5e', 'guru', 'SAKNJ/LDG/10018'),
('guru007', 'Ustaz Ahmad Fawzi bin Abd Kadir', '$2y$10$SIyANKiIHuhKOSfsRO8N0epwjQ38zql7X46xw/W9tS7QerbqqXe2K', 'guru', 'SAKNJ/LDG/10018');

-- --------------------------------------------------------

--
-- Table structure for table `penjaga`
--

CREATE TABLE `penjaga` (
  `ic_waris` bigint(20) NOT NULL,
  `nama_waris` varchar(100) NOT NULL,
  `status_waris` enum('bapa','ibu','penjaga','datuk','nenek') NOT NULL,
  `nombor_telefon_waris` varchar(20) DEFAULT NULL,
  `alamat` text NOT NULL,
  `poskod` int(5) NOT NULL,
  `negeri` varchar(50) NOT NULL,
  `bilangan_tanggungan` int(11) DEFAULT NULL,
  `pekerjaan_bapa` varchar(100) DEFAULT NULL,
  `pendapatan_bapa` decimal(10,2) DEFAULT NULL,
  `pekerjaan_ibu` varchar(100) DEFAULT NULL,
  `pendapatan_ibu` decimal(10,2) DEFAULT NULL,
  `pekerjaan_penjaga` varchar(100) DEFAULT NULL,
  `pendapatan_penjaga` decimal(10,2) DEFAULT NULL,
  `jumlah_pendapatan` decimal(10,2) DEFAULT NULL,
  `pendapatan_perkapita` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penjaga`
--

INSERT INTO `penjaga` (`ic_waris`, `nama_waris`, `status_waris`, `nombor_telefon_waris`, `alamat`, `poskod`, `negeri`, `bilangan_tanggungan`, `pekerjaan_bapa`, `pendapatan_bapa`, `pekerjaan_ibu`, `pendapatan_ibu`, `pekerjaan_penjaga`, `pendapatan_penjaga`, `jumlah_pendapatan`, `pendapatan_perkapita`) VALUES
(51236582212, 'Warimah binti Manan', 'ibu', '015-3685555', 'akvbalkvnasfvns;bvsfvn;slfvn;sfvn', 13213, 'Terengganu', 2, 'slvnas;lnv;salnv;slajnvs;alv', 1321321.00, 'dvasvknasf;vmsa\'v;s', 321213.00, '', 0.00, 1642534.00, 821267.00),
(523350000000, 'Muhammad', 'ibu', '015-2333333', 'Jalan Harmoni', 32132, 'Perlis', 2, '', 0.00, 'Kerani', 2000.00, '', 0.00, 2000.00, 1000.00),
(660309045612, 'Mohd Aziz bin Rahman', 'bapa', '018-3456780', 'No 67, Taman Sejahtera', 84950, 'Johor', 4, 'Kerani Kerajaan', 4200.00, 'Pembantu Tadbir', 2600.00, NULL, 0.00, 6800.00, 1700.00),
(660507041987, 'Nasir bin Hamid', 'bapa', '013-4567890', 'No 29, Taman Mutiara', 84950, 'Johor', 4, 'Pemandu Bas', 2800.00, 'Kerani Klinik', 2400.00, NULL, 0.00, 5200.00, 1300.00),
(680912037845, 'Siti Aminah binti Yusof', 'ibu', '017-2345678', 'Kampung Sungai Rambai', 84900, 'Johor', 4, 'Petani', 2800.00, 'Peniaga Kecil', 1200.00, NULL, 0.00, 4000.00, 1000.00),
(681127036789, 'Aishah binti Mohd', 'ibu', '012-8901234', 'Kampung Padang Luas', 84900, 'Johor', 2, NULL, 0.00, 'Pembantu Rumah', 1200.00, NULL, 0.00, 1200.00, 600.00),
(690815024567, 'Fatimah binti Ahmad', 'ibu', '012-5678901', 'Kampung Parit Jawa', 84950, 'Johor', 5, NULL, 0.00, 'Guru Tadika', 1800.00, NULL, 0.00, 1800.00, 360.00),
(691225078654, 'Zaleha binti Yaacob', 'ibu', '014-7890123', 'Kampung Tok Muda', 84950, 'Johor', 4, NULL, 0.00, 'Guru Tuisyen', 2200.00, NULL, 0.00, 2200.00, 550.00),
(700415043210, 'Mariam binti Said', 'ibu', '019-5678901', 'Kampung Seberang Sungai', 84900, 'Johor', 5, 'Nelayan', 1800.00, 'Penjual Kuih', 800.00, NULL, 0.00, 2600.00, 520.00),
(710812054321, 'Rohani binti Hassan', 'ibu', '013-9012345', 'Kampung Sungai Kesang', 84950, 'Johor', 3, 'Petani', 2000.00, 'Suri Rumah', 0.00, NULL, 0.00, 2000.00, 666.67),
(720508146789, 'Ahmad bin Sulaiman', 'bapa', '019-3456789', 'No 12, Jalan Mawar, Taman Seri', 84900, 'Johor', 3, 'Kerani', 3000.00, 'Suri Rumah', 0.00, NULL, 0.00, 3000.00, 1000.00),
(720819058765, 'Normah binti Ibrahim', 'ibu', '017-8901234', 'Kampung Parit Haji Taib', 84900, 'Johor', 3, NULL, 0.00, 'Cleaner Hospital', 1500.00, NULL, 0.00, 1500.00, 500.00),
(721104078901, 'Zainab binti Omar', 'ibu', '011-2345679', 'Kampung Bukit Tinggi', 84900, 'Johor', 2, 'Mekanik', 3800.00, 'Suri Rumah', 0.00, NULL, 0.00, 3800.00, 1900.00),
(730718092345, 'Khadijah binti Ismail', 'ibu', '014-5678902', 'Kampung Air Hitam', 84900, 'Johor', 3, NULL, 0.00, 'Peniaga', 2500.00, NULL, 0.00, 2500.00, 833.33),
(740206081234, 'Karim bin Abdullah', 'bapa', '017-1234567', 'No 91, Lorong Melur', 84900, 'Johor', 4, 'Penjaga Keselamatan', 2400.00, 'Cleaner', 1600.00, NULL, 0.00, 4000.00, 1000.00),
(751030087651, 'Halimah binti Razak', 'ibu', '015-1234568', 'Kampung Parit Sidang', 84900, 'Johor', 2, 'Mekanik', 3200.00, 'Suri Rumah', 0.00, NULL, 0.00, 3200.00, 1600.00),
(751203089456, 'Hassan bin Mahmud', 'bapa', '013-9876543', 'No 88, Lorong Delima 3', 84900, 'Johor', 2, 'Pemandu Teksi', 2200.00, 'Kerani', 2800.00, NULL, 0.00, 5000.00, 2500.00),
(760425067890, 'Zainal bin Abidin', 'bapa', '015-7890124', 'No 45, Jalan Cempaka', 84900, 'Johor', 2, 'Buruh Kilang', 2600.00, 'Tukang Masak', 1800.00, NULL, 0.00, 4400.00, 2200.00),
(770918095432, 'Rashid bin Yusof', 'bapa', '018-9012345', 'No 78, Lorong Bougainvilla', 84900, 'Johor', 3, 'Kerani Bank', 4500.00, 'Jururawat', 3800.00, NULL, 0.00, 8300.00, 2766.67),
(780622105678, 'Ibrahim bin Ali', 'bapa', '016-7890123', 'No 23, Jalan Orkid', 84900, 'Johor', 3, 'Tukang Kayu', 3200.00, 'Jahit Baju', 1500.00, NULL, 0.00, 4700.00, 1566.67),
(790830109876, 'Razak bin Othman', 'bapa', '016-2345678', 'No 34, Taman Harmoni', 84950, 'Johor', 3, 'Pemandu Lori', 3000.00, 'Kerani', 2200.00, NULL, 0.00, 5200.00, 1733.33),
(820103126543, 'Osman bin Daud', 'bapa', '011-6789012', 'No 56, Jalan Kenanga', 84900, 'Johor', 2, 'Tukang Elektrik', 3500.00, 'Tailor', 2000.00, NULL, 0.00, 5500.00, 2750.00);

-- --------------------------------------------------------

--
-- Table structure for table `sekolah`
--

CREATE TABLE `sekolah` (
  `kod_sekolah` varchar(20) NOT NULL,
  `nama_sekolah` varchar(200) NOT NULL,
  `nama_guru_besar` varchar(100) NOT NULL,
  `alamat_sekolah` text NOT NULL,
  `poskod` int(5) NOT NULL,
  `daerah` varchar(50) NOT NULL,
  `negeri` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telefon` varchar(20) DEFAULT NULL,
  `tarikh_tubuh_sekolah` date DEFAULT NULL,
  `jenis_sekolah` enum('Sekolah Agama Petang','Sekolah Agama Pagi Petang') NOT NULL,
  `sesi` enum('pagi','petang','penuh') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sekolah`
--

INSERT INTO `sekolah` (`kod_sekolah`, `nama_sekolah`, `nama_guru_besar`, `alamat_sekolah`, `poskod`, `daerah`, `negeri`, `email`, `no_telefon`, `tarikh_tubuh_sekolah`, `jenis_sekolah`, `sesi`) VALUES
('SAKNJ/LDG/10018', 'Sekolah Agama Bukit Banjar', 'Mohd Nazaruddin bin Taib', 'Kampung Bukit Banjar', 84900, 'Tangkak', 'Johor', 'sabukitbanjar23@gmail.com', '013-6610017', '1962-02-01', 'Sekolah Agama Petang', 'petang');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akademik`
--
ALTER TABLE `akademik`
  ADD PRIMARY KEY (`id_akademik`),
  ADD UNIQUE KEY `unique_akademik_per_student_year` (`ic_pelajar`,`tahun_penggal`),
  ADD KEY `idx_akademik_tahun` (`tahun_penggal`);

--
-- Indexes for table `bantuan`
--
ALTER TABLE `bantuan`
  ADD PRIMARY KEY (`id_bantuan`),
  ADD UNIQUE KEY `unique_bantuan_per_student_year` (`ic_pelajar`,`tahun_penggal`),
  ADD KEY `idx_bantuan_tahun` (`tahun_penggal`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `idx_kelas_sekolah` (`kod_sekolah`);

--
-- Indexes for table `kokurikulum`
--
ALTER TABLE `kokurikulum`
  ADD PRIMARY KEY (`id_kokurikulum`),
  ADD UNIQUE KEY `unique_kokurikulum_per_student_year` (`ic_pelajar`,`tahun_penggal`),
  ADD KEY `ic_pelajar` (`ic_pelajar`),
  ADD KEY `idx_kokurikulum_tahun` (`tahun_penggal`);

--
-- Indexes for table `pelajar`
--
ALTER TABLE `pelajar`
  ADD PRIMARY KEY (`ic_pelajar`),
  ADD KEY `ic_waris` (`ic_waris`),
  ADD KEY `idx_pelajar_nama` (`nama`),
  ADD KEY `idx_pelajar_kelas` (`id_kelas`);

--
-- Indexes for table `pelajar_tahun`
--
ALTER TABLE `pelajar_tahun`
  ADD PRIMARY KEY (`ic_pelajar`,`tahun_penggal`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD KEY `idx_pengguna_sekolah` (`kod_sekolah`);

--
-- Indexes for table `penjaga`
--
ALTER TABLE `penjaga`
  ADD PRIMARY KEY (`ic_waris`);

--
-- Indexes for table `sekolah`
--
ALTER TABLE `sekolah`
  ADD PRIMARY KEY (`kod_sekolah`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akademik`
--
ALTER TABLE `akademik`
  MODIFY `id_akademik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `bantuan`
--
ALTER TABLE `bantuan`
  MODIFY `id_bantuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kokurikulum`
--
ALTER TABLE `kokurikulum`
  MODIFY `id_kokurikulum` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `akademik`
--
ALTER TABLE `akademik`
  ADD CONSTRAINT `akademik_ibfk_1` FOREIGN KEY (`ic_pelajar`) REFERENCES `pelajar` (`ic_pelajar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_akademik_pelajar_tahun` FOREIGN KEY (`ic_pelajar`,`tahun_penggal`) REFERENCES `pelajar_tahun` (`ic_pelajar`, `tahun_penggal`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `bantuan`
--
ALTER TABLE `bantuan`
  ADD CONSTRAINT `bantuan_ibfk_1` FOREIGN KEY (`ic_pelajar`) REFERENCES `pelajar` (`ic_pelajar`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bantuan_pelajar_tahun` FOREIGN KEY (`ic_pelajar`,`tahun_penggal`) REFERENCES `pelajar_tahun` (`ic_pelajar`, `tahun_penggal`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`kod_sekolah`) REFERENCES `sekolah` (`kod_sekolah`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kokurikulum`
--
ALTER TABLE `kokurikulum`
  ADD CONSTRAINT `fk_kokurikulum_pelajar_tahun` FOREIGN KEY (`ic_pelajar`,`tahun_penggal`) REFERENCES `pelajar_tahun` (`ic_pelajar`, `tahun_penggal`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kokurikulum_ibfk_1` FOREIGN KEY (`ic_pelajar`) REFERENCES `pelajar` (`ic_pelajar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pelajar`
--
ALTER TABLE `pelajar`
  ADD CONSTRAINT `pelajar_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pelajar_ibfk_2` FOREIGN KEY (`ic_waris`) REFERENCES `penjaga` (`ic_waris`) ON UPDATE CASCADE;

--
-- Constraints for table `pelajar_tahun`
--
ALTER TABLE `pelajar_tahun`
  ADD CONSTRAINT `pelajar_tahun_ibfk_1` FOREIGN KEY (`ic_pelajar`) REFERENCES `pelajar` (`ic_pelajar`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD CONSTRAINT `fk_pengguna_sekolah` FOREIGN KEY (`kod_sekolah`) REFERENCES `sekolah` (`kod_sekolah`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

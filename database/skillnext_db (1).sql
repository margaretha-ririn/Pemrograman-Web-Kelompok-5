-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 01:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skillnext_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `id` int(11) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kategori` varchar(100) DEFAULT 'Umum',
  `banner` varchar(255) DEFAULT NULL,
  `total_member` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`id`, `nama`, `deskripsi`, `created_by`, `created_at`, `kategori`, `banner`, `total_member`) VALUES
(1, 'Public Speaking', 'Find your voice. Share your message. Inspire others.', 1, '2026-06-01 05:04:52', 'Public Speaking', NULL, 100001),
(2, 'UI/UX Communities', 'Komunitas desainer UI/UX Indonesia', 1, '2026-06-01 05:04:52', 'Desain', NULL, 5001),
(3, 'Programming Communities', 'Berbagi ilmu pemrograman bersama', 1, '2026-06-01 05:04:52', 'Teknologi', NULL, 8000),
(4, 'Musik Communities', 'Komunitas pecinta musik Indonesia', 1, '2026-06-01 05:04:52', 'Musik', NULL, 3001);

-- --------------------------------------------------------

--
-- Table structure for table `community_comments`
--

CREATE TABLE `community_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_comments`
--

INSERT INTO `community_comments` (`id`, `post_id`, `user_id`, `komentar`, `created_at`) VALUES
(1, 4, 5, 'Coba Figma Community', '2026-06-08 09:00:49');

-- --------------------------------------------------------

--
-- Table structure for table `community_members`
--

CREATE TABLE `community_members` (
  `id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `community_members`
--

INSERT INTO `community_members` (`id`, `community_id`, `user_id`, `joined_at`) VALUES
(2, 1, 4, '2026-06-01 05:21:48'),
(3, 4, 4, '2026-06-01 05:22:34'),
(4, 2, 5, '2026-06-08 08:43:17');

-- --------------------------------------------------------

--
-- Table structure for table `community_messages`
--

CREATE TABLE `community_messages` (
  `id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `konten` text NOT NULL,
  `tipe` enum('butuh_skill','tawarkan_skill') DEFAULT 'butuh_skill',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`id`, `community_id`, `user_id`, `konten`, `tipe`, `created_at`, `gambar`) VALUES
(2, 1, 1, 'Halo semua! Siapa yang mau latihan public speaking bareng minggu ini?', 'tawarkan_skill', '2026-06-03 08:46:49', NULL),
(3, 1, 1, 'Ada yang butuh tips mengatasi nervous saat presentasi? Aku bisa bantu!', 'tawarkan_skill', '2026-06-03 08:46:49', NULL),
(4, 2, 1, 'Lagi belajar Figma, ada yang mau sharing resource gratis?', 'butuh_skill', '2026-06-03 08:46:49', NULL),
(5, 3, 1, 'Siapa yang bisa ajarin dasar Python? Aku pemula banget nih', 'butuh_skill', '2026-06-03 08:46:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) DEFAULT 0.00,
  `thumbnail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `author` varchar(100) DEFAULT NULL,
  `tipe` enum('gratis','berbayar') DEFAULT 'gratis',
  `author_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `judul`, `deskripsi`, `harga`, `thumbnail`, `created_at`, `author`, `tipe`, `author_id`) VALUES
(1, 'Belajar Kunci Dasar Gitar', 'Belajar gitar dari nol sampai bisa memainkan lagu', 150000.00, 'belajargitar.jpg', '2026-05-18 03:43:57', 'William Tanu Wijaya', 'berbayar', 1),
(2, 'Belajar Dasar Pemrograman', 'Pengenalan logika dan dasar coding untuk pemula', 200000.00, 'menoding.png', '2026-05-18 03:43:57', 'Cleo Tahir', 'berbayar', 2),
(3, 'Belajar Memasak', 'Teknik memasak dasar hingga masakan lezat', 0.00, 'masak.png', '2026-05-18 03:43:57', 'Ferlita Hulu', 'gratis', 3),
(4, 'UI/UX Design Mastery', 'Belajar desain UI/UX dari dasar hingga mahir', 120000.00, 'desain.jpg', '2026-06-01 05:19:38', 'Ririn Simanjuntak', 'berbayar', 1),
(5, 'Public Speaking Dasar', 'Belajar berbicara di depan umum dengan percaya diri', 0.00, 'speaking.jpg', '2026-06-01 05:19:38', 'Sultan Tri', 'gratis', 6),
(6, 'Belajar Bahasa Inggris', 'Percakapan bahasa Inggris sehari-hari', 75000.00, 'english.jpg', '2026-06-01 05:19:38', 'Ivana Siagian', 'berbayar', 4),
(7, 'Dasar Desain Grafis', 'Pengenalan tools desain grafis untuk pemula', 0.00, 'grafis.jpg', '2026-06-01 05:19:38', 'Ferlita Hulu', 'gratis', 3);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp(),
  `progress` int(11) DEFAULT 0,
  `next_session` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `user_id`, `course_id`, `tanggal`, `progress`, `next_session`, `created_at`) VALUES
(1, 1, 1, '2026-05-18 03:45:07', 45, '2025-11-22', '2026-05-18 03:49:21'),
(2, 1, 2, '2026-05-18 03:45:07', 65, '2025-11-22', '2026-05-18 03:49:21'),
(3, 1, 3, '2026-05-18 03:45:07', 100, '2025-11-22', '2026-05-18 03:49:21'),
(4, 4, 3, '2026-06-01 05:24:32', 0, NULL, '2026-06-01 05:24:32'),
(5, 4, 5, '2026-06-02 04:23:50', 100, NULL, '2026-06-02 04:23:50'),
(6, 3, 3, '2026-06-08 03:57:08', 0, NULL, '2026-06-08 03:57:08'),
(7, 4, 7, '2026-06-08 08:01:07', 0, NULL, '2026-06-08 08:01:07'),
(8, 5, 3, '2026-06-08 08:10:08', 0, NULL, '2026-06-08 08:10:08'),
(9, 5, 5, '2026-06-08 08:13:17', 0, NULL, '2026-06-08 08:13:17'),
(10, 5, 7, '2026-06-08 09:04:48', 0, NULL, '2026-06-08 09:04:48');

-- --------------------------------------------------------

--
-- Table structure for table `forum_comments`
--

CREATE TABLE `forum_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_comments`
--

INSERT INTO `forum_comments` (`id`, `post_id`, `user_id`, `komentar`, `created_at`) VALUES
(1, 1, 1, 'halo kak ririn! aku maulia nih lgi pengen share pengetahuan aku sedikit, kalo kakaknya mau boleh ni hubungi aku kak', '2026-05-18 03:41:13'),
(2, 1, 5, 'coba mulai belajar bahasa C dulu kak', '2026-06-08 09:04:19');

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE `forum_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `konten` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kategori` varchar(100) DEFAULT 'Umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forum_posts`
--

INSERT INTO `forum_posts` (`id`, `user_id`, `judul`, `konten`, `created_at`, `kategori`) VALUES
(1, 1, 'belajar coding untuk pemula mulai dari mana?', 'Halo semua! aku mahasiswa sems 1 di usu, aku mau mulai belajar coding tapi bingung harus mulai dari mana. Ada yang bisa kasih saran? Apakah harus mulai dari HTML dulu atau langsung Python?', '2026-05-18 03:40:07', 'Programming');

-- --------------------------------------------------------

--
-- Table structure for table `global_chats`
--

CREATE TABLE `global_chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `pengirim_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `dibaca` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `pengirim_id`, `penerima_id`, `pesan`, `dibaca`, `created_at`) VALUES
(1, 3, 1, 'halo', 0, '2026-05-19 03:41:17'),
(2, 4, 3, 'fer', 1, '2026-06-03 03:10:32'),
(3, 4, 2, 'gmna ya kak', 0, '2026-06-03 03:10:45'),
(4, 4, 3, 'hello', 1, '2026-06-07 11:47:21'),
(5, 4, 3, 'oi', 1, '2026-06-08 03:56:25'),
(6, 5, 2, 'maul', 0, '2026-06-08 10:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `metode` varchar(50) DEFAULT NULL,
  `status` enum('pending','sukses','gagal') DEFAULT 'pending',
  `invoice` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `foto_profil` varchar(255) DEFAULT 'default.jpg',
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `foto_profil`, `bio`, `created_at`, `phone`, `kota`) VALUES
(1, 'Ririn Margaretha Simanjuntak', 'ririnmargaretha10@gmail.com', '$2y$10$pfa37h3jmXTdg50jusWoXOhVZ0MbeOs6pqj.soQRtmim2a5PkHKP6', 'foto_1_1778649364.jpg', 'always sibuk 24/7', '2026-05-13 04:51:50', '85372610487', 'Medan, Sumatera Utara'),
(2, 'Maulia Revani Putri', 'mauliaja@gmail.com', '$2y$10$oCPZ.79US1OxN50A.GfRiuTC9YBMiTGRYwwMdp9icgtFEA0IbnlrK', 'default.jpg', NULL, '2026-05-13 05:24:47', NULL, NULL),
(3, 'ferlita hulu', 'ferlitak@gmail.com', '$2y$10$.i2kslaUK5d04XYvWr0SP.XFzEto7Rfnb6hHq1n/eeS9GnCXwUAUu', 'foto_3_1779162129.jpg', NULL, '2026-05-19 03:39:49', NULL, NULL),
(4, 'Febiola Aruan', 'febiola@gmail.com', '$2y$10$FYG4pld4aIN876pKB.9LXOFCIU3NVpIFJgUHglYYf2qlZlDA.3eRK', 'foto_4_1780663139.jpg', NULL, '2026-05-31 14:42:56', NULL, NULL),
(5, 'anisa', 'anisa@gmail.com', '$2y$10$BWQ6fqpcUm6xjcTJgYo/MurCMHQKWta.EqcKG/pCGHT.f8TvlVgZa', 'foto_5_1780916089.jpg', NULL, '2026-06-08 08:04:46', NULL, NULL),
(6, 'Sultan Tri', 'sultan@gmail.com', '', 'default.jpg', NULL, '2026-06-08 08:40:59', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_members`
--
ALTER TABLE `community_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_id` (`community_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `community_messages`
--
ALTER TABLE `community_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `community_id` (`community_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_author` (`author_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `global_chats`
--
ALTER TABLE `global_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_global_chat_user` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengirim_id` (`pengirim_id`),
  ADD KEY `penerima_id` (`penerima_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `community_members`
--
ALTER TABLE `community_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `community_messages`
--
ALTER TABLE `community_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `forum_comments`
--
ALTER TABLE `forum_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `global_chats`
--
ALTER TABLE `global_chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `community_members`
--
ALTER TABLE `community_members`
  ADD CONSTRAINT `community_members_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`),
  ADD CONSTRAINT `community_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);

--
-- Constraints for table `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD CONSTRAINT `forum_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`),
  ADD CONSTRAINT `forum_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `forum_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `global_chats`
--
ALTER TABLE `global_chats`
  ADD CONSTRAINT `fk_global_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`pengirim_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`penerima_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

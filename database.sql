-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Veritabanı: `arac_yonetim`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Tablo yapısı: `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo yapısı: `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_turkish_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `settings`
-- (SMTP Şifresi ve Hassas Veriler Temizlendi)
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_favicon', 'favicon_1764188456.png'),
('site_logo', 'logo_1764187220.png'),
('site_title', 'AHD Kampüs Araç Kayıt Sistemi'),
('site_url', 'http://localhost/arac_takip'),
('smtp_email', 'ornek_mail@gmail.com'),
('smtp_host', 'smtp.gmail.com'),
('smtp_password', 'BURAYA_GMAIL_UYGULAMA_SIFRENIZI_GIRIN'),
('smtp_port', '587'),
('smtp_secure', 'tls'),
('theme_color', '#3b136c');

-- --------------------------------------------------------

--
-- Tablo yapısı: `units`
--

CREATE TABLE `units` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `units`
-- (Birimler sabit veri olduğu için bırakıldı)
--

INSERT INTO `units` (`id`, `name`, `created_at`) VALUES
(1, 'Rektörlük', NOW()),
(2, 'Mühendislik Fakültesi', NOW()),
(3, 'Fen Edebiyat Fakültesi', NOW()),
(4, 'Ziraat Fakültesi', NOW()),
(5, 'Fen Bilimleri Enstitüsü', NOW());

-- --------------------------------------------------------

--
-- Tablo yapısı: `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `unit_id` int DEFAULT NULL,
  `tc_number` varchar(11) COLLATE utf8mb4_turkish_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_turkish_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `role` enum('admin','approver','user') COLLATE utf8mb4_turkish_ci DEFAULT 'user',
  `must_change_password` tinyint(1) DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
-- (Kullanıcı verileri temizlendi. Giriş için manuel admin eklenmelidir.)
--

-- --------------------------------------------------------

--
-- Tablo yapısı: `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plate` varchar(20) COLLATE utf8mb4_turkish_ci NOT NULL,
  `brand` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `model` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `year` int NOT NULL,
  `color` varchar(30) COLLATE utf8mb4_turkish_ci NOT NULL,
  `ownership` varchar(50) COLLATE utf8mb4_turkish_ci NOT NULL,
  `license_image` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_turkish_ci DEFAULT 'pending',
  `rejection_reason` varchar(255) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

ALTER TABLE `password_resets` ADD PRIMARY KEY (`id`);

ALTER TABLE `settings` ADD PRIMARY KEY (`setting_key`), ADD UNIQUE KEY `setting_key` (`setting_key`);

ALTER TABLE `units` ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tc_number` (`tc_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `unit_id` (`unit_id`);

ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate` (`plate`),
  ADD KEY `user_id` (`user_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

ALTER TABLE `password_resets` MODIFY `id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `units` MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `users` MODIFY `id` int NOT NULL AUTO_INCREMENT;
ALTER TABLE `vehicles` MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

ALTER TABLE `users` ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL;
ALTER TABLE `vehicles` ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

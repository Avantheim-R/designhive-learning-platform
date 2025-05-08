-- Create and select database
DROP DATABASE IF EXISTS designhive;
CREATE DATABASE designhive;
USE designhive;

-- Set character set
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- DesignHIve Learning Platform Database
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database structure
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chapter` int(11) NOT NULL,
  `phase` enum('text','video','minigame') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `materi_id` (`materi_id`),
  CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `grade` float DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `materi_id` (`materi_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `quiz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `materi_id` int(11) NOT NULL,
  `question_type` enum('multiple','dragdrop','match') NOT NULL,
  `question` text NOT NULL,
  `options` json NOT NULL,
  `correct_answer` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `materi_id` (`materi_id`),
  CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `quiz_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` float DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `attempted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `quiz_id` (`quiz_id`),
  CONSTRAINT `quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quiz` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_type` enum('multiple','dragdrop','match') NOT NULL,
  `question` text NOT NULL,
  `options` json NOT NULL,
  `correct_answer` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `score` float DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_result_id` int(11) NOT NULL,
  `certificate_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `exam_result_id` (`exam_result_id`),
  CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `certificates_ibfk_2` FOREIGN KEY (`exam_result_id`) REFERENCES `exam_results` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `gamification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `badges` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gamification_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test users (password for all users is 'password123')
INSERT INTO users (name, username, password, email, role, nis) VALUES
('Admin User', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@designhive.com', 'admin', NULL),
('Teacher User', 'teacher', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher@designhive.com', 'teacher', NULL),
('Student One', 'student1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student1@designhive.com', 'student', '2024001'),
('Student Two', 'student2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student2@designhive.com', 'student', '2024002');

-- Insert sample learning materials
INSERT INTO materi (chapter, phase, title, content, url) VALUES
(1, 'text', 'Pengenalan Desain Grafis', '<h2>Apa itu Desain Grafis?</h2><p>Desain grafis adalah seni komunikasi visual yang menggunakan gambar, teks, dan grafik untuk menyampaikan pesan.</p>', NULL),
(1, 'video', 'Tutorial Dasar Adobe Photoshop', NULL, 'https://www.youtube.com/embed/example1'),
(1, 'minigame', 'Quiz Interaktif: Dasar Desain', NULL, 'https://example.com/games/design-basics'),
(2, 'text', 'Prinsip Dasar Layout', '<h2>Prinsip Layout dalam Desain</h2><p>Layout adalah cara mengatur elemen-elemen desain dalam suatu ruang.</p>', NULL);

-- Insert sample quiz questions
INSERT INTO quiz (materi_id, question_type, question, options, correct_answer) VALUES
(1, 'multiple', 'Apa fungsi utama desain grafis?', '["Komunikasi visual","Pemrograman","Akuntansi","Manajemen"]', 'Komunikasi visual'),
(1, 'multiple', 'Tool yang digunakan untuk mengedit foto adalah?', '["Microsoft Word","Adobe Photoshop","Notepad","Calculator"]', 'Adobe Photoshop');

-- Initialize gamification for students
INSERT INTO gamification (user_id, points, badges) VALUES
(3, 0, '[]'),
(4, 0, '[]');

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

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

<?php
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = sanitize_input($_POST['nis']);
    $name = sanitize_input($_POST['name']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $learning_style = sanitize_input($_POST['learning_style']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($nis) || !preg_match('/^\d+$/', $nis)) {
        $errors[] = 'NIS harus berupa angka';
    }
    
    if (empty($name) || strlen($name) < 3) {
        $errors[] = 'Nama harus diisi minimal 3 karakter';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter';
    }
    
    if ($password !== $password_confirm) {
        $errors[] = 'Password tidak cocok';
    }
    
    if (!in_array($learning_style, ['visual', 'auditory', 'kinesthetic'])) {
        $errors[] = 'Pilih gaya belajar yang valid';
    }
    
    // Check if NIS already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nis = ?");
    $stmt->execute([$nis]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'NIS sudah terdaftar';
    }
    
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (nis, name, password, role, learning_style) 
                VALUES (?, ?, ?, 'student', ?)
            ");
            
            $stmt->execute([$nis, $name, $hashed_password, $learning_style]);
            
            // Initialize gamification record
            $user_id = $pdo->lastInsertId();
            $stmt = $pdo->prepare("
                INSERT INTO gamification (user_id, points, badges) 
                VALUES (?, 0, '')
            ");
            $stmt->execute([$user_id]);
            
            // Set success message and redirect to login
            set_flash_message('success', 'Pendaftaran berhasil! Silakan login.');
            redirect('/login.php');
            
        } catch(PDOException $e) {
            set_flash_message('error', 'Terjadi kesalahan. Silakan coba lagi.');
            redirect('/register.php');
        }
    } else {
        // Store errors in session and redirect back
        $_SESSION['register_errors'] = $errors;
        redirect('/register.php');
    }
} else {
    redirect('/register.php');
}

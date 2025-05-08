<?php
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = isset($_POST['login_type']) ? $_POST['login_type'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate login type
    if (!in_array($login_type, ['staff', 'student'])) {
        set_flash_message('error', 'Tipe login tidak valid');
        redirect('/debug/login.php');
    }
    
    try {
        if ($login_type === 'staff') {
            // Admin & Teacher login using username
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            
            if (empty($username) || empty($password)) {
                set_flash_message('error', 'Username dan password harus diisi');
                redirect('/debug/login.php');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE username = ? 
                AND role IN ('admin', 'teacher')
                LIMIT 1
            ");
            $stmt->execute([$username]);
            
        } else {
            // Student login using NIS
            $nis = isset($_POST['nis']) ? trim($_POST['nis']) : '';
            
            if (empty($nis) || empty($password)) {
                set_flash_message('error', 'NIS dan password harus diisi');
                redirect('/debug/login.php');
            }
            
            $stmt = $pdo->prepare("
                SELECT * FROM users 
                WHERE nis = ? 
                AND role = 'student'
                LIMIT 1
            ");
            $stmt->execute([$nis]);
        }
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    redirect('/debug/admin/dashboard.php');
                    break;
                    
                case 'teacher':
                    redirect('/debug/teacher/dashboard.php');
                    break;
                    
                case 'student':
                    redirect('/debug/student/dashboard.php');
                    break;
            }
            
        } else {
            if ($login_type === 'staff') {
                set_flash_message('error', 'Username atau password salah');
            } else {
                set_flash_message('error', 'NIS atau password salah');
            }
            redirect('/debug/login.php');
        }
        
    } catch (PDOException $e) {
        set_flash_message('error', 'Terjadi kesalahan. Silakan coba lagi.');
        redirect('/debug/login.php');
    }
    
} else {
    redirect('/debug/login.php');
}

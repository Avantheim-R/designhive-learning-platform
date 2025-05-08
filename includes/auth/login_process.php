<?php
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = sanitize_input($_POST['nis']);
    $password = $_POST['password'];
    
    try {
        // Prepare SQL statement
        $stmt = $pdo->prepare("SELECT * FROM users WHERE nis = ?");
        $stmt->execute([$nis]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Start session and store user data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nis'] = $user['nis'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['learning_style'] = $user['learning_style'];
            
            // Redirect based on role
            switch($user['role']) {
                case 'admin':
                    redirect('/admin/index.php');
                    break;
                case 'teacher':
                    redirect('/teacher/index.php');
                    break;
                default:
                    redirect('/student/dashboard.php');
            }
        } else {
            set_flash_message('error', 'NIS atau password salah');
            redirect('/login.php');
        }
    } catch(PDOException $e) {
        set_flash_message('error', 'Terjadi kesalahan. Silakan coba lagi.');
        redirect('/login.php');
    }
} else {
    redirect('/login.php');
}

<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'designhive'); // Changed from designhive_db to designhive

// Establish database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['role'] ?? null;
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Certificate Configuration
define('CERTIFICATE_TEMPLATE', __DIR__ . '/../assets/images/certificate-template.jpg');
define('CERTIFICATE_FONT', __DIR__ . '/../assets/fonts/certificate-font.ttf');

// Points Configuration
define('POINTS_PER_QUIZ', 10);
define('POINTS_PER_ASSIGNMENT', 20);
define('POINTS_PERFECT_SCORE', 50);

// Security Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 3600); // 1 hour
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
session_set_cookie_params(SESSION_LIFETIME);

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_token() {
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// XSS Protection
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Input Validation
function validate_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = h($data);
    return $data;
}

// File Upload Helper
function upload_file($file, $destination) {
    if ($file['error'] !== UPLOAD_ERROR_OK) {
        throw new Exception('File upload failed');
    }

    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);

    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        throw new Exception('File type not allowed');
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File too large');
    }

    $new_filename = uniqid() . '.' . $extension;
    $upload_path = UPLOAD_DIR . $destination . '/' . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    return $destination . '/' . $new_filename;
}

// Error Logging
function log_error($message, $context = []) {
    error_log(date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($context) . "\n", 3, __DIR__ . '/../logs/error.log');
}

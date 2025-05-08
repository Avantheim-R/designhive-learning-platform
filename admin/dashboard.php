<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() !== 'admin') {
    redirect('/login.php');
}

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$stmt->execute();
$student_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'teacher'");
$stmt->execute();
$teacher_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM materi");
$stmt->execute();
$materi_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM submissions WHERE grade IS NOT NULL");
$stmt->execute();
$graded_submissions = $stmt->fetch()['total'];

// Get recent activities
$stmt = $pdo->prepare("
    SELECT 
        'submission' as type,
        s.submitted_at as timestamp,
        u.name as user_name,
        CONCAT('BAB ', m.chapter) as detail
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    JOIN materi m ON s.materi_id = m.id
    WHERE s.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'quiz' as type,
        qa.attempted_at as timestamp,
        u.name as user_name,
        CONCAT('Score: ', qa.score, '%') as detail
    FROM quiz_attempts qa
    JOIN users u ON qa.user_id = u.id
    WHERE qa.attempted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'exam' as type,
        er.completed_at as timestamp,
        u.name as user_name,
        CONCAT('Score: ', er.score, '%') as detail
    FROM exam_results er
    JOIN users u ON er.user_id = u.id
    WHERE er.completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY timestamp DESC
    LIMIT 10
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DesignHIve</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1E90FF',
                        secondary: '#FFD700',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="font-poppins bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-graduation-cap text-primary text-3xl mr-2"></i>
                        <span class="text-2xl font-bold text-gray-800">DesignHIve</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="manage_users.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-users mr-1"></i> Users
                    </a>
                    <a href="manage_materi.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-book mr-1"></i> Materi
                    </a>
                    <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="../includes/auth/logout.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800">
                Selamat Datang, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h1>
            <p class="text-gray-600 mt-1">
                Admin Dashboard - Overview Platform
            </p>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Students -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-primary">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Siswa</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $student_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Teachers -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-chalkboard-teacher text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Guru</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $teacher_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Learning Materials -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-book text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Materi</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $materi_count; ?></p>
                    </div>
                </div>
            </div>

            <!-- Graded Submissions -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Tugas Dinilai</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $graded_submissions; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Aktivitas Terbaru</h2>
            
            <div class="space-y-4">
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <?php
                            switch ($activity['type']) {
                                case 'submission':
                                    echo '<div class="w-8 h-8 rounded-full bg-blue-100 text-primary flex items-center justify-center">
                                            <i class="fas fa-upload"></i>
                                          </div>';
                                    break;
                                case 'quiz':
                                    echo '<div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                            <i class="fas fa-question-circle"></i>
                                          </div>';
                                    break;
                                case 'exam':
                                    echo '<div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center">
                                            <i class="fas fa-graduation-cap"></i>
                                          </div>';
                                    break;
                            }
                            ?>
                        </div>
                        <div class="ml-4 flex-grow">
                            <div class="flex justify-between">
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($activity['user_name']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    <?php echo date('d M Y H:i', strtotime($activity['timestamp'])); ?>
                                </p>
                            </div>
                            <p class="text-sm text-gray-500">
                                <?php
                                switch ($activity['type']) {
                                    case 'submission':
                                        echo 'Mengumpulkan tugas ' . htmlspecialchars($activity['detail']);
                                        break;
                                    case 'quiz':
                                        echo 'Menyelesaikan kuis dengan ' . htmlspecialchars($activity['detail']);
                                        break;
                                    case 'exam':
                                        echo 'Menyelesaikan ujian dengan ' . htmlspecialchars($activity['detail']);
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($recent_activities)): ?>
                <p class="text-gray-500 text-center">Tidak ada aktivitas terbaru</p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Aksi Cepat</h2>
                <div class="space-y-4">
                    <a href="add_user.php" class="block p-4 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-50">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-primary">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Tambah User Baru</p>
                                <p class="text-sm text-gray-500">Daftarkan siswa atau guru baru</p>
                            </div>
                        </div>
                    </a>

                    <a href="add_materi.php" class="block p-4 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-50">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-primary">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Tambah Materi</p>
                                <p class="text-sm text-gray-500">Upload materi pembelajaran baru</p>
                            </div>
                        </div>
                    </a>

                    <a href="manage_quiz.php" class="block p-4 rounded-lg border border-gray-200 hover:border-primary hover:bg-blue-50">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-primary">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Kelola Soal</p>
                                <p class="text-sm text-gray-500">Atur soal kuis dan ujian</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Sistem Info</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Status Sistem</p>
                                <p class="text-sm text-green-600">Aktif</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-primary">
                                <i class="fas fa-database"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Database</p>
                                <p class="text-sm text-gray-500">Terhubung</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Penyimpanan</p>
                                <p class="text-sm text-gray-500">70% tersedia</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

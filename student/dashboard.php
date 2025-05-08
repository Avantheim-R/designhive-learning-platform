<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get user's progress data
$stmt = $pdo->prepare("
    SELECT 
        m.chapter,
        m.phase,
        p.completed,
        p.completed_at
    FROM materi m
    LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
    ORDER BY m.chapter, m.phase
");
$stmt->execute([$_SESSION['user_id']]);
$progress = $stmt->fetchAll();

// Get user's gamification data
$stmt = $pdo->prepare("
    SELECT points, badges, level
    FROM gamification
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$gamification = $stmt->fetch();

// Get leaderboard data
$stmt = $pdo->prepare("
    SELECT u.name, g.points, g.level
    FROM gamification g
    JOIN users u ON g.user_id = u.id
    ORDER BY g.points DESC
    LIMIT 10
");
$stmt->execute();
$leaderboard = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa - DesignHIve</title>
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
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                Selamat Datang, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h1>
            <p class="text-gray-600">
                Gaya Belajar: <span class="font-medium"><?php echo ucfirst($_SESSION['learning_style']); ?></span>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Progress Section -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Progress Pembelajaran</h2>
                    
                    <?php
                    $current_chapter = null;
                    foreach ($progress as $item):
                        if ($current_chapter !== $item['chapter']):
                            if ($current_chapter !== null) echo '</div>'; // Close previous chapter div
                            $current_chapter = $item['chapter'];
                    ?>
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">BAB <?php echo $item['chapter']; ?></h3>
                    <?php
                        endif;
                    ?>
                        <div class="flex items-center mb-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center <?php echo $item['completed'] ? 'bg-green-500' : 'bg-gray-200'; ?> mr-3">
                                <i class="fas <?php
                                    switch($item['phase']) {
                                        case 'text':
                                            echo 'fa-book';
                                            break;
                                        case 'video':
                                            echo 'fa-video';
                                            break;
                                        case 'minigame':
                                            echo 'fa-gamepad';
                                            break;
                                    }
                                ?> text-white"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-700">Materi <?php
                                        switch($item['phase']) {
                                            case 'text':
                                                echo '1 - Teks & Gambar';
                                                break;
                                            case 'video':
                                                echo '2 - Video';
                                                break;
                                            case 'minigame':
                                                echo '3 - Mini Game';
                                                break;
                                        }
                                    ?></span>
                                    <?php if ($item['completed']): ?>
                                        <span class="text-sm text-green-500">Selesai</span>
                                    <?php endif; ?>
                                </div>
                                <div class="h-2 bg-gray-200 rounded-full mt-2">
                                    <div class="h-2 bg-primary rounded-full" style="width: <?php echo $item['completed'] ? '100' : '0'; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php
                    endforeach;
                    if ($current_chapter !== null) echo '</div>'; // Close last chapter div
                    ?>
                    
                    <div class="mt-6">
                        <a href="materi.php" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600">
                            <i class="fas fa-book-open mr-2"></i>
                            Lanjutkan Belajar
                        </a>
                    </div>
                </div>
            </div>

            <!-- Gamification Section -->
            <div class="space-y-8">
                <!-- Points and Level -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Pencapaianmu</h2>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Level</span>
                            <span class="text-2xl font-bold text-primary"><?php echo $gamification['level']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Poin</span>
                            <span class="text-2xl font-bold text-primary"><?php echo $gamification['points']; ?></span>
                        </div>
                        <?php if (!empty($gamification['badges'])): ?>
                        <div class="mt-4">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">Badge</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (json_decode($gamification['badges']) as $badge): ?>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-primary">
                                    <i class="fas fa-medal mr-1"></i>
                                    <?php echo htmlspecialchars($badge); ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Papan Peringkat</h2>
                    <div class="space-y-4">
                        <?php foreach ($leaderboard as $index => $user): ?>
                        <div class="flex items-center justify-between <?php echo $user['name'] === $_SESSION['name'] ? 'bg-blue-50 -mx-4 px-4 py-2 rounded' : ''; ?>">
                            <div class="flex items-center">
                                <span class="w-6 text-gray-600 font-medium"><?php echo $index + 1; ?>.</span>
                                <span class="text-gray-800"><?php echo htmlspecialchars($user['name']); ?></span>
                            </div>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-600">Level <?php echo $user['level']; ?></span>
                                <span class="font-medium text-primary"><?php echo $user['points']; ?> pts</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

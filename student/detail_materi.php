<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get materi ID from URL
$materi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$materi_id) {
    redirect('materi.php');
}

// Get materi details
$stmt = $pdo->prepare("
    SELECT m.*, p.completed
    FROM materi m
    LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
    WHERE m.id = ?
");
$stmt->execute([$_SESSION['user_id'], $materi_id]);
$materi = $stmt->fetch();

if (!$materi) {
    redirect('materi.php');
}

// Get comments for this materi
$stmt = $pdo->prepare("
    SELECT c.*, u.name as user_name
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.materi_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$materi_id]);
$comments = $stmt->fetchAll();

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = trim($_POST['comment']);
    if (!empty($comment)) {
        $stmt = $pdo->prepare("
            INSERT INTO comments (user_id, materi_id, content)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $materi_id, $comment]);
        redirect("detail_materi.php?id=$materi_id");
    }
}

// Handle completion marking
if (isset($_POST['mark_complete']) && !$materi['completed']) {
    $stmt = $pdo->prepare("
        INSERT INTO progress (user_id, materi_id, completed, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE completed = 1, completed_at = NOW()
    ");
    $stmt->execute([$_SESSION['user_id'], $materi_id]);
    
    // Update gamification points
    $stmt = $pdo->prepare("
        UPDATE gamification 
        SET points = points + 10
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    redirect("detail_materi.php?id=$materi_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($materi['title']); ?> - DesignHIve</title>
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
                    <a href="materi.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
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
        <!-- Content Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($materi['title']); ?></h1>
                    <p class="text-gray-600 mt-1">
                        BAB <?php echo $materi['chapter']; ?> - 
                        Materi <?php
                            switch($materi['phase']) {
                                case 'text':
                                    echo '1 (Teks & Gambar)';
                                    break;
                                case 'video':
                                    echo '2 (Video)';
                                    break;
                                case 'minigame':
                                    echo '3 (Mini Game)';
                                    break;
                            }
                        ?>
                    </p>
                </div>
                <?php if ($materi['completed']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Selesai
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <?php if ($materi['phase'] === 'text'): ?>
                        <!-- Text & Image Content -->
                        <div class="prose max-w-none">
                            <?php echo $materi['content']; ?>
                        </div>
                    
                    <?php elseif ($materi['phase'] === 'video'): ?>
                        <!-- Video Content -->
                        <div class="aspect-w-16 aspect-h-9">
                            <iframe 
                                class="w-full h-96 rounded-lg"
                                src="<?php echo htmlspecialchars($materi['url']); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    
                    <?php elseif ($materi['phase'] === 'minigame'): ?>
                        <!-- Minigame Content -->
                        <div class="aspect-w-16 aspect-h-9">
                            <iframe 
                                class="w-full h-96 rounded-lg"
                                src="<?php echo htmlspecialchars($materi['url']); ?>" 
                                frameborder="0">
                            </iframe>
                        </div>
                    <?php endif; ?>

                    <!-- Mark as Complete Button -->
                    <?php if (!$materi['completed']): ?>
                        <form method="POST" class="mt-8">
                            <button type="submit" name="mark_complete" class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-check-circle mr-2"></i>
                                Tandai Selesai
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Comments Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Diskusi</h2>
                    
                    <!-- Comment Form -->
                    <form method="POST" class="mb-6">
                        <div class="mt-1">
                            <textarea 
                                rows="3" 
                                name="comment" 
                                class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" 
                                placeholder="Tulis komentar atau pertanyaan..."></textarea>
                        </div>
                        <div class="mt-2 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Kirim
                            </button>
                        </div>
                    </form>

                    <!-- Comments List -->
                    <div class="space-y-4">
                        <?php foreach ($comments as $comment): ?>
                        <div class="flex space-x-3">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                                    <span class="text-lg font-medium"><?php echo strtoupper(substr($comment['user_name'], 0, 1)); ?></span>
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                </div>
                                <div class="mt-1 text-sm text-gray-700">
                                    <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    <?php echo date('d M Y H:i', strtotime($comment['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Navigation Card -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Navigasi BAB <?php echo $materi['chapter']; ?></h3>
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT m.*, p.completed
                        FROM materi m
                        LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
                        WHERE m.chapter = ?
                        ORDER BY 
                        CASE m.phase 
                            WHEN 'text' THEN 1 
                            WHEN 'video' THEN 2 
                            WHEN 'minigame' THEN 3 
                        END
                    ");
                    $stmt->execute([$_SESSION['user_id'], $materi['chapter']]);
                    $chapter_materials = $stmt->fetchAll();
                    ?>
                    
                    <div class="space-y-2">
                        <?php foreach ($chapter_materials as $material): ?>
                        <a href="detail_materi.php?id=<?php echo $material['id']; ?>" 
                           class="flex items-center p-2 rounded-lg <?php echo $material['id'] === $materi_id ? 'bg-blue-50 text-primary' : 'text-gray-600 hover:bg-gray-50'; ?>">
                            <i class="fas <?php
                                switch($material['phase']) {
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
                            ?> w-5"></i>
                            <span class="ml-3">Materi <?php
                                switch($material['phase']) {
                                    case 'text':
                                        echo '1';
                                        break;
                                    case 'video':
                                        echo '2';
                                        break;
                                    case 'minigame':
                                        echo '3';
                                        break;
                                }
                            ?></span>
                            <?php if ($material['completed']): ?>
                                <i class="fas fa-check-circle ml-auto text-green-500"></i>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Task Submission -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tugas BAB <?php echo $materi['chapter']; ?></h3>
                    <?php
                    // Check if student has submitted task for this chapter
                    $stmt = $pdo->prepare("
                        SELECT s.*, m.chapter
                        FROM submissions s
                        JOIN materi m ON s.materi_id = m.id
                        WHERE s.user_id = ? AND m.chapter = ?
                        ORDER BY s.submitted_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$_SESSION['user_id'], $materi['chapter']]);
                    $submission = $stmt->fetch();
                    ?>

                    <?php if ($submission): ?>
                        <div class="text-sm text-gray-600 mb-4">
                            <p>Status: 
                                <?php if ($submission['grade']): ?>
                                    <span class="text-green-600 font-medium">Sudah dinilai</span>
                                <?php else: ?>
                                    <span class="text-yellow-600 font-medium">Menunggu penilaian</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($submission['grade']): ?>
                                <p class="mt-2">Nilai: <span class="font-medium"><?php echo $submission['grade']; ?></span></p>
                            <?php endif; ?>
                            <?php if ($submission['feedback']): ?>
                                <div class="mt-2">
                                    <p class="font-medium">Feedback:</p>
                                    <p class="mt-1"><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <a href="submit_task.php?chapter=<?php echo $materi['chapter']; ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-upload mr-2"></i>
                        <?php echo $submission ? 'Upload Ulang Tugas' : 'Upload Tugas'; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

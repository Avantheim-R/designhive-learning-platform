<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get all chapters with their phases and progress
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        p.completed,
        p.completed_at,
        (
            SELECT COUNT(*)
            FROM progress p2
            JOIN materi m2 ON p2.materi_id = m2.id
            WHERE m2.chapter = m.chapter
            AND p2.user_id = ?
            AND p2.completed = 1
        ) as phase_completed_count
    FROM materi m
    LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
    ORDER BY m.chapter, 
    CASE m.phase 
        WHEN 'text' THEN 1 
        WHEN 'video' THEN 2 
        WHEN 'minigame' THEN 3 
    END
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$materials = $stmt->fetchAll();

// Group materials by chapter
$chapters = [];
foreach ($materials as $material) {
    if (!isset($chapters[$material['chapter']])) {
        $chapters[$material['chapter']] = [
            'materials' => [],
            'completed_phases' => $material['phase_completed_count']
        ];
    }
    $chapters[$material['chapter']]['materials'][] = $material;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materi Pembelajaran - DesignHIve</title>
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
                    <a href="dashboard.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-home mr-1"></i> Dashboard
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
        <h1 class="text-3xl font-bold text-gray-800 mb-8">Materi Pembelajaran</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($chapters as $chapter_num => $chapter): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Chapter Header -->
                <div class="p-6 bg-primary text-white">
                    <h2 class="text-xl font-bold">BAB <?php echo $chapter_num; ?></h2>
                    <div class="mt-2 flex items-center">
                        <div class="flex-grow">
                            <div class="h-2 bg-blue-200 rounded-full">
                                <div class="h-2 bg-white rounded-full" style="width: <?php echo ($chapter['completed_phases'] / 3) * 100; ?>%"></div>
                            </div>
                        </div>
                        <span class="ml-3 text-sm"><?php echo $chapter['completed_phases']; ?>/3</span>
                    </div>
                </div>

                <!-- Chapter Content -->
                <div class="p-6 space-y-4">
                    <?php foreach ($chapter['materials'] as $material): ?>
                    <div class="relative">
                        <?php
                        $is_locked = false;
                        if ($material['phase'] !== 'text') {
                            // Check if previous phase is completed
                            $prev_phase_completed = false;
                            foreach ($chapter['materials'] as $prev_material) {
                                if ($material['phase'] === 'video' && $prev_material['phase'] === 'text') {
                                    $prev_phase_completed = $prev_material['completed'];
                                } elseif ($material['phase'] === 'minigame' && $prev_material['phase'] === 'video') {
                                    $prev_phase_completed = $prev_material['completed'];
                                }
                            }
                            $is_locked = !$prev_phase_completed;
                        }
                        ?>
                        
                        <a href="<?php echo $is_locked ? '#' : 'detail_materi.php?id=' . $material['id']; ?>"
                           class="block p-4 rounded-lg border <?php echo $material['completed'] ? 'border-green-500 bg-green-50' : ($is_locked ? 'border-gray-300 bg-gray-50 cursor-not-allowed' : 'border-gray-300 hover:border-primary hover:bg-blue-50'); ?>">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center <?php echo $material['completed'] ? 'bg-green-500' : ($is_locked ? 'bg-gray-400' : 'bg-primary'); ?> text-white">
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
                                    ?>"></i>
                                </div>
                                <div class="ml-4 flex-grow">
                                    <h3 class="font-medium text-gray-800">
                                        Materi <?php
                                            switch($material['phase']) {
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
                                        ?>
                                    </h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($material['title']); ?></p>
                                </div>
                                <?php if ($is_locked): ?>
                                    <i class="fas fa-lock text-gray-400"></i>
                                <?php elseif ($material['completed']): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                <?php else: ?>
                                    <i class="fas fa-chevron-right text-primary"></i>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>

                    <!-- Quiz Button -->
                    <?php if ($chapter['completed_phases'] === 3): ?>
                    <a href="quiz.php?chapter=<?php echo $chapter_num; ?>" 
                       class="block mt-4 text-center py-2 px-4 bg-secondary text-gray-800 rounded-lg hover:bg-yellow-400 font-medium">
                        <i class="fas fa-question-circle mr-2"></i>
                        Mulai Kuis
                    </a>
                    <?php else: ?>
                    <button disabled 
                            class="block w-full mt-4 text-center py-2 px-4 bg-gray-200 text-gray-500 rounded-lg cursor-not-allowed font-medium">
                        <i class="fas fa-lock mr-2"></i>
                        Selesaikan Semua Materi
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Final Exam Button -->
        <?php
        $all_chapters_completed = true;
        foreach ($chapters as $chapter) {
            if ($chapter['completed_phases'] < 3) {
                $all_chapters_completed = false;
                break;
            }
        }
        ?>
        <div class="mt-8 text-center">
            <?php if ($all_chapters_completed): ?>
            <a href="exam.php" 
               class="inline-block py-3 px-8 bg-primary text-white rounded-lg hover:bg-blue-600 font-medium">
                <i class="fas fa-graduation-cap mr-2"></i>
                Mulai Ujian Akhir
            </a>
            <?php else: ?>
            <button disabled 
                    class="inline-block py-3 px-8 bg-gray-200 text-gray-500 rounded-lg cursor-not-allowed font-medium">
                <i class="fas fa-lock mr-2"></i>
                Selesaikan Semua BAB
            </button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

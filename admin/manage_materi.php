<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() !== 'admin') {
    redirect('/login.php');
}

// Handle deletion
if (isset($_POST['delete']) && isset($_POST['materi_id'])) {
    $materi_id = (int)$_POST['materi_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM materi WHERE id = ?");
        $stmt->execute([$materi_id]);
        set_flash_message('success', 'Materi berhasil dihapus');
        redirect('manage_materi.php');
    } catch (PDOException $e) {
        set_flash_message('error', 'Gagal menghapus materi');
    }
}

// Get all materi
$stmt = $pdo->prepare("
    SELECT m.*, 
           COUNT(DISTINCT p.user_id) as completion_count,
           COUNT(DISTINCT s.id) as submission_count
    FROM materi m
    LEFT JOIN progress p ON m.id = p.materi_id AND p.completed = 1
    LEFT JOIN submissions s ON m.id = s.materi_id
    GROUP BY m.id
    ORDER BY m.chapter, 
    CASE m.phase 
        WHEN 'text' THEN 1 
        WHEN 'video' THEN 2 
        WHEN 'minigame' THEN 3 
    END
");
$stmt->execute();
$materials = $stmt->fetchAll();

// Group materials by chapter
$chapters = [];
foreach ($materials as $material) {
    if (!isset($chapters[$material['chapter']])) {
        $chapters[$material['chapter']] = [];
    }
    $chapters[$material['chapter']][] = $material;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Materi - DesignHIve</title>
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
                        <i class="fas fa-arrow-left mr-1"></i> Dashboard
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
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Kelola Materi</h1>
            <a href="add_materi.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-plus mr-2"></i>
                Tambah Materi Baru
            </a>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="mb-8 p-4 rounded-md <?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $_SESSION['flash']['type'] === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm <?php echo $_SESSION['flash']['type'] === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                            <?php echo $_SESSION['flash']['message']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <!-- Materials List -->
        <div class="space-y-8">
            <?php foreach ($chapters as $chapter_num => $chapter_materials): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">BAB <?php echo $chapter_num; ?></h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        <?php foreach ($chapter_materials as $material): ?>
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center <?php
                                                switch($material['phase']) {
                                                    case 'text':
                                                        echo 'bg-blue-100 text-primary';
                                                        break;
                                                    case 'video':
                                                        echo 'bg-red-100 text-red-600';
                                                        break;
                                                    case 'minigame':
                                                        echo 'bg-green-100 text-green-600';
                                                        break;
                                                }
                                            ?>">
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
                                        </div>
                                        <div class="ml-4">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?php echo htmlspecialchars($material['title']); ?>
                                            </h3>
                                            <div class="mt-1 flex items-center text-sm text-gray-500">
                                                <span class="mr-4">
                                                    <i class="fas fa-users mr-1"></i>
                                                    <?php echo $material['completion_count']; ?> siswa selesai
                                                </span>
                                                <span>
                                                    <i class="fas fa-file-upload mr-1"></i>
                                                    <?php echo $material['submission_count']; ?> submission
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <a href="edit_materi.php?id=<?php echo $material['id']; ?>" 
                                           class="text-primary hover:text-blue-600">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="inline" 
                                              onsubmit="return confirm('Yakin ingin menghapus materi ini?');">
                                            <input type="hidden" name="materi_id" value="<?php echo $material['id']; ?>">
                                            <button type="submit" name="delete" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <div class="mt-4 text-sm text-gray-500">
                                    <?php
                                        $content = $material['content'];
                                        if (strlen($content) > 200) {
                                            $content = substr($content, 0, 200) . '...';
                                        }
                                        echo nl2br(htmlspecialchars($content));
                                    ?>
                                </div>

                                <?php if ($material['url']): ?>
                                    <div class="mt-2 text-sm text-gray-500">
                                        <i class="fas fa-link mr-1"></i>
                                        <a href="<?php echo htmlspecialchars($material['url']); ?>" 
                                           target="_blank"
                                           class="text-primary hover:text-blue-600">
                                            <?php echo htmlspecialchars($material['url']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($chapters)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-book-open text-6xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Belum ada materi</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Mulai dengan menambahkan materi pembelajaran baru.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

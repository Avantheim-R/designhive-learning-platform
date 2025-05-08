<?php
require_once '../config/config.php';

// Check if user is logged in and is a teacher
if (!is_logged_in() || get_user_role() !== 'teacher') {
    redirect('/login.php');
}

// Get submission ID from URL
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$submission_id) {
    redirect('dashboard.php');
}

// Get submission details
$stmt = $pdo->prepare("
    SELECT 
        s.*,
        u.name as student_name,
        u.nis,
        m.chapter,
        m.title
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    JOIN materi m ON s.materi_id = m.id
    WHERE s.id = ?
");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();

if (!$submission) {
    redirect('dashboard.php');
}

// Handle grading submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = isset($_POST['grade']) ? (float)$_POST['grade'] : null;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    $errors = [];
    
    if ($grade === null || $grade < 0 || $grade > 100) {
        $errors[] = 'Nilai harus antara 0 dan 100';
    }
    
    if (empty($feedback)) {
        $errors[] = 'Feedback tidak boleh kosong';
    }
    
    if (empty($errors)) {
        try {
            // Update submission
            $stmt = $pdo->prepare("
                UPDATE submissions 
                SET grade = ?, feedback = ?, graded_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$grade, $feedback, $submission_id]);
            
            // Update gamification points based on grade
            $points = floor($grade / 10); // Convert grade to points (e.g., 80 = 8 points)
            $stmt = $pdo->prepare("
                UPDATE gamification 
                SET points = points + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$points, $submission['user_id']]);
            
            // Add badge if grade is perfect (100)
            if ($grade === 100.0) {
                $stmt = $pdo->prepare("
                    UPDATE gamification 
                    SET badges = JSON_ARRAY_APPEND(
                        CASE 
                            WHEN badges IS NULL OR badges = '' THEN '[]'
                            ELSE badges 
                        END, 
                        '$', 
                        ?
                    )
                    WHERE user_id = ?
                ");
                $badge_name = "Perfect Score BAB " . $submission['chapter'];
                $stmt->execute([$badge_name, $submission['user_id']]);
            }
            
            set_flash_message('success', 'Penilaian berhasil disimpan');
            redirect('dashboard.php');
            
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai Tugas - DesignHIve</title>
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
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Submission Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">
                Nilai Tugas BAB <?php echo $submission['chapter']; ?>
            </h1>
            
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Siswa</h3>
                    <p class="mt-1 text-lg text-gray-900"><?php echo htmlspecialchars($submission['student_name']); ?></p>
                    <p class="text-sm text-gray-500">NIS: <?php echo htmlspecialchars($submission['nis']); ?></p>
                </div>
                
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Tanggal Submit</h3>
                    <p class="mt-1 text-lg text-gray-900">
                        <?php echo date('d M Y H:i', strtotime($submission['submitted_at'])); ?>
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-500">File Tugas</h3>
                <div class="mt-2">
                    <a href="../<?php echo htmlspecialchars($submission['file_path']); ?>" 
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-download mr-2"></i>
                        Download File
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mt-6 p-4 rounded-md bg-red-50 border border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Terjadi kesalahan:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Grading Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Form Penilaian</h2>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="grade" class="block text-sm font-medium text-gray-700">
                        Nilai (0-100)
                    </label>
                    <input type="number" 
                           name="grade" 
                           id="grade" 
                           min="0" 
                           max="100" 
                           step="0.1"
                           value="<?php echo $submission['grade'] ?? ''; ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                           <?php echo $submission['grade'] !== null ? 'readonly' : ''; ?>>
                </div>

                <div>
                    <label for="feedback" class="block text-sm font-medium text-gray-700">
                        Feedback
                    </label>
                    <textarea name="feedback" 
                              id="feedback" 
                              rows="4" 
                              required
                              <?php echo $submission['grade'] !== null ? 'readonly' : ''; ?>
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                              placeholder="Berikan feedback untuk siswa..."><?php echo $submission['feedback'] ?? ''; ?></textarea>
                </div>

                <?php if ($submission['grade'] === null): ?>
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fas fa-save mr-2"></i>
                            Simpan Penilaian
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>

<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get attempt ID from URL
$attempt_id = isset($_GET['attempt']) ? (int)$_GET['attempt'] : 0;
if (!$attempt_id) {
    redirect('materi.php');
}

// Get quiz attempt details
$stmt = $pdo->prepare("
    SELECT qa.*, q.question, q.question_type, q.options, q.correct_answer, m.chapter
    FROM quiz_attempts qa
    JOIN quiz q ON qa.quiz_id = q.id
    JOIN materi m ON q.materi_id = m.id
    WHERE qa.id = ? AND qa.user_id = ?
");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    redirect('materi.php');
}

// Decode answers
$answers = json_decode($attempt['answers'], true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Kuis - DesignHIve</title>
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
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Materi
                    </a>
                    <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Result Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">
                    Hasil Kuis BAB <?php echo $attempt['chapter']; ?>
                </h1>
                <div class="text-5xl font-bold <?php echo $attempt['score'] >= 70 ? 'text-green-500' : 'text-red-500'; ?> mb-4">
                    <?php echo $attempt['score']; ?>%
                </div>
                <p class="text-gray-600">
                    <?php if ($attempt['score'] >= 70): ?>
                        <span class="text-green-500 font-medium">Selamat!</span> Anda telah lulus kuis ini.
                    <?php else: ?>
                        <span class="text-red-500 font-medium">Maaf,</span> Anda belum lulus kuis ini. Silakan pelajari materi kembali dan coba lagi.
                    <?php endif; ?>
                </p>
                <div class="mt-4 text-sm text-gray-500">
                    Diselesaikan pada: <?php echo date('d M Y H:i', strtotime($attempt['attempted_at'])); ?>
                </div>
            </div>
        </div>

        <!-- Answer Review -->
        <div class="space-y-6">
            <?php foreach ($answers as $question_id => $answer): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <?php echo $attempt['question']; ?>
                        </h3>
                        <?php if ($answer['is_correct']): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Benar
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>
                                Salah
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4">
                        <!-- User's Answer -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Jawaban Anda:</h4>
                            <div class="p-3 rounded-lg <?php echo $answer['is_correct'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                                <?php
                                switch ($attempt['question_type']) {
                                    case 'multiple':
                                        echo htmlspecialchars($answer['user_answer']);
                                        break;
                                        
                                    case 'dragdrop':
                                    case 'match':
                                        $user_answers = json_decode($answer['user_answer'], true);
                                        if (is_array($user_answers)) {
                                            echo '<ul class="list-disc pl-4">';
                                            foreach ($user_answers as $key => $value) {
                                                echo '<li>' . htmlspecialchars($key) . ' → ' . htmlspecialchars($value) . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        break;
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Correct Answer (if wrong) -->
                        <?php if (!$answer['is_correct']): ?>
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Jawaban Benar:</h4>
                            <div class="p-3 rounded-lg bg-green-50 border border-green-200">
                                <?php
                                switch ($attempt['question_type']) {
                                    case 'multiple':
                                        echo htmlspecialchars($attempt['correct_answer']);
                                        break;
                                        
                                    case 'dragdrop':
                                    case 'match':
                                        $correct_answers = json_decode($attempt['correct_answer'], true);
                                        if (is_array($correct_answers)) {
                                            echo '<ul class="list-disc pl-4">';
                                            foreach ($correct_answers as $key => $value) {
                                                echo '<li>' . htmlspecialchars($key) . ' → ' . htmlspecialchars($value) . '</li>';
                                            }
                                            echo '</ul>';
                                        }
                                        break;
                                }
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 flex justify-center space-x-4">
            <a href="materi.php" class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali ke Materi
            </a>
            <?php if ($attempt['score'] < 70): ?>
            <a href="quiz.php?chapter=<?php echo $attempt['chapter']; ?>" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-redo mr-2"></i>
                Coba Lagi
            </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

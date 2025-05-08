<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Get chapter from URL
$chapter = isset($_GET['chapter']) ? (int)$_GET['chapter'] : 0;
if (!$chapter) {
    redirect('materi.php');
}

// Check if all phases of the chapter are completed
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_completed
    FROM materi m
    JOIN progress p ON m.id = p.materi_id
    WHERE m.chapter = ? AND p.user_id = ? AND p.completed = 1
");
$stmt->execute([$chapter, $_SESSION['user_id']]);
$completion = $stmt->fetch();

if ($completion['total_completed'] < 3) {
    set_flash_message('error', 'Selesaikan semua materi BAB ' . $chapter . ' terlebih dahulu.');
    redirect('materi.php');
}

// Get quiz questions for this chapter
$stmt = $pdo->prepare("
    SELECT *
    FROM quiz
    WHERE materi_id IN (
        SELECT id FROM materi WHERE chapter = ?
    )
    ORDER BY RAND()
    LIMIT 10
");
$stmt->execute([$chapter]);
$questions = $stmt->fetchAll();

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $answers = [];
    
    foreach ($questions as $question) {
        $user_answer = $_POST['answer_' . $question['id']] ?? null;
        if ($user_answer !== null) {
            $is_correct = false;
            
            switch ($question['question_type']) {
                case 'multiple':
                    $is_correct = $user_answer === $question['correct_answer'];
                    break;
                    
                case 'dragdrop':
                case 'match':
                    $correct_answers = json_decode($question['correct_answer'], true);
                    $user_answers = json_decode($user_answer, true);
                    $is_correct = $correct_answers == $user_answers;
                    break;
            }
            
            if ($is_correct) {
                $score += 10; // Each question is worth 10 points
            }
            
            $answers[$question['id']] = [
                'user_answer' => $user_answer,
                'is_correct' => $is_correct
            ];
        }
    }
    
    // Save quiz attempt
    try {
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, quiz_id, score, answers)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $questions[0]['id'], // Use first question's quiz_id
            $score,
            json_encode($answers)
        ]);
        
        // Update gamification points
        $points_earned = floor($score / 2); // Convert score to points (e.g., 80% = 40 points)
        $stmt = $pdo->prepare("
            UPDATE gamification 
            SET points = points + ?
            WHERE user_id = ?
        ");
        $stmt->execute([$points_earned, $_SESSION['user_id']]);
        
        // Redirect to results
        redirect("quiz_result.php?attempt=" . $pdo->lastInsertId());
        
    } catch (PDOException $e) {
        set_flash_message('error', 'Terjadi kesalahan. Silakan coba lagi.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuis BAB <?php echo $chapter; ?> - DesignHIve</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
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
    <style>
        .draggable.ui-draggable-dragging {
            z-index: 1000;
        }
        .droppable.ui-droppable-active {
            background-color: #f0f9ff;
        }
        .droppable.ui-droppable-hover {
            background-color: #e0f2fe;
        }
    </style>
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
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Quiz Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Kuis BAB <?php echo $chapter; ?></h1>
            <p class="text-gray-600 mt-2">
                Jawab semua pertanyaan di bawah ini. Setiap pertanyaan bernilai 10 poin.
            </p>
        </div>

        <!-- Quiz Form -->
        <form method="POST" id="quizForm" class="space-y-6">
            <?php foreach ($questions as $index => $question): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Pertanyaan <?php echo $index + 1; ?>
                        </h3>
                        <span class="text-sm text-gray-500">10 poin</span>
                    </div>

                    <div class="prose max-w-none mb-6">
                        <?php echo $question['question']; ?>
                    </div>

                    <?php
                    $options = json_decode($question['options'], true);
                    switch ($question['question_type']) {
                        case 'multiple':
                            ?>
                            <div class="space-y-3">
                                <?php foreach ($options as $option): ?>
                                <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?php echo $question['id']; ?>" 
                                           value="<?php echo htmlspecialchars($option); ?>"
                                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <span class="text-gray-700"><?php echo htmlspecialchars($option); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <?php
                            break;

                        case 'dragdrop':
                            ?>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <?php foreach ($options['items'] as $item): ?>
                                    <div class="draggable bg-white p-3 rounded-lg border border-gray-200 cursor-move">
                                        <?php echo htmlspecialchars($item); ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach ($options['slots'] as $slot): ?>
                                    <div class="droppable min-h-[50px] p-3 rounded-lg border border-dashed border-gray-300 bg-gray-50">
                                        <span class="text-gray-500"><?php echo htmlspecialchars($slot); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <input type="hidden" name="answer_<?php echo $question['id']; ?>" class="dragdrop-answer">
                            <?php
                            break;

                        case 'match':
                            ?>
                            <div class="grid grid-cols-2 gap-4">
                                <?php foreach ($options as $pair): ?>
                                <div class="match-pair p-3 rounded-lg border border-gray-200">
                                    <div class="font-medium"><?php echo htmlspecialchars($pair['item']); ?></div>
                                    <select name="match_<?php echo $question['id']; ?>[]" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        <option value="">Pilih jawaban...</option>
                                        <?php foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['match']); ?>">
                                            <?php echo htmlspecialchars($option['match']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="answer_<?php echo $question['id']; ?>" class="match-answer">
                            <?php
                            break;
                    }
                    ?>
                </div>
            <?php endforeach; ?>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" name="submit_quiz" class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-check-circle mr-2"></i>
                    Selesaikan Kuis
                </button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize drag and drop functionality
            $('.draggable').draggable({
                revert: 'invalid',
                helper: 'clone',
                zIndex: 100
            });

            $('.droppable').droppable({
                accept: '.draggable',
                drop: function(event, ui) {
                    $(this).html(ui.draggable.html());
                    ui.draggable.hide();
                    updateDragDropAnswers();
                }
            });

            // Update drag and drop answers
            function updateDragDropAnswers() {
                $('.dragdrop-answer').each(function() {
                    const answers = [];
                    $(this).closest('.question').find('.droppable').each(function() {
                        answers.push($(this).text().trim());
                    });
                    $(this).val(JSON.stringify(answers));
                });
            }

            // Update matching answers
            $('.match-pair select').change(function() {
                const $question = $(this).closest('.question');
                const answers = {};
                $question.find('.match-pair').each(function() {
                    const item = $(this).find('.font-medium').text().trim();
                    const match = $(this).find('select').val();
                    if (match) {
                        answers[item] = match;
                    }
                });
                $question.find('.match-answer').val(JSON.stringify(answers));
            });

            // Form validation before submit
            $('#quizForm').on('submit', function(e) {
                let isValid = true;
                
                // Check if all questions are answered
                $('.question').each(function() {
                    const $question = $(this);
                    const questionType = $question.data('type');
                    
                    switch(questionType) {
                        case 'multiple':
                            if (!$question.find('input[type="radio"]:checked').length) {
                                isValid = false;
                            }
                            break;
                            
                        case 'dragdrop':
                            if (!$question.find('.dragdrop-answer').val()) {
                                isValid = false;
                            }
                            break;
                            
                        case 'match':
                            if (!$question.find('.match-answer').val()) {
                                isValid = false;
                            }
                            break;
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Harap jawab semua pertanyaan sebelum mengirim.');
                }
            });
        });
    </script>
</body>
</html>

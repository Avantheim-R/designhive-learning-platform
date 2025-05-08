<?php
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!is_logged_in() || get_user_role() !== 'student') {
    redirect('/login.php');
}

// Check if all chapters are completed
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT m.chapter) as completed_chapters
    FROM materi m
    JOIN progress p ON m.id = p.materi_id
    WHERE p.user_id = ? AND p.completed = 1
");
$stmt->execute([$_SESSION['user_id']]);
$completion = $stmt->fetch();

// Get total number of chapters
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT chapter) as total_chapters FROM materi");
$stmt->execute();
$total = $stmt->fetch();

if ($completion['completed_chapters'] < $total['total_chapters']) {
    set_flash_message('error', 'Selesaikan semua BAB terlebih dahulu untuk mengakses ujian akhir.');
    redirect('materi.php');
}

// Check if exam is already in progress
$stmt = $pdo->prepare("
    SELECT id, answers
    FROM exam_results 
    WHERE user_id = ? AND completed_at IS NULL
    ORDER BY started_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$ongoing_exam = $stmt->fetch();

if ($ongoing_exam) {
    $saved_answers = json_decode($ongoing_exam['answers'], true) ?? [];
} else {
    // Get 50 random exam questions
    $stmt = $pdo->prepare("
        SELECT * FROM exam
        ORDER BY RAND()
        LIMIT 50
    ");
    $stmt->execute();
    $questions = $stmt->fetchAll();

    // Create new exam attempt
    $stmt = $pdo->prepare("
        INSERT INTO exam_results (user_id, answers)
        VALUES (?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], json_encode([])]);
    $exam_id = $pdo->lastInsertId();
    $saved_answers = [];
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_answer'])) {
        // Save individual answer
        $question_id = $_POST['question_id'];
        $answer = $_POST['answer'];
        $saved_answers[$question_id] = $answer;
        
        $stmt = $pdo->prepare("
            UPDATE exam_results 
            SET answers = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([json_encode($saved_answers), $ongoing_exam['id'], $_SESSION['user_id']]);
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (isset($_POST['submit_exam'])) {
        // Calculate score
        $score = 0;
        $answers = [];
        
        foreach ($questions as $question) {
            $user_answer = $saved_answers[$question['id']] ?? null;
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
                    $score += 2; // Each question is worth 2 points (total 100)
                }
                
                $answers[$question['id']] = [
                    'user_answer' => $user_answer,
                    'is_correct' => $is_correct
                ];
            }
        }
        
        // Update exam result
        $stmt = $pdo->prepare("
            UPDATE exam_results 
            SET score = ?, completed_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$score, $ongoing_exam['id'], $_SESSION['user_id']]);
        
        // If passed (score >= 70), generate certificate
        if ($score >= 70) {
            // Generate certificate (will be implemented in certificate.php)
            redirect("generate_certificate.php?exam=" . $ongoing_exam['id']);
        } else {
            redirect("exam_result.php?id=" . $ongoing_exam['id']);
        }
    }
}

// Get questions if exam is ongoing
if ($ongoing_exam) {
    $stmt = $pdo->prepare("
        SELECT * FROM exam
        ORDER BY RAND()
        LIMIT 50
    ");
    $stmt->execute();
    $questions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Akhir - DesignHIve</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <div id="timer" class="text-lg font-medium text-primary"></div>
                    <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- Question Navigation Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-8">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Navigasi Soal</h2>
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($questions as $index => $question): ?>
                            <button 
                                type="button"
                                data-question="<?php echo $index + 1; ?>"
                                class="question-nav w-10 h-10 rounded-lg border <?php echo isset($saved_answers[$question['id']]) ? 'bg-primary text-white' : 'border-gray-300 text-gray-700 hover:border-primary'; ?> flex items-center justify-center font-medium text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <?php echo $index + 1; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 space-y-2">
                        <div class="flex items-center text-sm">
                            <div class="w-4 h-4 bg-primary rounded mr-2"></div>
                            <span class="text-gray-600">Sudah dijawab</span>
                        </div>
                        <div class="flex items-center text-sm">
                            <div class="w-4 h-4 border border-gray-300 rounded mr-2"></div>
                            <span class="text-gray-600">Belum dijawab</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="button" 
                                onclick="submitExam()"
                                class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fas fa-check-circle mr-2"></i>
                            Selesaikan Ujian
                        </button>
                    </div>
                </div>
            </div>

            <!-- Questions -->
            <div class="lg:col-span-3 space-y-6">
                <?php foreach ($questions as $index => $question): ?>
                    <div id="question-<?php echo $index + 1; ?>" 
                         class="question-container bg-white rounded-lg shadow-md p-6 <?php echo $index === 0 ? '' : 'hidden'; ?>">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                Soal <?php echo $index + 1; ?> dari 50
                            </h3>
                        </div>

                        <div class="prose max-w-none mb-6">
                            <?php echo $question['question']; ?>
                        </div>

                        <?php
                        $options = json_decode($question['options'], true);
                        $saved_answer = $saved_answers[$question['id']] ?? null;
                        
                        switch ($question['question_type']):
                            case 'multiple':
                        ?>
                            <div class="space-y-3">
                                <?php foreach ($options as $option): ?>
                                <label class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer">
                                    <input type="radio" 
                                           name="answer_<?php echo $question['id']; ?>" 
                                           value="<?php echo htmlspecialchars($option); ?>"
                                           <?php echo $saved_answer === $option ? 'checked' : ''; ?>
                                           onchange="saveAnswer(<?php echo $question['id']; ?>, this.value)"
                                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300">
                                    <span class="text-gray-700"><?php echo htmlspecialchars($option); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>

                        <?php elseif ($question['question_type'] === 'dragdrop'): ?>
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

                        <?php elseif ($question['question_type'] === 'match'): ?>
                            <div class="grid grid-cols-2 gap-4">
                                <?php foreach ($options as $pair): ?>
                                <div class="match-pair p-3 rounded-lg border border-gray-200">
                                    <div class="font-medium"><?php echo htmlspecialchars($pair['item']); ?></div>
                                    <select name="match_<?php echo $question['id']; ?>[]" 
                                            onchange="saveMatchAnswer(<?php echo $question['id']; ?>)"
                                            class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                                        <option value="">Pilih jawaban...</option>
                                        <?php foreach ($options as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option['match']); ?>"
                                                <?php 
                                                if ($saved_answer) {
                                                    $saved_matches = json_decode($saved_answer, true);
                                                    if (isset($saved_matches[$pair['item']]) && $saved_matches[$pair['item']] === $option['match']) {
                                                        echo 'selected';
                                                    }
                                                }
                                                ?>>
                                            <?php echo htmlspecialchars($option['match']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Navigation Buttons -->
                        <div class="mt-6 flex justify-between">
                            <?php if ($index > 0): ?>
                            <button type="button" 
                                    onclick="showQuestion(<?php echo $index; ?>)"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Sebelumnya
                            </button>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>

                            <?php if ($index < count($questions) - 1): ?>
                            <button type="button" 
                                    onclick="showQuestion(<?php echo $index + 2; ?>)"
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                                Selanjutnya
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Timer functionality
        const startTime = new Date().getTime();
        const timerElement = document.getElementById('timer');

        function updateTimer() {
            const currentTime = new Date().getTime();
            const elapsedTime = Math.floor((currentTime - startTime) / 1000);
            const hours = Math.floor(elapsedTime / 3600);
            const minutes = Math.floor((elapsedTime % 3600) / 60);
            const seconds = elapsedTime % 60;
            
            timerElement.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        // Question navigation
        function showQuestion(number) {
            document.querySelectorAll('.question-container').forEach(container => {
                container.classList.add('hidden');
            });
            document.getElementById(`question-${number}`).classList.remove('hidden');
        }

        document.querySelectorAll('.question-nav').forEach(button => {
            button.addEventListener('click', () => {
                showQuestion(button.dataset.question);
            });
        });

        // Save answers
        function saveAnswer(questionId, answer) {
            $.post('exam.php', {
                save_answer: true,
                question_id: questionId,
                answer: answer
            }, function(The core structure and key pages of the DesignHIve website have been created, including authentication, dashboard, materi listing, detail materi, task submission, quiz, and quiz result pages, along with the database schema and config files.

Next steps to complete the project would be:
- Implement teacher dashboard and grading pages.
- Implement admin panel for materi management with rich text editor.
- Implement final exam page with navigation and PDF certificate generation.
- Add gamification logic and leaderboard updates.
- Add security hardening and input validation.
- Test all flows end-to-end.

Please confirm if you want me to continue with these next steps or focus on any specific part next.

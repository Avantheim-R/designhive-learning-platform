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

// Get chapter details
$stmt = $pdo->prepare("
    SELECT m.*, p.completed
    FROM materi m
    LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
    WHERE m.chapter = ?
    ORDER BY m.phase LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $chapter]);
$chapter_info = $stmt->fetch();

if (!$chapter_info) {
    redirect('materi.php');
}

// Get previous submission if exists
$stmt = $pdo->prepare("
    SELECT s.*, m.chapter
    FROM submissions s
    JOIN materi m ON s.materi_id = m.id
    WHERE s.user_id = ? AND m.chapter = ?
    ORDER BY s.submitted_at DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id'], $chapter]);
$previous_submission = $stmt->fetch();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($_FILES['design_file']) || $_FILES['design_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Pilih file untuk diupload';
    } else {
        $file = $_FILES['design_file'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'psd', 'ai', 'xd', 'fig'];
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = 'Format file tidak didukung. Format yang diizinkan: ' . implode(', ', $allowed_types);
        }
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Ukuran file maksimal 10MB';
        }
    }
    
    if (empty($errors)) {
        try {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/../uploads/design_files';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $filename = uniqid('design_') . '_' . $chapter . '_' . $_SESSION['user_id'] . '.' . $file_extension;
            $filepath = $upload_dir . '/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save submission to database
                $stmt = $pdo->prepare("
                    INSERT INTO submissions (user_id, materi_id, file_path)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $chapter_info['id'], 'uploads/design_files/' . $filename]);
                
                set_flash_message('success', 'Tugas berhasil diupload!');
                redirect("detail_materi.php?id=" . $chapter_info['id']);
            } else {
                $errors[] = 'Gagal mengupload file. Silakan coba lagi.';
            }
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
    <title>Upload Tugas BAB <?php echo $chapter; ?> - DesignHIve</title>
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
                    <a href="detail_materi.php?id=<?php echo $chapter_info['id']; ?>" class="text-gray-600 hover:text-primary">
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
        <div class="max-w-3xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Upload Tugas BAB <?php echo $chapter; ?></h1>
                <p class="text-gray-600 mt-1">
                    Upload file desain Anda untuk penilaian
                </p>
            </div>

            <!-- Previous Submission -->
            <?php if ($previous_submission): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Submission Sebelumnya</h2>
                <div class="text-sm text-gray-600">
                    <p>Diupload pada: <?php echo date('d M Y H:i', strtotime($previous_submission['submitted_at'])); ?></p>
                    
                    <?php if ($previous_submission['grade']): ?>
                        <p class="mt-2">Nilai: <span class="font-medium"><?php echo $previous_submission['grade']; ?></span></p>
                    <?php else: ?>
                        <p class="mt-2 text-yellow-600">Menunggu penilaian</p>
                    <?php endif; ?>

                    <?php if ($previous_submission['feedback']): ?>
                        <div class="mt-4">
                            <p class="font-medium text-gray-700">Feedback dari guru:</p>
                            <p class="mt-1"><?php echo nl2br(htmlspecialchars($previous_submission['feedback'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upload Form -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Upload File Baru</h2>

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 rounded-md bg-red-50 border border-red-200">
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

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File Desain</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                            <div class="space-y-1 text-center">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-3"></i>
                                <div class="flex text-sm text-gray-600">
                                    <label for="design_file" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary">
                                        <span>Upload file</span>
                                        <input id="design_file" name="design_file" type="file" class="sr-only">
                                    </label>
                                    <p class="pl-1">atau drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    Format yang didukung: JPG, PNG, GIF, PDF, PSD, AI, XD, FIG (Maks. 10MB)
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <i class="fas fa-upload mr-2"></i>
                            Upload Tugas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview selected file name
        const fileInput = document.getElementById('design_file');
        const fileLabel = document.querySelector('[for="design_file"]');
        
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                fileLabel.textContent = fileName;
            } else {
                fileLabel.textContent = 'Upload file';
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-primary');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-primary');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            fileInput.files = files;
            
            if (files[0]) {
                fileLabel.textContent = files[0].name;
            }
        }
    </script>
</body>
</html>

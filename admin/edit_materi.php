<?php
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || get_user_role() !== 'admin') {
    redirect('/login.php');
}

// Get materi ID from URL
$materi_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$materi_id) {
    redirect('manage_materi.php');
}

// Get materi details
$stmt = $pdo->prepare("SELECT * FROM materi WHERE id = ?");
$stmt->execute([$materi_id]);
$materi = $stmt->fetch();

if (!$materi) {
    redirect('manage_materi.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chapter = isset($_POST['chapter']) ? (int)$_POST['chapter'] : 0;
    $phase = isset($_POST['phase']) ? $_POST['phase'] : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';
    
    $errors = [];
    
    if ($chapter < 1) {
        $errors[] = 'BAB harus diisi';
    }
    
    if (!in_array($phase, ['text', 'video', 'minigame'])) {
        $errors[] = 'Pilih jenis materi yang valid';
    }
    
    if (empty($title)) {
        $errors[] = 'Judul harus diisi';
    }
    
    if ($phase === 'text' && empty($content)) {
        $errors[] = 'Konten harus diisi untuk materi teks';
    }
    
    if (($phase === 'video' || $phase === 'minigame') && empty($url)) {
        $errors[] = 'URL harus diisi untuk materi video/minigame';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE materi 
                SET chapter = ?, phase = ?, title = ?, content = ?, url = ?
                WHERE id = ?
            ");
            $stmt->execute([$chapter, $phase, $title, $content, $url, $materi_id]);
            
            set_flash_message('success', 'Materi berhasil diperbarui');
            redirect('manage_materi.php');
            
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
    <title>Edit Materi - DesignHIve</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
                    <a href="manage_materi.php" class="text-gray-600 hover:text-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <span class="text-gray-600"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Edit Materi</h1>
            <p class="text-gray-600 mt-1">
                Edit informasi materi pembelajaran
            </p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-8 p-4 rounded-md bg-red-50 border border-red-200">
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

        <!-- Edit Material Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label for="chapter" class="block text-sm font-medium text-gray-700">
                            BAB
                        </label>
                        <select name="chapter" 
                                id="chapter" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            <option value="">Pilih BAB</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $materi['chapter'] == $i ? 'selected' : ''; ?>>
                                    BAB <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label for="phase" class="block text-sm font-medium text-gray-700">
                            Jenis Materi
                        </label>
                        <select name="phase" 
                                id="phase" 
                                required
                                onchange="toggleFields()"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                            <option value="">Pilih Jenis</option>
                            <option value="text" <?php echo $materi['phase'] === 'text' ? 'selected' : ''; ?>>
                                Teks & Gambar
                            </option>
                            <option value="video" <?php echo $materi['phase'] === 'video' ? 'selected' : ''; ?>>
                                Video
                            </option>
                            <option value="minigame" <?php echo $materi['phase'] === 'minigame' ? 'selected' : ''; ?>>
                                Mini Game
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700">
                        Judul Materi
                    </label>
                    <input type="text" 
                           name="title" 
                           id="title" 
                           required
                           value="<?php echo htmlspecialchars($materi['title']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>

                <div id="content_section" style="display: <?php echo $materi['phase'] === 'text' ? 'block' : 'none'; ?>;">
                    <label for="content" class="block text-sm font-medium text-gray-700">
                        Konten
                    </label>
                    <textarea name="content" 
                              id="content"
                              rows="15"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"><?php echo htmlspecialchars($materi['content']); ?></textarea>
                </div>

                <div id="url_section" style="display: <?php echo $materi['phase'] !== 'text' ? 'block' : 'none'; ?>;">
                    <label for="url" class="block text-sm font-medium text-gray-700">
                        URL Video/Game
                    </label>
                    <input type="url" 
                           name="url" 
                           id="url"
                           value="<?php echo htmlspecialchars($materi['url']); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="manage_materi.php" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            images_upload_url: 'upload.php',
            images_upload_handler: function (blobInfo, success, failure) {
                // This is a placeholder. Implement proper image upload handling
                setTimeout(function () {
                    success('http://example.com/images/' + blobInfo.filename());
                }, 2000);
            }
        });

        // Toggle fields based on selected phase
        function toggleFields() {
            const phase = document.getElementById('phase').value;
            const contentSection = document.getElementById('content_section');
            const urlSection = document.getElementById('url_section');
            
            if (phase === 'text') {
                contentSection.style.display = 'block';
                urlSection.style.display = 'none';
            } else if (phase === 'video' || phase === 'minigame') {
                contentSection.style.display = 'none';
                urlSection.style.display = 'block';
            } else {
                contentSection.style.display = 'none';
                urlSection.style.display = 'none';
            }
        }
    </script>
</body>
</html>

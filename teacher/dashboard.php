<?php
require_once '../config/config.php';

// Check if user is logged in and is a teacher
if (!is_logged_in() || get_user_role() !== 'teacher') {
    redirect('/login.php');
}

// Get pending submissions
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.file_path,
        s.submitted_at,
        s.grade,
        s.feedback,
        u.name as student_name,
        u.nis,
        m.chapter,
        m.title
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    JOIN materi m ON s.materi_id = m.id
    WHERE s.grade IS NULL
    ORDER BY s.submitted_at ASC
");
$stmt->execute();
$pending_submissions = $stmt->fetchAll();

// Get graded submissions
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.file_path,
        s.submitted_at,
        s.grade,
        s.feedback,
        s.graded_at,
        u.name as student_name,
        u.nis,
        m.chapter,
        m.title
    FROM submissions s
    JOIN users u ON s.user_id = u.id
    JOIN materi m ON s.materi_id = m.id
    WHERE s.grade IS NOT NULL
    ORDER BY s.graded_at DESC
    LIMIT 10
");
$stmt->execute();
$graded_submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - DesignHIve</title>
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
            <h1 class="text-2xl font-bold text-gray-800">
                Selamat Datang, <?php echo htmlspecialchars($_SESSION['name']); ?>!
            </h1>
            <p class="text-gray-600 mt-1">
                Dashboard Guru - Penilaian Tugas Siswa
            </p>
        </div>

        <!-- Pending Submissions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Tugas yang Perlu Dinilai</h2>
            
            <?php if (empty($pending_submissions)): ?>
                <p class="text-gray-600">Tidak ada tugas yang perlu dinilai saat ini.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Siswa
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    BAB
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Submit
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    File
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending_submissions as $submission): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($submission['student_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        NIS: <?php echo htmlspecialchars($submission['nis']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        BAB <?php echo $submission['chapter']; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($submission['title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y H:i', strtotime($submission['submitted_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="../<?php echo htmlspecialchars($submission['file_path']); ?>" 
                                       target="_blank"
                                       class="text-primary hover:text-blue-600">
                                        <i class="fas fa-download mr-1"></i>
                                        Download
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="grade_submission.php?id=<?php echo $submission['id']; ?>" 
                                       class="text-primary hover:text-blue-600">
                                        Nilai Tugas
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Graded Submissions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Tugas yang Sudah Dinilai</h2>
            
            <?php if (empty($graded_submissions)): ?>
                <p class="text-gray-600">Belum ada tugas yang dinilai.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Siswa
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    BAB
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nilai
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tanggal Penilaian
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($graded_submissions as $submission): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($submission['student_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        NIS: <?php echo htmlspecialchars($submission['nis']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        BAB <?php echo $submission['chapter']; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($submission['title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?php echo $submission['grade']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y H:i', strtotime($submission['graded_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="grade_submission.php?id=<?php echo $submission['id']; ?>" 
                                       class="text-primary hover:text-blue-600">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

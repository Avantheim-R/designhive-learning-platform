<?php
require_once 'config/config.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_user_role();
    switch ($role) {
        case 'admin':
            redirect('/debug/admin/dashboard.php');
            break;
        case 'teacher':
            redirect('/debug/teacher/dashboard.php');
            break;
        case 'student':
            redirect('/debug/student/dashboard.php');
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DesignHIve</title>
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo and Title -->
            <div class="text-center">
                <i class="fas fa-graduation-cap text-primary text-5xl mb-4"></i>
                <h2 class="text-3xl font-bold text-gray-800">
                    DesignHIve
                </h2>
                <p class="mt-2 text-gray-600">
                    Platform Pembelajaran Desain Grafis
                </p>
            </div>

            <!-- Login Type Selection -->
            <div class="flex justify-center space-x-4 mb-8">
                <button onclick="showLoginForm('staff')" 
                        id="staffBtn"
                        class="px-6 py-2 rounded-full text-sm font-medium focus:outline-none transition-colors">
                    Admin & Guru
                </button>
                <button onclick="showLoginForm('student')" 
                        id="studentBtn"
                        class="px-6 py-2 rounded-full text-sm font-medium focus:outline-none transition-colors">
                    Siswa
                </button>
            </div>

            <!-- Staff Login Form (Admin & Teacher) -->
            <form id="staffLogin" method="POST" action="/debug/includes/auth/login_process.php" class="mt-8 space-y-6 hidden">
                <input type="hidden" name="login_type" value="staff">
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="staff-username" class="sr-only">Username</label>
                        <input id="staff-username" 
                               name="username" 
                               type="text" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Username">
                    </div>
                    <div>
                        <label for="staff-password" class="sr-only">Password</label>
                        <input id="staff-password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-lock"></i>
                        </span>
                        Login sebagai Admin/Guru
                    </button>
                </div>
            </form>

            <!-- Student Login Form -->
            <form id="studentLogin" method="POST" action="/debug/includes/auth/login_process.php" class="mt-8 space-y-6 hidden">
                <input type="hidden" name="login_type" value="student">
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="student-nis" class="sr-only">NIS</label>
                        <input id="student-nis" 
                               name="nis" 
                               type="text" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Nomor Induk Siswa (NIS)">
                    </div>
                    <div>
                        <label for="student-password" class="sr-only">Password</label>
                        <input id="student-password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" 
                               placeholder="Password">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-graduate"></i>
                        </span>
                        Login sebagai Siswa
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <a href="/debug/register.php" class="font-medium text-primary hover:text-blue-600">
                            Belum punya akun? Daftar
                        </a>
                    </div>
                    <div class="text-sm">
                        <a href="/debug/forgot_password.php" class="font-medium text-primary hover:text-blue-600">
                            Lupa password?
                        </a>
                    </div>
                </div>
            </form>

            <!-- Flash Messages -->
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="rounded-md p-4 <?php echo $_SESSION['flash']['type'] === 'success' ? 'bg-green-50' : 'bg-red-50'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas <?php echo $_SESSION['flash']['type'] === 'success' ? 'fa-check-circle text-green-400' : 'fa-exclamation-circle text-red-400'; ?>"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium <?php echo $_SESSION['flash']['type'] === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo $_SESSION['flash']['message']; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Function to show selected login form
        function showLoginForm(type) {
            const staffBtn = document.getElementById('staffBtn');
            const studentBtn = document.getElementById('studentBtn');
            const staffForm = document.getElementById('staffLogin');
            const studentForm = document.getElementById('studentLogin');

            if (type === 'staff') {
                staffBtn.classList.add('bg-primary', 'text-white');
                staffBtn.classList.remove('text-gray-600');
                studentBtn.classList.remove('bg-primary', 'text-white');
                studentBtn.classList.add('text-gray-600');
                staffForm.classList.remove('hidden');
                studentForm.classList.add('hidden');
            } else {
                studentBtn.classList.add('bg-primary', 'text-white');
                studentBtn.classList.remove('text-gray-600');
                staffBtn.classList.remove('bg-primary', 'text-white');
                staffBtn.classList.add('text-gray-600');
                studentForm.classList.remove('hidden');
                staffForm.classList.add('hidden');
            }
        }

        // Show student login by default
        window.onload = function() {
            showLoginForm('student');
        };
    </script>
</body>
</html>

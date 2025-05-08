<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - DesignHIve</title>
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
            <div>
                <div class="flex justify-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-graduation-cap text-primary text-4xl mr-2"></i>
                        <span class="text-3xl font-bold text-gray-800">DesignHIve</span>
                    </a>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Daftar Akun Baru
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Atau
                    <a href="login.php" class="font-medium text-primary hover:text-blue-500">
                        masuk jika sudah memiliki akun
                    </a>
                </p>
            </div>
            <form class="mt-8 space-y-6" action="includes/auth/register_process.php" method="POST">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="nis" class="sr-only">NIS</label>
                        <input id="nis" name="nis" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="NIS">
                    </div>
                    <div>
                        <label for="name" class="sr-only">Nama Lengkap</label>
                        <input id="name" name="name" type="text" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="Nama Lengkap">
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="Password">
                    </div>
                    <div>
                        <label for="password_confirm" class="sr-only">Konfirmasi Password</label>
                        <input id="password_confirm" name="password_confirm" type="password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm" placeholder="Konfirmasi Password">
                    </div>
                    <div>
                        <label for="learning_style" class="sr-only">Gaya Belajar</label>
                        <select id="learning_style" name="learning_style" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-primary focus:border-primary focus:z-10 sm:text-sm">
                            <option value="">Pilih Gaya Belajar</option>
                            <option value="visual">Visual - Belajar melalui gambar dan visual</option>
                            <option value="auditory">Auditori - Belajar melalui suara dan penjelasan</option>
                            <option value="kinesthetic">Kinestetik - Belajar melalui praktik dan gerakan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-user-plus"></i>
                        </span>
                        Daftar
                    </button>
                </div>
            </form>

            <!-- Learning Style Information -->
            <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tentang Gaya Belajar:</h3>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-eye text-primary"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">
                                <span class="font-medium text-gray-700">Visual:</span> Cocok untuk yang lebih mudah memahami melalui gambar, diagram, dan konten visual.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-headphones text-primary"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">
                                <span class="font-medium text-gray-700">Auditori:</span> Ideal untuk yang lebih mudah belajar melalui penjelasan lisan dan diskusi.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-hands text-primary"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">
                                <span class="font-medium text-gray-700">Kinestetik:</span> Tepat untuk yang lebih mudah belajar melalui praktik langsung dan pengalaman.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

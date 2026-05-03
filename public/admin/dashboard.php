<?php
session_start();
require_once '../../private/php/config.php';

// Cek sesi login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Fetch Stats
try {
    $count_berita = $pdo->query("SELECT COUNT(*) FROM berita")->fetchColumn();
    $count_proker = $pdo->query("SELECT COUNT(*) FROM proker")->fetchColumn();
    $count_aspirasi = $pdo->query("SELECT COUNT(*) FROM aspirasi")->fetchColumn();
    
    // Fetch Recent Aspirasi
    $stmt = $pdo->query("SELECT * FROM aspirasi ORDER BY created_at DESC LIMIT 5");
    $recent_aspirasi = $stmt->fetchAll();
} catch (PDOException $e) {
    $count_berita = 0;
    $count_proker = 0;
    $count_aspirasi = 0;
    $recent_aspirasi = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - HIMATEP</title>
    <?php include '../includes/meta_icons.php'; ?>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'himatep-green': '#1B5E20',
                        'himatep-light': '#6efa80', /* Sesuai warna hijau cerah di gambar */
                        'himatep-dark': '#111111',
                    },
                    fontFamily: {
                        'sans': ['Poppins', 'sans-serif'],
                        'cursive': ['"Great Vibes"', 'cursive'],
                    }
                }
            }
        }
    </script>
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    
    <!-- Overlay for Mobile -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition opacity-0 ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition opacity-100 ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false" 
         class="fixed inset-0 bg-black/50 z-30 lg:hidden" style="display:none;"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed lg:static inset-y-0 left-0 w-64 bg-[#1B5E20] text-white flex flex-col shadow-xl z-40 transition-transform duration-300 lg:translate-x-0">
        <div class="p-6 border-b border-green-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="../images/logo-himatep.png" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
                <span class="text-xl font-bold text-white">Admin Panel</span>
            </div>
            <!-- Close Button Mobile -->
            <button @click="sidebarOpen = false" class="lg:hidden text-green-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg bg-green-800 font-medium">Dashboard</a>
            <a href="manage_news.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Kelola Berita</a>
            <a href="manage_proker.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Program Kerja</a>
            <a href="manage_pengurus.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Struktur Pengurus</a>
            <a href="view_aspirasi.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Suara Mahasiswa</a>
        </nav>
        <div class="p-4 border-t border-green-800">
            <a href="logout.php" class="block w-full py-2 px-4 bg-red-500 hover:bg-red-600 rounded text-center font-bold transition shadow">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full">
        <!-- Header -->
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-4 lg:px-8 z-10">
            <div class="flex items-center gap-4">
                <!-- Hamburger Button -->
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800">Dashboard</h2>
            </div>
            <div class="flex items-center gap-3 lg:gap-4">
                <a href="../index.php" class="text-xs lg:text-sm text-himatep-green hover:underline font-medium">Lihat Website</a>
                <div class="flex items-center gap-2 lg:gap-4 border-l border-gray-200 pl-3 lg:pl-4">
                    <span class="font-medium text-xs lg:text-base text-gray-600 truncate max-w-[100px] lg:max-w-none">
                        Halo, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                    </span>
                </div>
            </div>
        </header>

        <!-- Dashboard Stats -->
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stat Card 1 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-400 flex items-center gap-4">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">📰</div>
                    <div>
                        <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Total Berita</h3>
                        <p class="text-3xl font-bold text-gray-800"><?= $count_berita ?></p>
                    </div>
                </div>
                <!-- Stat Card 2 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-400 flex items-center gap-4">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center text-2xl">📋</div>
                    <div>
                        <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Program Kerja</h3>
                        <p class="text-3xl font-bold text-gray-800"><?= $count_proker ?></p>
                    </div>
                </div>
                <!-- Stat Card 3 -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-400 flex items-center gap-4">
                    <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-2xl flex items-center justify-center text-2xl">🗣️</div>
                    <div>
                        <h3 class="text-gray-500 text-sm font-semibold uppercase tracking-wider">Aspirasi Baru</h3>
                        <p class="text-3xl font-bold text-gray-800"><?= $count_aspirasi ?></p>
                    </div>
                </div>
            </div>

            <!-- Recent Aspirasi -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-400 overflow-hidden">
                <div class="p-6 border-b">
                    <h3 class="text-xl font-bold">Aspirasi Terbaru</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama & Email</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4">Pesan</th>
                                <th class="px-6 py-4">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($recent_aspirasi as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium"><?= htmlspecialchars($item['nama'] ?: 'Anonim') ?></div>
                                    <div class="text-[10px] text-gray-500 italic"><?= htmlspecialchars($item['email'] == '-' ? 'Tanpa Email' : $item['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-blue-600"><?= htmlspecialchars($item['jenis']) ?></td>
                                <td class="px-6 py-4 text-sm truncate max-w-[150px]"><?= htmlspecialchars($item['pesan']) ?></td>
                                <td class="px-6 py-4 text-xs text-gray-400"><?= date('d/m/Y', strtotime($item['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_aspirasi)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 italic">Belum ada aspirasi masuk.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 bg-gray-50 text-center border-t">
                    <a href="view_aspirasi.php" class="text-sm text-himatep-green font-bold hover:underline">Lihat Semua Aspirasi</a>
                </div>
            </div>
        </div>
    </main>

    <!-- Tambahkan script Alpine jika belum ada (biasanya di head tapi aman jika di sini) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>

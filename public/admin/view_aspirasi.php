<?php
session_start();
require_once '../../private/php/config.php';

// Cek sesi login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

// Update Status if requested
if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['update_status'];
    try {
        $stmt = $pdo->prepare("UPDATE aspirasi SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);
        header("Location: view_aspirasi.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal memperbarui status.";
    }
}

// Delete if requested
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM aspirasi WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: view_aspirasi.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus data.";
    }
}

// Fetch All Aspirasi
try {
    $stmt = $pdo->query("SELECT * FROM aspirasi ORDER BY created_at DESC");
    $aspirasi_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $aspirasi_list = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Aspirasi - HIMATEP</title>
    <?php include '../includes/meta_icons.php'; ?>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'himatep-green': '#2563EB',
                        'himatep-light': '#DBEAFE', /* Nuansa biru muda untuk tema baru */
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    
    <!-- Overlay for Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 lg:hidden" x-transition style="display:none;"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed lg:static inset-y-0 left-0 w-64 bg-[#2563EB] text-white flex flex-col shadow-xl z-40 transition-transform duration-300 lg:translate-x-0">
        <div class="p-6 border-b border-green-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="../images/logo-himatep.png" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
                <span class="text-xl font-bold">Admin Panel</span>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-green-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg hover:bg-blue-800 transition text-green-100">Dashboard</a>
            <a href="manage_news.php" class="block py-3 px-4 rounded-lg hover:bg-blue-800 transition text-green-100">Kelola Berita</a>
            <a href="manage_proker.php" class="block py-3 px-4 rounded-lg hover:bg-blue-800 transition text-green-100">Program Kerja</a>
            <a href="manage_pengurus.php" class="block py-3 px-4 rounded-lg hover:bg-blue-800 transition text-green-100">Struktur Pengurus</a>
            <a href="view_aspirasi.php" class="block py-3 px-4 rounded-lg bg-blue-800 font-medium">Suara Mahasiswa</a>
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
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800">Kelola Aspirasi</h2>
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

        <div class="flex-1 p-8 overflow-y-auto">
            <?php if(isset($_GET['success'])): ?>
                <div class="bg-blue-100 text-green-700 p-4 rounded-xl mb-6">Status berhasil diperbarui!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
                <div class="bg-yellow-100 text-yellow-700 p-4 rounded-xl mb-6">Aspirasi berhasil dihapus.</div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-400 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama & Email</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4">Pesan</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($aspirasi_list as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($item['nama'] ?: 'Anonim') ?></div>
                                    <div class="text-xs text-gray-500 italic"><?= htmlspecialchars($item['email'] == '-' ? 'Tanpa Email' : $item['email']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-lg text-[10px] font-bold uppercase"><?= htmlspecialchars($item['jenis']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm whitespace-pre-wrap max-w-xs text-gray-600"><?= htmlspecialchars($item['pesan']) ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold 
                                        <?= $item['status'] == 'Baru' ? 'bg-red-100 text-red-600' : ($item['status'] == 'Dibaca' ? 'bg-blue-100 text-blue-600' : 'bg-blue-100 text-green-600') ?>">
                                        <?= $item['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-400"><?= date('d M Y H:i', strtotime($item['created_at'])) ?></td>
                                <td class="px-6 py-4 text-sm space-y-2">
                                    <div class="flex gap-2">
                                        <a href="?update_status=Dibaca&id=<?= $item['id'] ?>" class="text-blue-600 hover:underline">Tandai Dibaca</a>
                                        <a href="?update_status=Selesai&id=<?= $item['id'] ?>" class="text-green-600 hover:underline">Selesai</a>
                                    </div>
                                    <a href="?delete=1&id=<?= $item['id'] ?>" class="text-red-600 hover:underline block" onclick="return confirm('Hapus aspirasi ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($aspirasi_list)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada aspirasi masuk.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <!-- Script Alpine.js untuk fitur interaktif -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>

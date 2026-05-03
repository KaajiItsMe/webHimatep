<?php
session_start();
require_once '../../private/php/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

// Delete News
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM berita WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: manage_news.php?success=Berita berhasil dihapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus berita.";
    }
}

// Add/Update News
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $judul = $_POST['judul'];
    $slug = strtolower(str_replace(' ', '-', $judul));
    $kategori = $_POST['kategori'];
    $kategori_color = $_POST['kategori_color'];
    $penulis = $_POST['penulis'];
    $gambar = $_POST['gambar'];
    $ringkasan = $_POST['ringkasan'];
    $isi = $_POST['isi'];
    $tanggal = $_POST['tanggal_posting'];

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE berita SET judul=?, slug=?, kategori=?, kategori_color=?, penulis=?, gambar=?, ringkasan=?, isi=?, tanggal_posting=? WHERE id=?");
            $stmt->execute([$judul, $slug, $kategori, $kategori_color, $penulis, $gambar, $ringkasan, $isi, $tanggal, $id]);
            $message = "Berita berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO berita (judul, slug, kategori, kategori_color, penulis, gambar, ringkasan, isi, tanggal_posting) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$judul, $slug, $kategori, $kategori_color, $penulis, $gambar, $ringkasan, $isi, $tanggal]);
            $message = "Berita baru berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $error = "Gagal menyimpan berita: " . $e->getMessage();
    }
}

// Fetch News
$berita_list = $pdo->query("SELECT * FROM berita ORDER BY tanggal_posting DESC")->fetchAll();

// Edit Mode
$edit_data = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita - HIMATEP</title>
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
         
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed lg:static inset-y-0 left-0 w-64 bg-[#1B5E20] text-white flex flex-col shadow-xl z-40 transition-transform duration-300 lg:translate-x-0">
        <div class="p-6 border-b border-green-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="../images/logo-himatep.png" alt="Logo" class="h-8 w-8 bg-white rounded-full p-1">
                <span class="text-xl font-bold">Admin Panel</span>
            </div>
            <!-- Close Button Mobile -->
            <button @click="sidebarOpen = false" class="lg:hidden text-green-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Dashboard</a>
            <a href="manage_news.php" class="block py-3 px-4 rounded-lg bg-green-800 font-medium">Kelola Berita</a>
            <a href="manage_proker.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Program Kerja</a>
            <a href="view_aspirasi.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Suara Mahasiswa</a>
        </nav>
        <div class="p-4 border-t border-green-800">
            <a href="logout.php" class="block w-full py-2 px-4 bg-red-500 hover:bg-red-600 rounded text-center font-bold transition shadow">Logout</a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full">
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-4 lg:px-8 z-10">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800"><?= $edit_data ? 'Edit Berita' : 'Tambah Berita Baru' ?></h2>
            </div>
            <div class="flex items-center gap-3 lg:gap-4">
                <?php if($edit_data): ?>
                    <a href="manage_news.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 lg:px-4 lg:py-2 rounded-lg text-[10px] lg:text-sm font-bold text-gray-600 transition border border-gray-200">Batal</a>
                <?php endif; ?>
                <a href="../index.php" class="text-xs lg:text-sm text-himatep-green hover:underline font-medium">Lihat Website</a>
                <div class="flex items-center gap-2 lg:gap-4 border-l border-gray-200 pl-3 lg:pl-4">
                    <span class="font-medium text-xs lg:text-base text-gray-600 truncate max-w-[100px] lg:max-w-none">
                        Halo, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
                    </span>
                </div>
            </div>
        </header>

        <div class="flex-1 p-8 overflow-y-auto grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Form -->
            <div class="xl:col-span-1 bg-white p-6 rounded-2xl shadow-sm border border-gray-400 h-fit">
                <?php if($message): ?> <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-4"><?= $message ?></div> <?php endif; ?>
                <?php if($error): ?> <div class="bg-red-100 text-red-700 p-4 rounded-xl mb-4"><?= $error ?></div> <?php endif; ?>

                <form action="manage_news.php" method="POST" class="space-y-4">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
                    <div>
                        <label class="block text-sm font-bold mb-1">Judul Berita</label>
                        <input type="text" name="judul" value="<?= htmlspecialchars($edit_data['judul'] ?? '') ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-1">Kategori</label>
                            <input type="text" name="kategori" value="<?= htmlspecialchars($edit_data['kategori'] ?? '') ?>" class="w-full p-2 border rounded-lg" placeholder="Contoh: Kegiatan" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Warna Kategori</label>
                            <select name="kategori_color" class="w-full p-2 border rounded-lg">
                                <option value="green" <?= ($edit_data['kategori_color'] ?? '') == 'green' ? 'selected' : '' ?>>Hijau</option>
                                <option value="blue" <?= ($edit_data['kategori_color'] ?? '') == 'blue' ? 'selected' : '' ?>>Biru</option>
                                <option value="purple" <?= ($edit_data['kategori_color'] ?? '') == 'purple' ? 'selected' : '' ?>>Ungu</option>
                                <option value="yellow" <?= ($edit_data['kategori_color'] ?? '') == 'yellow' ? 'selected' : '' ?>>Kuning</option>
                                <option value="red" <?= ($edit_data['kategori_color'] ?? '') == 'red' ? 'selected' : '' ?>>Merah</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-1">Penulis</label>
                            <input type="text" name="penulis" value="<?= htmlspecialchars($edit_data['penulis'] ?? 'Admin') ?>" class="w-full p-2 border rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Tanggal Posting</label>
                            <input type="date" name="tanggal_posting" value="<?= $edit_data['tanggal_posting'] ?? date('Y-m-d') ?>" class="w-full p-2 border rounded-lg" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">URL Gambar</label>
                        <input type="text" name="gambar" value="<?= htmlspecialchars($edit_data['gambar'] ?? '') ?>" class="w-full p-2 border rounded-lg" placeholder="https://..." required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Ringkasan (Pendek)</label>
                        <textarea name="ringkasan" class="w-full p-2 border rounded-lg" rows="2" required><?= htmlspecialchars($edit_data['ringkasan'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Isi Berita (HTML diperbolehkan)</label>
                        <textarea name="isi" class="w-full p-2 border rounded-lg" rows="8" required><?= htmlspecialchars($edit_data['isi'] ?? '') ?></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-himatep-green text-white font-bold py-3 rounded-xl hover:bg-green-800 transition">
                            <?= $edit_data ? 'Simpan Perubahan' : 'Terbitkan Berita' ?>
                        </button>
                        <?php if(!$edit_data): ?>
                            <button type="reset" onclick="setTimeout(updatePreview, 10)" class="px-6 py-3 border border-gray-300 text-gray-500 font-bold rounded-xl active:bg-gray-200 hover:bg-gray-200 transition">
                                Kosongkan
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Preview & List Container -->
            <div class="xl:col-span-2 space-y-8">
                <!-- Live Preview -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-400 overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b font-bold flex justify-between items-center">
                        <span>Live Preview (Tampilan Berita)</span>
                        <span class="text-xs font-normal text-gray-500 italic">Pratinjau</span>
                    </div>
                    <div class="p-8 max-w-2xl mx-auto">
                        <div id="preview-category" class="inline-block px-3 py-1 rounded-full text-xs font-bold text-white bg-green-600 mb-4 uppercase tracking-wider">KATEGORI</div>
                        <h1 id="preview-title" class="text-3xl font-bold text-gray-900 mb-4 leading-tight">Judul Berita Akan Muncul Di Sini</h1>
                        <div class="flex items-center gap-3 mb-6 text-sm text-gray-500 pb-6 border-b">
                            <span id="preview-author" class="font-semibold text-gray-800">Penulis</span>
                            <span>•</span>
                            <span id="preview-date"><?= date('d M Y') ?></span>
                        </div>
                        <img id="preview-image" src="https://via.placeholder.com/800x400" class="w-full h-64 object-cover rounded-2xl mb-8 shadow-lg">
                        <div id="preview-content" class="prose prose-green max-w-none text-gray-600 leading-relaxed">
                            Konten berita Anda akan tampil di sini secara real-time...
                        </div>
                    </div>
                </div>

                <!-- List -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-400 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b font-bold">Daftar Berita</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="p-4">Preview</th>
                                <th class="p-4">Judul</th>
                                <th class="p-4">Kategori</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach($berita_list as $b): ?>
                            <tr>
                                <td class="p-4">
                                    <img src="<?= htmlspecialchars($b['gambar']) ?>" class="w-16 h-10 object-cover rounded shadow-sm" onerror="this.src='https://via.placeholder.com/100x60'">
                                </td>
                                <td class="p-4 font-medium"><?= htmlspecialchars($b['judul']) ?></td>
                                <td class="p-4 text-xs"><?= htmlspecialchars($b['kategori']) ?></td>
                                <td class="p-4 space-x-2">
                                    <a href="../detail-berita.php?slug=<?= $b['slug'] ?>" target="_blank" class="text-green-600 font-bold hover:underline">Lihat</a>
                                    <a href="?edit=1&id=<?= $b['id'] ?>" class="text-blue-600 font-bold">Edit</a>
                                    <a href="?delete=1&id=<?= $b['id'] ?>" class="text-red-600 font-bold" onclick="return confirm('Hapus berita ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <script>
        // Live Preview Logic
        const fields = ['judul', 'kategori', 'penulis', 'gambar', 'isi', 'tanggal_posting'];
        const previewMap = {
            judul: 'preview-title',
            kategori: 'preview-category',
            penulis: 'preview-author',
            gambar: 'preview-image',
            isi: 'preview-content',
            tanggal_posting: 'preview-date'
        };

        function updatePreview() {
            fields.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                const preview = document.getElementById(previewMap[field]);
                
                if (input && preview) {
                    if (field === 'gambar') {
                        preview.src = input.value || 'https://via.placeholder.com/800x400';
                    } else if (field === 'isi') {
                        preview.innerHTML = input.value || 'Konten berita Anda akan tampil di sini...';
                    } else if (field === 'kategori') {
                        preview.innerText = input.value.toUpperCase() || 'KATEGORI';
                        const colorInput = document.querySelector('[name="kategori_color"]');
                        const colors = {
                            green: 'bg-green-600',
                            blue: 'bg-blue-600',
                            purple: 'bg-purple-600',
                            yellow: 'bg-yellow-500',
                            red: 'bg-red-600'
                        };
                        preview.className = `inline-block px-3 py-1 rounded-full text-xs font-bold text-white mb-4 uppercase tracking-wider ${colors[colorInput.value]}`;
                    } else {
                        preview.innerText = input.value || (field === 'judul' ? 'Judul Berita Akan Muncul' : '...');
                    }
                }
            });
        }

        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });

        // Init
        updatePreview();
    </script>
    <!-- Script Alpine.js untuk fitur interaktif -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>

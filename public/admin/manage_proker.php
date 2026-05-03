<?php
session_start();
require_once '../../private/php/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';

$division_colors = [
    'BPH' => 'emerald',
    'PSDM' => 'blue',
    'Penelitian & Pengembangan' => 'blue',
    'Hubungan Masyarakat' => 'green',
    'Pengabdian Masyarakat' => 'purple',
    'Minat & Bakat' => 'orange',
    'Kesejahteraan Mahasiswa' => 'pink',
];

$icons = [
    'Calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'Users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'Book' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
    'Lightbulb' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    'Globe' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    'Trophy' => 'M20 7h-1.233c.125-.67.19-1.354.209-2.049C19.04 4.146 18.332 3 17 3H7c-1.332 0-2.04 1.146-1.976 1.951.019.695.084 1.379.209 2.049H4c-1.105 0-2 .895-2 2v2c0 1.105.895 2 2 2h2.82c1.017 2.025 2.84 3.513 5.18 3.862V19H9v2h6v-2h-3v-2.138c2.34-.349 4.163-1.837 5.18-3.862H20c1.105 0 2-.895 2-2V9c0-1.105-.895-2-2-2zM4 11V9h1.133c-.03.684-.047 1.365-.05 2H4zm14.867 0c-.003-.635-.02-1.316-.05-2H20v2h-1.133z',
    'Briefcase' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745V20a2 2 0 002 2h14a2 2 0 002-2v-6.745zM16 8V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v3H4a2 2 0 00-2 2v3a2 2 0 002 2h16a2 2 0 002-2v-3a2 2 0 00-2-2h-4zM10 8V5h4v3h-4z'
];

// Delete Proker
if (isset($_GET['delete']) && isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM proker WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        header("Location: manage_proker.php?success=Proker berhasil dihapus");
        exit;
    } catch (PDOException $e) {
        $error = "Gagal menghapus program kerja.";
    }
}

// Add/Update Proker
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? null;
    $judul = $_POST['judul'];
    $slug = strtolower(str_replace(' ', '-', $judul));
    $divisi = $_POST['divisi'];
    $divisi_color = $_POST['divisi_color'];
    $icon = $_POST['icon'];
    $target = $_POST['target'];
    $sasaran = $_POST['sasaran'];
    $gambar = $_POST['gambar'];
    $ringkasan = $_POST['ringkasan'];
    $isi = $_POST['isi'];
    $is_unggulan = isset($_POST['is_unggulan']) ? 1 : 0;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE proker SET judul=?, slug=?, divisi=?, divisi_color=?, icon=?, target=?, sasaran=?, gambar=?, ringkasan=?, isi=?, is_unggulan=? WHERE id=?");
            $stmt->execute([$judul, $slug, $divisi, $divisi_color, $icon, $target, $sasaran, $gambar, $ringkasan, $isi, $is_unggulan, $id]);
            $message = "Program kerja berhasil diperbarui!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO proker (judul, slug, divisi, divisi_color, icon, target, sasaran, gambar, ringkasan, isi, is_unggulan) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$judul, $slug, $divisi, $divisi_color, $icon, $target, $sasaran, $gambar, $ringkasan, $isi, $is_unggulan]);
            $message = "Program kerja baru berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        $error = "Gagal menyimpan program kerja: " . $e->getMessage();
    }
}

// Fetch Proker
$proker_list = $pdo->query("SELECT * FROM proker ORDER BY id ASC")->fetchAll();

// Edit Mode
$edit_data = null;
if (isset($_GET['edit']) && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM proker WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $edit_data = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Program Kerja - HIMATEP</title>
    <?php include '../includes/meta_icons.php'; ?>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Poppins', sans-serif; } </style>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden" x-data="{ sidebarOpen: false }">
    
    <!-- Overlay for Mobile -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 lg:hidden" x-transition style="display:none;"></div>

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" 
           class="fixed lg:static inset-y-0 left-0 w-64 bg-[#1B5E20] text-white flex flex-col shadow-xl z-40 transition-transform duration-300 lg:translate-x-0">
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
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Dashboard</a>
            <a href="manage_news.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Kelola Berita</a>
            <a href="manage_proker.php" class="block py-3 px-4 rounded-lg bg-green-800 font-medium">Program Kerja</a>
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
                <h2 class="text-xl lg:text-2xl font-bold text-gray-800"><?= $edit_data ? 'Edit Proker' : 'Tambah Proker Baru' ?></h2>
            </div>
            <div class="flex items-center gap-3 lg:gap-4">
                <?php if($edit_data): ?>
                    <a href="manage_proker.php" class="bg-gray-100 hover:bg-gray-200 px-3 py-1.5 lg:px-4 lg:py-2 rounded-lg text-[10px] lg:text-sm font-bold text-gray-600 transition border border-gray-200">Batal</a>
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

                <form action="manage_proker.php" method="POST" class="space-y-4">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
                    <div>
                        <label class="block text-sm font-bold mb-1">Judul Program Kerja</label>
                        <input type="text" name="judul" value="<?= htmlspecialchars($edit_data['judul'] ?? '') ?>" class="w-full p-2 border rounded-lg" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-1">Divisi Pelaksana</label>
                            <select name="divisi" class="w-full p-2 border rounded-lg" required onchange="updateDivisiColor(this.value)">
                                <option value="">Pilih Divisi</option>
                                <?php foreach($division_colors as $div => $col): ?>
                                    <option value="<?= $div ?>" <?= ($edit_data['divisi'] ?? '') == $div ? 'selected' : '' ?> data-color="<?= $col ?>"><?= $div ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Warna Divisi</label>
                            <input type="text" name="divisi_color" value="<?= htmlspecialchars($edit_data['divisi_color'] ?? 'green') ?>" class="w-full p-2 border rounded-lg bg-gray-100" readonly>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Icon Program</label>
                        <div class="grid grid-cols-4 gap-2 border p-3 rounded-lg bg-gray-50">
                            <?php foreach($icons as $name => $path): ?>
                                <label class="cursor-pointer group relative">
                                    <input type="radio" name="icon" value="<?= $path ?>" class="hidden peer" <?= ($edit_data['icon'] ?? '') == $path ? 'checked' : '' ?> required>
                                    <div class="p-2 border-2 rounded-lg bg-white peer-checked:border-himatep-green peer-checked:bg-green-50 hover:border-gray-300 transition-all flex flex-col items-center gap-1">
                                        <svg class="w-6 h-6 text-gray-600 peer-checked:text-himatep-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $path ?>"></path>
                                        </svg>
                                        <span class="text-[10px] text-gray-500"><?= $name ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold mb-1">Target Waktu</label>
                            <input type="text" name="target" value="<?= htmlspecialchars($edit_data['target'] ?? '') ?>" class="w-full p-2 border rounded-lg" placeholder="Contoh: April 2026" required>
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Sasaran</label>
                            <input type="text" name="sasaran" value="<?= htmlspecialchars($edit_data['sasaran'] ?? '') ?>" class="w-full p-2 border rounded-lg" placeholder="Contoh: Mahasiswa Aktif" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">URL Gambar Banner</label>
                        <input type="text" name="gambar" value="<?= htmlspecialchars($edit_data['gambar'] ?? '') ?>" class="w-full p-2 border rounded-lg" placeholder="https://..." required>
                    </div>
                    <div class="flex items-center gap-2 py-2">
                        <input type="checkbox" name="is_unggulan" id="is_unggulan" <?= ($edit_data['is_unggulan'] ?? 0) ? 'checked' : '' ?>>
                        <label for="is_unggulan" class="text-sm font-bold">Tampilkan sebagai Program Unggulan</label>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Ringkasan</label>
                        <textarea name="ringkasan" class="w-full p-2 border rounded-lg" rows="2" required><?= htmlspecialchars($edit_data['ringkasan'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Detail Konten (HTML)</label>
                        <textarea name="isi" class="w-full p-2 border rounded-lg" rows="8" required><?= htmlspecialchars($edit_data['isi'] ?? '') ?></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-himatep-green text-white font-bold py-3 rounded-xl hover:bg-green-800 transition">
                            <?= $edit_data ? 'Simpan Perubahan' : 'Tambah Program' ?>
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
                        <span>Live Preview (Tampilan Detail Program)</span>
                        <span class="text-xs font-normal text-gray-500 italic">Pratinjau</span>
                    </div>
                    <div class="p-8 max-w-2xl mx-auto">
                        <div id="preview-divisi" class="inline-block px-3 py-1 rounded-full text-xs font-bold text-white bg-green-600 mb-4 uppercase tracking-wider">DIVISI</div>
                        <h1 id="preview-title" class="text-3xl font-bold text-gray-900 mb-6 leading-tight">Nama Program Kerja</h1>
                        
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div class="text-xs text-gray-400 uppercase font-bold mb-1">Target Waktu</div>
                                <div id="preview-target" class="font-bold text-gray-700">April 2026</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <div class="text-xs text-gray-400 uppercase font-bold mb-1">Sasaran</div>
                                <div id="preview-sasaran" class="font-bold text-gray-700">Mahasiswa</div>
                            </div>
                        </div>

                        <img id="preview-image" src="https://via.placeholder.com/800x400" class="w-full h-64 object-cover rounded-2xl mb-8 shadow-lg">
                        
                        <div class="prose prose-green max-w-none text-gray-600 leading-relaxed">
                            <div id="preview-ringkasan" class="font-bold text-gray-800 mb-4 text-lg">Ringkasan singkat program akan tampil di sini.</div>
                            <div id="preview-content">Detail konten lengkap program...</div>
                        </div>
                    </div>
                </div>

                <!-- List -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-400 overflow-hidden">
                <div class="p-4 bg-gray-50 border-b font-bold">Daftar Proker</div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                            <tr>
                                <th class="p-4">Banner</th>
                                <th class="p-4">Nama Program</th>
                                <th class="p-4">Divisi</th>
                                <th class="p-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach($proker_list as $p): ?>
                            <tr>
                                <td class="p-4">
                                    <img src="<?= htmlspecialchars($p['gambar']) ?>" class="w-16 h-10 object-cover rounded shadow-sm" onerror="this.src='https://via.placeholder.com/100x60'">
                                </td>
                                <td class="p-4 font-medium"><?= htmlspecialchars($p['judul']) ?></td>
                                <td class="p-4 text-xs"><?= htmlspecialchars($p['divisi']) ?></td>
                                <td class="p-4 space-x-2">
                                    <a href="../detail-program.php?slug=<?= $p['slug'] ?>" target="_blank" class="text-green-600 font-bold hover:underline">Lihat</a>
                                    <a href="?edit=1&id=<?= $p['id'] ?>" class="text-blue-600 font-bold">Edit</a>
                                    <a href="?delete=1&id=<?= $p['id'] ?>" class="text-red-600 font-bold" onclick="return confirm('Hapus proker ini?')">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script>
        const fields = ['judul', 'divisi', 'gambar', 'target', 'sasaran', 'ringkasan', 'isi'];
        const previewMap = {
            judul: 'preview-title',
            divisi: 'preview-divisi',
            gambar: 'preview-image',
            target: 'preview-target',
            sasaran: 'preview-sasaran',
            ringkasan: 'preview-ringkasan',
            isi: 'preview-content'
        };

        function updateDivisiColor(divisiName) {
            const select = document.querySelector('select[name="divisi"]');
            const colorInput = document.querySelector('input[name="divisi_color"]');
            const selectedOption = select.options[select.selectedIndex];
            const color = selectedOption.getAttribute('data-color');
            
            if (color) {
                colorInput.value = color;
                updatePreview();
            }
        }

        function updatePreview() {
            fields.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                const preview = document.getElementById(previewMap[field]);
                
                if (input && preview) {
                    if (field === 'gambar') {
                        preview.src = input.value || 'https://via.placeholder.com/800x400';
                    } else if (field === 'isi' || field === 'ringkasan') {
                        preview.innerHTML = input.value || (field === 'ringkasan' ? 'Ringkasan...' : 'Konten...');
                    } else if (field === 'divisi') {
                        preview.innerText = input.value.toUpperCase() || 'DIVISI';
                        const colorInput = document.querySelector('[name="divisi_color"]');
                        const color = colorInput.value || 'green';
                        
                        // Remove existing color classes
                        preview.classList.forEach(cls => {
                            if (cls.startsWith('bg-')) preview.classList.remove(cls);
                        });
                        
                        // Add new color class
                        preview.classList.add(`bg-${color}-600`);
                    } else {
                        preview.innerText = input.value || '...';
                    }
                }
            });
            
            // Preview Icon in its target section (if we had a place for it in preview)
            // Currently the icon is only shown in proker.php cards, but we can update the preview if needed.
        }

        document.querySelectorAll('input, textarea, select').forEach(el => {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        });

        updatePreview();
    </script>
    <!-- Script Alpine.js untuk fitur interaktif -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>

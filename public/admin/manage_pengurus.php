<?php
session_start();
require_once '../../private/php/config.php';

// Cek sesi login
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$upload_dir = '../images/pengurus/';

// Pastikan folder upload ada
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Proses Hapus
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Cari foto lama
    $stmt = $pdo->prepare("SELECT foto FROM pengurus WHERE id = ?");
    $stmt->execute([$id]);
    $foto_lama = $stmt->fetchColumn();
    
    if ($foto_lama && $foto_lama !== 'default.png' && file_exists($upload_dir . $foto_lama)) {
        unlink($upload_dir . $foto_lama);
    }
    
    $stmt = $pdo->prepare("DELETE FROM pengurus WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['message'] = "Data pengurus berhasil dihapus.";
    }
    header("Location: manage_pengurus.php");
    exit;
}

// Proses Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama = trim($_POST['nama']);
    $jabatan = trim($_POST['jabatan']);
    $divisi = trim($_POST['divisi']);
    $urutan = (int)$_POST['urutan'];
    $periode = trim($_POST['periode']);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $foto = 'default.png';

    // Handle Upload File Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto']['tmp_name'];
        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $_FILES['foto']['name']);
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
            $foto = $file_name;
        }
    }

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO pengurus (nama, jabatan, divisi, urutan, periode, deskripsi, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nama, $jabatan, $divisi, $urutan, $periode, $deskripsi, $foto])) {
            $_SESSION['message'] = "Data pengurus berhasil ditambahkan.";
        }
    } elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Hapus foto lama jika ada
            $stmt = $pdo->prepare("SELECT foto FROM pengurus WHERE id = ?");
            $stmt->execute([$id]);
            $foto_lama = $stmt->fetchColumn();
            if ($foto_lama && $foto_lama !== 'default.png' && file_exists($upload_dir . $foto_lama)) {
                unlink($upload_dir . $foto_lama);
            }
            
            $stmt = $pdo->prepare("UPDATE pengurus SET nama=?, jabatan=?, divisi=?, urutan=?, periode=?, deskripsi=?, foto=? WHERE id=?");
            $stmt->execute([$nama, $jabatan, $divisi, $urutan, $periode, $deskripsi, $foto, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE pengurus SET nama=?, jabatan=?, divisi=?, urutan=?, periode=?, deskripsi=? WHERE id=?");
            $stmt->execute([$nama, $jabatan, $divisi, $urutan, $periode, $deskripsi, $id]);
        }
        $_SESSION['message'] = "Data pengurus berhasil diperbarui.";
    }
    header("Location: manage_pengurus.php");
    exit;
}

// Menangkap Flash Message
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Ambil semua data pengurus
$stmt = $pdo->query("SELECT * FROM pengurus ORDER BY urutan ASC");
$pengurus_list = $stmt->fetchAll();

// Pisahkan BPH dan Divisi
$bph_list = [];
$divisi_list = [];
$taken_bph_roles = [];

foreach ($pengurus_list as $p) {
    if (strtoupper(trim($p['divisi'])) === 'BPH') {
        $bph_list[] = $p;
        $taken_bph_roles[$p['jabatan']] = $p['id'];
    } else {
        $divisi_list[$p['divisi']][] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengurus - Admin HIMATEP</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* CSS untuk Org Chart (Preview) */
        .org-tree {
            display: flex;
            justify-content: center;
            overflow-x: auto;
            padding-bottom: 20px;
        }
        .org-tree ul {
            padding-top: 40px;
            position: relative;
            transition: all 0.5s;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Cegah li stretch ke bawah */
        }
        .org-tree li {
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 40px 5px 0 5px;
            transition: all 0.5s;
        }
        .org-tree li::before, .org-tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 2px solid #9ca3af; /* Warna garis yang jelas */
            width: 50%;
            height: 40px;
        }
        .org-tree li::after {
            right: auto;
            left: 50%;
            border-left: 2px solid #9ca3af;
        }
        .org-tree li:only-child::after, .org-tree li:only-child::before {
            display: none;
        }
        .org-tree li:only-child {
            padding-top: 0;
        }
        .org-tree li:first-child::before, .org-tree li:last-child::after {
            border: 0 none;
        }
        .org-tree li:last-child::before {
            border-right: 2px solid #9ca3af;
            border-radius: 0 5px 0 0;
        }
        .org-tree li:first-child::after {
            border-radius: 5px 0 0 0;
        }
        .org-tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 2px solid #9ca3af;
            width: 0;
            height: 40px;
        }
        .org-node {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid #e5e7eb;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            min-width: 150px;
            max-width: 150px;
            height: max-content; /* Paksa tingginya mengikuti konten */
            position: relative;
            z-index: 10;
        }
        .org-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 10px auto;
            border: 2px solid #1B5E20;
            padding: 2px;
            background: white;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'himatep-green': '#1B5E20',
                        'himatep-light': '#6efa80',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden" 
      x-data="{ 
          sidebarOpen: false, 
          modalOpen: false, 
          modalMode: 'add', 
          takenRoles: <?= htmlspecialchars(json_encode($taken_bph_roles), ENT_QUOTES, 'UTF-8') ?>,
          form: { id: '', nama: '', jabatan: '', divisi: 'BPH', urutan: '', periode: '2026/2027', deskripsi: '' },
          openModal(mode, data = null) {
              this.modalMode = mode;
              if (mode === 'edit' && data) {
                  this.form = { ...data };
              } else {
                  this.form = { id: '', nama: '', jabatan: '', divisi: 'BPH', urutan: '', periode: '2026/2027', deskripsi: '' };
              }
              this.modalOpen = true;
          },
          isRoleTaken(role) {
              if (!this.takenRoles[role]) return false;
              if (this.modalMode === 'edit' && this.form.id == this.takenRoles[role]) return false;
              return true;
          },
          isBphRole(role) {
              return ['Ketua Umum', 'Sekretaris Umum', 'Bendahara Umum'].includes(role);
          }
      }">

    <!-- Sidebar Overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-30 lg:hidden" style="display:none;"></div>

    <!-- Sidebar (Reused standard sidebar) -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed lg:static inset-y-0 left-0 w-64 bg-[#1B5E20] text-white flex flex-col shadow-xl z-40 transition-transform duration-300 lg:translate-x-0">
        <div class="p-6 border-b border-green-800 flex items-center justify-between">
            <span class="text-xl font-bold text-white">Admin Panel</span>
            <button @click="sidebarOpen = false" class="lg:hidden text-green-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Dashboard</a>
            <a href="manage_news.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Kelola Berita</a>
            <a href="manage_proker.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Program Kerja</a>
            <a href="manage_pengurus.php" class="block py-3 px-4 rounded-lg bg-green-800 font-medium">Struktur Pengurus</a>
            <a href="view_aspirasi.php" class="block py-3 px-4 rounded-lg hover:bg-green-800 transition text-green-100">Suara Mahasiswa</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-screen overflow-hidden w-full">
        <!-- Header -->
        <header class="h-20 bg-white shadow-sm flex items-center px-4 lg:px-8 z-10 gap-4">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg bg-gray-100 text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <h2 class="text-2xl font-bold text-gray-800">Struktur Pengurus</h2>
        </header>

        <!-- Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-800">Daftar Pengurus</h3>
                    <button @click="openModal('add')" class="bg-himatep-green hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
                        + Tambah Pengurus
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100/50 text-gray-500 text-sm uppercase tracking-wider">
                                <th class="p-4 border-b">Urutan</th>
                                <th class="p-4 border-b">Pengurus</th>
                                <th class="p-4 border-b">Divisi</th>
                                <th class="p-4 border-b">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm">
                            <?php foreach ($pengurus_list as $p): ?>
                            <tr class="hover:bg-gray-50 transition border-b border-gray-100/50">
                                <td class="p-4 font-medium text-gray-900"><?= $p['urutan'] ?></td>
                                <td class="p-4 flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 overflow-hidden flex-shrink-0">
                                        <img src="../images/pengurus/<?= htmlspecialchars($p['foto']) ?>" onerror="this.src='../images/logo-himatep.png'" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800"><?= htmlspecialchars($p['nama']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($p['jabatan']) ?></p>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold"><?= htmlspecialchars($p['divisi']) ?></span>
                                </td>
                                <td class="p-4 flex gap-2">
                                    <?php
                                        // Escaping khusus untuk pass data JSON di Alpine x-on:click
                                        $p_json = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <button @click="openModal('edit', <?= $p_json ?>)" class="text-blue-500 hover:text-blue-700 font-medium">Edit</button>
                                    <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Yakin hapus?')" class="text-red-500 hover:text-red-700 font-medium ml-2">Hapus</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($pengurus_list)): ?>
                            <tr>
                                <td colspan="4" class="p-8 text-center text-gray-500">Belum ada data pengurus.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Preview Struktur Organisasi (Org Chart) -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Preview Struktur Keseluruhan</h3>
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded font-medium">BPH & Divisi</span>
                </div>
                <div class="p-8 overflow-x-auto">
                    <div class="org-tree w-full flex justify-center min-w-max">
                        <?php if(!empty($bph_list)): ?>
                        <ul>
                            <li>
                                <!-- Level 1: Ketua Umum -->
                                <div class="org-node border-green-600 border-2">
                                    <img src="../images/pengurus/<?= htmlspecialchars($bph_list[0]['foto'] ?? 'default.png') ?>" onerror="this.src='../images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[0]['jabatan']) ?>" class="org-avatar">
                                    <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[0]['nama']) ?></h4>
                                    <p class="text-xs text-green-700 font-bold mt-1"><?= htmlspecialchars($bph_list[0]['jabatan']) ?></p>
                                </div>
                                
                                <?php if(isset($bph_list[1])): ?>
                                <ul>
                                    <li>
                                        <!-- Level 2: Wakil Ketua Umum -->
                                        <div class="org-node border-green-500 border-2">
                                            <img src="../images/pengurus/<?= htmlspecialchars($bph_list[1]['foto'] ?? 'default.png') ?>" onerror="this.src='../images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[1]['jabatan']) ?>" class="org-avatar">
                                            <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[1]['nama']) ?></h4>
                                            <p class="text-xs text-green-700 font-semibold mt-1"><?= htmlspecialchars($bph_list[1]['jabatan']) ?></p>
                                        </div>
                                        
                                        <?php if(count($bph_list) > 2 || !empty($divisi_list)): ?>
                                        <ul>
                                            <!-- Level 3: BPH Sisa (Sekretaris, Bendahara, dll) -->
                                            <?php for($i=2; $i<count($bph_list); $i++): ?>
                                            <li>
                                                <div class="org-node">
                                                    <img src="../images/pengurus/<?= htmlspecialchars($bph_list[$i]['foto'] ?? 'default.png') ?>" onerror="this.src='../images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[$i]['jabatan']) ?>" class="org-avatar">
                                                    <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[$i]['nama']) ?></h4>
                                                    <p class="text-xs text-green-700 font-semibold mt-1"><?= htmlspecialchars($bph_list[$i]['jabatan']) ?></p>
                                                </div>
                                            </li>
                                            <?php endfor; ?>

                                            <!-- Level 3: Divisi-Divisi -->
                                            <?php foreach($divisi_list as $nama_divisi => $anggota_divisi): ?>
                                            <li>
                                                <div class="org-node bg-green-50 border-green-500 border-2 shadow-sm">
                                                    <h4 class="font-bold text-green-800 text-sm uppercase mt-2 mb-2">Divisi<br><?= htmlspecialchars($nama_divisi) ?></h4>
                                                </div>
                                                
                                                <?php if(!empty($anggota_divisi)): ?>
                                                <ul>
                                                    <!-- Level 4: Anggota Divisi -->
                                                    <?php foreach($anggota_divisi as $anggota): 
                                                        $is_koordinator = stripos($anggota['jabatan'], 'Koordinator') !== false;
                                                    ?>
                                                    <li>
                                                        <div class="org-node <?= $is_koordinator ? 'border-green-400 bg-green-50/30' : '' ?>">
                                                            <img src="../images/pengurus/<?= htmlspecialchars($anggota['foto'] ?? 'default.png') ?>" onerror="this.src='../images/logo-himatep.png'" alt="<?= htmlspecialchars($anggota['jabatan']) ?>" class="org-avatar">
                                                            <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($anggota['nama']) ?></h4>
                                                            <p class="text-[10px] text-gray-600 font-bold mt-1 uppercase bg-gray-100 px-2 py-1 rounded inline-block"><?= htmlspecialchars($anggota['jabatan']) ?></p>
                                                        </div>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>

                                        </ul>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <?php else: ?>
                            <div class="bg-gray-100 px-6 py-4 rounded-xl text-center border border-gray-300 w-full text-gray-500">Belum ada data pengurus untuk divisualisasikan.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Modal Form Tambah/Edit Pengurus -->
    <div x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="modalOpen = false"></div>
        
        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg z-50 transform transition-all"
                 x-show="modalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                 
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-xl">
                    <h3 class="text-lg font-bold text-gray-800" x-text="modalMode === 'add' ? 'Tambah Pengurus' : 'Edit Pengurus'"></h3>
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-red-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form action="manage_pengurus.php" method="POST" enctype="multipart/form-data">
                    <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <input type="hidden" name="action" :value="modalMode">
                        <input type="hidden" name="id" x-model="form.id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama" x-model="form.nama" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                            <select name="jabatan" x-model="form.jabatan" @change="if(isBphRole(form.jabatan)) { form.divisi = 'BPH'; }" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none bg-white">
                                <option value="" disabled>-- Pilih Jabatan --</option>
                                <optgroup label="Pengurus Inti (BPH) - Hanya 1 Orang per Peran">
                                    <option value="Ketua Umum" x-bind:disabled="isRoleTaken('Ketua Umum')">Ketua Umum</option>
                                    <option value="Sekretaris Umum" x-bind:disabled="isRoleTaken('Sekretaris Umum')">Sekretaris Umum</option>
                                    <option value="Bendahara Umum" x-bind:disabled="isRoleTaken('Bendahara Umum')">Bendahara Umum</option>
                                </optgroup>
                                <optgroup label="Jabatan Divisi (Bisa Banyak Orang)">
                                    <option value="Koordinator">Koordinator</option>
                                    <option value="Ketua Divisi">Ketua Divisi</option>
                                    <option value="Anggota">Anggota</option>
                                </optgroup>
                            </select>
                            <span x-show="isBphRole(form.jabatan)" class="text-xs text-orange-600 mt-1 block" style="display:none;">
                                *Jabatan Inti mengatur Divisi terkunci sebagai "BPH (Non-Divisi)".
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Divisi</label>
                            <select name="divisi" x-model="form.divisi" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none transition-colors"
                                    :class="isBphRole(form.jabatan) ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : 'bg-white'"
                                    :style="isBphRole(form.jabatan) ? 'pointer-events: none;' : ''">
                                <option value="BPH">BPH (Badan Pengurus Harian - Non Divisi)</option>
                                <option value="PSDM">PSDM</option>
                                <option value="Penelitian & Pengembangan">Penelitian & Pengembangan</option>
                                <option value="Hubungan Masyarakat">Hubungan Masyarakat</option>
                                <option value="Pengabdian Masyarakat">Pengabdian Masyarakat</option>
                                <option value="Minat & Bakat">Minat & Bakat</option>
                                <option value="Kesejahteraan Mahasiswa">Kesejahteraan Mahasiswa</option>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Urutan</label>
                                <input type="number" name="urutan" x-model="form.urutan" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none">
                                <span class="text-xs text-gray-500 mt-1 block">Logika Tree berdasar urutan</span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Periode</label>
                                <input type="text" name="periode" x-model="form.periode" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                            <textarea name="deskripsi" x-model="form.deskripsi" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none" placeholder="Tulis deskripsi singkat pengurus..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Foto Pengurus</label>
                            <input type="file" name="foto" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-himatep-green focus:border-himatep-green outline-none text-sm">
                            <div x-show="modalMode === 'edit'" class="mt-2 text-xs text-gray-500 bg-blue-50 p-2 rounded inline-block">
                                *Biarkan kosong jika tidak ingin mengubah foto.
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex justify-end gap-3">
                        <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg font-medium transition">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-himatep-green hover:bg-green-700 text-white rounded-lg font-medium transition" x-text="modalMode === 'add' ? 'Simpan Data' : 'Update Data'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
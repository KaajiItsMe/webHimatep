<?php
require_once '../private/php/config.php';

// Ambil Proker (untuk Agenda di Profile)
try {
    $stmt = $pdo->query("
        SELECT p.*, a.tanggal_event, a.waktu, a.lokasi 
        FROM proker p 
        LEFT JOIN agenda a ON p.id = a.proker_id 
        WHERE a.tanggal_event IS NOT NULL
        ORDER BY a.tanggal_event ASC
    ");
    $agenda_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $agenda_list = [];
}

// Persiapkan data untuk Alpine.js
$data_program_json = json_encode(array_map(function($p) {
    $date = new DateTime($p['tanggal_event']);
    return [
        'id' => $p['id'],
        'slug' => $p['slug'],
        'judul' => $p['judul'],
        'divisiColor' => $p['divisi_color'],
        'gambar' => $p['gambar'],
        'agenda' => [
            'date' => $p['tanggal_event'],
            'bulan' => strtoupper($date->format('M')),
            'tanggal' => $date->format('d'),
            'waktu' => $p['waktu'],
            'lokasi' => $p['lokasi']
        ]
    ];
}, $agenda_list));

// Ambil Data Pengurus (BPH & Divisi)
try {
    $stmt = $pdo->query("SELECT * FROM pengurus ORDER BY urutan ASC");
    $semua_pengurus = $stmt->fetchAll();
    
    $bph_list = [];
    $divisi_list = [];
    
    foreach ($semua_pengurus as $p) {
        if (strtoupper($p['divisi']) === 'BPH') {
            $bph_list[] = $p;
        } else {
            $divisi_list[$p['divisi']][] = $p;
        }
    }
} catch (PDOException $e) {
    $bph_list = [];
    $divisi_list = [];
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Profil Lengkap HIMATEP FIP UNM. Kenali visi, misi, struktur organisasi, dan sejarah Himpunan Mahasiswa Teknologi Pendidikan UNM.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Profil Organisasi - HIMATEP FIP UNM">
    <meta property="og:description" content="Kenali visi, misi, dan struktur organisasi HIMATEP FIP UNM.">

    <title>Profil Organisasi - HIMATEP FIP UNM</title>
    <?php include 'includes/meta_icons.php'; ?>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'himatep-green': '#1B5E20',
                        'himatep-light': '#6efa80',
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

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- GSAP & ScrollTrigger -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
        /* CSS untuk Org Chart */
        .org-tree ul {
            padding-top: 20px;
            position: relative;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
            display: flex;
            justify-content: center;
        }

        .org-tree li {
            float: left;
            text-align: center;
            list-style-type: none;
            position: relative;
            padding: 20px 5px 0 5px;
            transition: all 0.5s;
            -webkit-transition: all 0.5s;
            -moz-transition: all 0.5s;
        }

        /* Garis penghubung */
        .org-tree li::before,
        .org-tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: 50%;
            border-top: 2px solid #ccc;
            width: 50%;
            height: 20px;
        }

        .org-tree li::after {
            right: auto;
            left: 50%;
            border-left: 2px solid #ccc;
        }

        /* Hapus garis dari elemen pertama dan terakhir */
        .org-tree li:only-child::after,
        .org-tree li:only-child::before {
            display: none;
        }

        .org-tree li:only-child {
            padding-top: 0;
        }

        /* Garis vertikal ke anak pertama */
        .org-tree li:first-child::before,
        .org-tree li:last-child::after {
            border: 0 none;
        }

        /* Garis vertikal dari parent ke child */
        .org-tree li:last-child::before {
            border-right: 2px solid #ccc;
            border-radius: 0 5px 0 0;
            -webkit-border-radius: 0 5px 0 0;
            -moz-border-radius: 0 5px 0 0;
        }

        .org-tree li:first-child::after {
            border-radius: 5px 0 0 0;
            -webkit-border-radius: 5px 0 0 0;
            -moz-border-radius: 5px 0 0 0;
        }

        /* Garis turun ke anak */
        .org-tree ul ul::before {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            border-left: 2px solid #ccc;
            width: 0;
            height: 20px;
        }

        .org-node {
            display: inline-block;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
            z-index: 10;
            min-width: 140px;
        }

        .org-node:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #1B5E20;
        }

        .org-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 10px auto;
            background-color: #f3f4f6;
            border: 2px solid #1B5E20;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .org-tree ul {
                flex-direction: column;
                align-items: center;
                padding-top: 0;
            }

            .org-tree li {
                padding: 10px 0;
                width: 100%;
            }

            .org-tree li::before,
            .org-tree li::after,
            .org-tree ul ul::before {
                display: none;
            }

            /* Garis vertikal sederhana untuk mobile */
            .org-tree li:not(:last-child) {
                border-bottom: 2px solid #ccc;
                padding-bottom: 20px;
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body class="font-sans bg-gray-50 text-himatep-dark overflow-x-hidden" x-data="{ mobileMenuOpen: false }">

    <!-- Navbar -->
    <nav class="fixed w-full z-[100] pt-4" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="flex justify-between items-center h-20 bg-white backdrop-blur-xl rounded-full px-6 shadow-md border border-gray-400 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <img src="images/logo-himatep.png" alt="Logo" class="h-10 w-10 rounded-full bg-gray-100"
                        onerror="this.src='https://via.placeholder.com/50x50.png?text=Logo'">
                    <span
                        class="font-bold text-xs md:text-sm leading-tight text-himatep-dark">HIMATEP<br>FIP<br>UNM</span>
                </div>
                <div class="hidden md:flex space-x-4 lg:space-x-8 text-sm lg:text-base">
                    <a href="index.php#hero"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Beranda</a>
                    <a href="#"
                        class="nav-link whitespace-nowrap text-himatep-green font-medium hover:text-himatep-green transition">Profile</a>
                    <a href="index.php#proker"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Program Kerja</a>
                    <a href="index.php#kalender"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Agenda</a>
                    <a href="index.php#berita"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Berita</a>
                    <a href="index.php#aspirasi"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Suara
                        Mahasiswa</a>
                </div>
                <div class="hidden md:flex items-center gap-4">
                    <a href="admin/login.php" class="text-gray-400 hover:text-himatep-green transition-all p-2 rounded-full hover:bg-green-50" title="Admin Panel">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </a>
                    <div class="relative" x-data="{ dropdownOpen: false }" @mouseenter="dropdownOpen = true"
                        @mouseleave="dropdownOpen = false">
                    <a href="#kontak"
                        class="bg-green-400 hover:bg-green-500 text-himatep-dark px-6 py-2 rounded-full font-medium transition shadow-md flex items-center gap-2 focus:outline-none">
                        Narahubung <svg class="w-4 h-4 transition-transform duration-200"
                            :class="{'rotate-180': dropdownOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </a>
                    <!-- Dropdown Menu -->
                    <div x-show="dropdownOpen" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-2"
                        class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 border border-gray-400 z-50"
                        style="display: none;">
                        <a href="index.php#kontak"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-himatep-green transition flex items-center gap-2"><svg
                                class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg> WhatsApp</a>
                        <a href="index.php#kontak"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-himatep-green transition flex items-center gap-2"><svg
                                class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg> Email</a>
                        <a href="index.php#kontak"
                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-himatep-green transition flex items-center gap-2"><svg
                                class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                                </path>
                            </svg> Media Sosial</a>
                    </div>
                    </div>
                </div>
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="M6 18L18 6M6 6l12 12" style="display:none;" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" class="md:hidden bg-white shadow-lg absolute w-full mt-2 rounded-b-2xl"
            x-transition style="display:none;">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a @click="mobileMenuOpen = false" href="index.php#hero"
                    class="nav-link block px-3 py-2 text-gray-600 font-medium">Beranda</a>
                <a @click="mobileMenuOpen = false" href="#"
                    class="nav-link block px-3 py-2 text-himatep-green font-medium">Profile</a>
                <a @click="mobileMenuOpen = false" href="index.php#proker"
                    class="nav-link block px-3 py-2 text-gray-600 font-medium">Program Kerja</a>
                <a @click="mobileMenuOpen = false" href="index.php#kalender"
                    class="nav-link block px-3 py-2 text-gray-600 font-medium">Agenda</a>
                <a @click="mobileMenuOpen = false" href="index.php#berita"
                    class="nav-link block px-3 py-2 text-gray-600 font-medium">Berita</a>
                <a @click="mobileMenuOpen = false" href="index.php#aspirasi"
                    class="nav-link block px-3 py-2 text-gray-600 font-medium">Suara Mahasiswa</a>
                <a @click="mobileMenuOpen = false" href="index.php#kontak"
                    class="nav-link block px-3 py-2 text-gray-600 font-bold hover:text-himatep-green">Narahubung</a>
                <hr class="border-gray-100 my-2">
                <a href="admin/login.php" class="block px-3 py-2 text-gray-400 text-sm flex items-center gap-2 hover:text-himatep-green transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg> Admin Panel
                </a>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <section
        class="pt-40 pb-24 relative overflow-hidden min-h-[800px] flex items-center justify-center bg-himatep-dark">
        <!-- Background Image Placeholder (Extra Large) -->
        <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1920&q=80"
            alt="Background Profil Organisasi"
            class="absolute inset-0 w-full h-full object-cover z-0 opacity-40 mix-blend-luminosity">

        <!-- Soft Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-himatep-light/40 to-transparent z-0"></div>

        <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 drop-shadow-lg">Profil Organisasi</h1>
            <p class="text-xl text-gray-100 max-w-2xl mx-auto font-medium drop-shadow-md">Mengenal lebih dekat struktur
                kepengurusan dan divisi-divisi di HIMATEP FIP UNM.</p>
        </div>
    </section>

    <!-- Struktur Organisasi (Org Chart) -->
    <section class="py-20 bg-white gsap-fade-up">
        <div class="max-w-7xl mx-auto px-4 overflow-x-auto pb-12">
            <h2 class="text-3xl font-bold text-center mb-16 text-himatep-green">Struktur Pengurus Inti</h2>

            <div class="org-tree w-full flex justify-center min-w-max md:min-w-0">
                <?php if(!empty($bph_list)): ?>
                <ul>
                    <li>
                        <div class="org-node">
                            <img src="images/pengurus/<?= htmlspecialchars($bph_list[0]['foto'] ?? 'default.png') ?>" onerror="this.src='images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[0]['jabatan']) ?>" class="org-avatar">
                            <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[0]['nama']) ?></h4>
                            <p class="text-xs text-himatep-green font-semibold mt-1"><?= htmlspecialchars($bph_list[0]['jabatan']) ?></p>
                        </div>
                        
                        <?php if(isset($bph_list[1])): ?>
                        <ul>
                            <li>
                                <div class="org-node">
                                    <img src="images/pengurus/<?= htmlspecialchars($bph_list[1]['foto'] ?? 'default.png') ?>" onerror="this.src='images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[1]['jabatan']) ?>" class="org-avatar">
                                    <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[1]['nama']) ?></h4>
                                    <p class="text-xs text-himatep-green font-semibold mt-1"><?= htmlspecialchars($bph_list[1]['jabatan']) ?></p>
                                </div>
                                <?php if(count($bph_list) > 2): ?>
                                <ul>
                                    <?php for($i=2; $i<count($bph_list); $i++): ?>
                                    <li>
                                        <div class="org-node">
                                            <img src="images/pengurus/<?= htmlspecialchars($bph_list[$i]['foto'] ?? 'default.png') ?>" onerror="this.src='images/logo-himatep.png'" alt="<?= htmlspecialchars($bph_list[$i]['jabatan']) ?>" class="org-avatar">
                                            <h4 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($bph_list[$i]['nama']) ?></h4>
                                            <p class="text-xs text-himatep-green font-semibold mt-1"><?= htmlspecialchars($bph_list[$i]['jabatan']) ?></p>
                                        </div>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                                <?php endif; ?>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </li>
                </ul>
                <?php else: ?>
                    <div class="bg-gray-100 px-6 py-4 rounded-xl items-center border border-gray-300">Belum ada data pengurus BPH.</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Divisi dan Anggota -->
    <section class="py-20 bg-gray-50 gsap-fade-up">
        <div class="max-w-7xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-16 text-himatep-green">Divisi & Anggota</h2>

            <?php if(!empty($divisi_list)): ?>
                <?php foreach($divisi_list as $nama_divisi => $anggota_divisi): ?>
                <!-- Divisi Loop -->
                <div class="mb-16">
                    <div class="flex items-center gap-4 mb-8 border-b-2 border-green-200 pb-4">
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-himatep-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Divisi <?= htmlspecialchars($nama_divisi) ?></h3>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <?php foreach($anggota_divisi as $anggota): 
                            $is_koordinator = stripos($anggota['jabatan'], 'Koordinator') !== false;
                        ?>
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-400 text-center hover:shadow-md transition-shadow">
                            <img src="images/pengurus/<?= htmlspecialchars($anggota['foto'] ?? 'default.png') ?>" onerror="this.src='images/logo-himatep.png'" alt="<?= htmlspecialchars($anggota['jabatan']) ?>" class="w-20 h-20 rounded-full mx-auto mb-4 border-2 border-himatep-green object-cover">
                            <h4 class="font-bold text-gray-800"><?= htmlspecialchars($anggota['nama']) ?></h4>
                            <span class="text-xs font-semibold <?= $is_koordinator ? 'text-white bg-himatep-green' : 'text-gray-600 bg-gray-200' ?> px-3 py-1 rounded-full mt-2 inline-block">
                                <?= htmlspecialchars($anggota['jabatan']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">Belum ada data divisi lainnya yang terdaftar.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Upcoming Agenda -->
    <!-- Agenda Section -->
    <section class="py-20 bg-white gsap-fade-up border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center md:items-end mb-12 text-center md:text-left">
                <div>
                    <h2 class="text-3xl font-bold text-himatep-green mb-2">Agenda Mendatang</h2>
                    <p class="text-gray-600">Jadwal kegiatan terdekat yang tidak boleh Anda lewatkan</p>
                </div>
                <a href="index.php#kalender"
                    class="mt-4 md:mt-0 inline-flex items-center text-himatep-green font-semibold hover:underline">
                    Lihat Kalender Penuh <span class="ml-2">&rarr;</span>
                </a>
            </div>

            <!-- Dynamic Agenda Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8"
                x-data="{ agendas: dataProgram.slice(0, 3) }">
                <template x-for="item in agendas" :key="item.id">
                    <a :href="'detail-program.php?slug=' + item.slug"
                        class="card-hover flex flex-col bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-400 hover:shadow-xl transition-all duration-300 group cursor-pointer">
                        <div class="relative h-48 w-full overflow-hidden">
                            <img :src="item.gambar" :alt="item.judul"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div
                                :class="'absolute top-4 right-4 text-white rounded-2xl p-2 flex flex-col justify-center items-center shadow-lg min-w-[70px] bg-' + (item.divisiColor || 'green') + '-600'">
                                <span class="text-xs font-bold uppercase tracking-wider opacity-90"
                                    x-text="item.agenda.bulan"></span>
                                <span class="text-2xl font-black leading-none" x-text="item.agenda.tanggal"></span>
                            </div>
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 transition-colors"
                                :class="'group-hover:text-' + (item.divisiColor || 'green') + '-600'" x-text="item.judul"></h3>
                            <div class="space-y-3 mt-auto">
                                <div class="flex items-center text-sm font-medium text-gray-500">
                                    <svg class="w-5 h-5 mr-3" :class="'text-' + (item.divisiColor || 'green') + '-500'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span x-text="item.agenda.waktu"></span>
                                </div>
                                <div class="flex items-center text-sm font-medium text-gray-500">
                                    <svg class="w-5 h-5 mr-3" :class="'text-' + (item.divisiColor || 'green') + '-500'"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span x-text="item.agenda.lokasi"></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-himatep-dark text-white py-16 relative overflow-hidden">
        <!-- Decoration -->
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-green-900 rounded-full blur-3xl opacity-20 transform translate-x-1/2 -translate-y-1/2">
        </div>

        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-4 gap-12 relative z-10">
            <div class="md:col-span-2">
                <div class="flex items-center gap-3 mb-6">
                    <img src="images/logo-himatep.png" alt="Logo" class="h-12 w-12 bg-white rounded-full p-1"
                        onerror="this.src='https://via.placeholder.com/50x50.png?text=Logo'">
                    <span class="text-2xl font-bold">HIMATEP FIP UNM</span>
                </div>
                <p class="text-gray-400 mb-6 max-w-md leading-relaxed">Wadah kreasi, inovasi, dan pengabdian mahasiswa
                    Teknologi Pendidikan menuju generasi unggul dan berkarakter.</p>
                <!-- Social Media -->
                <div class="flex space-x-4">
                    <a href="#"
                        class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-himatep-green transition"><svg
                            class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                        </svg></a>
                    <a href="#"
                        class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-himatep-green transition"><svg
                            class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z" />
                        </svg></a>
                </div>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-6 border-b border-gray-800 pb-2 inline-block">Tautan Cepat</h3>
                <ul class="space-y-3 text-gray-400">
                    <li><a href="index.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Beranda</a></li>
                    <li><a href="#" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Profil</a></li>
                    <li><a href="proker.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Program Kerja</a></li>
                    <li><a href="berita.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Berita</a></li>
                    <li><a href="admin/login.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Admin Login</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-6 border-b border-gray-800 pb-2 inline-block">Sekretariat</h3>
                <address class="text-gray-400 not-italic leading-relaxed">
                    Gedung PKM FIP UNM<br>
                    Kampus Tidung, Gn. Sari<br>
                    Makassar, Sulawesi Selatan<br>
                    Kode Pos 90222
                </address>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 mt-12 pt-8 border-t border-gray-800 text-center text-gray-500 text-sm">
            &copy; 2026 HIMATEP FIP UNM. All rights reserved. Designed with ❤️
        </div>
    </footer>


    <script>
        const dataProgram = <?php echo $data_program_json; ?>;
    </script>
    <script src="js/animations.js"></script>
    <script src="js/main.js"></script>
</body>

</html>

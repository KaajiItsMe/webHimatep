<?php
require_once '../private/php/config.php';

// Ambil Semua Berita
try {
    $stmt = $pdo->query("SELECT * FROM berita ORDER BY tanggal_posting DESC");
    $all_berita = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_berita = [];
}

// Persiapkan data untuk Alpine.js
$berita_json = json_encode(array_map(function($b) {
    return [
        'id' => $b['id'],
        'slug' => $b['slug'],
        'judul' => $b['judul'],
        'kategori' => $b['kategori'],
        'kategoriColor' => $b['kategori_color'],
        'tanggal' => $b['tanggal_posting'],
        'penulis' => $b['penulis'],
        'gambar' => $b['gambar'],
        'ringkasan' => $b['ringkasan']
    ];
}, $all_berita));
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portal Berita Resmi HIMATEP FIP UNM. Temukan kabar terbaru mengenai kegiatan mahasiswa, prestasi, dan pengumuman penting.">
    
    <!-- Open Graph -->
    <meta property="og:title" content="Portal Berita - HIMATEP FIP UNM">
    <meta property="og:description" content="Kabar terbaru mengenai kegiatan, prestasi, dan pengumuman HIMATEP FIP UNM.">

    <title>Portal Berita - HIMATEP FIP UNM</title>
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
</head>

<body class="font-sans bg-gray-50 text-himatep-dark overflow-x-hidden"
    x-data="{ mobileMenuOpen: false, daftarBerita: dataBerita }">

    <!-- Navbar -->
    <nav class="fixed w-full z-[100] pt-4" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="flex justify-between items-center h-20 bg-white backdrop-blur-xl rounded-full px-6 shadow-md border border-gray-400 transition-all duration-300">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-3 cursor-pointer">
                        <img src="images/logo-himatep.png" alt="Logo" class="h-10 w-10 rounded-full bg-gray-100"
                            onerror="this.src='https://via.placeholder.com/50x50.png?text=Logo'">
                        <span
                            class="font-bold text-xs md:text-sm leading-tight text-himatep-dark">HIMATEP<br>FIP<br>UNM</span>
                    </a>
                </div>
                <div class="hidden md:flex space-x-4 lg:space-x-8 text-sm lg:text-base">
                    <a href="index.php#hero"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Beranda</a>
                    <a href="index.php#profile"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Profile</a>
                    <a href="index.php#proker"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Program Kerja</a>
                    <a href="index.php#kalender"
                        class="nav-link whitespace-nowrap text-gray-600 font-medium hover:text-himatep-green transition">Agenda</a>
                    <a href="#" class="nav-link whitespace-nowrap text-himatep-green font-medium transition">Berita</a>
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
                    <a href="index.php#kontak"
                        class="bg-green-400 hover:bg-green-500 text-himatep-dark px-6 py-2 rounded-full font-medium transition shadow-md flex items-center gap-2 focus:outline-none">
                        Narahubung
                    </a>
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
                    class="block px-3 py-2 text-gray-600 font-medium">Beranda</a>
                <a @click="mobileMenuOpen = false" href="index.php#profile"
                    class="block px-3 py-2 text-gray-600 font-medium">Profile</a>
                <a @click="mobileMenuOpen = false" href="index.php#proker"
                    class="block px-3 py-2 text-gray-600 font-medium">Program Kerja</a>
                <a @click="mobileMenuOpen = false" href="index.php#kalender"
                    class="block px-3 py-2 text-gray-600 font-medium">Agenda</a>
                <a @click="mobileMenuOpen = false" href="#"
                    class="block px-3 py-2 text-himatep-green font-medium">Berita</a>
                <a @click="mobileMenuOpen = false" href="index.php#aspirasi"
                    class="block px-3 py-2 text-gray-600 font-medium">Suara Mahasiswa</a>
                <a @click="mobileMenuOpen = false" href="index.php#kontak"
                    class="block px-3 py-2 text-gray-600 font-bold hover:text-himatep-green">Narahubung</a>
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
        <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1920&q=80"
            alt="Background Profil Organisasi"
            class="absolute inset-0 w-full h-full object-cover z-0 opacity-40 mix-blend-luminosity">

        <!-- Soft Overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-himatep-light/40 to-transparent z-0"></div>
        <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
            <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
                <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 drop-shadow-lg">Portal Berita</h1>
                <p class="text-xl text-gray-100 max-w-2xl mx-auto font-medium drop-shadow-md">Ikuti berita terbaru,
                    kegiatan, dan prestasi
                    dari HIMATEP FIP UNM.</p>
            </div>
        </div>
    </section>

    <!-- Daftar Berita -->
    <section class="py-20 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4">

            <!-- Headline Berita Terbaru (Ambil berita pertama) -->
            <template x-if="daftarBerita.length > 0">
                <div class="mb-16">
                    <div class="bg-white rounded-3xl overflow-hidden shadow-lg border border-gray-400 flex flex-col md:flex-row hover:shadow-xl transition-shadow group cursor-pointer"
                        @click="window.location.href='detail-berita.php?slug=' + daftarBerita[0].slug">
                        <div class="md:w-1/2 relative overflow-hidden">
                            <img :src="daftarBerita[0].gambar" :alt="daftarBerita[0].judul"
                                class="w-full h-64 md:h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <div class="absolute top-4 left-4">
                                <span class="text-xs font-bold text-white px-3 py-1 rounded-full shadow-md"
                                    :class="'bg-' + daftarBerita[0].kategoriColor + '-600'"
                                    x-text="daftarBerita[0].kategori"></span>
                            </div>
                        </div>
                        <div class="md:w-1/2 p-8 md:p-12 flex flex-col justify-center relative">
                            <div
                                class="absolute top-8 right-8 text-sm font-semibold text-himatep-green bg-green-50 px-3 py-1 rounded-full border border-green-200">
                                BERITA UTAMA
                            </div>
                            <div class="flex items-center text-sm text-gray-500 mb-4 mt-8 md:mt-0">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <span x-text="daftarBerita[0].tanggal"></span>
                                <span class="mx-2">•</span>
                                <span x-text="daftarBerita[0].penulis"></span>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-800 mb-4 group-hover:text-himatep-green transition-colors"
                                x-text="daftarBerita[0].judul"></h2>
                            <p class="text-gray-600 mb-6 text-lg" x-text="daftarBerita[0].ringkasan"></p>
                            <a :href="'detail-berita.php?slug=' + daftarBerita[0].slug"
                                class="inline-flex items-center font-bold text-himatep-green hover:gap-2 transition-all">
                                Baca Artikel Lengkap <span class="ml-1">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Grid Berita Lainnya -->
            <h3 class="text-2xl font-bold text-gray-800 mb-8 border-b-2 border-gray-200 pb-4">Berita Lainnya</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Loop dari berita index ke-1 sampai habis -->
                <template x-for="(berita, index) in daftarBerita" :key="berita.id">
                    <div x-show="index > 0"
                        class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-400 flex flex-col cursor-pointer card-hover"
                        @click="window.location.href='detail-berita.php?slug=' + berita.slug">
                        <div class="relative h-48 overflow-hidden">
                            <img :src="berita.gambar" :alt="berita.judul" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="mb-3">
                                <span class="text-xs font-bold px-3 py-1 rounded-full"
                                    :class="'text-' + berita.kategoriColor + '-600 bg-' + berita.kategoriColor + '-100'"
                                    x-text="berita.kategori"></span>
                            </div>
                            <h3 class="text-xl font-bold mb-3 text-gray-800 line-clamp-2 hover:text-himatep-green transition-colors"
                                x-text="berita.judul"></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-3 flex-1" x-text="berita.ringkasan"></p>

                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
                                <div class="text-xs text-gray-500 font-medium" x-text="berita.tanggal"></div>
                                <a :href="'detail-berita.php?slug=' + berita.slug"
                                    class="text-sm text-himatep-green font-bold hover:underline">Baca &rarr;</a>
                            </div>
                        </div>
                    </div>
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
                    <li><a href="profile.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Profil</a></li>
                    <li><a href="proker.php" class="hover:text-himatep-light transition flex items-center"><span
                                class="mr-2">&rsaquo;</span> Program Kerja</a></li>
                    <li><a href="#" class="hover:text-himatep-light transition flex items-center"><span
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
        const dataBerita = <?php echo $berita_json; ?>;
    </script>
    <script src="js/animations.js"></script>
    <script src="js/main.js"></script>
</body>

</html>
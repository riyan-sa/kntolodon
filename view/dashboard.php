<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set timezone to Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// $user = $_SESSION['user'] ?? null;
// if (!$user) {
//   header('Location: index.php?page=home');
//   exit;
// }

// Data dari controller
// $has_active_booking dan $active_booking sudah di-set oleh controller
// $ruangan_list sudah di-set oleh controller

require __DIR__ . '/components/head.php';
?>

<title>Dashboard</title>
<link rel="stylesheet" href="<?= $asset('assets/css/dashboard.css') ?>">
</head>

<body class="text-gray-800 min-h-screen flex flex-col">

    <?php if (!$has_active_booking): ?>
    <!-- NAVBAR -->
    <nav class="bg-white px-6 py-4 flex justify-between items-center shadow-sm sticky top-0 z-50">
        <a href="?page=dashboard" class="flex items-center">
            <!-- Logo -->
            <img src="<?= $asset('/assets/image/logo.png') ?>" alt="BookEZ Logo" class="h-8 w-auto mr-2 inline-block object-contain logo-scale">
        </a>
        
        <div class="hidden md:block">
            <h1 class="text-2xl font-bold text-gray-800">Pesan Ruangan Belajarmu</h1>
        </div>
        
        <a href="?page=profile" class="flex items-center gap-3">
            <span class="text-xl font-bold text-gray-800"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></span>
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 overflow-hidden">
                <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                         alt="Foto Profil" 
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
        </a>
    </nav>

    <div class="md:hidden text-center mt-6 mb-2">
        <h1 class="text-xl font-bold text-gray-800">Pesan Ruangan Belajarmu</h1>
    </div>
    <?php endif; ?>

    <?php if ($has_active_booking): ?>
    <!-- NAVBAR UNTUK USER DENGAN BOOKING AKTIF -->
    <nav class="bg-white px-6 py-4 flex justify-between items-center shadow-sm sticky top-0 z-50">
        <a href="?page=dashboard" class="flex items-center">
            <!-- Logo -->
            <img src="<?= $asset('/assets/image/logo.png') ?>" alt="BookEZ Logo" class="h-8 w-auto mr-2 inline-block object-contain logo-scale">
        </a>
        
        <div class="hidden md:block">
            <h1 class="text-2xl font-bold text-gray-800">Booking Aktif</h1>
        </div>
        
        <a href="?page=profile" class="flex items-center gap-3">
            <span class="text-xl font-bold text-gray-800"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></span>
            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400 overflow-hidden">
                <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                    <img src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                         alt="Foto Profil" 
                         class="w-full h-full object-cover">
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                <?php endif; ?>
            </div>
        </a>
    </nav>

    <div class="md:hidden text-center mt-6 mb-2">
        <h1 class="text-xl font-bold text-gray-800">Booking Aktif</h1>
    </div>
    <?php endif; ?>

    <main class="grow p-6 max-w-[1400px] mx-auto w-full">

        <?php if ($has_active_booking): ?>
            <!-- TAMPILAN KETIKA USER SUDAH BOOKING -->
            <div class="flex items-center justify-center min-h-[calc(100vh-200px)]">
                <div class="w-full max-w-2xl">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden flex h-48 transition hover:shadow-lg">
                        <div class="w-5/12 relative">
                            <?php 
                            $fotoBookingAktif = !empty($active_booking['foto_ruangan']) ? $asset($active_booking['foto_ruangan']) : $asset('/assets/image/gambar ruangan.jpg');
                            ?>
                            <img src="<?= $fotoBookingAktif ?>" alt="<?= htmlspecialchars($active_booking['nama_ruangan']) ?>" class="absolute inset-0 w-full h-full object-cover">
                            <!-- Badge Role di pojok kiri atas gambar -->
                            <?php 
                            $is_ketua = isset($active_booking['is_ketua']) && $active_booking['is_ketua'] == 1;
                            ?>
                            <?php if ($is_ketua): ?>
                            <div class="absolute top-2 left-2 bg-blue-600 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-md flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.504 1.132a1 1 0 01.992 0l1.75 1a1 1 0 11-.992 1.736L10 3.152l-1.254.716a1 1 0 11-.992-1.736l1.75-1z" clip-rule="evenodd" />
                                </svg>
                                KETUA
                            </div>
                            <?php else: ?>
                            <div class="absolute top-2 left-2 bg-gray-600 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-md flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                ANGGOTA
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="w-7/12 p-4 flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($active_booking['nama_ruangan']) ?></h3>
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p>Tanggal: <?= date('d M Y', strtotime($active_booking['tanggal_schedule'])) ?></p>
                                    <p>Jam: <?= date('H:i', strtotime($active_booking['waktu_mulai'])) ?> - <?= date('H:i', strtotime($active_booking['waktu_selesai'])) ?> WIB</p>
                                    <p>Kapasitas: <?= $active_booking['minimal_kapasitas_ruangan'] ?> - <?= $active_booking['maksimal_kapasitas_ruangan'] ?> Orang</p>
                                </div>
                            </div>
                            <div class="mt-2">
                                <?php if ($is_ketua): ?>
                                    <?php
                                    // Validasi waktu untuk enable/disable tombol Selesai
                                    $now = new DateTime();
                                    $tanggal_booking = new DateTime($active_booking['tanggal_schedule']);
                                    $waktu_mulai = new DateTime($active_booking['tanggal_schedule'] . ' ' . $active_booking['waktu_mulai']);
                                    $waktu_selesai = new DateTime($active_booking['tanggal_schedule'] . ' ' . $active_booking['waktu_selesai']);
                                    
                                    // Tambah toleransi 10 menit sebelum waktu selesai untuk safety
                                    $waktu_selesai_toleransi = clone $waktu_selesai;
                                    $waktu_selesai_toleransi->modify('+10 minutes');
                                    
                                    // Tombol aktif jika: hari H DAN (sudah check-in DAN waktu sudah dimulai) DAN belum lewat waktu selesai + toleransi
                                    $is_same_day = $now->format('Y-m-d') === $tanggal_booking->format('Y-m-d');
                                    
                                    // Cek apakah user sudah check-in
                                    $is_checked_in = isset($active_booking['is_checked_in']) && $active_booking['is_checked_in'] == 1;
                                    
                                    // CRITICAL: Harus KEDUANYA - check-in DAN waktu sudah dimulai
                                    // Admin hanya bisa check-in pada Hari H, tapi tetap harus tunggu waktu mulai
                                    $is_time_started = $now >= $waktu_mulai;
                                    $is_ready = $is_checked_in && $is_time_started;
                                    $is_before_end = $now <= $waktu_selesai_toleransi;
                                    
                                    $can_finish = $is_same_day && $is_ready && $is_before_end;
                                    
                                    // Tentukan pesan tooltip
                                    if (!$is_same_day) {
                                        if ($now->format('Y-m-d') < $tanggal_booking->format('Y-m-d')) {
                                            $tooltip = 'Booking hanya bisa diselesaikan pada hari H (' . date('d M Y', strtotime($active_booking['tanggal_schedule'])) . ')';
                                        } else {
                                            $tooltip = 'Waktu booking sudah lewat';
                                        }
                                    } elseif (!$is_ready) {
                                        if (!$is_checked_in && !$is_time_started) {
                                            $diff_minutes = ceil(($waktu_mulai->getTimestamp() - $now->getTimestamp()) / 60);
                                            $tooltip = 'Anda harus check-in DAN menunggu waktu mulai (' . date('H:i', strtotime($active_booking['waktu_mulai'])) . ' WIB). Masih ' . $diff_minutes . ' menit lagi';
                                        } elseif (!$is_checked_in) {
                                            $tooltip = 'Anda harus check-in terlebih dahulu oleh admin';
                                        } else {
                                            // Waktu sudah dimulai tapi belum check-in
                                            $tooltip = 'Anda harus check-in terlebih dahulu oleh admin (waktu sudah dimulai)';
                                        }
                                    } elseif (!$is_before_end) {
                                        $tooltip = 'Waktu booking sudah berakhir';
                                    } else {
                                        $tooltip = 'Selesaikan booking dan berikan feedback';
                                    }
                                    ?>
                                    <?php if ($can_finish): ?>
                                    <form method="POST" action="?page=dashboard&action=selesai_booking" id="form-selesai">
                                        <input type="hidden" name="id_booking" value="<?= $active_booking['id_booking'] ?>">
                                        <button type="button" id="btn-finish" class="w-full bg-white text-sky-600 font-semibold text-sm py-1.5 rounded hover:bg-sky-100 transition border border-gray-100" title="<?= htmlspecialchars($tooltip) ?>">
                                            Selesai
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button disabled class="w-full bg-gray-100 text-gray-400 font-semibold text-sm py-1.5 rounded border border-gray-200 cursor-not-allowed" title="<?= htmlspecialchars($tooltip) ?>">
                                        Selesai (Belum Waktunya)
                                    </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                <button disabled class="w-full bg-gray-100 text-gray-400 font-semibold text-sm py-1.5 rounded border border-gray-200 cursor-not-allowed" title="❌ Hanya ketua yang bisa selesaikan booking">
                                    Selesai (Khusus Ketua)
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Konfirmasi Selesai (Hidden by default) -->
                    <div id="confirmation-overlay" class="hidden mt-0 -translate-y-48">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden flex h-48 transition hover:shadow-lg">
                            <div class="w-5/12 relative">
                                <img src="<?= $fotoBookingAktif ?>" alt="<?= htmlspecialchars($active_booking['nama_ruangan']) ?>" class="absolute inset-0 w-full h-full object-cover">
                            </div>
                            <div class="w-7/12 p-4 flex flex-col justify-center items-center">
                                <p class="text-lg font-semibold text-gray-800 mb-4">Selesaikan Sesi?</p>
                                <div class="flex flex-col gap-2 w-full px-4">
                                    <button type="button" id="btn-confirm-yes" class="w-full bg-sky-600 text-white font-semibold text-sm py-2 rounded hover:bg-sky-700 transition">
                                        Ya, Selesai
                                    </button>
                                    <button type="button" id="btn-confirm-no" class="w-full bg-white text-gray-600 font-semibold text-sm py-2 rounded hover:bg-gray-100 transition border border-gray-200">
                                        Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- TAMPILAN DASHBOARD NORMAL (TIDAK ADA BOOKING AKTIF) -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <!-- FOREACH RUANGAN -->
                <?php
                // Data ruangan dari database
                foreach ($ruangan_list as $ruangan):
                    $tersedia = $ruangan['status_ruangan'] === 'Tersedia';
            ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden flex h-48 transition hover:shadow-lg">
                    <div class="w-5/12 relative">
                        <?php 
                        $fotoRuangan = !empty($ruangan['foto_ruangan']) ? $asset($ruangan['foto_ruangan']) : $asset('/assets/image/gambar ruangan.jpg');
                        ?>
                        <img src="<?= $fotoRuangan ?>" alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>" class="absolute inset-0 w-full h-full object-cover">
                    </div>
                    <div class="w-7/12 p-4 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 mb-2"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></h3>
                            <div class="text-xs text-gray-500 space-y-1">
                                <p>Minimal : <?= $ruangan['minimal_kapasitas_ruangan'] ?></p>
                                <p>Maksimal : <?= $ruangan['maksimal_kapasitas_ruangan'] ?></p>
                                <p>Tersedia: <span class="<?= $tersedia ? 'text-green-500' : 'text-red-500' ?> font-semibold"><?= $tersedia ? 'Ya' : 'Tidak' ?></span></p>
                            </div>
                        </div>
                        <div class="mt-2">
                            <button <?= $tersedia ? '' : 'disabled' ?> data-booking-action="booking" data-room-id="<?= $ruangan['id_ruangan'] ?>" class="w-full bg-white text-sky-600 disabled:bg-gray-200 disabled:text-gray-800 font-semibold text-sm py-1.5 rounded hover:bg-sky-100 transition mb-2 border border-gray-100" <?= !$tersedia ? 'disabled' : '' ?>>
                                Booking
                            </button>
                            <div class="flex justify-between items-center border-t border-gray-100 pt-2 cursor-pointer group" data-modal-action="open" data-room-id="<?= $ruangan['id_ruangan'] ?>">
                                <span class="text-xs font-semibold text-gray-600">Deskripsi</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400 group-hover:text-gray-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal untuk ruangan ini -->
                <div id="modal-<?= $ruangan['id_ruangan'] ?>" class="hidden fixed inset-0 bg-white/60 backdrop-blur-sm z-50 items-center justify-center p-4">
                    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl transform transition-all" onclick="event.stopPropagation()">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h2 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></h2>
                                <button data-modal-action="close" data-room-id="<?= $ruangan['id_ruangan'] ?>" class="text-gray-400 hover:text-gray-600 transition">
                                    <i class="fa-solid fa-xmark text-2xl"></i>
                                </button>
                            </div>
                            <div class="text-gray-600 leading-relaxed">
                                <p><?= htmlspecialchars($ruangan['deskripsi'] ?? 'Tidak ada deskripsi') ?></p>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <h3 class="font-semibold text-gray-800 mb-2">Tata Tertib:</h3>
                                    <p><?= htmlspecialchars($ruangan['tata_tertib'] ?? 'Tidak ada tata tertib') ?></p>
                                </div>
                            </div>
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <button data-modal-action="close" data-room-id="<?= $ruangan['id_ruangan'] ?>" class="w-full bg-sky-600 text-white font-semibold py-2.5 rounded-lg hover:bg-sky-700 transition">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <!-- END FOREACH -->
            </div>
        <?php endif; ?>
    </main>

    <!-- Data akan di-inject melalui data attributes -->
    <div id="dashboard-data" data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('assets/js/dashboard.js'); ?>" defer></script>
    <script src="<?= $asset('assets/js/auth.js'); ?>" defer></script>

</body>
</html>


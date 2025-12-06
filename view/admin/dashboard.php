<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Only Admin and Super Admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

require __DIR__ . '/../components/head.php';
?>

<title>Admin Dashboard - BookEZ</title>
<link rel="stylesheet" href="<?= $asset('assets/css/admin-dashboard.css') ?>">
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

    <?php require __DIR__ . '/../components/navbar_admin.php'; ?>

    <!-- Main Content -->
    <main class="max-w-[1400px] w-full mx-auto p-4 lg:p-8 mt-4">

        <!-- White Container Box -->
        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-6 lg:p-10">

            <!-- Grid Layout -->
            <!-- Grid 6 kolom agar fleksibel: 
                 - Baris atas pakai col-span-3 (setengah lebar)
                 - Baris bawah pakai col-span-2 (sepertiga lebar) 
            -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-6">

                <!-- ROW 1: Booking Eksternal -->
                 <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=booking_external" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer col-span-1 lg:col-span-4 h-84 lg:h-95">
                    <div class="absolute inset-0 bg-gray-200">
                        <img src="<?= $asset('/assets/image/thumb_handshake.png') ?>"
                            alt="Booking Eksternal"
                            class="card-zoom-image w-full h-full object-cover">
                    </div>
                    <!-- Label Button -->
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-sky-400/90 backdrop-blur-sm px-8 py-3 rounded-lg shadow-lg">
                            <span class="text-slate-900 font-bold text-lg lg:text-xl whitespace-nowrap">Booking Eksternal</span>
                        </div>
                    </div>
                </a>
                <?php endif; ?>

                <!-- ROW 1: Kelola Ruangan -->
                <a href="?page=admin&action=kelola_ruangan" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer col-span-1 lg:col-span-3 h-84 lg:h-95">
                    <div class="absolute inset-0 bg-gray-200">
                        <img src="<?= $asset('/assets/image/room.png') ?>"
                            alt="Kelola Ruangan"
                            class="card-zoom-image w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-sky-400/90 backdrop-blur-sm px-8 py-3 rounded-lg shadow-lg">
                            <span class="text-slate-900 font-bold text-lg lg:text-xl whitespace-nowrap">Kelola Ruangan</span>
                        </div>
                    </div>
                </a>

                <!-- ROW 2: Laporan Peminjaman -->
                <a href="?page=admin&action=laporan" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer <?= ($_SESSION['user']['role'] === 'Super Admin') ? "col-span-2 lg:col-span-3 h-56 lg:h-64" : "col-span-3 lg:col-span-4 h-86 lg:h-94" ?>">
                    <div class="absolute inset-0 bg-gray-200">
                        <img src="<?= $asset('/assets/image/thumb_laporan.png') ?>"
                            alt="Laporan Peminjaman"
                            class="card-zoom-image w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-sky-400/90 backdrop-blur-sm px-6 py-2 rounded-lg shadow-lg">
                            <span class="text-slate-900 font-bold text-base lg:text-lg whitespace-nowrap">Laporan Peminjaman</span>
                        </div>
                    </div>
                </a>

                <!-- ROW 2: Booking-List -->
                <a href="?page=admin&action=booking_list" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer col-span-1 lg:col-span-2 h-56 lg:h-64 <?= ($_SESSION['user']['role'] === 'Super Admin') ? "col-span-1 lg:col-span-2 h-56 lg:h-64" : "col-span-3 lg:col-span-4 h-86 lg:h-94" ?>">
                    <div class="absolute inset-0 bg-gray-200">
                        <img src="<?= $asset('/assets/image/thumb_booking.png') ?>"
                            alt="Booking List"
                            class="card-zoom-image w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-sky-400/90 backdrop-blur-sm px-6 py-2 rounded-lg shadow-lg">
                            <span class="text-slate-900 font-bold text-base lg:text-lg whitespace-nowrap">Booking-List</span>
                        </div>
                    </div>
                </a>

                <!-- ROW 2: Member-List -->
                <a href="?page=admin&action=member_list" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer <?= ($_SESSION['user']['role'] === 'Super Admin') ? "col-span-1 lg:col-span-2 h-56 lg:h-64" : "col-span-2 lg:col-span-3 h-86 lg:h-94" ?>">
                    <div class="absolute inset-0 bg-gray-200">
                        <img src="<?= $asset('/assets/image/thumb_member.png') ?>"
                            alt="Member List"
                            class="card-zoom-image w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-sky-400/90 backdrop-blur-sm px-6 py-2 rounded-lg shadow-lg">
                            <span class="text-slate-900 font-bold text-base lg:text-lg whitespace-nowrap">Member-List</span>
                        </div>
                    </div>
                </a>

                <!-- ROW 3: Pengaturan (Super Admin Only) -->
                <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
                <a href="?page=admin&action=pengaturan" class="relative group overflow-hidden rounded-2xl shadow-sm hover:shadow-md cursor-pointer col-span-1 lg:col-span-7 h-48 lg:h-56">
                    <div class="absolute inset-0 bg-linear-to-r from-sky-500 to-blue-600">
                        <div class="absolute inset-0 opacity-10">
                            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white">
                                <path d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center z-10 pointer-events-none">
                        <div class="bg-white/95 backdrop-blur-sm px-10 py-4 rounded-lg shadow-lg">
                            <div class="flex items-center gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                </svg>
                                <span class="text-slate-900 font-bold text-lg lg:text-xl whitespace-nowrap">Pengaturan Sistem</span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <script>
        window.ASSET_BASE_PATH = '<?= $basePath ?>';
    </script>
    <script src="<?= $asset('assets/js/admin.js') ?>" defer></script>

</body>

</html>
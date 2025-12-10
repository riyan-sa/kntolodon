<?php
/**
 * ============================================================================
 * ADMIN/DASHBOARD.PHP - Admin Dashboard View
 * ============================================================================
 * 
 * Main dashboard untuk Admin dan Super Admin dengan grid layout cards.
 * Each card links to specific admin functionality.
 * 
 * ACCESS CONTROL:
 * - Required roles: Admin or Super Admin
 * - Blocks regular User access (redirects to user dashboard)
 * - Check: !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])
 * 
 * FEATURES:
 * 1. GRID LAYOUT (7 columns on desktop)
 *    - Responsive: 1 col (mobile), 2 cols (md), 7 cols (lg)
 *    - Gap: gap-6
 *    - White container: rounded-[2.5rem] shadow-sm
 * 
 * 2. FEATURE CARDS (7 cards total)
 *    a. Booking Eksternal (Super Admin only, col-span-4, height 84/95)
 *    b. Kelola Ruangan (col-span-3, height 84/95)
 *    c. Laporan Peminjaman (col-span-4, height 56/60)
 *    d. Booking-List (col-span-3, height 56/60)
 *    e. Member-List (col-span-3, height 56/60)
 *    f. Pengaturan (Super Admin only, col-span-4, height 56/60)
 *    g. Dashboard Admin (col-span-full, height auto - reserved/future)
 * 
 * 3. CARD INTERACTION
 *    - Hover: shadow-sm → shadow-md transition
 *    - Zoom effect: .card-zoom-image class (CSS animation)
 *    - Clickable: Full card is <a> link
 *    - Label: Centered badge with title
 * 
 * 4. ROLE-BASED VISIBILITY
 *    - Super Admin: Sees all 7 cards
 *    - Regular Admin: Sees 5 cards (excludes Booking Eksternal & Pengaturan)
 *    - Pattern: <?php if ($_SESSION['user']['role'] === 'Super Admin'): ?>
 * 
 * CARD STRUCTURE (Each card):
 * - Link: <a href="?page=admin&action={action}">
 * - Container: relative group overflow-hidden rounded-2xl
 * - Background: Image dengan card-zoom-image class
 * - Label overlay: Centered badge dengan title
 * - Badge: bg-sky-400/90 backdrop-blur-sm (semi-transparent blue)
 * 
 * CARD IMAGES:
 * - Booking Eksternal: thumb_handshake.png
 * - Kelola Ruangan: thumb_ruangan.png
 * - Laporan Peminjaman: thumb_laporan.png
 * - Booking-List: thumb_booking_list.png
 * - Member-List: thumb_member_list.png
 * - Pengaturan: thumb_pengaturan.png
 * - All stored in: assets/image/
 * 
 * ROUTING:
 * - Booking Eksternal: ?page=admin&action=booking_external
 * - Kelola Ruangan: ?page=admin&action=kelola_ruangan
 * - Laporan Peminjaman: ?page=admin&action=laporan
 * - Booking-List: ?page=admin&action=booking_list
 * - Member-List: ?page=admin&action=member_list
 * - Pengaturan: ?page=admin&action=pengaturan
 * 
 * CSS CLASSES:
 * - .card-zoom-image: Custom zoom animation on hover (defined in admin-dashboard.css)
 * - .logo-scale: Logo animation (defined in admin-dashboard.css)
 * - Tailwind: All layout and styling utilities
 * 
 * LAYOUT BREAKDOWN:
 * - Container: max-w-[1400px] centered
 * - Padding: p-4 mobile, lg:p-8 desktop
 * - White box: Large rounded corners (2.5rem)
 * - Grid gap: 6 (1.5rem)
 * 
 * RESPONSIVE BEHAVIOR:
 * - Mobile (< 768px): Single column, stacked cards
 * - Tablet (md: 768px+): 2 columns
 * - Desktop (lg: 1024px+): 7 columns dengan varying spans
 * - Card heights adjust: h-84 mobile, lg:h-95 desktop (for larger cards)
 * 
 * SUPER ADMIN EXCLUSIVE:
 * - Booking Eksternal card (top-left, largest)
 * - Pengaturan card (bottom-right, medium)
 * - Both hidden for regular Admin
 * - Grid adapts: Removes cards, other cards fill space
 * 
 * CARD SIZES (col-span):
 * Row 1:
 * - Booking Eksternal: 4 cols (Super Admin only)
 * - Kelola Ruangan: 3 cols
 * Row 2:
 * - Laporan Peminjaman: 4 cols
 * - Booking-List: 3 cols
 * Row 3:
 * - Member-List: 3 cols
 * - Pengaturan: 4 cols (Super Admin only)
 * 
 * CSS FILE:
 * - External: assets/css/admin-dashboard.css
 * - Defines: .card-zoom-image, .logo-scale, custom animations
 * 
 * JAVASCRIPT:
 * - assets/js/admin.js: Dashboard card interactions (if any)
 * - No inline scripts - pure CSS hover effects
 * 
 * NAVBAR:
 * - Component: navbar_admin.php
 * - Shows: Logo + navigation links + profile
 * - Responsive: Stacks on mobile
 * 
 * DATA REQUIREMENTS:
 * - None (static dashboard)
 * - No data fetched from controller
 * - Pure navigation hub
 * 
 * ACCESS FLOW:
 * 1. Admin/Super Admin logs in
 * 2. LoginController redirects to ?page=admin&action=index
 * 3. AdminController::index() renders this dashboard
 * 4. User clicks card → Navigate to feature
 * 
 * SECURITY:
 * - Session check: $_SESSION['user'] must exist
 * - Role check: Must be Admin or Super Admin
 * - Redirect: Non-admins sent to user dashboard
 * - Role-based cards: Super Admin features hidden from Admin
 * 
 * INTEGRATION:
 * - Controller: AdminController (index method)
 * - Component: navbar_admin.php
 * - Assets: admin-dashboard.css, thumbnail images
 * - Routes: All admin action routes
 * 
 * @package BookEZ
 * @subpackage Views\Admin
 * @version 1.0
 */
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
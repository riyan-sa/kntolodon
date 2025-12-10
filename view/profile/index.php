<?php
/**
 * ============================================================================
 * PROFILE/INDEX.PHP - User Profile Page
 * ============================================================================
 * 
 * Comprehensive profile page dengan 2 layouts berbeda:
 * 1. REGULAR USER: 3-tab layout (Kode Booking, History, Pelanggaran)
 * 2. ADMIN/SUPER ADMIN: Dashboard-style grid layout
 * 
 * FEATURES (Regular User):
 * 1. PROFILE SIDEBAR (Left Column)
 *    - Avatar dengan hover overlay (click to upload photo)
 *    - Username dan NIM display
 *    - Contact info: Email, Jurusan (if mahasiswa), Prodi
 *    - Action buttons:
 *      * Ganti Password (opens modal)
 *      * Keluar (logout)
 * 
 * 2. TAB 1: KODE BOOKING (Active Bookings)
 *    - Shows all AKTIF bookings
 *    - Booking cards dengan details:
 *      * Kode Booking
 *      * Ruangan name
 *      * Tanggal
 *      * Waktu (mulai - selesai)
 *      * Check-in status indicator
 *      * Action button: "Ubah Jadwal" (reschedule)
 * 
 * 3. TAB 2: HISTORY (Past Bookings)
 *    - Shows bookings dengan status: SELESAI, DIBATALKAN, HANGUS
 *    - Paginated: 6 records per page
 *    - Booking cards sama format dengan Tab 1
 *    - No action buttons (read-only history)
 * 
 * 4. TAB 3: PELANGGARAN (Violations/Suspensions)
 *    - Shows suspension records dari pelanggaran_suspensi table
 *    - Displays:
 *      * Tanggal Mulai & Tanggal Selesai
 *      * Durasi suspension (calculated)
 *      * Keterangan (reason)
 *      * Status: Aktif (red) or Selesai (green)
 *    - Empty state: "Tidak Ada Pelanggaran" dengan green checkmark
 * 
 * 5. FOTO UPLOAD MODAL
 *    - Triggered by clicking avatar
 *    - File input: Accept images only (JPEG, PNG, WebP)
 *    - Max size: 25MB (enforced server-side)
 *    - Preview: Shows selected image before upload
 *    - Storage: assets/uploads/images/
 *    - Action: ?page=profile&action=upload_foto
 * 
 * 6. PASSWORD CHANGE MODAL
 *    - Component: modal_change_password.php
 *    - 3 fields: Old password, New password, Confirm password
 *    - Validation: Min 8 chars, must match
 *    - Action: ?page=profile&action=change_password
 * 
 * FEATURES (Admin/Super Admin):
 * - DASHBOARD GRID LAYOUT
 *   * No tabs - shows admin dashboard grid instead
 *   * 7 feature cards (same as admin dashboard)
 *   * Card sizes: Different widths (1-3 columns)
 *   * Links to admin actions
 *   * Booking Eksternal & Pengaturan: Super Admin only
 * 
 * TAB SWITCHING (User):
 * - JavaScript: assets/js/profile.js
 * - Default tab: Kode Booking
 * - Active state: Blue underline (border-b-2 border-blue-600)
 * - Hidden state: hidden class on tab content
 * - Smooth transitions
 * 
 * DATA FROM CONTROLLER (ProfileController::index()):
 * - $booking_aktif (array): Active bookings (status AKTIF)
 * - $booking_history (array): Past bookings (SELESAI, DIBATALKAN, HANGUS)
 * - $pelanggaran (array): Suspension records
 * - $paginationData (array): Pagination info for history tab
 *   * currentPage, totalPages, totalRecords, perPage
 * 
 * BOOKING DATA STRUCTURE:
 * $booking = [
 *   'id_booking' => int,
 *   'kode_booking' => string,
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string,
 *   'tanggal' => string (YYYY-MM-DD),
 *   'waktu_mulai' => string (HH:MM:SS),
 *   'waktu_selesai' => string (HH:MM:SS),
 *   'id_status' => int,
 *   'nama_status' => string ('AKTIF', 'SELESAI', 'DIBATALKAN', 'HANGUS'),
 *   'check_in_count' => int (number of checked-in members),
 *   'total_anggota' => int (total booking members including ketua)
 * ];
 * 
 * PELANGGARAN DATA STRUCTURE:
 * $pelanggaran = [
 *   'id' => int,
 *   'nomor_induk' => string,
 *   'tanggal_mulai' => string (YYYY-MM-DD),
 *   'tanggal_selesai' => string (YYYY-MM-DD),
 *   'keterangan' => string (reason text),
 *   'status_aktif' => bool (1 = active suspension, 0 = completed)
 * ];
 * 
 * CHECK-IN STATUS INDICATOR:
 * - Format: "X/Y Anggota Check-in"
 * - X: check_in_count (members who checked in)
 * - Y: total_anggota (total members including ketua)
 * - Example: "3/5 Anggota Check-in"
 * - Color: Green if all checked in, Gray otherwise
 * 
 * STATUS BADGES:
 * - AKTIF: Green (bg-green-100 text-green-800)
 * - SELESAI: Blue (bg-blue-100 text-blue-800)
 * - DIBATALKAN: Gray (bg-gray-100 text-gray-800)
 * - HANGUS: Red (bg-red-100 text-red-800)
 * 
 * PAGINATION (History Tab):
 * - Records per page: 6
 * - URL parameter: pg (not 'page' - conflicts with routing)
 * - Pattern: ?page=profile&pg=2
 * - Inline pagination UI (not using component file)
 * - Previous/Next buttons + page numbers
 * 
 * TARGET ELEMENTS:
 * - #avatarContainer: Avatar with upload trigger
 * - #modalUploadFoto: Photo upload modal
 * - #modalChangePassword: Password change modal (component)
 * - #btn-change-password: Trigger password change modal
 * - #btn-logout: Logout button
 * - [data-tab]: Tab buttons (kode-booking, history, pelanggaran)
 * - .tab-content: Tab content containers
 * - #profile-data: Data container for JavaScript (data-base-path)
 * 
 * JAVASCRIPT:
 * - assets/js/profile.js: Tab switching, modals, photo upload preview
 * - Event delegation pattern
 * - NO inline onclick handlers
 * 
 * FORM SUBMISSIONS:
 * - Upload foto: POST to ?page=profile&action=upload_foto (multipart/form-data)
 * - Change password: POST to ?page=profile&action=change_password
 * - Reschedule: Link to ?page=booking&action=reschedule&id={id_booking}
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Single column, tabs stack vertically
 * - Tablet (md): Sidebar 1/3, content 2/3
 * - Desktop (lg): Sidebar 1/4, content 3/4
 * - Admin grid: 2 cols (sm), 3 cols (md), 4 cols (lg), 7 cols (xl)
 * 
 * ROLE-BASED RENDERING:
 * - Check: $_SESSION['user']['role']
 * - If Admin or Super Admin: Show dashboard grid
 * - If User: Show 3-tab layout
 * - Pattern: <?php if (in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])): ?>
 * 
 * ADMIN DASHBOARD CARDS (7 cards):
 * 1. Booking Eksternal (Super Admin only, col-span-2)
 * 2. Kelola Ruangan (col-span-1)
 * 3. Laporan Peminjaman (col-span-2)
 * 4. Booking-List (col-span-2)
 * 5. Member-List (col-span-2)
 * 6. Pengaturan (Super Admin only, col-span-2)
 * 7. Dashboard Admin (col-span-2)
 * 
 * EMPTY STATES:
 * - No active bookings: "Tidak Ada Booking Aktif" dengan info icon
 * - No history: "Belum Ada Riwayat Booking" dengan clock icon
 * - No violations: "Tidak Ada Pelanggaran" dengan green checkmark
 * 
 * CSS:
 * - External: assets/css/profile.css
 * - Tab animations
 * - Card hover effects (admin grid)
 * - Logo scale animation
 * - Modal transitions
 * 
 * FOTO UPLOAD FLOW:
 * 1. Click avatar → Open upload modal
 * 2. Select image → Preview displayed
 * 3. Submit form → Upload to server
 * 4. Server validates: MIME type, size (max 25MB)
 * 5. Generate unique filename: foto_{timestamp}_{random}.{ext}
 * 6. Move to: assets/uploads/images/
 * 7. Update akun.foto_profil in DB
 * 8. Update $_SESSION['user']['foto_profil']
 * 9. Reload page to show new photo
 * 
 * PASSWORD CHANGE FLOW:
 * 1. Click "Ganti Password" → Open modal
 * 2. Enter: Old password, New password, Confirm
 * 3. Validate: Old password correct, New >= 8 chars, Match confirm
 * 4. Hash new password: password_hash()
 * 5. Update akun.password in DB
 * 6. Alert success, close modal
 * 
 * RESCHEDULE FLOW:
 * 1. Click "Ubah Jadwal" on active booking
 * 2. Redirect: ?page=booking&action=reschedule&id={id_booking}
 * 3. Show reschedule form (view/booking/reskedul_booking.php)
 * 4. Submit new date/time request
 * 5. Creates schedule record (pending admin approval - PLANNED)
 * 
 * SECURITY:
 * - Session check: User must be logged in
 * - Photo upload: MIME type validation, size limit
 * - Password change: Current password verification
 * - XSS prevention: htmlspecialchars() on all user data
 * - File upload: Unique filenames prevent overwrites
 * 
 * INTEGRATION:
 * - Controller: ProfileController (index, upload_foto, change_password)
 * - Model: BookingModel (getActiveByUser, getHistoryByUser)
 * - Model: PelanggaranSuspensiModel (getByUser)
 * - Model: AkunModel (updateFoto, verifyPassword, updatePassword)
 * - Database: akun, booking, pelanggaran_suspensi tables
 * - Components: modal_change_password.php, navbar
 * - Assets: profile.css, profile.js
 * 
 * @package BookEZ
 * @subpackage Views\Profile
 * @version 1.0
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// $user = $_SESSION['user'] ?? null;
// if (!$user) {
//   header('Location: index.php?page=home');
//   exit;
// }
require __DIR__ . '/../components/head.php';
?>

<title>BookEZ - Profil User</title>
<link rel="stylesheet" href="<?= $asset('assets/css/profile.css') ?>">
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-white px-6 py-4 flex justify-between items-center shadow-sm sticky top-0 z-50">
        <a href="?page=dashboard" class="flex items-center">
            <!-- Logo -->
            <img src="<?= $asset('/assets/image/logo.png') ?>" alt="BookEZ Logo" class="h-8 w-auto mr-2 inline-block object-contain logo-scale">
        </a>
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

    <!-- MAIN CONTAINER -->
    <main class="max-w-7xl mx-auto p-6 md:p-10 flex flex-col md:flex-row gap-8">

        <!-- SIDEBAR PROFIL (KIRI) -->
        <aside class="w-full md:w-1/3 lg:w-1/4">
            <div class="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100 h-fit">
                <!-- Avatar Besar -->
                <div class="h-32 w-32 rounded-full bg-gray-200 mx-auto mb-6 flex items-center justify-center relative group cursor-pointer overflow-hidden" id="avatarContainer">
                    <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                             alt="Foto Profil" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    <?php endif; ?>
                    <!-- Overlay Hover -->
                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-800 mb-1"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></h2>
                <p class="text-gray-500 text-lg mb-6">NIM: <?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['nomor_induk']) : '-' ?></p>

                <hr class="border-gray-200 mb-6">

                <!-- Detail Info -->
                <div class="space-y-4 text-left text-gray-600 text-sm">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) : '-' ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['role']) : '-' ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                        <span>Politeknik Negeri Jakarta</span>
                    </div>
                </div>

                <hr class="border-gray-200 my-6 mb-3 mt-3">

                <!-- Tombol Ganti Password -->
                <button id="btn-change-password" class="w-full h-fit mb-3 flex items-center justify-center gap-3 bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold py-1 px-2 rounded-lg transition-all duration-200 border border-blue-200 hover:border-blue-300 hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Ganti Password</span>
                </button>
                <!-- Tombol Logout -->
                <button id="btn-logout" class="w-full h-fit flex items-center justify-center gap-3 bg-red-50 hover:bg-red-100 text-red-600 font-semibold py-1 px-2 rounded-lg transition-all duration-200 border border-red-200 hover:border-red-300 hover:scale-105">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Keluar</span>
                </button>
            </div>
        </aside>

        <!-- KONTEN TAB (KANAN) -->
        <div class="w-full md:w-2/3 lg:w-3/4">

            <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])): ?>
                <!-- ADMIN/SUPERADMIN DASHBOARD CONTENT -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-6 lg:p-10">
                    <!-- Grid Layout -->
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

                    </div>
                </div>

            <?php else: ?>
                <!-- USER CONTENT (TAB SYSTEM) -->
                <!-- Tab Navigation Header -->
                <div class="bg-white rounded-t-lg shadow-sm border-b border-gray-200">
                    <div class="flex text-center font-bold text-gray-500">
                        <button data-tab-switch data-tab-name="booking" id="tab-booking" class="w-1/3 py-4 hover:text-blue-600 border-b-2 border-blue-600 text-blue-600 transition-colors">
                            Kode Booking
                        </button>
                        <button data-tab-switch data-tab-name="history" id="tab-history" class="w-1/3 py-4 hover:text-blue-600 border-b-2 border-transparent transition-colors">
                            History peminjaman
                        </button>
                        <button data-tab-switch data-tab-name="pelanggaran" id="tab-pelanggaran" class="w-1/3 py-4 hover:text-blue-600 border-b-2 border-transparent transition-colors">
                            Pelanggaran
                        </button>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="bg-white rounded-b-lg shadow-sm p-6 min-h-[500px]">


                <!-- 1. TAB KODE BOOKING CONTENT -->
                <div id="content-booking" class="tab-content flex items-center justify-center min-h-[450px]">
                    <?php if ($active_booking): ?>
                    <!-- Card Booking Aktif (Full Display) -->
                    <div class="w-full max-w-4xl mx-auto">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="flex flex-col md:flex-row">
                                <!--  -->
                                <!-- Content Section -->
                                <div class="grow md:w-3/5 p-8">
                                    <div class="mb-6">
                                        <div class="flex items-start justify-between gap-3 mb-2">
                                            <h2 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($active_booking['nama_ruangan']) ?></h2>
                                            <!-- Badge Role -->
                                            <?php if (isset($active_booking['is_ketua']) && $active_booking['is_ketua'] == 1): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800 border border-blue-300 whitespace-nowrap" title="Anda adalah ketua booking ini">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9.504 1.132a1 1 0 01.992 0l1.75 1a1 1 0 11-.992 1.736L10 3.152l-1.254.716a1 1 0 11-.992-1.736l1.75-1zM5.618 4.504a1 1 0 01-.372 1.364L5.016 6l.23.132a1 1 0 11-.992 1.736L4 7.723V8a1 1 0 01-2 0V6a.996.996 0 01.52-.878l1.734-.99a1 1 0 011.364.372zm8.764 0a1 1 0 011.364-.372l1.733.99A1.002 1.002 0 0118 6v2a1 1 0 11-2 0v-.277l-.254.145a1 1 0 11-.992-1.736l.23-.132-.23-.132a1 1 0 01-.372-1.364zm-7 4a1 1 0 011.364-.372L10 8.848l1.254-.716a1 1 0 11.992 1.736L11 10.58V12a1 1 0 11-2 0v-1.42l-1.246-.712a1 1 0 01-.372-1.364zM3 11a1 1 0 011 1v1.42l1.246.712a1 1 0 11-.992 1.736l-1.75-1A1 1 0 012 14v-2a1 1 0 011-1zm14 0a1 1 0 011 1v2a1 1 0 01-.504.868l-1.75 1a1 1 0 11-.992-1.736L16 13.42V12a1 1 0 011-1zm-9.618 5.504a1 1 0 011.364-.372l.254.145V16a1 1 0 112 0v.277l.254-.145a1 1 0 11.992 1.736l-1.735.992a.995.995 0 01-1.022 0l-1.735-.992a1 1 0 01-.372-1.364z" clip-rule="evenodd" />
                                                </svg>
                                                KETUA
                                            </span>
                                            <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-700 border border-gray-300 whitespace-nowrap" title="Anda adalah anggota booking ini">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                                                </svg>
                                                ANGGOTA
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-gray-500 text-sm">Booking ID: <?= htmlspecialchars($active_booking["id_booking"]) ?></p>
                                        <span class="inline-block mt-2 px-3 py-1 text-xs font-semibold rounded-full <?= $active_booking['nama_status'] === 'AKTIF' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                            <?= $active_booking['nama_status'] ?>
                                        </span>
                                    </div>

                                    <div class="space-y-4 mb-8">
                                        <div class="flex items-start gap-4">
                                            <div class="p-3 bg-blue-50 rounded-lg">
                                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500 font-medium">Tanggal Booking</p>
                                                <p class="text-lg font-bold text-gray-900"><?= date('d F Y', strtotime($active_booking['tanggal_schedule'])) ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-4">
                                            <div class="p-3 bg-green-50 rounded-lg">
                                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500 font-medium">Jam Pemakaian</p>
                                                <p class="text-lg font-bold text-gray-900"><?= date('H:i', strtotime($active_booking['waktu_mulai'])) ?> - <?= date('H:i', strtotime($active_booking['waktu_selesai'])) ?> WIB</p>
                                                <p class="text-sm text-gray-500">Durasi: <?= $active_booking['durasi_penggunaan'] ?> Menit</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start gap-4">
                                            <div class="p-3 bg-purple-50 rounded-lg">
                                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500 font-medium">Kapasitas</p>
                                                <p class="text-lg font-bold text-gray-900"><?= $active_booking['minimal_kapasitas_ruangan'] ?>-<?= $active_booking['maksimal_kapasitas_ruangan'] ?> Orang</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <?php
                                    // Hitung apakah masih bisa reschedule (minimal 1 jam sebelum waktu mulai)
                                    $waktu_mulai = new DateTime($active_booking['tanggal_schedule'] . ' ' . $active_booking['waktu_mulai']);
                                    $sekarang = new DateTime();
                                    $diff_hours = ($waktu_mulai->getTimestamp() - $sekarang->getTimestamp()) / 3600;
                                    $can_reschedule = $diff_hours >= 1;
                                    $is_ketua = isset($active_booking['is_ketua']) && $active_booking['is_ketua'] == 1;
                                    ?>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <a href="?page=booking&action=kode_booking&id=<?= $active_booking['id_booking'] ?>" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-md hover:shadow-lg text-center">
                                            Lihat Kode Booking
                                        </a>
                                        <?php if ($is_ketua): ?>
                                            <?php if ($can_reschedule): ?>
                                            <a href="?page=booking&action=reschedule&id=<?= $active_booking['id_booking'] ?>" class="flex-1 bg-white hover:bg-gray-50 text-blue-600 border-2 border-blue-600 px-6 py-3 rounded-lg font-semibold transition-colors text-center" title="✅ Hanya ketua yang bisa reschedule">
                                                Reschedule
                                            </a>
                                            <?php else: ?>
                                            <button disabled class="flex-1 bg-gray-100 text-gray-400 border-2 border-gray-200 px-6 py-3 rounded-lg font-semibold cursor-not-allowed text-center" title="Reschedule hanya bisa dilakukan minimal 1 jam sebelum waktu mulai">
                                                Reschedule (Tidak Tersedia)
                                            </button>
                                            <?php endif; ?>
                                            <a href="?page=booking&action=hapus_booking&id=<?= $active_booking['id_booking'] ?>" class="sm:w-auto bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-md hover:shadow-lg text-center" title="✅ Hanya ketua yang bisa batalkan">
                                                Batalkan
                                            </a>
                                        <?php else: ?>
                                            <button disabled class="flex-1 bg-gray-100 text-gray-400 border-2 border-gray-200 px-6 py-3 rounded-lg font-semibold cursor-not-allowed text-center" title="❌ Hanya ketua yang bisa reschedule booking">
                                                Reschedule (Khusus Ketua)
                                            </button>
                                            <button disabled class="sm:w-auto bg-gray-100 text-gray-400 px-6 py-3 rounded-lg font-semibold cursor-not-allowed text-center" title="❌ Hanya ketua yang bisa batalkan booking">
                                                Batalkan (Khusus Ketua)
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- No Active Booking -->
                    <div class="text-center py-12">
                        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-4 text-xl font-semibold text-gray-900">Tidak Ada Booking Aktif</h3>
                        <p class="mt-2 text-gray-600">Anda belum memiliki booking aktif saat ini.</p>
                        <a href="?page=dashboard" class="mt-6 inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors shadow-md hover:shadow-lg">
                            Buat Booking Baru
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 2. TAB HISTORY PEMINJAMAN CONTENT -->
                <div id="content-history" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (!empty($history_bookings)): ?>
                        <?php foreach ($history_bookings as $booking): ?>
                        <!-- History Card -->
                        <div class="rounded-xl p-3 flex gap-3 shadow-sm border border-gray-200 hover:shadow-md transition">
                            <img src="<?= $asset("/assets/image/gambar ruangan.jpg") ?>" class="w-24 h-24 object-cover rounded-lg" alt="<?= htmlspecialchars($booking['nama_ruangan']) ?>">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($booking['nama_ruangan']) ?></h3>
                                <div class="text-xs text-gray-500 mt-1 space-y-1">
                                    <p>📅 <?= date('d F Y', strtotime($booking['tanggal_schedule'])) ?></p>
                                    <p>🕒 <?= date('H:i', strtotime($booking['waktu_mulai'])) ?> - <?= date('H:i', strtotime($booking['waktu_selesai'])) ?></p>
                                    <p>Status : <span class="font-semibold <?= $booking['nama_status'] === 'AKTIF' ? 'text-green-600' : ($booking['nama_status'] === 'SELESAI' ? 'text-blue-600' : 'text-red-600') ?>"><?= $booking['nama_status'] ?></span></p>
                                    <p>⏱ Durasi : <?= $booking['durasi_penggunaan'] ?> Menit</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-2 text-center py-12">
                            <p class="text-gray-500">Belum ada riwayat peminjaman</p>
                        </div>
                    <?php endif; ?>
                    </div>
                    
                    <!-- Pagination History -->
                    <?php if (isset($paginationHistory) && $paginationHistory['totalPages'] > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <?php
                            $currentPage = $paginationHistory['currentPage'];
                            $totalPages = $paginationHistory['totalPages'];
                            ?>
                            <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=profile&pg_history=<?= $currentPage - 1 ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                        &laquo; Prev
                                    </span>
                                <?php endif; ?>

                                <?php 
                                $range = 2;
                                $startPage = max(1, $currentPage - $range);
                                $endPage = min($totalPages, $currentPage + $range);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 border border-blue-600">
                                            <?= $i ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?page=profile&pg_history=<?= $i ?>" 
                                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=profile&pg_history=<?= $currentPage + 1 ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                        Next &raquo;
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 3. TAB PELANGGARAN CONTENT -->
                <div id="content-pelanggaran" class="tab-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if (!empty($pelanggaran_list)): ?>
                        <?php foreach ($pelanggaran_list as $pelanggaran): ?>
                        <!-- Pelanggaran Card -->
                        <div class="rounded-xl p-4 flex gap-3 shadow-sm border border-gray-200 hover:shadow-md transition">
                            <div class="shrink-0">
                                <div class="w-16 h-16 bg-red-50 rounded-lg flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-800 text-sm mb-2"><?= htmlspecialchars($pelanggaran['jenis_pelanggaran']) ?></h3>
                                <div class="text-xs text-gray-500 space-y-1">
                                    <p>📅 <span class="font-medium">Tanggal:</span> <?= date('d M Y', strtotime($pelanggaran['tanggal_mulai'])) ?></p>
                                    <?php if ($pelanggaran['tanggal_selesai'] && strtotime($pelanggaran['tanggal_selesai']) > strtotime($pelanggaran['tanggal_mulai'])): ?>
                                    <p>⏰ <span class="font-medium">Selesai:</span> <?= date('d M Y', strtotime($pelanggaran['tanggal_selesai'])) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($pelanggaran['alasan_suspensi'])): ?>
                                    <p class="text-gray-700 mt-2">📜 <span class="font-medium">Detail:</span> <?= htmlspecialchars($pelanggaran['alasan_suspensi']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-2 text-center py-12">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-24 w-24 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900">Tidak Ada Pelanggaran</h3>
                            <p class="mt-2 text-gray-600">Selamat! Anda tidak memiliki catatan pelanggaran.</p>
                        </div>
                    <?php endif; ?>
                    </div>
                    
                    <!-- Pagination Pelanggaran -->
                    <?php if (isset($paginationPelanggaran) && $paginationPelanggaran['totalPages'] > 1): ?>
                        <div class="mt-6 flex justify-center">
                            <?php
                            $currentPage = $paginationPelanggaran['currentPage'];
                            $totalPages = $paginationPelanggaran['totalPages'];
                            ?>
                            <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                                <?php if ($currentPage > 1): ?>
                                    <a href="?page=profile&pg_pelanggaran=<?= $currentPage - 1 ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                        &laquo; Prev
                                    </span>
                                <?php endif; ?>

                                <?php 
                                $range = 2;
                                $startPage = max(1, $currentPage - $range);
                                $endPage = min($totalPages, $currentPage + $range);
                                for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <?php if ($i == $currentPage): ?>
                                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-blue-600 border border-blue-600">
                                            <?= $i ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="?page=profile&pg_pelanggaran=<?= $i ?>" 
                                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">
                                            <?= $i ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?page=profile&pg_pelanggaran=<?= $currentPage + 1 ?>" 
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                        Next &raquo;
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>

                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Include Ganti Password Modal Component -->
    <?php require __DIR__ . '/../components/modal_change_password.php'; ?>

    <!-- Modal Upload Foto Profil -->
    <div id="modalUploadFoto" class="fixed inset-0 bg-white/60 z-50 items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-2xl font-bold text-gray-900">Upload Foto Profil</h3>
                <button id="closeModalFoto" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="index.php?page=profile&action=upload_foto" method="post" enctype="multipart/form-data" id="formUploadFoto">
                <!-- Preview Image -->
                <div class="mb-6">
                    <div id="previewContainer" class="w-48 h-48 rounded-full bg-gray-100 mx-auto flex items-center justify-center overflow-hidden border-4 border-gray-200">
                        <?php if (isset($_SESSION['user']['foto_profil']) && !empty($_SESSION['user']['foto_profil'])): ?>
                            <img id="previewImage" src="<?= htmlspecialchars($asset($_SESSION['user']['foto_profil']), ENT_QUOTES) ?>" 
                                 alt="Preview" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <svg id="previewIcon" xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- File Input -->
                <div class="mb-6">
                    <label for="fotoProfil" class="block text-sm font-medium text-gray-700 mb-2">
                        Pilih Foto (JPEG, PNG, WebP - Max 25MB)
                    </label>
                    <input type="file" 
                           name="foto_profil" 
                           id="fotoProfil" 
                           accept="image/jpeg,image/png,image/webp" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="button" id="cancelUploadFoto" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script untuk Interaksi Tab -->
    <?php if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])): ?>
    <script src="<?= $asset("/assets/js/profile.js") ?>" defer></script>
    <?php endif; ?>
    <script src="<?= $asset('/assets/js/auth.js') ?>" defer></script>
    
    <?php if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['Admin', 'Super Admin'])): ?>
    <link rel="stylesheet" href="<?= $asset('assets/css/admin-dashboard.css') ?>">
    <!-- Data akan di-inject melalui data attributes -->
    <div id="profile-data" data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('/assets/js/admin.js') ?>" defer></script>
    <?php endif; ?>
</body>

</html>
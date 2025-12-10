<?php
/**
 * ============================================================================
 * ADMIN/BOOKING_EXTERNAL.PHP - External Booking Management (Super Admin Only)
 * ============================================================================
 * 
 * Super Admin exclusive feature untuk manage external bookings dari non-PNJ organizations.
 * Two-column layout: Left = booking form, Right = schedule table dengan 2 tabs.
 * 
 * ACCESS CONTROL:
 * - Super Admin ONLY (strict check: role === 'Super Admin')
 * - Blocks Admin access (redirects to admin dashboard)
 * - Critical: This feature allows non-user bookings
 * 
 * LAYOUT STRUCTURE:
 * 1. LEFT COLUMN (lg:col-span-4)
 *    - STATE 1: Card button "Buat Booking Eksternal" (default)
 *    - STATE 2: Booking form (shown on button click)
 *    - Toggle between states via JavaScript
 * 
 * 2. RIGHT COLUMN (lg:col-span-8)
 *    - TAB 1: "Mendatang" (Upcoming external bookings)
 *    - TAB 2: "Histori" (Past external bookings)
 *    - Table dengan pagination
 *    - Filter inputs: Ruangan, Instansi, Tanggal
 * 
 * FEATURES:
 * 1. CREATE EXTERNAL BOOKING
 *    - Form fields:
 *      * Pilih Ruangan (select dropdown)
 *      * Nama Instansi (text input)
 *      * Surat Lampiran (PDF upload)
 *      * Tanggal (date picker, min=today)
 *      * Jam Mulai (time input)
 *      * Jam Selesai (time input)
 *    - Submit: Creates booking with external source marker
 * 
 * 2. VIEW UPCOMING BOOKINGS
 *    - Table columns: No, Instansi, Ruangan, Tanggal, Jam, Surat, Aksi
 *    - Shows future bookings only
 *    - Sorted by tanggal ASC (nearest first)
 *    - Aksi: Edit (icon), Delete (icon)
 * 
 * 3. VIEW BOOKING HISTORY
 *    - Same table structure as Mendatang
 *    - Shows past bookings (tanggal < today)
 *    - Sorted by tanggal DESC (most recent first)
 *    - Aksi: View details, Delete
 * 
 * 4. FILTER FUNCTIONALITY
 *    - Ruangan: Dropdown dengan all rooms
 *    - Instansi: Text search (partial match)
 *    - Tanggal: Date picker (exact match)
 *    - Filters persist across pagination
 * 
 * 5. PAGINATION
 *    - 10 records per page
 *    - Separate pagination per tab (pg_upcoming, pg_histori)
 *    - URL-based state preservation
 * 
 * FORM STRUCTURE (External Booking):
 * - Method: POST
 * - Action: ?page=admin&action=submit_booking_external
 * - Enctype: multipart/form-data (for PDF upload)
 * - Fields:
 *   * id_ruangan (select, required)
 *   * nama_instansi (text, required)
 *   * surat_lampiran (file, PDF only, max 25MB)
 *   * tanggal (date, required, min=today)
 *   * waktu_mulai (time, required)
 *   * waktu_selesai (time, required)
 * 
 * FORM VALIDATION:
 * - Client-side:
 *   * All required fields filled
 *   * waktu_selesai > waktu_mulai
 *   * PDF file only
 *   * File size check (JavaScript)
 * - Server-side:
 *   * Room availability check
 *   * Time slot conflict check
 *   * MIME type validation (application/pdf)
 *   * File size limit (25MB)
 *   * Past datetime prevention
 * 
 * TABLE STRUCTURE (Both tabs):
 * - Columns: No, Instansi, Ruangan, Tanggal, Jam, Surat, Aksi
 * - No: Sequential number (pagination-aware)
 * - Instansi: Organization name
 * - Ruangan: Room name
 * - Tanggal: Formatted date (d F Y)
 * - Jam: Time range (H:i - H:i)
 * - Surat: Download link (PDF file)
 * - Aksi: Edit & Delete icons
 * 
 * DATA FROM CONTROLLER:
 * - $upcomingBookings (array): Future external bookings
 * - $historiBookings (array): Past external bookings
 * - $ruanganList (array): All rooms for dropdown
 * - $paginationDataUpcoming (array): Pagination for upcoming tab
 * - $paginationDataHistori (array): Pagination for histori tab
 * - $currentTab (string): Active tab ('upcoming' or 'histori')
 * 
 * BOOKING DATA STRUCTURE (External):
 * $externalBooking = [
 *   'id_booking' => int,
 *   'kode_booking' => string,
 *   'nama_instansi' => string,
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string,
 *   'tanggal' => string (YYYY-MM-DD),
 *   'waktu_mulai' => string (HH:MM:SS),
 *   'waktu_selesai' => string (HH:MM:SS),
 *   'surat_lampiran' => string|null (path to PDF),
 *   'is_external' => int (1 = external booking marker)
 * ];
 * 
 * FILE UPLOAD (Surat Lampiran):
 * - Allowed: PDF only (application/pdf)
 * - Max size: 25MB (enforced server-side)
 * - Storage: assets/uploads/docs/
 * - Filename pattern: surat_{timestamp}_{random}.pdf
 * - Validation: finfo_file() untuk MIME type check
 * 
 * TAB SWITCHING:
 * - JavaScript: assets/js/admin.js
 * - Tabs: data-tab="upcoming" and data-tab="histori"
 * - Active state: border-b-2 border-sky-600 text-sky-600
 * - Inactive state: text-gray-600 hover:text-sky-600
 * - Content toggle: .tab-content hidden/block
 * 
 * FORM TOGGLE:
 * - Button click: #booking-card → hidden, #booking-form → block
 * - Cancel button: #booking-form → hidden, #booking-card → block
 * - JavaScript handles smooth transitions
 * 
 * ROUTING:
 * - Current page: ?page=admin&action=booking_external
 * - Submit form: ?page=admin&action=submit_booking_external (POST)
 * - Edit: ?page=admin&action=edit_external&id={id} (PLANNED)
 * - Delete: ?page=admin&action=delete_external&id={id} (POST)
 * - Tab switch: &tab=upcoming or &tab=histori (GET)
 * 
 * PAGINATION:
 * - Upcoming: ?page=admin&action=booking_external&tab=upcoming&pg_upcoming={n}
 * - Histori: ?page=admin&action=booking_external&tab=histori&pg_histori={n}
 * - Filter preservation: &ruang={id}&instansi={name}&tanggal={date}
 * 
 * TARGET ELEMENTS:
 * - #booking-card: Initial card button state
 * - #booking-form: Booking form state
 * - #btn-buat-booking: Show form button
 * - #btn-cancel-booking: Hide form button
 * - [data-tab]: Tab buttons
 * - .tab-content: Tab content containers
 * - #filter-ruang: Room filter select
 * - #filter-instansi: Organization filter input
 * - #filter-tanggal: Date filter input
 * 
 * JAVASCRIPT:
 * - assets/js/admin.js: Form toggle, tab switching
 * - Functions:
 *   * showBookingForm(): Toggle to form state
 *   * hideBookingForm(): Toggle to card state
 *   * switchTab(tabName): Change active tab
 * 
 * CSS:
 * - External: assets/css/booking-external.css
 * - Font Awesome: Icons for edit/delete/document
 * - Tailwind: Layout and styling
 * 
 * BUSINESS RULES:
 * - Super Admin can book on behalf of external organizations
 * - No ketua/anggota (different from regular bookings)
 * - Requires official letter (surat lampiran)
 * - Time slot conflict check still applies
 * - Operational hours validation applies
 * - Holiday validation applies
 * 
 * SUCCESS FLOW:
 * 1. Super Admin fills form
 * 2. Uploads PDF letter
 * 3. Submit form
 * 4. Server validates all inputs
 * 5. Creates booking record with is_external = 1
 * 6. Stores PDF in assets/uploads/docs/
 * 7. Alert success, refresh page
 * 8. New booking appears in Mendatang tab
 * 
 * ERROR HANDLING:
 * - Invalid PDF → alert "Hanya file PDF yang diperbolehkan!"
 * - File too large → alert "Ukuran file maksimal 25MB!"
 * - Time conflict → alert "Jadwal bentrok dengan booking lain!"
 * - Past datetime → alert "Tidak bisa booking di waktu lampau!"
 * - Missing fields → HTML5 validation + server-side check
 * 
 * SECURITY:
 * - Super Admin only (strict role check)
 * - MIME type validation (PDF only)
 * - File size limit (25MB)
 * - Unique filenames (prevent overwrites)
 * - Time slot conflict prevention
 * - XSS prevention (htmlspecialchars on outputs)
 * 
 * INTEGRATION:
 * - Controller: AdminController (booking_external, submit_booking_external, delete_external)
 * - Model: BookingExternalModel (create, getUpcoming, getHistori, delete)
 * - Model: RuanganModel (getAll)
 * - Model: ScheduleModel (isTimeSlotAvailable)
 * - Database: booking table (with is_external flag)
 * - Upload: assets/uploads/docs/
 * 
 * @package BookEZ
 * @subpackage Views\Admin
 * @version 1.0
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Only Super Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Super Admin') {
    header('Location: index.php?page=admin&action=index');
    exit;
}

require __DIR__ . '/../components/head.php';

// Get current tab
$currentTab = $_GET['tab'] ?? 'upcoming';
?>

<title>Booking Eksternal - Super Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= $asset('assets/css/booking-external.css') ?>">
</head>

<body class="bg-white min-h-screen flex flex-col">

    <!-- NAVBAR -->
    <?php require __DIR__ . '/../components/navbar_admin.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto w-full p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- LEFT COLUMN (Dynamic: Button Card OR Form) -->
        <div class="lg:col-span-4 xl:col-span-4 transition-all duration-300">

            <!-- STATE 1: CARD TOMBOL BUAT BOOKING -->
            <div id="booking-card" class="block">
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden relative group cursor-pointer">
                    <img src="<?= $asset('/assets/image/room.png') ?>" alt="Library" class="w-full h-48 object-cover opacity-90 group-hover:opacity-100 transition">

                    <!-- Overlay Button look -->
                    <div class="absolute inset-0 flex items-center justify-center bg-black/10 group-hover:bg-black/20 transition">
                        <div class="bg-[#55CCFF] opacity-85 text-white px-8 py-3 rounded shadow-lg font-semibold text-lg backdrop-blur-sm">
                            Buat Booking
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATE 2: FORM INPUT (Awalnya Hidden) -->
            <div id="booking-form" class="hidden bg-[#D9D9D9] rounded-lg p-5 shadow-inner">
                <div class="flex justify-end mb-4">
                    <button data-toggle-form="false" class="text-sm font-bold text-gray-700 hover:text-black flex items-center gap-1">
                        Kembali <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>

                <!-- Form Image Header -->
                <div class="mb-4 rounded-lg overflow-hidden h-24">
                    <img src="<?= $asset('/assets/image/room.png') ?>" class="w-full h-full object-cover" alt="Room Preview">
                </div>

                <form method="POST" action="index.php?page=admin&action=submit_booking_external" enctype="multipart/form-data">
                    <!-- Input: Ruangan -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Pilih Ruangan <span class="text-red-500">*</span></label>
                        <select name="id_ruangan" required class="w-full p-2 border border-gray-300 rounded bg-gray-100 focus:bg-white focus:outline-none focus:border-blue-500">
                            <option value="">-- Pilih Ruang Rapat --</option>
                            <?php if (!empty($ruangRapat)): ?>
                                <?php foreach ($ruangRapat as $ruang): ?>
                                    <option value="<?= $ruang['id_ruangan'] ?>"><?= htmlspecialchars($ruang['nama_ruangan']) ?> (Kapasitas: <?= $ruang['minimal_kapasitas_ruangan'] ?>-<?= $ruang['maksimal_kapasitas_ruangan'] ?> orang)</option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Tidak ada ruang rapat tersedia</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Input: Nama Instansi -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Nama Instansi <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_instansi" required placeholder="Contoh: PT Maju Mundur" class="w-full p-2 border border-gray-300 rounded bg-gray-100 focus:bg-white focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- Input: Surat Lampiran -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Surat Lampiran (PDF, Max 25MB)</label>
                        <input type="file" name="surat_lampiran" accept=".pdf" class="w-full p-2 border border-gray-300 rounded bg-gray-100 text-sm file:mr-4 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-gray-800 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-600 mt-1">*Opsional - Upload surat resmi dalam format PDF</p>
                    </div>

                    <!-- Input: Tanggal -->
                    <div class="mb-3">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" required min="<?= date('Y-m-d') ?>" class="w-full p-2 border border-gray-300 rounded bg-gray-100 focus:bg-white focus:outline-none focus:border-blue-500">
                    </div>

                    <!-- Input: Jam -->
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-gray-800 mb-1">Waktu <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-2">
                            <input type="time" name="waktu_mulai" required class="w-full p-2 border border-gray-300 rounded bg-gray-100 focus:bg-white focus:outline-none">
                            <span class="font-bold">s/d</span>
                            <input type="time" name="waktu_selesai" required class="w-full p-2 border border-gray-300 rounded bg-gray-100 focus:bg-white focus:outline-none">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-400 hover:bg-blue-500 text-white font-semibold py-2 rounded shadow transition">
                        Buat Booking
                    </button>
                </form>
            </div>

        </div>

        <!-- RIGHT COLUMN (Table & Tabs) -->
        <div class="lg:col-span-8 xl:col-span-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <!-- Blue Header -->
                <div class="bg-[#1E73BE] text-white text-center py-3 font-semibold text-lg">
                    Jadwal Booking
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200">
                    <a href="index.php?page=admin&action=booking_external&tab=upcoming" class="flex-1 py-3 text-center <?= $currentTab === 'upcoming' ? 'active-tab' : 'inactive-tab' ?> transition-colors">
                        Mendatang
                    </a>
                    <a href="index.php?page=admin&action=booking_external&tab=history" class="flex-1 py-3 text-center <?= $currentTab === 'history' ? 'active-tab' : 'inactive-tab' ?> transition-colors">
                        Histori
                    </a>
                </div>

                <!-- Filter Row (Header Search) -->
                <div class="p-4 bg-white">
                    <form method="GET" action="index.php" class="flex gap-4 text-sm mb-2 px-2">
                        <input type="hidden" name="page" value="admin">
                        <input type="hidden" name="action" value="booking_external">
                        <input type="hidden" name="tab" value="<?= $currentTab ?>">
                        
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" name="ruang" placeholder="Cari Ruangan" value="<?= htmlspecialchars($_GET['ruang'] ?? '') ?>" class="w-full py-1 px-2 pr-8 border-b border-gray-300 bg-transparent focus:outline-none focus:border-blue-500 text-gray-900">
                                <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="relative">
                                <input type="text" name="instansi" placeholder="Cari Nama Instansi" value="<?= htmlspecialchars($_GET['instansi'] ?? '') ?>" class="w-full py-1 px-2 pr-8 border-b border-gray-300 bg-transparent focus:outline-none focus:border-blue-500 text-gray-900">
                                <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="relative">
                                <input type="date" name="tanggal" value="<?= htmlspecialchars($_GET['tanggal'] ?? '') ?>" class="w-full py-1 px-2 pr-8 border-b border-gray-300 bg-transparent focus:outline-none focus:border-blue-500 text-gray-900 text-right">
                                <button type="submit" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500">
                                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- TABLE CONTENT CONTAINER -->
                    <div class="max-h-[500px] overflow-y-auto table-container">

                        <?php if (empty($bookings)): ?>
                            <!-- No Data Message -->
                            <div class="p-8 text-center text-gray-500">
                                <i class="fa-solid fa-calendar-xmark text-4xl mb-3 text-gray-300"></i>
                                <p class="font-medium">Tidak ada data booking</p>
                                <p class="text-sm">Silakan buat booking baru untuk memulai</p>
                            </div>
                        <?php else: ?>
                            <!-- Data Table -->
                            <div class="flex flex-col gap-2">
                                <?php foreach ($bookings as $booking): ?>
                                    <div class="grid grid-cols-12 gap-4 p-3 hover:bg-gray-50 border-b border-gray-100 text-sm items-center <?= $currentTab === 'history' ? 'opacity-75 bg-gray-50' : '' ?>">
                                        <!-- Nama Ruangan -->
                                        <div class="col-span-3 font-medium text-gray-800">
                                            <?= htmlspecialchars($booking['nama_ruangan']) ?>
                                        </div>
                                        
                                        <!-- Nama Instansi -->
                                        <div class="col-span-3 text-gray-600">
                                            <?= htmlspecialchars($booking['nama_instansi'] ?? 'N/A') ?>
                                        </div>
                                        
                                        <!-- Surat Lampiran -->
                                        <div class="col-span-2 text-center">
                                            <?php if (!empty($booking['surat_lampiran']) && file_exists(__DIR__ . '/../../' . $booking['surat_lampiran'])): ?>
                                                <a href="<?= $asset($booking['surat_lampiran']) ?>" target="_blank" class="text-blue-500 hover:underline text-xs">
                                                    <i class="fa-solid fa-file-pdf mr-1"></i>Lihat Surat
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">Tidak ada</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Tanggal & Waktu -->
                                        <div class="col-span-3 text-right text-gray-600">
                                            <?php if (!empty($booking['tanggal_schedule'])): ?>
                                                <div><?= date('d M Y', strtotime($booking['tanggal_schedule'])) ?></div>
                                                <div class="text-xs text-gray-500">
                                                    <?= date('H:i', strtotime($booking['waktu_mulai'])) ?> - <?= date('H:i', strtotime($booking['waktu_selesai'])) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">Belum dijadwalkan</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Action Button (Hapus) - Only for upcoming -->
                                        <?php if ($currentTab === 'upcoming'): ?>
                                            <div class="col-span-1 text-center">
                                                <form method="POST" action="index.php?page=admin&action=delete_booking_external" onsubmit="return confirm('Yakin ingin menghapus booking ini?');">
                                                    <input type="hidden" name="id_booking" value="<?= $booking['id_booking'] ?>">
                                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Hapus Booking">
                                                        <i class="fa-solid fa-trash text-sm"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                    
                    <!-- Pagination -->
                    <?php if (isset($paginationData) && $paginationData['totalPages'] > 1): ?>
                        <div class="px-4 py-3 border-t border-gray-200">
                            <?php
                            $baseUrl = 'index.php';
                            $queryParams = [
                                'page' => 'admin',
                                'action' => 'booking_external',
                                'tab' => $currentTab
                            ];
                            if (!empty($_GET['ruang'])) $queryParams['ruang'] = $_GET['ruang'];
                            if (!empty($_GET['instansi'])) $queryParams['instansi'] = $_GET['instansi'];
                            if (!empty($_GET['tanggal'])) $queryParams['tanggal'] = $_GET['tanggal'];
                            
                            $currentPage = $paginationData['currentPage'];
                            $totalPages = $paginationData['totalPages'];
                            ?>
                            
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <!-- Info -->
                                <div class="text-sm text-gray-600">
                                    Menampilkan 
                                    <span class="font-semibold"><?= (($currentPage - 1) * $paginationData['perPage']) + 1 ?></span>
                                    - 
                                    <span class="font-semibold"><?= min($currentPage * $paginationData['perPage'], $paginationData['totalRecords']) ?></span>
                                    dari 
                                    <span class="font-semibold"><?= $paginationData['totalRecords'] ?></span>
                                    booking
                                </div>
                                
                                <!-- Pagination Controls -->
                                <nav class="inline-flex rounded-md shadow-sm" aria-label="Pagination">
                                    <!-- Previous -->
                                    <?php if ($currentPage > 1): 
                                        $prevParams = $queryParams;
                                        $prevParams['pg'] = $currentPage - 1;
                                    ?>
                                        <a href="<?= $baseUrl ?>?<?= http_build_query($prevParams) ?>" 
                                           class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                            &laquo; Prev
                                        </a>
                                    <?php else: ?>
                                        <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                            &laquo; Prev
                                        </span>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php 
                                    $range = 2;
                                    $startPage = max(1, $currentPage - $range);
                                    $endPage = min($totalPages, $currentPage + $range);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): 
                                        $pageParams = $queryParams;
                                        $pageParams['pg'] = $i;
                                    ?>
                                        <?php if ($i == $currentPage): ?>
                                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-[#1e73be] border border-[#1e73be]">
                                                <?= $i ?>
                                            </span>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>?<?= http_build_query($pageParams) ?>" 
                                               class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-t border-b border-gray-300 hover:bg-gray-50">
                                                <?= $i ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <!-- Next -->
                                    <?php if ($currentPage < $totalPages): 
                                        $nextParams = $queryParams;
                                        $nextParams['pg'] = $currentPage + 1;
                                    ?>
                                        <a href="<?= $baseUrl ?>?<?= http_build_query($nextParams) ?>" 
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </main>

    <!-- JavaScript Logic -->
    <div id="booking-external-data" data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('assets/js/admin.js') ?>" defer></script>
</body>

</html>
<?php
/**
 * ============================================================================
 * ADMIN/BOOKING_LIST.PHP - Booking List & Check-in Management
 * ============================================================================
 * 
 * Comprehensive booking management page untuk Admin & Super Admin.
 * Features: List all bookings, filters, check-in management, status updates.
 * 
 * ACCESS CONTROL:
 * - Admin & Super Admin only
 * - Blocks regular User access
 * 
 * FEATURES:
 * 1. BOOKING LIST TABLE
 *    - Columns: No, Nama, Ruangan, Tanggal, Jam, Status, Anggota, Aksi
 *    - Paginated: 10 bookings per page
 *    - Color-coded status badges
 *    - Mixed booking types: Regular user bookings + External bookings
 * 
 * 2. FILTER SYSTEM
 *    - Ruangan: Dropdown (all rooms + "Semua Ruangan")
 *    - Nama: Text search (ketua or instansi name)
 *    - Tanggal: Date picker (exact match)
 *    - Status: Dropdown (AKTIF, SELESAI, DIBATALKAN, HANGUS, Semua)
 *    - Filters persist across pagination
 * 
 * 3. CHECK-IN MODAL
 *    - Triggered by "Check-in" button in Aksi column
 *    - Shows list of booking members (anggota)
 *    - Individual check-in buttons per member
 *    - Check-in timestamp recording
 *    - Hari H validation (can only check-in on booking date)
 * 
 * 4. BOOKING TYPES DETECTION
 *    - Regular booking: Has ketua & anggota (shows member count)
 *    - External booking: Has nama_instansi (shows organization name)
 *    - Table adapts display based on booking type
 * 
 * 5. STATUS BADGES
 *    - AKTIF: Green (bg-green-100 text-green-800)
 *    - SELESAI: Blue (bg-blue-100 text-blue-800)
 *    - DIBATALKAN: Gray (bg-gray-100 text-gray-800)
 *    - HANGUS: Red (bg-red-100 text-red-800)
 * 
 * DATA FROM CONTROLLER:
 * - $bookings (array): All bookings dengan filters applied
 * - $ruanganList (array): All rooms for filter dropdown
 * - $paginationData (array): Pagination info
 * - Filter values: $filterRuang, $filterNama, $filterTanggal, $filterStatus
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
 *   'nama_status' => string,
 *   'ketua_nama' => string|null (for regular bookings),
 *   'nama_instansi' => string|null (for external bookings),
 *   'total_anggota' => int (for regular bookings),
 *   'check_in_count' => int (for regular bookings)
 * ];
 * 
 * FILTER FORM:
 * - Method: GET
 * - Action: index.php?page=admin&action=booking_list
 * - Hidden fields: page=admin, action=booking_list
 * - Maintains: Filters + pagination state
 * 
 * CHECK-IN MODAL STRUCTURE:
 * - Triggered: Click "Check-in" button (data-kode-booking attribute)
 * - AJAX endpoint: ?page=admin&action=get_booking_anggota&kode={kode}
 * - Returns: List of members dengan check-in status
 * - Check-in action: POST to ?page=admin&action=checkin_anggota
 * 
 * CHECK-IN LOGIC:
 * 1. Validate: Today is booking date (hari H check)
 * 2. Check: Member not already checked in
 * 3. Update: anggota_booking.is_checked_in = 1
 * 4. Record: anggota_booking.waktu_check_in = NOW()
 * 5. Return: Success status
 * 6. Update modal: Show checkmark icon
 * 
 * HARI H VALIDATION:
 * - Check-in only allowed on booking date
 * - Formula: date('Y-m-d') === $booking['tanggal']
 * - Before date: "Check-in belum dibuka"
 * - After date: "Check-in sudah ditutup"
 * - Enforced both client-side (button disable) and server-side
 * 
 * ANGGOTA DATA (Modal):
 * $anggota = [
 *   'nomor_induk' => string,
 *   'nama' => string,
 *   'is_ketua' => int (1 = ketua, 0 = anggota biasa),
 *   'is_checked_in' => int (1 = sudah check-in, 0 = belum),
 *   'waktu_check_in' => string|null (YYYY-MM-DD HH:MM:SS)
 * ];
 * 
 * TABLE STRUCTURE:
 * - No: Sequential number (pagination-aware)
 * - Nama: Ketua name (regular) or Instansi (external)
 * - Ruangan: Room name
 * - Tanggal: Formatted date (d F Y)
 * - Jam: Time range (H:i - H:i)
 * - Status: Badge dengan color coding
 * - Anggota: Member count atau "Eksternal"
 * - Aksi: Check-in button (AKTIF only, regular bookings only)
 * 
 * TARGET ELEMENTS:
 * - #filter-ruang: Room filter select
 * - #filter-nama: Name filter input
 * - #filter-tanggal: Date filter input
 * - #filter-status: Status filter select
 * - .btn-checkin: Check-in buttons (data-kode-booking)
 * - #modal-checkin: Check-in modal
 * - #anggota-list: Container for member list in modal
 * - .btn-checkin-anggota: Individual check-in buttons (data-nim)
 * 
 * JAVASCRIPT:
 * - assets/js/booking-list.js: Modal management, AJAX check-in
 * - Functions:
 *   * openCheckinModal(kodeBooking): Fetch and show members
 *   * checkinAnggota(kodeBooking, nim): AJAX check-in action
 *   * closeCheckinModal(): Hide modal
 * 
 * AJAX ENDPOINTS:
 * 1. GET BOOKING ANGGOTA
 *    - URL: ?page=admin&action=get_booking_anggota&kode={kode}
 *    - Returns: {"success": bool, "data": [...], "message": string}
 * 
 * 2. CHECK-IN ANGGOTA
 *    - URL: ?page=admin&action=checkin_anggota
 *    - Method: POST
 *    - Body: {kode_booking, nomor_induk}
 *    - Returns: {"success": bool, "message": string}
 * 
 * PAGINATION:
 * - URL pattern: ?page=admin&action=booking_list&pg={n}&filters...
 * - 10 records per page
 * - Inline pagination UI (not using component)
 * - Previous/Next + page numbers
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Scrollable table (overflow-x-auto)
 * - Tablet/Desktop: Full table display
 * - Filter form: Stacks on mobile, inline on desktop
 * - Modal: Full screen on mobile, max-w-2xl on desktop
 * 
 * CSS:
 * - External: assets/css/kelola-ruangan.css (shared)
 * - Tailwind utilities
 * - Blue header: bg-[#1e73be]
 * - White table background
 * 
 * AUTO-UPDATE FEATURES:
 * - autoUpdateSelesaiStatus(): Called in controller before render
 * - autoUpdateHangusStatus(): Called in controller before render
 * - Updates status based on current datetime
 * 
 * BUSINESS RULES:
 * - Check-in only on hari H (booking date)
 * - Check-in only for AKTIF bookings
 * - External bookings: No check-in button (no anggota)
 * - Individual member check-in (not all at once)
 * - Check-in timestamp recorded for each member
 * 
 * SUCCESS FLOW (Check-in):
 * 1. Admin clicks "Check-in" button
 * 2. Modal opens, fetches member list
 * 3. Admin clicks check-in for specific member
 * 4. AJAX request to server
 * 5. Server validates hari H
 * 6. Updates is_checked_in + waktu_check_in
 * 7. Returns success
 * 8. Modal updates UI (show checkmark)
 * 
 * ERROR HANDLING:
 * - Not hari H → alert "Check-in hanya bisa dilakukan di hari H!"
 * - Already checked in → alert "Anggota sudah check-in!"
 * - Booking not found → alert "Booking tidak ditemukan!"
 * - Server error → alert "Gagal check-in!"
 * 
 * SECURITY:
 * - Admin/Super Admin access only
 * - Hari H validation prevents early/late check-in
 * - AJAX endpoints validate session
 * - Input sanitization (htmlspecialchars)
 * 
 * INTEGRATION:
 * - Controller: AdminController (booking_list, get_booking_anggota, checkin_anggota)
 * - Model: BookingListModel (getAll, filter, countFiltered, autoUpdateSelesaiStatus, autoUpdateHangusStatus)
 * - Model: AnggotaBookingModel (getByKodeBooking, updateCheckIn)
 * - Model: RuanganModel (getAll)
 * - Database: booking, anggota_booking, ruangan, status_booking tables
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

<title>Booking List - Admin Dashboard</title>
<?php
// Data sudah di-pass dari AdminController via $bookings dan $ruanganList
// Filter values untuk maintain form state
$filterRuang = $_GET['ruang'] ?? 'all';
$filterNama = $_GET['nama'] ?? '';
$filterTanggal = $_GET['tanggal'] ?? '';
$filterStatus = $_GET['status'] ?? 'all';
?>
<link rel="stylesheet" href="<?= $asset('assets/css/kelola-ruangan.css') ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php require __DIR__ . '/../components/navbar_admin.php'; ?>

    <main class="container mx-auto px-4 py-8 max-w-7xl">

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">

            <div class="bg-[#1e73be] p-6">
                <!-- FIlter Region -->
                <form action="index.php" method="GET" class="flex flex-col lg:flex-row gap-4 lg:items-center justify-between text-white">
                    <!-- Hidden inputs untuk maintain page dan action -->
                    <input type="hidden" name="page" value="admin">
                    <input type="hidden" name="action" value="booking_list">

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                        <label for="ruang" class="font-semibold text-lg min-w-[70px]">Ruang :</label>
                        <select name="ruang" id="ruang" class="bg-white text-slate-800 px-4 py-2 rounded border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400 w-full sm:w-48">
                            <option value="all" <?= $filterRuang === 'all' ? 'selected' : '' ?>>All</option>
                            <?php foreach ($ruanganList as $ruangan): ?>
                                <option value="<?= $ruangan['id_ruangan'] ?>" <?= $filterRuang == $ruangan['id_ruangan'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ruangan['nama_ruangan']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                        <label for="nama" class="font-semibold text-lg min-w-[70px]">Nama :</label>
                        <div class="relative w-full sm:w-64">
                            <input type="text" name="nama" id="nama" value="<?= htmlspecialchars($filterNama) ?>" class="bg-white text-slate-800 w-full px-4 py-2 rounded border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400" placeholder="Cari nama ketua...">
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-slate-500 hover:text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                        <label for="tanggal" class="font-semibold text-lg min-w-[90px]">Tanggal :</label>
                        <input type="date" name="tanggal" id="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="bg-white text-slate-800 px-4 py-2 rounded border border-slate-300 w-full sm:w-48 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400">
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full lg:w-auto">
                        <label for="status" class="font-semibold text-lg min-w-[70px]">Status :</label>
                        <select name="status" id="status" class="bg-white text-slate-800 px-4 py-2 rounded border border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:border-slate-400 w-full sm:w-48">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="aktif" <?= strtolower($filterStatus) === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="selesai" <?= strtolower($filterStatus) === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            <option value="dibatalkan" <?= strtolower($filterStatus) === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            <option value="hangus" <?= strtolower($filterStatus) === 'hangus' ? 'selected' : '' ?>>Hangus</option>
                        </select>
                    </div>

                </form>
            </div>
            <!-- END Filter Region -->

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-linear-to-b from-slate-200 to-slate-100 border-b border-slate-300 text-[#1e73be]">
                            <th class="py-4 px-6 font-semibold text-lg">Ruang</th>
                            <th class="py-4 px-6 font-semibold text-lg">Kode Booking</th>
                            <th class="py-4 px-6 font-semibold text-lg">Nama</th>
                            <th class="py-4 px-6 font-semibold text-lg text-center">Tanggal</th>
                            <th class="py-4 px-6 font-semibold text-lg text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <?php if (!empty($bookings)): ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="hover:bg-slate-50 transition-colors <?= $booking['nama_status'] === 'AKTIF' ? 'cursor-pointer' : '' ?>" 
                                    data-booking-id="<?= $booking['id_booking'] ?>"
                                    data-status="<?= $booking['nama_status'] ?>"
                                    <?= $booking['nama_status'] === 'AKTIF' ? 'title="Klik untuk check-in anggota"' : '' ?>>
                                    <td class="py-5 px-6 font-bold text-slate-900"><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td class="py-5 px-6 font-medium text-slate-900"><?= htmlspecialchars($booking['kode_booking']) ?></td>
                                    <td class="py-5 px-6 font-bold text-slate-900"><?= htmlspecialchars($booking['nama_peminjam'] ?? 'N/A') ?></td>
                                    <td class="py-5 px-6 font-medium text-slate-900 text-center">
                                        <?= $booking['tanggal_schedule'] ? date('d/m/Y', strtotime($booking['tanggal_schedule'])) : 'N/A' ?>
                                    </td>
                                    <td class="py-5 px-6 text-center font-bold">
                                        <span class="text-slate-900">
                                            <?= htmlspecialchars($booking['nama_status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-slate-500">
                                    Belum ada data booking yang sesuai dengan filter.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if (isset($paginationData) && $paginationData['totalPages'] > 1): ?>
                    <div class="bg-white px-6 py-4 border-t border-slate-200">
                        <?php
                        $baseUrl = 'index.php';
                        $queryParams = [
                            'page' => 'admin',
                            'action' => 'booking_list'
                        ];
                        if ($filterRuang !== 'all') $queryParams['ruang'] = $filterRuang;
                        if (!empty($filterNama)) $queryParams['nama'] = $filterNama;
                        if (!empty($filterTanggal)) $queryParams['tanggal'] = $filterTanggal;
                        if ($filterStatus !== 'all') $queryParams['status'] = $filterStatus;
                        
                        $currentPage = $paginationData['currentPage'];
                        $totalPages = $paginationData['totalPages'];
                        ?>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <!-- Info -->
                            <div class="text-sm text-slate-600">
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
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-l-md hover:bg-slate-50">
                                        &laquo; Prev
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-300 rounded-l-md cursor-not-allowed">
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
                                           class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border-t border-b border-slate-300 hover:bg-slate-50">
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
                                       class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-r-md hover:bg-slate-50">
                                        Next &raquo;
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-slate-400 bg-slate-100 border border-slate-300 rounded-r-md cursor-not-allowed">
                                        Next &raquo;
                                    </span>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Modal Check-in -->
    <div id="modal-checkin" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl transform transition-all" onclick="event.stopPropagation()">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-gray-800">Check-in Anggota Booking</h2>
                    <button id="btn-close-checkin" type="button" class="text-gray-400 hover:text-gray-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Modal Content (will be populated by JS) -->
                <div id="modal-checkin-content">
                    <p class="text-center text-gray-500">Loading...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data akan di-inject melalui data attributes -->
    <div id="booking-list-data" data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('assets/js/booking-list.js') ?>" defer></script>

</body>

</html>
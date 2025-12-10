<?php
/**
 * ============================================================================
 * ADMIN/LAPORAN.PHP - Booking Reports & Analytics Dashboard
 * ============================================================================
 * 
 * Comprehensive reporting page untuk analisis booking trends dan usage statistics.
 * Multi-period tabs: Harian, Bulanan, Tahunan dengan different data structures.
 * 
 * ACCESS CONTROL:
 * - Admin & Super Admin only
 * - Blocks regular User access
 * 
 * FEATURES:
 * 1. TAB SYSTEM (3 Periods)
 *    - TAB 1: Harian (Daily) - Specific date report
 *    - TAB 2: Bulanan (Monthly) - Month summary report
 *    - TAB 3: Tahunan (Yearly) - Year summary report
 *    - Each tab shows: Filter form + Stats cards + Table + Chart
 * 
 * 2. FILTER FORMS (Period-specific)
 *    - Harian: Date picker (tanggal)
 *    - Bulanan: Month + Year selects
 *    - Tahunan: Year select
 *    - Submit: Reloads with filtered data
 * 
 * 3. STATISTICS CARDS (4 Cards per tab)
 *    - Total Booking: Count of all bookings in period
 *    - Ruangan Terfavorit: Most booked room in period
 *    - Total Durasi: Sum of booking durations (jam:menit)
 *    - Rata-rata Durasi: Average duration per booking
 *    - Color-coded icons (blue, green, purple, orange)
 * 
 * 4. BOOKING TABLE
 *    - Columns vary by period:
 *      * Harian: No, Ruangan, Nama, Jam, Durasi, Status
 *      * Bulanan: No, Ruangan, Total Booking, Total Durasi
 *      * Tahunan: No, Bulan, Total Booking, Total Durasi
 *    - Sorted by appropriate field
 *    - Color-coded status badges (Harian only)
 * 
 * 5. ANALYTICS CHART
 *    - Chart.js bar chart
 *    - Harian: Bookings per room (bar chart)
 *    - Bulanan: Bookings per day (line/bar chart)
 *    - Tahunan: Bookings per month (bar chart)
 *    - Responsive canvas element
 * 
 * DATA FROM CONTROLLER:
 * - $tab (string): Active tab ('harian', 'bulanan', 'tahunan')
 * - $tanggal (string): Selected date for Harian (YYYY-MM-DD)
 * - $bulan (int): Selected month for Bulanan (1-12)
 * - $tahun (int): Selected year for all tabs
 * - $data (array): Booking data for selected period
 * - $stats (array): Statistics cards data
 * - $mostBookedRoom (array): Most booked room info
 * - $availableYears (array): Years with booking data
 * 
 * DATA STRUCTURES:
 * 
 * HARIAN DATA:
 * $harianBooking = [
 *   'kode_booking' => string,
 *   'nama_ruangan' => string,
 *   'ketua_nama' => string|null (regular) OR 'nama_instansi' (external),
 *   'waktu_mulai' => string (HH:MM:SS),
 *   'waktu_selesai' => string (HH:MM:SS),
 *   'durasi_menit' => int,
 *   'nama_status' => string,
 *   'is_external' => int
 * ];
 * 
 * BULANAN DATA:
 * $bulananData = [
 *   'nama_ruangan' => string,
 *   'total_booking' => int,
 *   'total_durasi_menit' => int
 * ];
 * 
 * TAHUNAN DATA:
 * $tahunanData = [
 *   'bulan' => int (1-12),
 *   'nama_bulan' => string (Januari-Desember),
 *   'total_booking' => int,
 *   'total_durasi_menit' => int
 * ];
 * 
 * STATISTICS STRUCTURE:
 * $stats = [
 *   'total_booking' => int,
 *   'total_durasi' => int (menit),
 *   'rata_rata_durasi' => float (menit),
 *   'ruangan_terfavorit' => string|null
 * ];
 * 
 * MOST BOOKED ROOM:
 * $mostBookedRoom = [
 *   'nama_ruangan' => string,
 *   'total_booking' => int
 * ];
 * 
 * HELPER FUNCTIONS:
 * - formatTime($time): Converts "HH:MM:SS" to "HH:MM"
 * - formatDuration($minutes): Converts minutes to "X Jam Y Menit" format
 *   * Pattern: 90 minutes → "1 Jam 30 Menit"
 *   * Pattern: 60 minutes → "1 Jam"
 *   * Pattern: 45 minutes → "45 Menit"
 * 
 * FILTER FORMS:
 * 1. HARIAN FILTER
 *    - Action: ?page=admin&action=laporan&tab=harian
 *    - Field: tanggal (date, default=today)
 *    - Button: "Tampilkan Laporan"
 * 
 * 2. BULANAN FILTER
 *    - Action: ?page=admin&action=laporan&tab=bulanan
 *    - Fields: bulan (select 1-12), tahun (select from available)
 *    - Button: "Tampilkan Laporan"
 * 
 * 3. TAHUNAN FILTER
 *    - Action: ?page=admin&action=laporan&tab=tahunan
 *    - Field: tahun (select from available)
 *    - Button: "Tampilkan Laporan"
 * 
 * STATISTICS CARDS:
 * - Grid: 1 col (mobile), 2 cols (md), 4 cols (lg)
 * - Each card: Icon + Label + Value
 * - Icon colors:
 *   * Total Booking: Blue (bg-blue-100 text-blue-600)
 *   * Ruangan Terfavorit: Green (bg-green-100 text-green-600)
 *   * Total Durasi: Purple (bg-purple-100 text-purple-600)
 *   * Rata-rata Durasi: Orange (bg-orange-100 text-orange-600)
 * - Inline SVG icons (no icon libraries)
 * 
 * TABLE STRUCTURE (Harian):
 * - No: Sequential number (1, 2, 3, ...)
 * - Ruangan: Room name
 * - Nama: Ketua name (regular) or Instansi (external)
 * - Jam: Formatted time range (HH:MM - HH:MM)
 * - Durasi: Formatted duration (X Jam Y Menit)
 * - Status: Badge dengan color coding
 * 
 * TABLE STRUCTURE (Bulanan):
 * - No: Sequential number
 * - Ruangan: Room name
 * - Total Booking: Count of bookings for room
 * - Total Durasi: Sum duration for room (formatted)
 * 
 * TABLE STRUCTURE (Tahunan):
 * - No: Sequential number
 * - Bulan: Month name (Januari - Desember)
 * - Total Booking: Count of bookings in month
 * - Total Durasi: Sum duration in month (formatted)
 * 
 * CHART CONFIGURATIONS:
 * - Library: Chart.js (included via CDN)
 * - Canvas: #bookingChart (responsive)
 * - Chart types:
 *   * Harian: Bar chart (bookings per room)
 *   * Bulanan: Bar chart (bookings per day)
 *   * Tahunan: Bar chart (bookings per month)
 * - Colors: Blue palette (primary color: #1e73be)
 * 
 * CHART DATA PATTERNS:
 * ```javascript
 * // Harian: Bookings per room
 * labels: ['Ruang A', 'Ruang B', ...]
 * data: [5, 3, 7, ...]
 * 
 * // Bulanan: Bookings per day
 * labels: ['1', '2', '3', ..., '31']
 * data: [2, 0, 3, 1, ...]
 * 
 * // Tahunan: Bookings per month
 * labels: ['Jan', 'Feb', 'Mar', ..., 'Dec']
 * data: [15, 20, 18, ...]
 * ```
 * 
 * TARGET ELEMENTS:
 * - [data-tab-action]: Tab buttons (data-tab-name attribute)
 * - .tab-content: Tab content containers
 * - #bookingChart: Chart.js canvas
 * - #laporan-data: Data container (JSON data for JS)
 * 
 * JAVASCRIPT:
 * - assets/js/laporan.js: Tab switching, chart rendering
 * - Chart.js: Chart visualization library
 * - Functions:
 *   * switchTab(tabName): Change active tab
 *   * initChart(tab, data): Render chart based on tab
 * 
 * DATA ATTRIBUTES PATTERN:
 * ```php
 * <div id="laporan-data"
 *      data-tab="<?= $tab ?>"
 *      data-harian='<?= json_encode($harianChartData ?? []) ?>'
 *      data-bulanan='<?= json_encode($bulananChartData ?? []) ?>'
 *      data-tahunan='<?= json_encode($tahunanChartData ?? []) ?>'
 *      style="display:none;">
 * </div>
 * ```
 * 
 * CSS:
 * - External: assets/css/laporan.css
 * - Tailwind utilities
 * - Blue header: bg-[#1e73be]
 * - Card styles: Rounded, shadow, white background
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Single column, stacked cards, scrollable table
 * - Tablet (md): 2-column grid for stats
 * - Desktop (lg): 4-column grid for stats, full table
 * - Chart: Responsive via Chart.js options
 * 
 * TAB SWITCHING:
 * - Active tab: bg-blue-600 text-white
 * - Inactive tab: bg-white text-gray-600 hover:bg-blue-50
 * - JavaScript handles content visibility
 * - URL param: ?tab=harian/bulanan/tahunan
 * 
 * EMPTY STATE:
 * - No data message: "Tidak ada data booking untuk periode ini"
 * - Shows when: $data is empty array
 * - Stats cards show zeros
 * - Chart shows empty state
 * 
 * ROUTING:
 * - View: ?page=admin&action=laporan
 * - Harian filter: ?page=admin&action=laporan&tab=harian&tanggal={date}
 * - Bulanan filter: ?page=admin&action=laporan&tab=bulanan&bulan={m}&tahun={y}
 * - Tahunan filter: ?page=admin&action=laporan&tab=tahunan&tahun={y}
 * 
 * BUSINESS RULES:
 * - Include SELESAI bookings only (exclude DIBATALKAN, HANGUS)
 * - Duration calculation: waktu_selesai - waktu_mulai
 * - Most booked room: Room dengan highest booking count
 * - Average duration: Total duration / Total bookings
 * - External bookings: Counted and included in statistics
 * 
 * QUERY OPTIMIZATION:
 * - Use date range filters in SQL
 * - Aggregate functions: COUNT(), SUM(), AVG()
 * - Group by: Room (Bulanan), Month (Tahunan)
 * - Index suggestions: tanggal, id_status, id_ruangan
 * 
 * SUCCESS FLOW:
 * 1. Admin navigates to Laporan page
 * 2. Default: Current month (Bulanan tab)
 * 3. Admin selects tab (Harian/Bulanan/Tahunan)
 * 4. Admin adjusts filter (date/month/year)
 * 5. Clicks "Tampilkan Laporan"
 * 6. Page reloads with filtered data
 * 7. Stats cards update
 * 8. Table updates
 * 9. Chart re-renders
 * 
 * ERROR HANDLING:
 * - Invalid date → Use today's date
 * - Invalid month → Use current month
 * - Invalid year → Use current year
 * - No data → Show empty state message
 * - Chart error → Fallback to table only
 * 
 * SECURITY:
 * - Admin/Super Admin access only
 * - Date input sanitization
 * - XSS prevention: htmlspecialchars on outputs
 * - No sensitive user data exposed
 * 
 * INTEGRATION:
 * - Controller: AdminController (laporan action)
 * - Model: LaporanModel (getHarian, getBulanan, getTahunan, getStats, getMostBooked)
 * - Database: booking, ruangan, status_booking tables
 * - Chart.js: External CDN library
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

// Data sudah dikirim dari controller:
// $tab, $tanggal, $bulan, $tahun, $data, $stats, $mostBookedRoom, $availableYears

// Helper untuk format waktu
function formatTime($time) {
    return date('H:i', strtotime($time));
}

// Helper untuk format durasi (dari menit ke jam:menit)
function formatDuration($minutes) {
    if (!$minutes) return '0 menit';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0 && $mins > 0) {
        return "{$hours} Jam {$mins} Menit";
    } elseif ($hours > 0) {
        return "{$hours} Jam";
    } else {
        return "{$mins} Menit";
    }
}
?>

<title>Laporan Peminjaman - Admin Dashboard</title>
<link rel="stylesheet" href="<?= $asset('assets/css/laporan.css') ?>">
</head>

<body class="bg-slate-50 min-h-screen text-slate-800">

    <?php require __DIR__ . '/../components/navbar_admin.php'; ?>

    <main class="container mx-auto px-4 py-8 max-w-7xl">

        <div id="content-harian" class="tab-content active animate-fade-in">
            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                <!-- Tab Navigation -->
                <div class="border-b border-slate-200">
                    <div class="flex overflow-x-auto">
                        <button data-laporan-tab data-tab-name="harian" id="btn-harian" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button data-laporan-tab data-tab-name="mingguan" id="btn-mingguan" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button data-laporan-tab data-tab-name="bulanan" id="btn-bulanan" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button data-laporan-tab data-tab-name="tahunan" id="btn-tahunan" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Tahunan
                        </button>
                    </div>
                </div>

                <!-- Date Picker & Download -->
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <label class="font-bold text-slate-900 whitespace-nowrap">Pilih Tanggal:</label>
                            <input type="date" id="filter-tanggal" value="<?= $tanggal ?>" class="border border-slate-300 rounded px-3 py-1.5 text-sm w-full md:w-48 focus:outline-none focus:border-sky-500">
                        </div>
                        <button data-download-laporan data-periode="harian" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
                            <span>Unduh</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Booking Harian</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['total_booking'] ?? 0 ?></p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Ruangan Paling Sering Dibooking</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $mostBookedRoom ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-slate-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#1e73be] text-white">
                        <tr>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Ruangan</th>
                            <th class="py-4 px-6 font-semibold text-sm">Waktu Booking</th>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Peminjam</th>
                            <th class="py-4 px-6 font-semibold text-sm text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="4" class="py-8 px-6 text-center text-slate-500">
                                    Tidak ada data booking untuk tanggal ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $booking): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6 font-bold text-slate-800 text-sm"><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= formatTime($booking['waktu_mulai']) ?> - <?= formatTime($booking['waktu_selesai']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= htmlspecialchars($booking['nama_peminjam']) ?></td>
                                    <td class="py-4 px-6 text-center text-sm">
                                        <?php 
                                        $statusClass = '';
                                        switch($booking['nama_status']) {
                                            case 'SELESAI': $statusClass = 'text-green-500'; break;
                                            case 'AKTIF': $statusClass = 'text-blue-500'; break;
                                            case 'DIBATALKAN': $statusClass = 'text-red-500'; break;
                                            case 'HANGUS': $statusClass = 'text-orange-500'; break;
                                            default: $statusClass = 'text-slate-500';
                                        }
                                        ?>
                                        <span class="<?= $statusClass ?> font-medium"><?= htmlspecialchars($booking['nama_status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                </div>
            </div>
        </div>

        <div id="content-mingguan" class="tab-content animate-fade-in">
            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                <!-- Tab Navigation -->
                <div class="border-b border-slate-200">
                    <div class="flex overflow-x-auto">
                        <button data-laporan-tab data-tab-name="harian" id="btn-harian-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button data-laporan-tab data-tab-name="mingguan" id="btn-mingguan-2" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button data-laporan-tab data-tab-name="bulanan" id="btn-bulanan-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button data-laporan-tab data-tab-name="tahunan" id="btn-tahunan-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Tahunan
                        </button>
                    </div>
                </div>

                <!-- Date Picker & Download -->
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <label class="font-bold text-slate-900 whitespace-nowrap">Pilih Tanggal:</label>
                            <input type="date" id="filter-tanggal-mingguan" value="<?= $tanggal ?>" class="border border-slate-300 rounded px-3 py-1.5 text-sm w-full md:w-48 focus:outline-none focus:border-sky-500">
                        </div>
                        <button data-download-laporan data-periode="mingguan" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
                            <span>Unduh</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Booking Mingguan</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['total_booking'] ?? 0 ?></p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Ruangan Paling Sering Dibooking</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $mostBookedRoom ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-slate-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#1e73be] text-white">
                        <tr>
                            <th class="py-4 px-6 font-semibold text-sm">Tanggal</th>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Ruangan</th>
                            <th class="py-4 px-6 font-semibold text-sm">Waktu Booking</th>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Peminjam</th>
                            <th class="py-4 px-6 font-semibold text-sm text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-6 text-center text-slate-500">
                                    Tidak ada data booking untuk minggu ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $booking): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6 font-bold text-slate-800 text-sm"><?= date('d M Y', strtotime($booking['tanggal_schedule'])) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= formatTime($booking['waktu_mulai']) ?> - <?= formatTime($booking['waktu_selesai']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= htmlspecialchars($booking['nama_peminjam']) ?></td>
                                    <td class="py-4 px-6 text-center text-sm">
                                        <?php 
                                        $statusClass = '';
                                        switch($booking['nama_status']) {
                                            case 'SELESAI': $statusClass = 'text-green-500'; break;
                                            case 'AKTIF': $statusClass = 'text-blue-500'; break;
                                            case 'DIBATALKAN': $statusClass = 'text-red-500'; break;
                                            case 'HANGUS': $statusClass = 'text-orange-500'; break;
                                            default: $statusClass = 'text-slate-500';
                                        }
                                        ?>
                                        <span class="<?= $statusClass ?> font-medium"><?= htmlspecialchars($booking['nama_status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                </div>
            </div>
        </div>

        <div id="content-bulanan" class="tab-content animate-fade-in">
            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                <!-- Tab Navigation -->
                <div class="border-b border-slate-200">
                    <div class="flex overflow-x-auto">
                        <button data-laporan-tab data-tab-name="harian" id="btn-harian-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button data-laporan-tab data-tab-name="mingguan" id="btn-mingguan-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button data-laporan-tab data-tab-name="bulanan" id="btn-bulanan-3" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button data-laporan-tab data-tab-name="tahunan" id="btn-tahunan-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Tahunan
                        </button>
                    </div>
                </div>

                <!-- Date Picker & Download -->
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <label class="font-bold text-slate-900 whitespace-nowrap">Pilih Bulan & Tahun:</label>
                            <select id="filter-bulan" class="border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-sky-500">
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $m == $bulan ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
                                <?php endfor; ?>
                            </select>
                            <select id="filter-tahun-bulanan" class="border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-sky-500">
                                <?php foreach($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $tahun ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button data-download-laporan data-periode="bulanan" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
                            <span>Unduh</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Booking Bulanan</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['total_booking'] ?? 0 ?></p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Ruangan Paling Sering Dibooking</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $mostBookedRoom ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-slate-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#1e73be] text-white">
                        <tr>
                            <th class="py-4 px-6 font-semibold text-sm">Tanggal</th>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Ruangan</th>
                            <th class="py-4 px-6 font-semibold text-sm">Waktu Booking</th>
                            <th class="py-4 px-6 font-semibold text-sm">Nama Peminjam</th>
                            <th class="py-4 px-6 font-semibold text-sm text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="5" class="py-8 px-6 text-center text-slate-500">
                                    Tidak ada data booking untuk bulan ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $booking): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6 font-bold text-slate-800 text-sm"><?= date('d M Y', strtotime($booking['tanggal_schedule'])) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= htmlspecialchars($booking['nama_ruangan']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= formatTime($booking['waktu_mulai']) ?> - <?= formatTime($booking['waktu_selesai']) ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm"><?= htmlspecialchars($booking['nama_peminjam']) ?></td>
                                    <td class="py-4 px-6 text-center text-sm">
                                        <?php 
                                        $statusClass = '';
                                        switch($booking['nama_status']) {
                                            case 'SELESAI': $statusClass = 'text-green-500'; break;
                                            case 'AKTIF': $statusClass = 'text-blue-500'; break;
                                            case 'DIBATALKAN': $statusClass = 'text-red-500'; break;
                                            case 'HANGUS': $statusClass = 'text-orange-500'; break;
                                            default: $statusClass = 'text-slate-500';
                                        }
                                        ?>
                                        <span class="<?= $statusClass ?> font-medium"><?= htmlspecialchars($booking['nama_status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                </div>
            </div>
        </div>

        <div id="content-tahunan" class="tab-content animate-fade-in">
            <div class="bg-white shadow-sm rounded-lg border border-slate-200">
                <!-- Tab Navigation -->
                <div class="border-b border-slate-200">
                    <div class="flex overflow-x-auto">
                        <button data-laporan-tab data-tab-name="harian" id="btn-harian-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button data-laporan-tab data-tab-name="mingguan" id="btn-mingguan-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button data-laporan-tab data-tab-name="bulanan" id="btn-bulanan-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button data-laporan-tab data-tab-name="tahunan" id="btn-tahunan-4" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Tahunan
                        </button>
                    </div>
                </div>

                <!-- Date Picker & Download -->
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <label class="font-bold text-slate-900 whitespace-nowrap">Pilih Tahun:</label>
                            <select id="filter-tahun-tahunan" class="border border-slate-300 rounded px-3 py-1.5 text-sm w-full md:w-48 focus:outline-none focus:border-sky-500">
                                <?php foreach($availableYears as $year): ?>
                                    <option value="<?= $year ?>" <?= $year == $tahun ? 'selected' : '' ?>><?= $year ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button data-download-laporan data-periode="tahunan" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
                            <span>Unduh</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Total Booking Tahunan</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $stats['total_booking'] ?? 0 ?></p>
                        </div>
                    </div>
                    <div class="bg-slate-50 p-6 rounded-lg border border-slate-200 flex items-center gap-4">
                        <div class="p-3 bg-white rounded-lg border border-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500 font-medium">Ruangan Paling Sering Dibooking</p>
                            <p class="text-2xl font-bold text-slate-800"><?= $mostBookedRoom ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden border border-slate-200 rounded-lg">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#1e73be] text-white">
                        <tr>
                            <th class="py-4 px-6 font-semibold text-sm">Bulan</th>
                            <th class="py-4 px-6 font-semibold text-sm text-center">Total Booking</th>
                            <th class="py-4 px-6 font-semibold text-sm text-center">Total Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="3" class="py-8 px-6 text-center text-slate-500">
                                    Tidak ada data booking untuk tahun ini
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data as $monthly): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6 font-bold text-slate-800 text-sm"><?= $monthly['nama_bulan'] ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm text-center"><?= $monthly['jumlah_booking'] ?></td>
                                    <td class="py-4 px-6 text-slate-600 text-sm text-center"><?= formatDuration($monthly['total_durasi']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Data akan di-inject melalui data attributes -->
    <div id="laporan-data" data-base-path="<?= $basePath ?>" style="display:none;"></div>
    <script src="<?= $asset('assets/js/laporan.js') ?>" defer></script>
</body>

</html>
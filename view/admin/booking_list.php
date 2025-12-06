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

    <script>
        // Expose asset base path to external scripts
        window.ASSET_BASE_PATH = '<?= $basePath ?>';
    </script>
    <script src="<?= $asset('assets/js/booking-list.js') ?>" defer></script>

</body>

</html>
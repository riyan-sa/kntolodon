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
                        <button onclick="gantiTabLaporan('harian')" id="btn-harian-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button onclick="gantiTabLaporan('mingguan')" id="btn-mingguan-2" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button onclick="gantiTabLaporan('bulanan')" id="btn-bulanan-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button onclick="gantiTabLaporan('tahunan')" id="btn-tahunan-2" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
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
                        <button onclick="downloadLaporan('mingguan')" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
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
                        <button onclick="gantiTabLaporan('harian')" id="btn-harian-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button onclick="gantiTabLaporan('mingguan')" id="btn-mingguan-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button onclick="gantiTabLaporan('bulanan')" id="btn-bulanan-3" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button onclick="gantiTabLaporan('tahunan')" id="btn-tahunan-3" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
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
                        <button onclick="downloadLaporan('bulanan')" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
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
                        <button onclick="gantiTabLaporan('harian')" id="btn-harian-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Harian
                        </button>
                        <button onclick="gantiTabLaporan('mingguan')" id="btn-mingguan-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Mingguan
                        </button>
                        <button onclick="gantiTabLaporan('bulanan')" id="btn-bulanan-4" class="tab-btn flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
                            Bulanan
                        </button>
                        <button onclick="gantiTabLaporan('tahunan')" id="btn-tahunan-4" class="tab-btn active flex-1 px-8 py-4 text-sm font-medium text-slate-500 hover:text-sky-600 transition-colors border-b-2 border-transparent">
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
                        <button onclick="downloadLaporan('tahunan')" class="flex items-center gap-2 text-green-600 hover:text-green-700 font-semibold transition-colors">
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

    <script>
        // Expose asset base path untuk external scripts
        window.ASSET_BASE_PATH = '<?= $basePath ?>';
    </script>
    <script src="<?= $asset('assets/js/laporan.js') ?>" defer></script>
</body>

</html>
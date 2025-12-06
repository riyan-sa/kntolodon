<?php
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
    <title>BookEZ - Kode Booking</title>
    <link rel="stylesheet" href="<?= $asset('assets/css/booking.css') ?>">
</head>
<body class="h-screen w-full flex items-center justify-center bg-gray-50">

    <div class="bg-white w-full max-w-[450px] rounded-xl shadow-2xl border border-gray-100 p-6 relative mx-4">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-bold text-gray-800">Kode Booking Anda</h2>
            <button data-action="back-to-dashboard" class="text-gray-500 hover:text-gray-800 transition">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>

        <div class="flex flex-col items-center">
            
            <h3 class="text-xl font-bold text-gray-800 mb-4"><?= htmlspecialchars($booking['nama_ruangan']) ?></h3>

            <div class="p-2 border-2 border-black mb-6">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($booking['kode_booking'] . '-BookEZ') ?>" 
                     alt="QR Code" 
                     class="w-40 h-40 object-contain">
            </div>

            <div class="w-full bg-white border border-gray-200 rounded-lg shadow-sm p-3 flex justify-between items-center mb-6 hover:border-sky-200 transition group cursor-pointer" data-action="copy-code">
                <span id="bookingCode" class="text-2xl font-bold text-gray-800 tracking-wide pl-2"><?= htmlspecialchars($booking['kode_booking']) ?></span>
                
                <button type="button" class="text-gray-500 group-hover:text-sky-600 transition px-2" title="Salin Kode">
                    <i class="fa-regular fa-copy text-lg" id="copyIcon"></i>
                </button>
            </div>

            <div class="w-full space-y-3 text-gray-600 text-sm px-2">
                <div class="flex items-center gap-3">
                    <i class="fa-regular fa-calendar text-gray-400 w-5 text-center"></i>
                    <span>Tanggal : <?= date('d F Y', strtotime($booking['tanggal_schedule'])) ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fa-regular fa-clock text-gray-400 w-5 text-center"></i>
                    <span>Jam : <?= date('H:i', strtotime($booking['waktu_mulai'])) ?> - <?= date('H:i', strtotime($booking['waktu_selesai'])) ?></span>
                </div>
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-info text-gray-400 w-5 text-center"></i>
                    <span>Status : <span class="font-semibold <?= $booking['nama_status'] === 'AKTIF' ? 'text-green-600' : ($booking['nama_status'] === 'SELESAI' ? 'text-blue-600' : 'text-gray-600') ?>"><?= $booking['nama_status'] ?></span></span>
                </div>
            </div>

        </div>

        <div class="mt-10">
            <button data-action="back-to-dashboard" class="w-full bg-white text-sky-600 font-medium py-2.5 rounded shadow-[0_2px_8px_rgba(0,0,0,0.1)] hover:shadow-md border border-gray-100 transition text-sm">
                Tutup
            </button>
        </div>

    </div>

    <script src="<?= $asset('assets/js/booking.js') ?>" defer></script>

</body>
</html>
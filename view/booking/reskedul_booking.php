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
<title>BookEZ - Pilih Jadwal</title>
<link rel="stylesheet" href="<?= $asset('assets/css/booking.css') ?>">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <!-- Modal Container -->
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full border border-gray-200 overflow-hidden">

        <!-- Header Modal -->
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-bold text-gray-800">Pilih Tanggal, dan Jam Booking</h2>

            <!-- Tombol Close (X) -->
            <!-- Mengarah ke ?page=profile sesuai permintaan -->
            <a href="?page=profile" class="text-gray-500 hover:text-gray-700 transition-colors focus:outline-none p-1 rounded-full hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </div>

        <!-- Body Modal -->
        <form method="POST" action="?page=booking&action=reschedule&id=<?= $_GET['id'] ?? '' ?>" class="p-8 space-y-6">

            <!-- Input Tanggal -->
            <div>
                <label class="block text-lg font-bold text-gray-800 mb-2">Tanggal :</label>
                <input type="date" name="tanggal" required
                    min="<?= date('Y-m-d') ?>"
                    value="<?= $booking['tanggal_schedule'] ?? '' ?>"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-600">
            </div>

            <!-- Input Jam -->
            <div>
                <label class="block text-lg font-bold text-gray-800 mb-2">Jam :</label>
                <div class="flex items-center gap-3">
                    <!-- Jam Mulai -->
                    <input type="time" name="waktu_mulai" required
                        value="<?= $booking['waktu_mulai'] ?? '' ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-full text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-600">

                    <span class="text-xl font-bold text-gray-800">-</span>

                    <!-- Jam Selesai -->
                    <input type="time" name="waktu_selesai" required
                        value="<?= $booking['waktu_selesai'] ?? '' ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-full text-center focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-600">
                </div>
            </div>

            <!-- Input Alasan (Optional) -->
            <div>
                <label class="block text-lg font-bold text-gray-800 mb-2">Alasan Reschedule (opsional) :</label>
                <textarea name="alasan_reschedule" rows="3"
                    placeholder="Jelaskan alasan reschedule..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-gray-600"></textarea>
            </div>

            <!-- Tombol Booking -->
            <div class="pt-4 flex justify-center">
                <button type="submit" class="bg-white text-blue-600 border border-gray-200 font-semibold py-2 px-12 rounded shadow-md hover:shadow-lg hover:bg-gray-50 transition-all transform active:scale-95">
                    Reschedule
                </button>
            </div>

        </form>
    </div>

</body>

</html>
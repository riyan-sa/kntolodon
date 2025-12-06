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

<title>BookEZ - Hapus Booking</title>
<link rel="stylesheet" href="<?= $asset('assets/css/profile.css') ?>">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <!-- Modal Container -->
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full text-center relative border border-gray-100">

        <!-- Icon Sampah -->
        <div class="mx-auto mb-6 w-20 h-20 rounded-full border-2 border-red-500 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </div>

        <!-- Judul -->
        <h2 class="text-xl font-bold text-gray-900 mb-2">Hapus Booking</h2>

        <!-- Deskripsi -->
        <p class="text-gray-600 mb-8 text-sm leading-relaxed px-4">
            Apakah Anda yakin ingin menghapus pemesanan Ini? Tindakan ini tidak dapat diurungkan.
        </p>

        <!-- Form Hapus -->
        <form method="POST" action="?page=booking&action=hapus_booking&id=<?= $_GET['id'] ?? '' ?>">
            <!-- Tombol Aksi -->
            <div class="flex gap-4 justify-center">
                <!-- Tombol Batal (Link ke Profile) -->
                <a href="?page=profile" class="w-1/2 py-2.5 px-4 bg-white border border-gray-200 rounded shadow-sm text-blue-600 text-sm font-semibold hover:bg-gray-50 hover:shadow-md transition-all flex items-center justify-center decoration-transparent">
                    Batal
                </a>

                <!-- Tombol Hapus (Konfirmasi Hapus) -->
                <button type="submit" class="w-1/2 py-2.5 px-4 bg-[#D50000] rounded text-white text-sm font-semibold hover:bg-red-700 transition-colors shadow-md hover:shadow-lg">
                    Hapus
                </button>
            </div>
        </form>

    </div>

</body>

</html>
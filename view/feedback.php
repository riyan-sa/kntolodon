<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/components/head.php';
?>

<title>Feedback - BookEZ</title>
<link rel="stylesheet" href="<?= $asset('assets/css/dashboard.css') ?>">
</head>

<body class="text-gray-800 min-h-screen flex flex-col bg-gray-50">

    <!-- NAVBAR -->
    <nav class="bg-white px-6 py-4 flex justify-between items-center shadow-sm sticky top-0 z-50">
        <a href="?page=dashboard" class="flex items-center">
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

    <main class="grow flex items-center justify-center px-6">
        <div class="flex-col flex items-center justify-center text-center max-w-5xl w-full">
            
            <!-- Teks Pesan -->
            <h1 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-12 leading-relaxed">
                Terima Kasih sudah menggunakan layanan kami, silahkan tunggu esok hari untuk meminjam kembali
            </h1>

            <!-- Ikon Reaksi -->
            <div class="flex items-center justify-center gap-10 mb-12">
                
                <!-- Ikon Senyum (Hijau) - Rating 5 -->
                <button type="button" class="rating-btn group focus:outline-none transform active:scale-95 transition-transform" data-rating="5">
                    <!-- Lingkaran Hijau -->
                    <div class="w-24 h-24 bg-[#00C853] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:bg-[#00E676] transition-all">
                        <!-- Wajah -->
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Mata -->
                            <circle cx="8" cy="9" r="1.5" fill="white"/>
                            <circle cx="16" cy="9" r="1.5" fill="white"/>
                            <!-- Mulut Senyum -->
                            <path d="M7 14C8.5 16.5 11 17 12 17C13 17 15.5 16.5 17 14" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>

                <!-- Ikon Sedih (Merah) - Rating 1 -->
                <button type="button" class="rating-btn group focus:outline-none transform active:scale-95 transition-transform" data-rating="1">
                    <!-- Lingkaran Merah -->
                    <div class="w-24 h-24 bg-[#D50000] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:bg-[#FF1744] transition-all">
                        <!-- Wajah -->
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <!-- Mata -->
                            <circle cx="8" cy="9" r="1.5" fill="white"/>
                            <circle cx="16" cy="9" r="1.5" fill="white"/>
                            <!-- Mulut Sedih (Kebalikan Senyum) -->
                            <path d="M7 16C8.5 13.5 11 13 12 13C13 13 15.5 13.5 17 16" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </button>
            </div>

            <!-- Form Kritik dan Saran -->
            <form method="POST" action="?page=dashboard&action=submit_feedback" class="w-full max-w-2xl" id="feedback-form">
                <input type="hidden" name="id_booking" value="<?= htmlspecialchars($_SESSION['feedback_booking_id']) ?>">
                <input type="hidden" name="rating" id="rating-input" value="">
                
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Masukan Kritik dan Saran</h2>
                <textarea 
                    name="kritik_saran" 
                    id="kritik-saran" 
                    rows="5" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 resize-none text-gray-700"
                    placeholder="Tulis kritik dan saran Anda di sini... (opsional)"></textarea>
                
                <button 
                    type="submit" 
                    id="submit-btn"
                    disabled
                    class="mt-4 bg-gray-300 text-gray-500 border-2 border-gray-300 font-semibold py-3 px-8 rounded-lg cursor-not-allowed transition-colors shadow-md disabled:opacity-50">
                    Kirim Umpan Balik
                </button>
            </form>

        </div>
    </main>

    <script src="<?= $asset('assets/js/feedback.js') ?>" defer></script>

</body>
</html>

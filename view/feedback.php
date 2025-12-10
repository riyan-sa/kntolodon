<?php
/**
 * ============================================================================
 * FEEDBACK.PHP - Post-Booking Feedback View
 * ============================================================================
 * 
 * Halaman feedback setelah user menyelesaikan booking.
 * User diminta untuk rate experience dengan 3 pilihan emoji:
 * - Senyum (Rating 5) = Sangat Puas (Hijau)
 * - Netral (Rating 3) = Tidak ditampilkan (simplified to binary)
 * - Sedih (Rating 1) = Tidak Puas (Merah)
 * 
 * NOTE: Current implementation ONLY shows 2 options (Senyum & Sedih)
 *       untuk simplified feedback (5 or 1, no neutral)
 * 
 * FITUR:
 * 1. THANK YOU MESSAGE
 *    - Text: "Terima Kasih sudah menggunakan layanan kami..."
 *    - Informasi: User harus tunggu esok hari untuk booking lagi
 *    - Explanation: One active booking per user rule
 * 
 * 2. RATING SELECTION (Binary: 5 or 1)
 *    - Senyum (Hijau) → Rating 5: Sangat puas dengan layanan
 *    - Sedih (Merah) → Rating 1: Tidak puas dengan layanan
 *    - Click emoji → Visual feedback (scale animation)
 *    - Selected rating stored for form submission
 * 
 * 3. KRITIK DAN SARAN
 *    - Textarea: User dapat memberikan feedback text
 *    - Field: kritik_saran (required)
 *    - Placeholder: "Tulis kritik dan saran Anda di sini..."
 *    - Max length: No explicit limit (database TEXT type)
 * 
 * 4. FORM SUBMISSION
 *    - Hidden field: skala_kepuasan (populated by JavaScript)
 *    - Hidden field: id_booking (from session or controller)
 *    - Textarea: kritik_saran (required)
 *    - Submit button: "Kirim Feedback"
 *    - Action: index.php?page=feedback&action=submit (PLANNED)
 *    - Method: POST
 * 
 * FORM FLOW:
 * - User finishes booking → Dashboard "Selesai" button
 * - Redirect to feedback page
 * - Select rating (Senyum or Sedih)
 * - Write kritik_saran (required)
 * - Submit → FeedbackModel::create()
 * - Redirect to dashboard with success message
 * 
 * RATING VALUES:
 * - Rating 5 (Senyum): Excellent experience
 * - Rating 3 (Netral): NOT USED in current implementation
 * - Rating 1 (Sedih): Poor experience
 * - Stored in feedback.skala_kepuasan (TINYINT)
 * 
 * TARGET ELEMENTS:
 * - .rating-btn: Rating buttons (data-rating attribute)
 * - #skala_kepuasan: Hidden input untuk rating value
 * - #kritik_saran: Textarea untuk feedback text
 * - #feedback-form: Form element
 * 
 * JAVASCRIPT:
 * - assets/js/feedback.js: Rating selection and validation
 * - Visual feedback: Scale animation on click
 * - Form validation: Ensure rating selected before submit
 * 
 * DATA ATTRIBUTES:
 * - data-rating: Rating value (5 or 1)
 * - Used by JavaScript untuk populate hidden field
 * 
 * DATABASE STRUCTURE (feedback table):
 * - id: Auto-increment primary key
 * - id_booking: Foreign key to booking table
 * - skala_kepuasan: TINYINT (1 or 5)
 * - kritik_saran: TEXT (required, gabungan kritik dan saran)
 * - created_at: DATETIME (auto)
 * 
 * BUSINESS RULES:
 * - Feedback collection ONLY after booking status = SELESAI
 * - One feedback per booking (enforced by unique id_booking)
 * - Rating is required (JavaScript validates before submit)
 * - kritik_saran is required (server-side validation)
 * 
 * EMOJI ICONS:
 * - Senyum: SVG emoji (green circle, smiling face)
 * - Sedih: SVG emoji (red circle, sad face)
 * - Size: 24x24 (w-24 h-24)
 * - Hover effect: Shadow and background color change
 * 
 * LAYOUT STRUCTURE:
 * - Navbar: Same as dashboard (logo + profile)
 * - Main content: Centered vertically and horizontally
 *   - Thank you message
 *   - Rating emoji buttons (flex gap-10)
 *   - Kritik saran textarea
 *   - Submit button
 * 
 * RESPONSIVE DESIGN:
 * - Text size: 2xl mobile, 3xl tablet/desktop
 * - Emoji size: Consistent 24x24 (w-24 h-24)
 * - Content max-width: max-w-5xl
 * - Padding: px-6 for mobile safety
 * 
 * STYLING:
 * - Rating buttons:
 *   * Default: Shadow-lg
 *   * Hover: Shadow-xl + background lightens
 *   * Active: Scale-95 (transform)
 * - Textarea:
 *   * Border: Gray-300
 *   * Focus: Blue-500 border
 *   * Resize: Vertical only
 * - Submit button:
 *   * Primary blue (bg-blue-600)
 *   * Hover: bg-blue-700
 *   * Full width on mobile
 * 
 * ERROR HANDLING:
 * - No rating selected → JavaScript alert "Pilih rating terlebih dahulu!"
 * - Empty kritik_saran → HTML5 required validation
 * - Server errors → JavaScript alert from controller
 * 
 * ROUTING:
 * - Current page: ?page=feedback (VIEW ONLY - action not implemented yet)
 * - Submit action: ?page=feedback&action=submit (PLANNED)
 * - After submit: Redirect to ?page=dashboard
 * 
 * INTEGRATION:
 * - Entry point: Dashboard "Selesai" button
 * - Controller: FeedbackController (PLANNED - not exists yet)
 * - Model: FeedbackModel (create method)
 * - Database: feedback table
 * - Related: booking table (id_booking foreign key)
 * 
 * FUTURE IMPROVEMENTS:
 * - Add FeedbackController untuk handle submission
 * - Implement ?page=feedback&action=submit route
 * - Add success/error messages via session flash
 * - Consider adding neutral rating option (Rating 3)
 * 
 * @package BookEZ
 * @subpackage Views
 * @version 1.0
 */
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

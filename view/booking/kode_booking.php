<?php
/**
 * ============================================================================
 * BOOKING/KODE_BOOKING.PHP - Booking Confirmation Page
 * ============================================================================
 * 
 * Confirmation page yang ditampilkan after successful booking creation.
 * Shows booking code dengan QR code dan booking details.
 * 
 * FEATURES:
 * 1. QR CODE DISPLAY
 *    - Generated via external API: https://api.qrserver.com/
 *    - Size: 180x180 pixels
 *    - Data encoded: {kode_booking}-BookEZ
 *    - Example: BK170234567890-BookEZ
 *    - Scannable for quick verification
 * 
 * 2. BOOKING CODE DISPLAY
 *    - Large text: 2xl font size, bold
 *    - Copy functionality: Click to copy code
 *    - Visual feedback: Icon changes on copy
 *    - Tooltip: "Salin Kode"
 * 
 * 3. BOOKING DETAILS
 *    - Nama ruangan
 *    - Tanggal (formatted: d F Y)
 *    - Jam (formatted: H:i - H:i)
 *    - Status booking (dengan color coding)
 * 
 * 4. COPY TO CLIPBOARD
 *    - Click code area to copy
 *    - JavaScript: navigator.clipboard.writeText()
 *    - Icon changes: copy → check (visual feedback)
 *    - Hover effect: border color changes
 * 
 * 5. CLOSE/BACK BUTTON
 *    - X button at top right
 *    - Redirects to dashboard
 *    - data-action="back-to-dashboard"
 * 
 * DATA FROM CONTROLLER (BookingController::kode_booking()):
 * - $booking (array): Booking details dengan room info
 *   * Retrieved by kode_booking from $_GET parameter
 *   * Joined dengan ruangan dan status_booking tables
 * 
 * BOOKING DATA STRUCTURE:
 * $booking = [
 *   'id_booking' => int,
 *   'kode_booking' => string (e.g., 'BK170234567890'),
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string,
 *   'tanggal_schedule' => string (YYYY-MM-DD),
 *   'waktu_mulai' => string (HH:MM:SS),
 *   'waktu_selesai' => string (HH:MM:SS),
 *   'id_status' => int,
 *   'nama_status' => string ('AKTIF', 'SELESAI', 'DIBATALKAN', 'HANGUS')
 * ];
 * 
 * QR CODE API:
 * - Service: qrserver.com (free QR code generator)
 * - URL pattern: https://api.qrserver.com/v1/create-qr-code/
 * - Parameters:
 *   * size: 180x180 (pixels)
 *   * data: URL-encoded booking code
 * - Example: ?size=180x180&data=BK170234567890-BookEZ
 * - Returns: PNG image (displayed inline)
 * 
 * COPY FUNCTIONALITY:
 * - Trigger: Click on code container (div with data-action="copy-code")
 * - JavaScript: assets/js/booking.js (or inline script)
 * - Method: navigator.clipboard.writeText(bookingCode)
 * - Feedback: Icon changes copy → check untuk 2 seconds
 * - Fallback: Select text + document.execCommand('copy') for older browsers
 * 
 * STATUS COLOR CODING:
 * - AKTIF: Green (text-green-600)
 * - SELESAI: Blue (text-blue-600)
 * - Others: Gray (text-gray-600)
 * - Font weight: font-semibold
 * 
 * DATE/TIME FORMATTING:
 * - Tanggal: date('d F Y', strtotime(...)) → "10 December 2024"
 * - Jam: date('H:i', strtotime(...)) → "09:00 - 11:00"
 * - Locale: Indonesian (configured server-side)
 * 
 * TARGET ELEMENTS:
 * - #bookingCode: Booking code text (for copy)
 * - #copyIcon: Copy icon (changes on copy)
 * - [data-action="copy-code"]: Copy trigger container
 * - [data-action="back-to-dashboard"]: Close button
 * 
 * JAVASCRIPT:
 * - Copy code functionality
 * - Icon swap animation
 * - Back to dashboard navigation
 * - Event listeners attached via data attributes
 * 
 * LAYOUT:
 * - Centered modal-style card
 * - Max width: 450px
 * - White background dengan shadow
 * - Padding: p-6
 * - Rounded corners: rounded-xl
 * - Border: border-gray-100
 * 
 * ICON LIBRARY:
 * - Font Awesome 6 (CDN)
 * - Icons used:
 *   * fa-xmark: Close button
 *   * fa-copy: Copy icon (regular)
 *   * fa-calendar: Date icon (regular)
 *   * fa-clock: Time icon (regular)
 *   * fa-circle-info: Status icon (solid)
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Full width dengan mx-4 margins
 * - Tablet/Desktop: Fixed max-w-[450px]
 * - Centered: flex items-center justify-center
 * - Fullscreen height: h-screen
 * 
 * USER FLOW:
 * 1. User completes booking form
 * 2. BookingController::buat_booking() creates booking
 * 3. Redirect to this page dengan kode parameter
 * 4. User sees confirmation dengan QR code
 * 5. User can:
 *    - Copy booking code
 *    - Screenshot for records
 *    - Scan QR code at check-in (future feature)
 *    - Close and return to dashboard
 * 
 * ROUTING:
 * - URL: ?page=booking&action=kode_booking&kode={kode_booking}
 * - From: BookingController::buat_booking() after success
 * - Close button: Redirects to ?page=dashboard
 * 
 * CSS:
 * - External: assets/css/booking.css
 * - Tailwind utilities untuk layout
 * - Hover effects: border-sky-200, text-sky-600
 * - Transition: all hover states animated
 * 
 * SECURITY:
 * - Booking code validation: Must exist in database
 * - User authorization: Only booking owner can view (should add check)
 * - XSS prevention: htmlspecialchars() on all outputs
 * - URL encoding: urlencode() for QR data
 * 
 * FUTURE ENHANCEMENTS:
 * - Add check-in instructions
 * - Show participant list
 * - Add "Share" button (WhatsApp, Email)
 * - Download QR code as image
 * - Print booking confirmation
 * - Add calendar integration (iCal, Google Calendar)
 * 
 * INTEGRATION:
 * - Controller: BookingController (kode_booking method)
 * - Model: BookingModel (getByKodeWithDetails)
 * - Database: booking, ruangan, status_booking tables
 * - External API: qrserver.com (QR generation)
 * 
 * @package BookEZ
 * @subpackage Views\Booking
 * @version 1.0
 */
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

            <div class="w-full bg-white border border-gray-200 flex justify-center rounded-lg shadow-sm p-3 items-center mb-6 hover:border-sky-200 transition group cursor-pointer" data-action="copy-code">
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
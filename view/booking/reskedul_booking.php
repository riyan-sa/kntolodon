<?php
/**
 * ============================================================================
 * BOOKING/RESKEDUL_BOOKING.PHP - Reschedule Booking Form
 * ============================================================================
 * 
 * Form untuk reschedule existing booking ke tanggal/waktu baru.
 * Shows current schedule dan allows user to select new datetime.
 * 
 * FEATURES:
 * 1. CURRENT SCHEDULE DISPLAY
 *    - Pre-filled inputs dengan existing values
 *    - Tanggal: Current booking date
 *    - Waktu Mulai: Current start time
 *    - Waktu Selesai: Current end time
 * 
 * 2. NEW SCHEDULE SELECTION
 *    - Date picker: Min = today (no past dates)
 *    - Time inputs: Rounded-full pill style
 *    - Validation: End time > Start time
 *    - Validation: Not in past datetime
 * 
 * 3. CLOSE/CANCEL BUTTON
 *    - X button at top right
 *    - Links back to profile page
 *    - No changes saved
 * 
 * 4. SUBMIT BUTTON
 *    - "Ubah Jadwal" text
 *    - Blue primary button
 *    - Submits form to controller
 * 
 * DATA FROM CONTROLLER (BookingController::reschedule()):
 * - $booking (array): Current booking details
 *   * Retrieved by id_booking from $_GET parameter
 *   * Contains: tanggal_schedule, waktu_mulai, waktu_selesai
 * 
 * BOOKING DATA STRUCTURE:
 * $booking = [
 *   'id_booking' => int,
 *   'kode_booking' => string,
 *   'tanggal_schedule' => string (YYYY-MM-DD),
 *   'waktu_mulai' => string (HH:MM:SS),
 *   'waktu_selesai' => string (HH:MM:SS),
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string
 * ];
 * 
 * FORM STRUCTURE:
 * - Method: POST
 * - Action: ?page=booking&action=reschedule&id={id_booking}
 * - Fields:
 *   * tanggal (type="date", required, min=today)
 *   * waktu_mulai (type="time", required)
 *   * waktu_selesai (type="time", required)
 * 
 * FORM SUBMISSION:
 * - Action: ?page=booking&action=reschedule&id={$_GET['id']}
 * - Method: POST
 * - Controller: BookingController::reschedule()
 * - Updates: tanggal, waktu_mulai, waktu_selesai in booking record
 * 
 * VALIDATION (Client-side):
 * - Tanggal: Must be today or future (min="<?= date('Y-m-d') ?>")
 * - Waktu: waktu_selesai > waktu_mulai
 * - All fields: Required (HTML5 validation)
 * 
 * VALIDATION (Server-side - Controller):
 * 1. Check: User owns the booking
 * 2. Check: Booking status is AKTIF
 * 3. Validate: New datetime not in past
 * 4. Validate: waktu_selesai > waktu_mulai
 * 5. Validate: No time slot conflicts dengan other bookings
 * 6. Validate: Within operational hours (if pengaturan exists)
 * 7. Validate: Not on holiday (if hari_libur registered)
 * 
 * RESCHEDULE LOGIC:
 * - CURRENT IMPLEMENTATION: Direct update
 *   * Updates booking record directly
 *   * Changes: tanggal, waktu_mulai, waktu_selesai
 *   * No approval workflow
 * 
 * - PLANNED IMPLEMENTATION: Approval workflow
 *   * Creates schedule record (pending approval)
 *   * Admin reviews reschedule requests
 *   * Admin approves/rejects via Booking-List page
 *   * On approval: Updates booking record
 *   * See: IMPLEMENTASI_INVITATION_CONFIRMATION.md
 * 
 * BUSINESS RULES:
 * - Only AKTIF bookings can be rescheduled
 * - Cannot reschedule to past datetime
 * - New schedule must not conflict dengan other bookings
 * - Must respect operational hours
 * - Must not fall on registered holidays
 * - User can only reschedule their own bookings
 * 
 * TARGET ELEMENTS:
 * - form: Reschedule form
 * - input[name="tanggal"]: Date input
 * - input[name="waktu_mulai"]: Start time input
 * - input[name="waktu_selesai"]: End time input
 * - button[type="submit"]: Submit button
 * - a[href="?page=profile"]: Close/cancel link
 * 
 * LAYOUT:
 * - Fullscreen: min-h-screen
 * - Centered: flex items-center justify-center
 * - Modal card: max-w-lg
 * - White background: bg-white
 * - Rounded: rounded-lg
 * - Shadow: shadow-xl
 * - Border: border-gray-200
 * 
 * HEADER SECTION:
 * - Title: "Pilih Tanggal, dan Jam Booking"
 * - Close button: X icon (SVG)
 * - Border bottom: border-b border-gray-200
 * - Padding: px-6 py-4
 * 
 * BODY SECTION:
 * - Form padding: p-8
 * - Space between: space-y-6
 * - Labels: text-lg font-bold
 * - Inputs: Full width, rounded borders
 * 
 * TIME INPUT STYLING:
 * - Rounded: rounded-full (pill shape)
 * - Text center: text-center
 * - Focus ring: ring-2 ring-blue-500
 * - Separator: Dash between start and end times
 * 
 * BUTTON SECTION:
 * - Flex layout: flex gap-4
 * - Equal width: w-full for each
 * - Cancel: White bg, gray border, blue text
 * - Submit: Blue bg, white text
 * - Padding: px-6 py-2
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Full width dengan padding
 * - Desktop: Fixed max-w-lg
 * - Form inputs: Full width responsive
 * - Time inputs: Flex gap-3 (side by side)
 * 
 * USER FLOW:
 * 1. User clicks "Ubah Jadwal" in profile/active booking
 * 2. Redirect to this reschedule form
 * 3. Form pre-filled dengan current schedule
 * 4. User modifies date/time
 * 5. User clicks "Ubah Jadwal" button
 * 6. System validates new schedule
 * 7. If valid: Update booking, redirect to profile
 * 8. If invalid: Show error, stay on form
 * 
 * ROUTING:
 * - URL: ?page=booking&action=reschedule&id={id_booking}
 * - From: Profile page "Ubah Jadwal" button (active booking card)
 * - Cancel: Redirects to ?page=profile
 * - Submit: POST to same URL → Redirects to ?page=profile after success
 * 
 * ERROR HANDLING:
 * - Invalid ID → alert "Booking tidak ditemukan!"
 * - Not owner → alert "Anda tidak berhak mengubah booking ini!"
 * - Not AKTIF → alert "Hanya booking aktif yang bisa diubah!"
 * - Past datetime → alert "Tidak bisa booking di waktu lampau!"
 * - Time conflict → alert "Jadwal bentrok dengan booking lain!"
 * - Invalid time range → alert "Waktu selesai harus lebih dari waktu mulai!"
 * - All errors via JavaScript alert() from controller
 * 
 * SUCCESS MESSAGE:
 * - alert "Jadwal berhasil diubah!"
 * - Redirect to profile page
 * - Booking shows new schedule
 * 
 * CSS:
 * - External: assets/css/booking.css
 * - Tailwind utilities untuk layout
 * - Blue color scheme: focus:ring-blue-500
 * - Rounded inputs: rounded-md (date), rounded-full (time)
 * 
 * SECURITY:
 * - User authorization: Verify booking owner (should add in controller)
 * - Input validation: Date min, required fields
 * - Time slot conflict check
 * - Operational hours check
 * - Holiday check
 * - CSRF protection: None (consider adding)
 * 
 * ACCESSIBILITY:
 * - Labels for all inputs
 * - Required attributes
 * - Large click targets
 * - Clear action buttons
 * - Close button in header
 * 
 * INTEGRATION:
 * - Controller: BookingController (reschedule method GET/POST)
 * - Model: BookingModel (getById, update)
 * - Model: ScheduleModel (isTimeSlotAvailable)
 * - Model: PengaturanModel (validateWaktuOperasi, validateHariLibur)
 * - Database: booking table (update tanggal, waktu_mulai, waktu_selesai)
 * 
 * FUTURE ENHANCEMENTS:
 * - Add approval workflow (admin review)
 * - Show timeline conflict detection
 * - Email notification on reschedule
 * - Reschedule reason field
 * - Reschedule history log
 * - Limit reschedule frequency (e.g., max 3 times per booking)
 * - Add deadline (e.g., can't reschedule within 1 hour of start time)
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
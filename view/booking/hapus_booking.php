<?php
/**
 * ============================================================================
 * BOOKING/HAPUS_BOOKING.PHP - Delete Booking Confirmation
 * ============================================================================
 * 
 * Confirmation dialog untuk cancel/delete booking.
 * Shows warning dan requires explicit confirmation.
 * 
 * FEATURES:
 * 1. CONFIRMATION DIALOG
 *    - Modal-style centered card
 *    - Red trash icon (visual warning)
 *    - Warning text: "Tindakan ini tidak dapat diurungkan"
 *    - Two action buttons: Batal (cancel), Hapus (confirm)
 * 
 * 2. DELETE ACTION
 *    - Form submission: POST to controller
 *    - Sets booking status to DIBATALKAN
 *    - Does NOT physically delete record (soft delete)
 *    - Preserves data for reporting
 * 
 * 3. CANCEL ACTION
 *    - Link back to profile page
 *    - No changes made
 *    - Returns to booking history
 * 
 * FORM STRUCTURE:
 * - Method: POST
 * - Action: ?page=booking&action=hapus_booking&id={id_booking}
 * - No input fields (ID passed in URL)
 * - Single submit button: "Hapus"
 * 
 * FORM SUBMISSION:
 * - Action: ?page=booking&action=hapus_booking&id={$_GET['id']}
 * - Method: POST
 * - Controller: BookingController::hapus_booking()
 * - Parameter: id (booking ID from URL)
 * 
 * DELETE LOGIC (Controller):
 * 1. Validate: User owns the booking
 * 2. Check: Booking status is AKTIF (can only cancel active bookings)
 * 3. Update: Set status_booking to DIBATALKAN (id_status = 3)
 * 4. Log: Record cancellation timestamp (optional)
 * 5. Redirect: Back to profile dengan success message
 * 
 * BUSINESS RULES:
 * - Only AKTIF bookings can be cancelled
 * - Cannot delete SELESAI, DIBATALKAN, or HANGUS bookings
 * - User can only delete their own bookings
 * - Cancellation is permanent (no undo)
 * - Record preserved in database (status change only)
 * 
 * ICON DISPLAY:
 * - Red trash icon (stroke-width: 1.5)
 * - Circular border: border-2 border-red-500
 * - Size: w-20 h-20 (icon container)
 * - SVG: h-10 w-10 (icon itself)
 * - Color: text-red-600
 * 
 * BUTTON STYLING:
 * - Batal (Cancel):
 *   * White bg dengan blue text
 *   * Border: border-gray-200
 *   * Hover: bg-gray-50
 *   * Width: w-1/2
 * - Hapus (Delete):
 *   * Red bg: bg-[#D50000]
 *   * White text
 *   * Hover: bg-red-700
 *   * Width: w-1/2
 *   * Shadow: shadow-md hover:shadow-lg
 * 
 * TARGET ELEMENTS:
 * - form: Delete confirmation form
 * - button[type="submit"]: Delete button
 * - a[href="?page=profile"]: Cancel link
 * 
 * LAYOUT:
 * - Fullscreen: min-h-screen
 * - Centered: flex items-center justify-center
 * - Modal card: max-w-md
 * - Padding: p-4 (outer), p-8 (inner)
 * - Background: bg-gray-50
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Full width dengan padding
 * - Desktop: Fixed max-w-md
 * - Button layout: Flex gap-4, equal width (w-1/2 each)
 * 
 * USER FLOW:
 * 1. User clicks "Hapus" in profile/booking history
 * 2. Redirect to this confirmation page
 * 3. User sees warning dialog
 * 4. User can:
 *    - Click "Batal" → Return to profile (no changes)
 *    - Click "Hapus" → Submit form → Delete booking → Return to profile
 * 5. Success message via alert() from controller
 * 
 * ROUTING:
 * - URL: ?page=booking&action=hapus_booking&id={id_booking}
 * - From: Profile page booking card "Hapus" button
 * - Cancel: Redirects to ?page=profile
 * - Submit: POST to same URL → Redirects to ?page=profile after success
 * 
 * ERROR HANDLING:
 * - Invalid ID → alert "Booking tidak ditemukan!"
 * - Not owner → alert "Anda tidak berhak menghapus booking ini!"
 * - Not AKTIF → alert "Hanya booking aktif yang bisa dibatalkan!"
 * - DB error → alert "Gagal menghapus booking!"
 * - All errors via JavaScript alert() from controller
 * 
 * SUCCESS MESSAGE:
 * - alert "Booking berhasil dibatalkan!"
 * - Redirect to profile page
 * - Booking now shows status DIBATALKAN in history
 * 
 * CSS:
 * - External: assets/css/profile.css
 * - Tailwind utilities for layout
 * - Red color scheme: #D50000 (primary delete red)
 * - Shadow effects: shadow-xl on card, shadow-md on button
 * 
 * SECURITY:
 * - User authorization: Verify booking owner (should add in controller)
 * - CSRF protection: None implemented (consider adding token)
 * - Input validation: ID must be valid integer
 * - Status check: Only allow AKTIF cancellations
 * 
 * ACCESSIBILITY:
 * - Clear action labels ("Batal", "Hapus")
 * - Warning icon untuk visual cue
 * - Descriptive text: "Tindakan ini tidak dapat diurungkan"
 * - High contrast buttons (red vs white)
 * - Large click targets (py-2.5 px-4)
 * 
 * INTEGRATION:
 * - Controller: BookingController (hapus_booking method)
 * - Model: BookingModel (updateStatus, getById)
 * - Database: booking table (status update)
 * - Status: DIBATALKAN (id_status = 3 in status_booking table)
 * 
 * FUTURE ENHANCEMENTS:
 * - Add cancellation reason field (optional)
 * - Show booking details before deletion
 * - Add cancellation deadline (e.g., can't cancel within 1 hour)
 * - Email notification on cancellation
 * - Refund policy (if payment implemented)
 * - Undo option (grace period)
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
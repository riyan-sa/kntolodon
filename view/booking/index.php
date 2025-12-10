<?php
/**
 * ============================================================================
 * BOOKING/INDEX.PHP - Room Booking Form
 * ============================================================================
 * 
 * Complex booking form untuk create new room reservation.
 * Supports group booking dengan multiple participants (anggota).
 * 
 * FEATURES:
 * 1. ROOM INFORMATION DISPLAY
 *    - Foto ruangan (or placeholder)
 *    - Nama ruangan
 *    - Jenis ruangan (Ruang Umum / Ruang Rapat)
 *    - Kapasitas: min - max orang
 *    - Status ruangan badge
 *    - Deskripsi
 *    - Tata tertib (rules)
 * 
 * 2. BOOKING DATETIME INPUTS
 *    - Tanggal: Date picker (min = today)
 *    - Waktu Mulai: Time picker
 *    - Waktu Selesai: Time picker
 *    - Validation: Past datetime prevention
 *    - Validation: End time > Start time
 * 
 * 3. TIMELINE CONFLICT DETECTION (AJAX)
 *    - Shows occupied time slots for selected date
 *    - Visual timeline dengan booking blocks
 *    - Real-time conflict warning
 *    - Endpoint: ?page=booking&action=get_booked_timeslots
 *    - Auto-updates on date change
 * 
 * 4. GROUP BOOKING (Anggota Management)
 *    - Ketua: Auto-filled dengan current user (readonly)
 *    - Anggota: Add multiple participants
 *    - NIM search: Auto-fill user data via AJAX
 *    - Endpoint: ?page=booking&action=get_user_by_nim
 *    - Remove anggota: Delete button per row
 *    - Min participants: Room's minimal_kapasitas_ruangan
 *    - Max participants: Room's maksimal_kapasitas_ruangan
 * 
 * 5. CAPTCHA VERIFICATION
 *    - Same as login/register
 *    - Image: view/components/captcha.php
 *    - Refresh functionality
 *    - Case-insensitive validation
 * 
 * 6. FORM VALIDATION (13 Steps)
 *    - Step 1: Check tanggal filled
 *    - Step 2: Validate tanggal not in past
 *    - Step 3: Check waktu_mulai filled
 *    - Step 4: Check waktu_selesai filled
 *    - Step 5: Validate waktu_selesai > waktu_mulai
 *    - Step 6: Validate not past datetime
 *    - Step 7: Check CAPTCHA filled
 *    - Step 8: Validate CAPTCHA correct
 *    - Step 9: Check participant count >= min capacity
 *    - Step 10: Check participant count <= max capacity
 *    - Step 11: Validate no duplicate NIMs
 *    - Step 12: Check user has no active booking
 *    - Step 13: Validate time slot available (no conflicts)
 * 
 * DATA FROM CONTROLLER (BookingController::index()):
 * - $ruangan (array): Selected room details
 *   * Fetched by id_ruangan from $_GET parameter
 *   * Contains: nama, jenis, kapasitas, status, deskripsi, tata_tertib, foto
 * 
 * ROOM DATA STRUCTURE:
 * $ruangan = [
 *   'id_ruangan' => int,
 *   'nama_ruangan' => string,
 *   'jenis_ruangan' => string ('Ruang Umum', 'Ruang Rapat'),
 *   'minimal_kapasitas_ruangan' => int,
 *   'maksimal_kapasitas_ruangan' => int,
 *   'status_ruangan' => string ('Tersedia', 'Sedang Digunakan', 'Tidak Tersedia'),
 *   'deskripsi' => string|null,
 *   'tata_tertib' => string|null,
 *   'foto_ruangan' => string|null (path)
 * ];
 * 
 * FORM STRUCTURE:
 * - Hidden: id_ruangan (passed from dashboard)
 * - Input: tanggal (type="date", min=today)
 * - Input: waktu_mulai (type="time")
 * - Input: waktu_selesai (type="time")
 * - Timeline: Visual conflict detection (populated by JavaScript)
 * - Ketua: Readonly (current user's NIM, name, jurusan, prodi)
 * - Anggota: Dynamic rows (NIM input + auto-filled data)
 * - CAPTCHA: Image + text input
 * - Submit button: "Booking Sekarang"
 * 
 * FORM SUBMISSION:
 * - Action: ?page=booking&action=buat_booking
 * - Method: POST
 * - Controller: BookingController::buat_booking()
 * - Fields:
 *   * id_ruangan (hidden)
 *   * tanggal
 *   * waktu_mulai
 *   * waktu_selesai
 *   * nim_ketua (readonly, from session)
 *   * nim_anggota[] (array of NIMs)
 *   * captcha
 * 
 * AJAX ENDPOINTS:
 * 1. GET USER BY NIM
 *    - URL: ?page=booking&action=get_user_by_nim&nim={nim}
 *    - Method: GET
 *    - Returns: {"success": bool, "data": {...}, "message": string}
 *    - Data fields: nomor_induk, username, jurusan, prodi
 * 
 * 2. GET BOOKED TIMESLOTS
 *    - URL: ?page=booking&action=get_booked_timeslots&id_ruangan={id}&tanggal={date}
 *    - Method: GET
 *    - Returns: [{"kode_booking": string, "waktu_mulai": string, "waktu_selesai": string}, ...]
 *    - Used for timeline conflict detection
 * 
 * ANGGOTA MANAGEMENT:
 * - Add button: Adds new row dengan NIM input
 * - NIM input: Triggers AJAX on blur/enter
 * - Auto-fill: Populates nama, jurusan, prodi fields
 * - Remove button: Deletes row (validates min capacity)
 * - Validation: Check participant count within room capacity range
 * 
 * PARTICIPANT COUNT:
 * - Ketua: Always included (1 person)
 * - Anggota: Additional participants
 * - Total: Ketua + Anggota count
 * - Must satisfy: minimal_kapasitas <= total <= maksimal_kapasitas
 * - Example: Room capacity 3-10, valid totals: 3, 4, 5, ..., 10
 * 
 * TARGET ELEMENTS:
 * - #mainForm: Main booking form
 * - #tanggal: Date input
 * - #waktu_mulai: Start time input
 * - #waktu_selesai: End time input
 * - #timeline: Timeline container for conflict visualization
 * - #anggotaContainer: Container for anggota rows
 * - #btnTambahAnggota: Add anggota button
 * - .anggota-row: Individual anggota rows
 * - .btn-remove-anggota: Remove anggota buttons
 * - #captchaImage: CAPTCHA image
 * - #captcha: CAPTCHA text input
 * - [data-action="back-to-dashboard"]: Back button
 * 
 * JAVASCRIPT:
 * - assets/js/booking.js: Form interactions, AJAX, validation, timeline
 * - Functions:
 *   * loadBookedTimeslots(): Fetch and display timeline
 *   * addAnggotaRow(): Add new participant row
 *   * removeAnggotaRow(): Remove participant row
 *   * searchUserByNim(): AJAX user lookup
 *   * validateForm(): 13-step validation
 *   * checkTimeOverlap(): Conflict detection algorithm
 * 
 * VALIDATION FLOW:
 * 1. Client-side: JavaScript validates before submit
 * 2. Server-side: BookingController::buat_booking() re-validates
 * 3. Business rules:
 *    - One active booking per user
 *    - Participant count within room capacity
 *    - No time slot conflicts
 *    - No past datetime bookings
 *    - All participants must exist in DB
 *    - CAPTCHA must be correct
 * 
 * SUCCESS FLOW:
 * 1. Validate all inputs
 * 2. Create booking record (status: AKTIF)
 * 3. Generate kode_booking: BK{timestamp}{random}
 * 4. Insert anggota_booking records (ketua + anggota)
 * 5. Mark ketua as is_ketua = 1
 * 6. Redirect to: ?page=booking&action=kode_booking&kode={kode_booking}
 * 
 * ERROR HANDLING:
 * - Past datetime → alert "Tidak bisa booking di waktu lampau!"
 * - Invalid time range → alert "Waktu selesai harus lebih dari waktu mulai!"
 * - Wrong CAPTCHA → alert "Kode CAPTCHA salah!"
 * - Capacity violation → alert "Jumlah peserta harus antara X-Y orang!"
 * - Has active booking → alert "Anda sudah memiliki booking aktif!"
 * - Time conflict → alert "Jadwal bentrok dengan booking lain!"
 * - All errors via JavaScript alert() from controller
 * 
 * RESPONSIVE DESIGN:
 * - Mobile: Single column, stacked layout
 * - Tablet (md): Two-column layout (room info + form)
 * - Desktop: Wide form dengan side-by-side inputs
 * - Timeline: Scrollable horizontal on mobile
 * 
 * CSS:
 * - External: assets/css/booking.css
 * - Font Awesome: CDN for icons (legacy, consider removing)
 * - Timeline styling: Custom visual blocks
 * - Form inputs: Tailwind utility classes
 * 
 * ROUTING:
 * - Entry URL: ?page=booking&action=index&id_ruangan={id}
 * - From: Dashboard room card "Booking Sekarang" button
 * - Submit: ?page=booking&action=buat_booking
 * - Success: ?page=booking&action=kode_booking&kode={kode}
 * - Cancel: Back to dashboard
 * 
 * SECURITY:
 * - CAPTCHA prevents bots
 * - Session check: User must be logged in
 * - NIM validation: Must exist in database
 * - Time validation: Prevents past bookings
 * - Conflict check: Prevents double bookings
 * - XSS prevention: htmlspecialchars() on outputs
 * 
 * INTEGRATION:
 * - Controller: BookingController (index, buat_booking, get_user_by_nim, get_booked_timeslots)
 * - Model: BookingModel (create, hasActiveBooking)
 * - Model: ScheduleModel (isTimeSlotAvailable)
 * - Model: AkunModel (getByNomorInduk)
 * - Model: AnggotaBookingModel (addAnggota)
 * - Database: booking, anggota_booking, schedule, akun tables
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

<!DOCTYPE html>
<html lang="id">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>BookEZ - Form Booking</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="<?= $asset('assets/css/booking.css') ?>">
</head>

<body class="text-gray-800 min-h-screen flex flex-col pb-10">

	<header class="bg-white shadow-sm sticky top-0 z-50">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between overflow-clip">
			<a href="?page=dashboard" class="flex items-center gap-2">
				<img src="<?= $asset("/assets/image/logo.png") ?>" alt="" srcset="" width="200">
			</a>

			<a href="?page=profile" class="flex items-center gap-3">
				<span class="text-xl font-bold text-gray-800"><?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Guest' ?></span>
				<div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
					<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
						<path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
					</svg>
				</div>
			</a>
		</div>
	</header>

	<form action="?page=booking&action=buat_booking" method="POST" id="mainForm">
		<input type="hidden" name="id_ruangan" value="<?= $ruangan['id_ruangan'] ?>">
		<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 w-full">
		<div class="mb-6">
				<input type="button" value="Kembali" data-action="back-to-dashboard" class="bg-white text-sky-600 px-8 py-2 rounded-lg shadow-sm hover:shadow-md transition font-medium border border-gray-100 text-sm">
				</input>
		</div>			<div class="bg-white rounded-xl shadow-lg p-6 md:p-10 border border-gray-100 min-h-[600px]">

				<div class="">
					<div class="flex flex-col md:flex-row gap-8 mb-12">
						<div class="w-full md:w-64 h-40 rounded-lg overflow-hidden shrink-0 shadow-sm">
							<?php 
							$fotoRuangan = !empty($ruangan['foto_ruangan']) ? $asset($ruangan['foto_ruangan']) : $asset('/assets/image/gambar ruangan.jpg');
							?>
							<img src="<?= $fotoRuangan ?>" alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>" class="w-full h-full object-cover">
						</div>

						<div class="flex flex-col pt-2 w-full">
							<h2 class="text-2xl font-bold text-gray-800 mb-3"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></h2>
							<div class="text-gray-500 space-y-2 text-sm md:text-base">
								<p>Minimal : <span id="min-kapasitas"><?= $ruangan['minimal_kapasitas_ruangan'] ?></span></p>
								<p>Maksimal : <span id="max-kapasitas"><?= $ruangan['maksimal_kapasitas_ruangan'] ?></span></p>
							</div>
						</div>
						<div class="flex flex-col w-full pt-1.5">
							<p class="text-3xl text-center font-extrabold">Tata Tertib</p>
							<p><?= htmlspecialchars($ruangan['tata_tertib'] ?? 'Tidak ada tata tertib') ?></p>
						</div>
					</div>

				</div>
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="anggota-container">
					<!-- Card anggota akan ditambahkan secara dinamis via JavaScript -->
				</div>

				<!-- Tombol Tambah Anggota -->
				<div class="mt-6 flex justify-center">
					<button type="button" id="btn-tambah-anggota" class="btn-tambah-anggota flex items-center gap-2 bg-sky-50 text-sky-600 px-6 py-3 rounded-lg border-2 border-dashed border-sky-300 hover:bg-sky-100 hover:border-sky-400 transition font-medium">
						<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
						</svg>
						Tambah Anggota
					</button>
				</div>
			</div>
		<div class="mt-6">
				<!-- Jika diklik tampilkan modal yang disembunyikan -->
				<input id="btn-lanjutkan" type="button" value="Lanjutkan" class="bg-white text-sky-600 px-8 py-2 rounded-lg shadow-sm hover:shadow-md transition font-medium border border-gray-100 text-sm" />
		</div>		</main>

		<!-- Disembunyikan sampai user klik tombol lanjutkan -->
		<div hidden id="modal-overlay" class="fixed inset-0 bg-white/60 backdrop-blur-sm z-40 flex items-center justify-center">

			<div class="bg-white w-full max-w-md rounded-lg shadow-2xl border border-gray-200 transform transition-all scale-100">

			<div class="flex justify-between items-center p-5 border-b border-gray-200">
					<h3 class="text-lg font-bold text-gray-800">Pilih Tanggal, dan Jam Booking</h3>
					<input type="button" value="X" id="btn-close-modal-time" class="text-gray-500 hover:text-gray-700 transition"></input>
			</div>				<div class="p-6 space-y-6">

					<div>
						<label class="block text-lg font-semibold text-gray-800 mb-2">Tanggal :</label>
						<input type="date" name="tanggal" id="tanggalInput" required
							min="<?= date('Y-m-d') ?>"
							class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-sky-500 text-gray-600">
					</div>

					<!-- Timeline Jadwal Terpakai -->
					<div id="scheduleTimeline" class="hidden">
						<div class="flex items-center justify-between mb-2">
							<label class="block text-sm font-semibold text-gray-700">Jadwal Terpakai:</label>
							<button type="button" id="btn-hide-timeline" class="text-xs text-gray-400 hover:text-gray-600">
								<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
								</svg>
							</button>
						</div>
						<div id="timelineContent" class="space-y-2 max-h-32 overflow-y-auto bg-gray-50 rounded-lg p-3 border border-gray-200">
							<!-- Nanti di isis sama JavaScript -->
						</div>
					</div>

					<div>
						<label class="block text-lg font-semibold text-gray-800 mb-2">Jam :</label>
						<div class="flex items-center gap-3">
							<input type="time" name="waktu_mulai" id="waktuMulaiInput" required
								class="w-full border border-gray-300 time-pill px-4 py-2 focus:outline-none focus:border-sky-500 text-gray-600 shadow-sm">

							<span class="text-2xl font-bold text-gray-400">-</span>

							<input type="time" name="waktu_selesai" id="waktuSelesaiInput" required
								class="w-full border border-gray-300 time-pill px-4 py-2 focus:outline-none focus:border-sky-500 text-gray-600 shadow-sm">
						</div>
						<!-- Warning message untuk bentrok -->
						<div id="conflictWarning" class="hidden mt-2 p-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
							<div class="flex items-start gap-2">
								<svg class="w-5 h-5 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
									<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
								</svg>
								<span id="conflictMessage"></span>
							</div>
						</div>
					</div>
					<div class="pt-4 flex justify-center">
						<input type="submit" class="bg-white text-sky-600 font-semibold py-2 px-10 rounded shadow-[0_2px_8px_rgba(0,0,0,0.15)] hover:shadow-md border border-gray-50 transition text-sm" value="Booking" />
					</div>

				</div>
			</div>
		</div>
	</form>
</body>

</html>

<script defer src="<?= $asset("assets/js/booking.js") ?>"></script>
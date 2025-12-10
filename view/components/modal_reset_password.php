<?php
/**
 * ============================================================================
 * MODAL_RESET_PASSWORD.PHP - Password Reset Modal (3-Step Flow)
 * ============================================================================
 * 
 * Three-step password reset flow untuk users yang lupa password.
 * Uses OTP verification via email untuk secure password recovery.
 * 
 * 3-STEP FLOW:
 * STEP 1: VERIFY EMAIL
 *    - User enters email address
 *    - System sends 6-digit OTP to email
 *    - OTP expires in 5 minutes
 * 
 * STEP 2: INPUT OTP
 *    - User enters 6-digit code dari email
 *    - Timer displays: "5:00" countdown
 *    - Resend OTP available after 60 seconds
 *    - Validates OTP against session/database
 * 
 * STEP 3: SET NEW PASSWORD
 *    - User enters new password (min 8 chars)
 *    - User confirms new password
 *    - System updates password in database
 *    - Redirect to login page
 * 
 * MODAL 1: VERIFY EMAIL (#modalVerifyEmail)
 * - Form: #formVerifyEmail
 * - Input: email (required, type="email")
 * - Placeholder: "contoh@pnj.ac.id"
 * - Buttons: Batal (cancel), Kirim OTP (submit)
 * - Action: LoginController::sendResetOTP()
 * - Validation: PNJ domain email check
 * 
 * MODAL 2: INPUT OTP (#modalInputOtp)
 * - Form: #formInputOtp
 * - Input: otp (6 digits, pattern="[0-9]{6}", maxlength="6")
 * - Display: User's email (#displayEmail)
 * - Timer: Countdown from 5:00 (#otpTimer)
 * - Buttons: Batal, Verifikasi
 * - Resend button: #resendOtp (disabled for 60 seconds)
 * - Action: LoginController::verifyOTP()
 * 
 * MODAL 3: NEW PASSWORD (#modalNewPassword)
 * - Form: #formNewPassword
 * - Input 1: new_password (min 8 chars, type="password")
 * - Input 2: confirm_password (must match new_password)
 * - Buttons: Batal, Reset Password
 * - Action: LoginController::resetPassword()
 * 
 * OTP GENERATION & VALIDATION:
 * - OTP format: 6-digit numeric code (000000-999999)
 * - Generation: rand(100000, 999999) or similar
 * - Storage: $_SESSION['reset_otp'] with timestamp
 * - Expiry: 5 minutes (300 seconds)
 * - Validation: Compare input dengan session code + check timestamp
 * 
 * EMAIL SENDING:
 * - SMTP: config/Email.php (PHPMailer)
 * - Subject: "Kode Reset Password - BookEZ"
 * - Body: OTP code + expiry warning
 * - From: bookez.web@gmail.com
 * - To: User's registered email
 * 
 * TIMER FUNCTIONALITY (JavaScript):
 * - Initial: 5:00 (5 minutes)
 * - Countdown: Updates every second
 * - Format: MM:SS
 * - Expiry: Shows "Kode OTP telah expired"
 * - Implementation: setInterval() in reset-password.js
 * 
 * RESEND OTP FUNCTIONALITY:
 * - Initial state: Disabled for 60 seconds
 * - Cooldown: Prevents spam
 * - After cooldown: Enabled, user can request new OTP
 * - New OTP: Invalidates previous OTP
 * - Timer: Resets to 5:00
 * 
 * TARGET ELEMENTS:
 * MODAL 1:
 * - #modalVerifyEmail: Container
 * - #formVerifyEmail: Form
 * - #emailVerify: Email input
 * - #closeModalVerifyEmail: Close button
 * - #cancelVerifyEmail: Cancel button
 * 
 * MODAL 2:
 * - #modalInputOtp: Container
 * - #formInputOtp: Form
 * - #otpCode: OTP input (6 digits)
 * - #displayEmail: Shows user's email
 * - #otpTimer: Countdown timer display
 * - #resendOtp: Resend button
 * - #closeModalInputOtp: Close button
 * - #cancelInputOtp: Cancel button
 * 
 * MODAL 3:
 * - #modalNewPassword: Container
 * - #formNewPassword: Form
 * - #newPasswordReset: New password input
 * - #confirmPasswordReset: Confirm password input
 * - #closeModalNewPassword: Close button
 * - #cancelNewPassword: Cancel button
 * 
 * FORM SUBMISSIONS:
 * Step 1: POST to ?page=login&action=sendResetOTP
 *    - Field: email
 *    - Response: Success → open modal 2, Error → alert
 * 
 * Step 2: POST to ?page=login&action=verifyOTP
 *    - Field: otp
 *    - Response: Valid → open modal 3, Invalid → alert
 * 
 * Step 3: POST to ?page=login&action=resetPassword
 *    - Fields: new_password, confirm_password
 *    - Response: Success → redirect login, Error → alert
 * 
 * VALIDATION RULES:
 * Step 1 (Email):
 * - Required, valid email format
 * - Must be PNJ domain (@pnj.ac.id or subdomains)
 * - Email must exist in database
 * 
 * Step 2 (OTP):
 * - Required, exactly 6 digits
 * - Must match session/DB stored OTP
 * - Must not be expired (< 5 minutes old)
 * 
 * Step 3 (Password):
 * - Required, minimum 8 characters
 * - new_password !== old_password (optional check)
 * - new_password === confirm_password (must match)
 * 
 * JAVASCRIPT INTEGRATION:
 * - assets/js/reset-password.js: Complete flow management
 * - Functions:
 *   * openModal1(): Show email verification
 *   * openModal2(email): Show OTP input + start timer
 *   * openModal3(): Show new password form
 *   * closeAllModals(): Hide all modals
 *   * startOtpTimer(): Countdown from 5:00
 *   * handleResendOtp(): Resend OTP functionality
 * 
 * MODAL TRANSITIONS:
 * - Step 1 → Step 2: On successful OTP send
 * - Step 2 → Step 3: On successful OTP verification
 * - Step 3 → Login: On successful password reset
 * - Cancel any step: Close all modals, return to login
 * 
 * STYLING:
 * - All modals: Same design pattern
 * - Overlay: bg-black/50 (50% opacity)
 * - Modal: bg-white rounded-2xl shadow-2xl
 * - Max width: max-w-md
 * - Z-index: z-50
 * - Info box (OTP modal): bg-blue-50 border-blue-200
 * 
 * INPUT STYLING:
 * - Email: Standard input styling
 * - OTP: text-center text-2xl font-bold tracking-widest (large digits)
 * - Password: Standard input with rounded-lg
 * - Focus: ring-2 ring-blue-500
 * 
 * ERROR HANDLING:
 * - Email not found → alert "Email tidak terdaftar!"
 * - Invalid OTP → alert "Kode OTP salah!"
 * - Expired OTP → alert "Kode OTP sudah expired!"
 * - Password mismatch → alert "Password tidak cocok!"
 * - All errors via JavaScript alert() from controller
 * 
 * SUCCESS MESSAGES:
 * - OTP sent → "Kode OTP telah dikirim ke email Anda"
 * - OTP valid → Proceed to password reset
 * - Password reset → "Password berhasil direset! Silakan login."
 * 
 * SECURITY FEATURES:
 * - OTP expiration (5 minutes)
 * - Resend cooldown (60 seconds)
 * - One-time use OTP (invalidated after verification)
 * - Email verification before reset
 * - No password visibility toggle
 * - Session-based OTP storage
 * 
 * ACCESSIBILITY:
 * - Labels for all inputs
 * - Required attributes
 * - Pattern validation (OTP)
 * - Keyboard navigation
 * - Escape key closes modals
 * - Focus trap in active modal
 * 
 * USAGE:
 * - Included in: view/login.php
 * - Triggered by: "Lupa Password?" link below login form
 * - Pattern: require __DIR__ . '/components/modal_reset_password.php';
 * 
 * INTEGRATION:
 * - Controller: LoginController (sendResetOTP, verifyOTP, resetPassword)
 * - Model: AkunModel (findByEmail, updatePassword)
 * - Config: Email.php (SMTP email sending)
 * - Database: akun table
 * - JavaScript: assets/js/reset-password.js
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */
?>
<!-- Modal Reset Password (3 Steps) -->
<!-- Step 1: Verifikasi Email -->
<div id="modalVerifyEmail" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Lupa Password</h3>
            <button id="closeModalVerifyEmail" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formVerifyEmail">
            <div class="mb-6">
                <label for="emailVerify" class="block text-sm font-medium text-gray-700 mb-2">
                    Masukkan Email Anda
                </label>
                <input type="email" 
                       name="email" 
                       id="emailVerify" 
                       required
                       placeholder="contoh@pnj.ac.id"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="mt-2 text-xs text-gray-500">Kami akan mengirimkan kode OTP ke email Anda</p>
            </div>

            <div class="flex gap-3">
                <button type="button" id="cancelVerifyEmail" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Kirim OTP
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Step 2: Input OTP -->
<div id="modalInputOtp" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Verifikasi OTP</h3>
            <button id="closeModalInputOtp" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm text-blue-800">Kode OTP telah dikirim ke <span id="displayEmail" class="font-semibold"></span></p>
                    <p class="text-xs text-blue-700 mt-1">Kode berlaku selama <span id="otpTimer" class="font-semibold">5:00</span> menit</p>
                </div>
            </div>
        </div>

        <form id="formInputOtp">
            <div class="mb-6">
                <label for="otpCode" class="block text-sm font-medium text-gray-700 mb-2">
                    Masukkan Kode OTP (6 Digit)
                </label>
                <input type="text" 
                       name="otp" 
                       id="otpCode" 
                       required
                       maxlength="6"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg text-center text-2xl font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex gap-3">
                <button type="button" id="cancelInputOtp" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Verifikasi
                </button>
            </div>

            <div class="mt-4 text-center">
                <button type="button" id="resendOtp" class="text-sm text-blue-600 hover:underline disabled:text-gray-400 disabled:no-underline disabled:cursor-not-allowed">
                    Kirim Ulang OTP
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Step 3: Set Password Baru -->
<div id="modalNewPassword" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Buat Password Baru</h3>
            <button id="closeModalNewPassword" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formNewPassword" method="POST" action="index.php?page=login&action=reset_password">
            <div class="mb-4">
                <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">
                    Password Baru
                </label>
                <input type="password" 
                       name="new_password" 
                       id="newPassword" 
                       required
                       minlength="8"
                       placeholder="Minimal 8 karakter"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label for="confirmNewPassword" class="block text-sm font-medium text-gray-700 mb-2">
                    Konfirmasi Password Baru
                </label>
                <input type="password" 
                       name="confirm_password" 
                       id="confirmNewPassword" 
                       required
                       minlength="8"
                       placeholder="Ketik ulang password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex gap-3">
                <button type="button" id="cancelNewPassword" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Simpan Password
                </button>
            </div>
        </form>
    </div>
</div>

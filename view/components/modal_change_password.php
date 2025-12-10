<?php
/**
 * ============================================================================
 * MODAL_CHANGE_PASSWORD.PHP - Password Change Modal Component
 * ============================================================================
 * 
 * Modal component untuk change password functionality dalam profile page.
 * Requires current password verification before allowing password update.
 * 
 * FEATURES:
 * 1. PASSWORD VERIFICATION
 *    - Old password input: Required untuk verify user identity
 *    - Validates against current password in database
 *    - Prevents unauthorized password changes
 * 
 * 2. NEW PASSWORD REQUIREMENTS
 *    - Minimum 8 characters (minlength="8")
 *    - HTML5 validation + server-side validation
 *    - Must be different from old password
 * 
 * 3. PASSWORD CONFIRMATION
 *    - Confirm password field: Must match new password
 *    - Client-side validation: JavaScript compares before submit
 *    - Server-side validation: Double-check in controller
 * 
 * 4. SECURITY NOTICE
 *    - Yellow info box dengan warning icon
 *    - Reminds: "Password minimal 8 karakter"
 *    - Instruction: "Pastikan password baru berbeda dengan password lama"
 * 
 * FORM STRUCTURE:
 * - 3 input fields:
 *   1. old_password: Current password (type="password", required)
 *   2. new_password: New password (type="password", required, minlength="8")
 *   3. confirm_password: Confirm new (type="password", required, minlength="8")
 * - 2 action buttons:
 *   1. Batal: Cancel button (closes modal)
 *   2. Ganti Password: Submit button (triggers form submission)
 * 
 * FORM SUBMISSION:
 * - Action: index.php?page=profile&action=change_password
 * - Method: POST
 * - Controller: ProfileController::change_password()
 * - Fields:
 *   * old_password: Verify current password
 *   * new_password: New password value
 *   * confirm_password: Confirmation (must match new_password)
 * 
 * VALIDATION FLOW:
 * 1. CLIENT-SIDE (JavaScript in profile.js):
 *    - Check all fields filled
 *    - Verify new_password === confirm_password
 *    - Check minlength (8 chars)
 * 2. SERVER-SIDE (ProfileController::change_password()):
 *    - Verify old_password matches current password in DB
 *    - Verify new_password !== old_password
 *    - Verify new_password === confirm_password
 *    - Check password length >= 8
 *    - Hash new password dengan password_hash()
 *    - Update in akun table
 * 
 * TARGET ELEMENTS:
 * - #modalChangePassword: Modal container
 * - #formChangePassword: Form element
 * - #oldPassword: Old password input
 * - #newPasswordProfile: New password input
 * - #confirmPasswordProfile: Confirm password input
 * - #closeModalChangePassword: Close button (X icon)
 * - #cancelChangePassword: Cancel button
 * 
 * JAVASCRIPT INTEGRATION:
 * - assets/js/profile.js: Modal open/close, form validation
 * - Event listeners:
 *   * closeModalChangePassword.click → hide modal
 *   * cancelChangePassword.click → hide modal
 *   * formChangePassword.submit → validate before submit
 * 
 * MODAL BEHAVIOR:
 * - Initial state: hidden (class="hidden")
 * - Open: Remove "hidden" class
 * - Close: Add "hidden" class
 * - Close triggers:
 *   1. X button click
 *   2. Cancel button click
 *   3. Escape key press
 *   4. Click outside modal (optional)
 * 
 * STYLING:
 * - Overlay: bg-black/50 (50% opacity black)
 * - Modal: bg-white rounded-2xl shadow-2xl
 * - Max width: max-w-md
 * - Padding: p-6
 * - Z-index: z-50 (above page content)
 * - Centered: items-center justify-center
 * - Fixed position: fixed inset-0 (fullscreen overlay)
 * 
 * INPUT STYLING:
 * - Border: border-gray-300 rounded-lg
 * - Focus: ring-2 ring-blue-500
 * - Padding: px-4 py-3
 * - Placeholders: Gray text hints
 * 
 * BUTTON STYLING:
 * - Cancel: border border-gray-300 (secondary style)
 * - Submit: bg-blue-600 (primary style)
 * - Hover: bg-gray-50 (cancel), bg-blue-700 (submit)
 * - Layout: flex gap-3 (equal width flex-1)
 * 
 * ERROR HANDLING:
 * - Wrong old password → alert "Password lama salah!"
 * - Password mismatch → alert "Password baru tidak cocok!"
 * - Too short → HTML5 validation message
 * - Same as old → alert "Password baru harus berbeda!"
 * - All errors via JavaScript alert() from controller
 * 
 * SUCCESS FLOW:
 * - Valid submission → Update password in DB
 * - Show alert: "Password berhasil diubah!"
 * - Close modal
 * - Stay on profile page (no redirect)
 * 
 * SECURITY FEATURES:
 * - Current password verification (prevents unauthorized changes)
 * - Password hashing: password_hash() dengan PASSWORD_DEFAULT
 * - Minimum length enforcement (8 chars)
 * - No password visibility toggle (type="password" always)
 * - Session-based auth check (user must be logged in)
 * 
 * ACCESSIBILITY:
 * - Labels for all inputs (text labels, not placeholder-only)
 * - Required attributes
 * - Keyboard navigation support
 * - Escape key closes modal
 * - Focus management (trap focus in modal when open)
 * 
 * USAGE:
 * - Included in: view/profile/index.php
 * - Triggered by: "Ganti Password" button in profile settings
 * - Pattern: require __DIR__ . '/../components/modal_change_password.php';
 * 
 * INTEGRATION:
 * - Controller: ProfileController (change_password method)
 * - Model: AkunModel (verifyPassword, updatePassword)
 * - Database: akun table (password column)
 * - JavaScript: assets/js/profile.js
 * 
 * @package BookEZ
 * @subpackage Views\Components
 * @version 1.0
 */
?>
<!-- Modal Ganti Password (Profile) -->
<div id="modalChangePassword" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-gray-900">Ganti Password</h3>
            <button id="closeModalChangePassword" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="formChangePassword" method="POST" action="index.php?page=profile&action=change_password">
            <div class="mb-4">
                <label for="oldPassword" class="block text-sm font-medium text-gray-700 mb-2">
                    Password Lama
                </label>
                <input type="password" 
                       name="old_password" 
                       id="oldPassword" 
                       required
                       placeholder="Masukkan password lama"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-4">
                <label for="newPasswordProfile" class="block text-sm font-medium text-gray-700 mb-2">
                    Password Baru
                </label>
                <input type="password" 
                       name="new_password" 
                       id="newPasswordProfile" 
                       required
                       minlength="8"
                       placeholder="Minimal 8 karakter"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-6">
                <label for="confirmPasswordProfile" class="block text-sm font-medium text-gray-700 mb-2">
                    Konfirmasi Password Baru
                </label>
                <input type="password" 
                       name="confirm_password" 
                       id="confirmPasswordProfile" 
                       required
                       minlength="8"
                       placeholder="Ketik ulang password baru"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-yellow-800">Catatan Keamanan</p>
                        <p class="text-xs text-yellow-700 mt-1">Password minimal 8 karakter. Pastikan password baru berbeda dengan password lama.</p>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" id="cancelChangePassword" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Ganti Password
                </button>
            </div>
        </form>
    </div>
</div>

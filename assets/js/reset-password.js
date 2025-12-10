/**
 * ============================================================================
 * RESET-PASSWORD.JS - Password Reset Flow Handler
 * ============================================================================
 * 
 * Complex module untuk menangani 3-step password reset workflow:
 * - Step 1: Email Verification (send OTP)
 * - Step 2: OTP Input (verify code)
 * - Step 3: New Password (reset password)
 * 
 * FUNGSI UTAMA:
 * 1. STEP 1 - EMAIL VERIFICATION
 *    - User enters email address
 *    - AJAX POST to: ?page=login&action=verify_email
 *    - Server validates email dan sends 6-digit OTP via email
 *    - Server stores OTP in session dengan 5-minute expiration
 *    - On success: show OTP input modal
 * 
 * 2. STEP 2 - OTP INPUT
 *    - Display email address for confirmation
 *    - 5-minute countdown timer (visual feedback)
 *    - User enters 6-digit OTP code
 *    - AJAX POST to: ?page=login&action=verify_otp
 *    - Server validates OTP and expiration
 *    - On success: set session flag dan show new password modal
 *    - Resend OTP button dengan cooldown timer
 * 
 * 3. STEP 3 - NEW PASSWORD
 *    - User enters new password (min 8 chars)
 *    - User confirms password (must match)
 *    - AJAX POST to: ?page=login&action=reset_password
 *    - Server validates OTP verified flag
 *    - Server updates password (hashed)
 *    - On success: clear session dan redirect to login
 * 
 * TIMER FUNCTIONALITY:
 * - startOtpTimer(seconds): Countdown dari 5 minutes (300 seconds)
 * - Display format: M:SS (e.g., 4:37)
 * - Shows "Kadaluarsa" when timer reaches 0
 * - Timer doesn't block form submission (server validates expiration)
 * 
 * - startResendCooldown(seconds): Cooldown untuk resend button
 * - Prevents spam resend requests
 * - Disables button during cooldown
 * - Shows countdown in button text
 * 
 * MODAL FLOW:
 * 1. Click "Lupa Password" → open email verification modal
 * 2. Submit email → close email modal, open OTP modal
 * 3. Submit OTP → close OTP modal, open new password modal
 * 4. Submit new password → close modal, redirect to login
 * 
 * ERROR HANDLING:
 * - AJAX errors: alert dengan error message
 * - Validation errors: alert dengan specific error
 * - Network errors: alert "Terjadi kesalahan"
 * - OTP expired: alert "OTP kadaluarsa"
 * - Password mismatch: alert "Password tidak cocok"
 * 
 * TARGET ELEMENTS (STEP 1):
 * - #btnForgotPassword: Trigger button (on login page)
 * - #modalVerifyEmail: Modal container
 * - #formVerifyEmail: Form element
 * - #closeModalVerifyEmail: Close button
 * - #cancelVerifyEmail: Cancel button
 * 
 * TARGET ELEMENTS (STEP 2):
 * - #modalInputOtp: Modal container
 * - #formInputOtp: Form element
 * - #displayEmail: Email display element
 * - #otpTimer: Timer display element
 * - #resendOtp: Resend OTP button
 * - #closeModalInputOtp: Close button
 * - #cancelInputOtp: Cancel button
 * 
 * TARGET ELEMENTS (STEP 3):
 * - #modalNewPassword: Modal container
 * - #formNewPassword: Form element
 * - #closeModalNewPassword: Close button
 * - #cancelNewPassword: Cancel button
 * 
 * STATE MANAGEMENT:
 * - currentEmail: Stores email for display dan resend functionality
 * - otpCountdown: Interval ID untuk OTP timer
 * - resendCooldown: Interval ID untuk resend button cooldown
 * - All state reset on modal close
 * 
 * AJAX PATTERN:
 * - Uses fetch API untuk all requests
 * - POST requests dengan FormData
 * - JSON responses: {success: bool, message: string}
 * - Error handling dengan try-catch
 * 
 * HELPER FUNCTIONS:
 * - showModal(modal): Remove hidden, add flex
 * - hideModal(modal): Add hidden, remove flex
 * - Clear intervals on modal close
 * 
 * SECURITY FEATURES:
 * - OTP: 6-digit random, 5-minute expiration
 * - Session-based: OTP stored server-side
 * - Rate limiting: Resend cooldown prevents spam
 * - Password validation: Min 8 chars, match confirmation
 * - Email validation: PNJ domain only (server-side)
 * 
 * EMAIL TEMPLATE:
 * - Subject: "Kode OTP Reset Password - BookEZ"
 * - Body: Contains 6-digit OTP + expiration notice
 * - From: bookez.web@gmail.com (MAIL_FROM_ADDRESS)
 * 
 * PASSWORD POLICY:
 * - Minimum 8 characters
 * - Confirmation must match
 * - Hashed via password_hash() server-side
 * 
 * USAGE:
 * - Included in: view/login.php
 * - Initializes on DOM ready
 * - Triggered by "Lupa Password?" link
 * 
 * INTEGRATION:
 * - Server: LoginController::verify_email(), verify_otp(), reset_password()
 * - Database: akun table (password update)
 * - Email: config/Email.php (SMTP sending)
 * - Session: $_SESSION['reset_otp'], $_SESSION['reset_email'], $_SESSION['otp_verified']
 * 
 * @module reset-password
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

document.addEventListener('DOMContentLoaded', function() {
    // ==================== DOM ELEMENTS ====================
    
    // Step 1: Email Verification Modal
    const modalVerifyEmail = document.getElementById('modalVerifyEmail');
    const formVerifyEmail = document.getElementById('formVerifyEmail');
    const closeModalVerifyEmail = document.getElementById('closeModalVerifyEmail');
    const cancelVerifyEmail = document.getElementById('cancelVerifyEmail');
    const btnForgotPassword = document.getElementById('btnForgotPassword');
    
    // Step 2: OTP Input Modal
    const modalInputOtp = document.getElementById('modalInputOtp');
    const formInputOtp = document.getElementById('formInputOtp');
    const closeModalInputOtp = document.getElementById('closeModalInputOtp');
    const cancelInputOtp = document.getElementById('cancelInputOtp');
    const displayEmail = document.getElementById('displayEmail');
    const otpTimer = document.getElementById('otpTimer');
    const resendOtp = document.getElementById('resendOtp');
    
    // Step 3: New Password Modal
    const modalNewPassword = document.getElementById('modalNewPassword');
    const formNewPassword = document.getElementById('formNewPassword');
    const closeModalNewPassword = document.getElementById('closeModalNewPassword');
    const cancelNewPassword = document.getElementById('cancelNewPassword');
    
    // ==================== STATE MANAGEMENT ====================
    
    let currentEmail = '';
    let otpCountdown = null;
    let resendCooldown = null;
    
    // ==================== HELPER FUNCTIONS ====================
    
    function showModal(modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    
    function hideModal(modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    function startOtpTimer(seconds) {
        if (otpCountdown) clearInterval(otpCountdown);
        
        let remaining = seconds;
        
        function updateTimer() {
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;
            otpTimer.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
            
            if (remaining <= 0) {
                clearInterval(otpCountdown);
                otpTimer.textContent = 'Kadaluarsa';
            }
            remaining--;
        }
        
        updateTimer();
        otpCountdown = setInterval(updateTimer, 1000);
    }
    
    function startResendCooldown(seconds) {
        resendOtp.disabled = true;
        let remaining = seconds;
        
        resendCooldown = setInterval(() => {
            if (remaining <= 0) {
                clearInterval(resendCooldown);
                resendOtp.disabled = false;
                resendOtp.textContent = 'Kirim Ulang OTP';
            } else {
                resendOtp.textContent = `Kirim Ulang OTP (${remaining}s)`;
            }
            remaining--;
        }, 1000);
    }
    
    // ==================== STEP 1: EMAIL VERIFICATION ====================
    
    // Open modal when "Lupa Password" is clicked
    if (btnForgotPassword) {
        btnForgotPassword.addEventListener('click', function(e) {
            e.preventDefault();
            showModal(modalVerifyEmail);
        });
    }
    
    // Close modal handlers
    if (closeModalVerifyEmail) {
        closeModalVerifyEmail.addEventListener('click', () => hideModal(modalVerifyEmail));
    }
    
    if (cancelVerifyEmail) {
        cancelVerifyEmail.addEventListener('click', () => hideModal(modalVerifyEmail));
    }
    
    // Submit email for verification and OTP sending
    if (formVerifyEmail) {
        formVerifyEmail.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Mengirim...';
            
            const formData = new FormData(this);
            const email = formData.get('email');
            currentEmail = email;
            
            try {
                const response = await fetch('index.php?page=login&action=verify_email', {
                    method: 'POST',
                    body: formData
                });
                
                // Debug: Log response
                console.log('Response status:', response.status);
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                // Parse JSON
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    alert('Server mengembalikan response yang tidak valid. Periksa console untuk detail.');
                    return;
                }
                
                if (result.success) {
                    // Close email modal, show OTP modal
                    hideModal(modalVerifyEmail);
                    displayEmail.textContent = email;
                    startOtpTimer(5 * 60); // 5 minutes
                    startResendCooldown(60); // 60 seconds cooldown before resend
                    showModal(modalInputOtp);
                    
                    // Clear OTP input and reset form
                    document.getElementById('otpCode').value = '';
                    formVerifyEmail.reset();
                } else {
                    alert(result.message || 'Gagal mengirim OTP');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
    
    // ==================== STEP 2: OTP VERIFICATION ====================
    
    // Close modal handlers
    if (closeModalInputOtp) {
        closeModalInputOtp.addEventListener('click', () => {
            hideModal(modalInputOtp);
            if (otpCountdown) clearInterval(otpCountdown);
            if (resendCooldown) clearInterval(resendCooldown);
        });
    }
    
    if (cancelInputOtp) {
        cancelInputOtp.addEventListener('click', () => {
            hideModal(modalInputOtp);
            if (otpCountdown) clearInterval(otpCountdown);
            if (resendCooldown) clearInterval(resendCooldown);
        });
    }
    
    // Auto-format OTP input (numbers only)
    const otpInput = document.getElementById('otpCode');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    }
    
    // Submit OTP for verification
    if (formInputOtp) {
        formInputOtp.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Memverifikasi...';
            
            const formData = new FormData(this);
            const otpValue = formData.get('otp');
            
            // Debug log
            console.log('Submitting OTP:', otpValue, 'Length:', otpValue.length);
            
            try {
                const response = await fetch('index.php?page=login&action=verify_otp', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('OTP verification result:', result);
                
                if (result.success) {
                    // OTP valid, show new password modal
                    hideModal(modalInputOtp);
                    if (otpCountdown) clearInterval(otpCountdown);
                    if (resendCooldown) clearInterval(resendCooldown);
                    showModal(modalNewPassword);
                    
                    // Clear password inputs
                    document.getElementById('newPassword').value = '';
                    document.getElementById('confirmNewPassword').value = '';
                } else {
                    alert(result.message || 'Kode OTP salah');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
    }
    
    // Resend OTP
    if (resendOtp) {
        resendOtp.addEventListener('click', async function() {
            if (this.disabled) return;
            
            const formData = new FormData();
            formData.append('email', currentEmail);
            
            try {
                const response = await fetch('index.php?page=login&action=verify_email', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('OTP baru telah dikirim ke email Anda');
                    startOtpTimer(5 * 60);
                    startResendCooldown(60);
                    document.getElementById('otpCode').value = '';
                } else {
                    alert(result.message || 'Gagal mengirim OTP');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            }
        });
    }
    
    // ==================== STEP 3: NEW PASSWORD ====================
    
    // Close modal handlers
    if (closeModalNewPassword) {
        closeModalNewPassword.addEventListener('click', () => hideModal(modalNewPassword));
    }
    
    if (cancelNewPassword) {
        cancelNewPassword.addEventListener('click', () => hideModal(modalNewPassword));
    }
    
    // Submit new password
    if (formNewPassword) {
        formNewPassword.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmNewPassword').value;
            
            // Validasi di client-side
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Password minimal 8 karakter');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok');
                return;
            }
            
            // Form akan di-submit ke index.php?page=login&action=reset_password
        });
    }
    
    // ==================== CLOSE MODAL ON OUTSIDE CLICK ====================
    
    [modalVerifyEmail, modalInputOtp, modalNewPassword].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    hideModal(this);
                    if (otpCountdown) clearInterval(otpCountdown);
                    if (resendCooldown) clearInterval(resendCooldown);
                }
            });
        }
    });
});

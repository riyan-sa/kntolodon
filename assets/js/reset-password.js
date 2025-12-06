/**
 * Reset Password Flow - Login Page
 * Handles 3-step modal flow: Email Verification -> OTP Input -> New Password
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

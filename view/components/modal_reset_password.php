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

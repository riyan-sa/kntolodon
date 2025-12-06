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

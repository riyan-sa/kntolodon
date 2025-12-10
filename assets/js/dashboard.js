/**
 * Dashboard Page Scripts
 * Handles booking finish flow, rating submission, and room description modals
 */

// ==================== DATA INITIALIZATION ====================

// Initialize global variables from data attributes
document.addEventListener('DOMContentLoaded', function() {
    const dataContainer = document.getElementById('dashboard-data');
    if (dataContainer) {
        window.ASSET_BASE_PATH = dataContainer.dataset.basePath || '';
    }
    
    initDashboard();
});

// ========================================
// BOOKING FINISH FLOW
// ========================================

// Handle tombol Selesai
function initDashboard() {
    const btnFinish = document.getElementById('btn-finish');
    const btnConfirmNo = document.getElementById('btn-confirm-no');
    const btnConfirmYes = document.getElementById('btn-confirm-yes');
    
    if (btnFinish) {
        btnFinish.addEventListener('click', function() {
            // Sembunyikan card pertama dan tampilkan konfirmasi
            this.closest('.bg-white').style.display = 'none';
            document.getElementById('confirmation-overlay').classList.remove('hidden');
            document.getElementById('confirmation-overlay').classList.add('block');
        });
    }

    // Handle tombol Belum - kembali ke tampilan sebelumnya
    if (btnConfirmNo) {
        btnConfirmNo.addEventListener('click', function() {
            document.getElementById('confirmation-overlay').classList.add('hidden');
            document.getElementById('confirmation-overlay').classList.remove('block');
            const cardElement = document.getElementById('btn-finish').closest('.bg-white');
            cardElement.style.display = '';
        });
    }

    // Handle tombol Selesai (konfirmasi) - submit form
    if (btnConfirmYes) {
        btnConfirmYes.addEventListener('click', function() {
            // Submit form selesai - akan redirect ke halaman feedback
            const form = document.getElementById('form-selesai');
            if (form) {
                form.submit();
            }
        });
    }
    
    // OLD CODE - DISABLED (tampilkan halaman rating) - Now using separate feedback page
    if (false && btnConfirmYes) {
        btnConfirmYes.addEventListener('click', function() {
            // Sembunyikan booking content dan tampilkan rating page
            const assetPath = window.ASSET_BASE_PATH || '';
            document.querySelector('main').innerHTML = `
                <div class="flex-col flex items-center justify-center px-6 text-center h-full">
                    
                    <!-- Teks Pesan -->
                    <h1 class="text-2xl md:text-3xl font-semibold text-gray-800 mb-12 max-w-5xl leading-relaxed">
                        Terima Kasih sudah menggunakan layanan kami, silahkan tunggu esok hari untuk meminjam kembali
                    </h1>

                    <!-- Ikon Reaksi -->
                    <div class="flex items-center justify-center gap-10 mb-12">
                        
                        <!-- Ikon Senyum (Hijau) -->
                        <button class="group focus:outline-none transform active:scale-95 transition-transform" onclick="submitRating('positive')">
                            <!-- Lingkaran Hijau -->
                            <div class="w-24 h-24 bg-[#00C853] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:bg-[#00E676] transition-all">
                                <!-- Wajah -->
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Mata -->
                                    <circle cx="8" cy="9" r="1.5" fill="white"/>
                                    <circle cx="16" cy="9" r="1.5" fill="white"/>
                                    <!-- Mulut Senyum -->
                                    <path d="M7 14C8.5 16.5 11 17 12 17C13 17 15.5 16.5 17 14" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Ikon Sedih (Merah) -->
                        <button class="group focus:outline-none transform active:scale-95 transition-transform" onclick="submitRating('negative')">
                            <!-- Lingkaran Merah -->
                            <div class="w-24 h-24 bg-[#D50000] rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl group-hover:bg-[#FF1744] transition-all">
                                <!-- Wajah -->
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <!-- Mata -->
                                    <circle cx="8" cy="9" r="1.5" fill="white"/>
                                    <circle cx="16" cy="9" r="1.5" fill="white"/>
                                    <!-- Mulut Sedih (Kebalikan Senyum) -->
                                    <path d="M7 16C8.5 13.5 11 13 12 13C13 13 15.5 13.5 17 16" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Form Kritik dan Saran -->
                    <div class="w-full max-w-2xl">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Masukan Kritik dan Saran</h2>
                        <textarea 
                            id="feedback-text" 
                            rows="5" 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500 resize-none text-gray-700"
                            placeholder="Tulis kritik dan saran Anda di sini..."></textarea>
                        <button 
                            onclick="submitFeedback()" 
                            class="mt-4 bg-white text-gray-600 border-2 border-gray-600 font-semibold py-3 px-8 rounded-lg hover:bg-gray-50 transition-colors shadow-md">
                            Kirim Umpan Balik
                        </button>
                    </div>

                </div>
            `;
            
            // Tampilkan navbar tanpa teks
            showNavbarWithoutTitle(assetPath);
        });
    }
});

// Fungsi untuk menampilkan navbar tanpa teks title
function showNavbarWithoutTitle(assetPath = '') {
    const navbarHTML = `
        <nav class="bg-white px-6 py-4 flex justify-between items-center shadow-sm sticky top-0 z-50">
            <a href="?page=dashboard" class="flex items-center">
                <img src="${assetPath}assets/image/logo.png" alt="BookEZ Logo" class="h-8 w-auto mr-2 inline-block object-contain" style="transform: scale(6); transform-origin: left center;">
            </a>
            
            <a href="?page=profile" class="flex items-center gap-3">
                <span class="text-xl font-bold text-gray-800">[Nama User]</span>
                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
            </a>
        </nav>
    `;
    document.body.insertAdjacentHTML('afterbegin', navbarHTML);
}

// Fungsi untuk handle submit rating
function submitRating(rating) {
    // TODO: Kirim rating ke server
    // console.log('Rating:', rating);
    
    // Store rating temporarily (could be sent with feedback)
    sessionStorage.setItem('tempRating', rating);
    
    // Optional: Show confirmation or just scroll to feedback section
    const feedbackText = document.getElementById('feedback-text');
    if (feedbackText) {
        feedbackText.focus();
        feedbackText.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// Fungsi untuk handle submit feedback
function submitFeedback() {
    const feedbackText = document.getElementById('feedback-text');
    const rating = sessionStorage.getItem('tempRating') || 'not_rated';
    
    if (!feedbackText || !feedbackText.value.trim()) {
        alert('Mohon masukkan kritik dan saran Anda.');
        return;
    }
    
    // TODO: Kirim feedback dan rating ke server
    // console.log('Rating:', rating);
    // console.log('Feedback:', feedbackText.value);
    
    // Clear temporary rating
    sessionStorage.removeItem('tempRating');
    
    // Redirect ke dashboard setelah submit
    window.location.href = '?page=dashboard';
}

// ========================================
// ROOM DESCRIPTION MODAL
// ========================================

// Fungsi untuk membuka modal deskripsi ruangan
function openDescModal(roomId) {
    const modal = document.getElementById('modal-' + roomId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden'; // Prevent body scroll when modal is open
    }
}

// Fungsi untuk menutup modal deskripsi ruangan
function closeDescModal(roomId) {
    const modal = document.getElementById('modal-' + roomId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = ''; // Restore body scroll
    }
}

// Close modal when clicking outside the content area
document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('[id^="modal-"]');
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                const roomId = this.id.replace('modal-', '');
                closeDescModal(roomId);
            }
        });
    });
    
    // Handle booking buttons
    document.querySelectorAll('[data-booking-action]').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-booking-action');
            const roomId = this.getAttribute('data-room-id');
            
            if (action === 'booking') {
                window.location.href = 'index.php?page=booking&room=' + roomId;
            }
        });
    });
    
    // Handle view detail buttons/divs
    document.querySelectorAll('[data-modal-action]').forEach(element => {
        element.addEventListener('click', function() {
            const action = this.getAttribute('data-modal-action');
            const roomId = this.getAttribute('data-room-id');
            
            if (action === 'open') {
                openDescModal(roomId);
            } else if (action === 'close') {
                closeDescModal(roomId);
            }
        });
    });
});

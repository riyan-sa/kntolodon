/**
 * ============================================================================
 * PROFILE.JS - User Profile Page Scripts
 * ============================================================================
 * 
 * Module untuk menangani user profile page interactions:
 * - Tab switching (Kode Booking, History, Pelanggaran)
 * - Foto profil upload modal dengan preview
 * - Change password modal
 * - Auto-switch tab based on URL parameters (pagination persistence)
 * 
 * FUNGSI UTAMA:
 * 1. TAB SWITCHING
 *    - switchTab(tabName): Toggle between 3 tabs
 *    - Show/hide content containers
 *    - Update button styles (border highlight)
 *    - Different display modes: block (booking) vs grid (history, pelanggaran)
 * 
 * 2. FOTO PROFIL UPLOAD MODAL
 *    - Click avatar container → open modal
 *    - File input change → preview image before upload
 *    - Submit → upload to server → update session
 *    - Close via X button, cancel button, or overlay click
 * 
 * 3. CHANGE PASSWORD MODAL
 *    - Click \"Ganti Password\" button → open modal
 *    - Form validation: old password, new password, confirmation
 *    - Server-side verification dan update
 *    - Close via X button, cancel button, or overlay click
 * 
 * 4. AUTO-SWITCH TAB (URL PARAMS)
 *    - Read pg_history or pg_pelanggaran from URL
 *    - Auto-switch to corresponding tab on page load
 *    - Maintains tab state after pagination navigation
 * 
 * TAB STRUCTURE:
 * - booking: Active booking card (if exists)
 * - history: Paginated booking history (SELESAI, DIBATALKAN, HANGUS)
 * - pelanggaran: Paginated violation records
 * 
 * TARGET ELEMENTS (TABS):
 * - [data-tab-switch]: Tab button elements
 * - #tab-{name}: Tab button (booking, history, pelanggaran)
 * - #content-{name}: Tab content container
 * 
 * TARGET ELEMENTS (FOTO PROFIL MODAL):
 * - #avatarContainer: Avatar image (clickable trigger)
 * - #modalUploadFoto: Modal container
 * - #closeModalFoto: Close button
 * - #cancelUploadFoto: Cancel button
 * - #fotoProfil: File input
 * - #previewImage: Image preview element
 * - #previewIcon: Placeholder icon (removed on preview)
 * - #previewContainer: Container untuk preview
 * 
 * TARGET ELEMENTS (CHANGE PASSWORD MODAL):
 * - #btn-change-password: Trigger button
 * - #modalChangePassword: Modal container
 * - #closeModalChangePassword: Close button
 * - #cancelChangePassword: Cancel button
 * - #formChangePassword: Form element
 * 
 * IMAGE PREVIEW PATTERN:
 * 1. User selects file
 * 2. FileReader reads file as Data URL
 * 3. Create/update <img> element with preview
 * 4. Remove placeholder icon if exists
 * 5. On submit → upload actual file to server
 * 
 * FILE UPLOAD:
 * - Server endpoint: ?page=profile&action=upload_foto
 * - Max size: 25MB
 * - Allowed types: JPEG, PNG, WebP
 * - Storage: assets/uploads/images/
 * - Filename: profile_{nomor_induk}_{timestamp}.{ext}
 * - Old foto auto-deleted on new upload
 * 
 * PASSWORD CHANGE:
 * - Server endpoint: ?page=profile&action=change_password
 * - Validation: old password verification, min 8 chars, match confirmation
 * - Password hashing: Server-side via password_hash()
 * - Prevent reuse: new password !== old password
 * 
 * PAGINATION PERSISTENCE:
 * - URL params: pg_history={n}, pg_pelanggaran={n}
 * - DOMContentLoaded: Check params → auto-switch tab
 * - Maintains tab state when navigating paginated results
 * 
 * USAGE:
 * - Included in: view/profile/index.php
 * - Initializes on DOM ready
 * - Event delegation untuk dynamically loaded content
 * 
 * @module profile
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

// ==================== TAB SWITCHING ====================

/**
 * Switch between profile tabs (Kode Booking, History, Pelanggaran)
 * 
 * Hides all tab contents dan shows selected tab.
 * Updates button styles untuk visual feedback.
 * Uses different display modes: block vs grid.
 * 
 * @function switchTab
 * @param {string} tabName - Name of tab to show ('booking', 'history', or 'pelanggaran')
 */
function switchTab(tabName) {
    // 1. Sembunyikan semua konten
    document.getElementById('content-booking').classList.add('hidden');
    document.getElementById('content-booking').classList.remove('block');

    document.getElementById('content-history').classList.add('hidden');
    document.getElementById('content-history').classList.remove('grid'); // History & Pelanggaran menggunakan grid

    document.getElementById('content-pelanggaran').classList.add('hidden');
    document.getElementById('content-pelanggaran').classList.remove('grid');

    // 2. Tampilkan konten yang dipilih
    const selectedContent = document.getElementById('content-' + tabName);
    selectedContent.classList.remove('hidden');

    if (tabName === 'booking') {
        selectedContent.classList.add('block');
    } else {
        selectedContent.classList.add('grid');
    }

    // 3. Reset style tombol tab (matikan semua highlight)
    const tabs = ['booking', 'history', 'pelanggaran'];
    tabs.forEach(t => {
        const btn = document.getElementById('tab-' + t);
        btn.classList.remove('text-blue-600', 'border-blue-600');
        btn.classList.add('border-transparent');
    });

    // 4. Highlight tombol tab yang aktif
    const activeBtn = document.getElementById('tab-' + tabName);
    activeBtn.classList.add('text-blue-600', 'border-blue-600');
    activeBtn.classList.remove('border-transparent');
}

// Event delegation untuk tab switching
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-tab-switch]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab-name');
            switchTab(tabName);
        });
    });
});

// Fungsi untuk mengarahkan ke halaman kode booking dengan booking_id
function viewBookingCode(bookingId) {
    window.location.href = 'index.php?page=booking&action=kode_booking&booking_id=' + bookingId;
}

function rescheduleBooking(bookingId) {
    window.location.href = 'index.php?page=booking&action=reschedule&booking_id=' + bookingId;
}

function hapusBooking(bookingId) {
    window.location.href = 'index.php?page=booking&action=hapus_booking&booking_id=' + bookingId;
}

// Modal Upload Foto Profil
const modalUploadFoto = document.getElementById('modalUploadFoto');
const avatarContainer = document.getElementById('avatarContainer');
const closeModalFoto = document.getElementById('closeModalFoto');
const cancelUploadFoto = document.getElementById('cancelUploadFoto');
const fotoProfilInput = document.getElementById('fotoProfil');
const previewImage = document.getElementById('previewImage');
const previewIcon = document.getElementById('previewIcon');
const previewContainer = document.getElementById('previewContainer');

// Open modal when avatar is clicked
if (avatarContainer) {
    avatarContainer.addEventListener('click', () => {
        modalUploadFoto.style.display = 'flex';
    });
}

// Close modal
if (closeModalFoto) {
    closeModalFoto.addEventListener('click', () => {
        modalUploadFoto.style.display = 'none';
    });
}

if (cancelUploadFoto) {
    cancelUploadFoto.addEventListener('click', () => {
        modalUploadFoto.style.display = 'none';
    });
}

// Close modal when clicking outside
if (modalUploadFoto) {
    modalUploadFoto.addEventListener('click', (e) => {
        if (e.target === modalUploadFoto) {
            modalUploadFoto.style.display = 'none';
        }
    });
}

// Preview image before upload
if (fotoProfilInput) {
    fotoProfilInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                // Remove icon if exists
                if (previewIcon) {
                    previewIcon.remove();
                }
                
                // Create or update image element
                if (!previewImage) {
                    const img = document.createElement('img');
                    img.id = 'previewImage';
                    img.className = 'w-full h-full object-cover';
                    img.src = event.target.result;
                    previewContainer.appendChild(img);
                } else {
                    previewImage.src = event.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    });
}

// ==================== AUTO-SWITCH TAB ON PAGE LOAD ====================
// Deteksi parameter URL untuk menentukan tab aktif saat page load
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Jika ada parameter pg_history, buka tab history
    if (urlParams.has('pg_history')) {
        switchTab('history');
    }
    // Jika ada parameter pg_pelanggaran, buka tab pelanggaran
    else if (urlParams.has('pg_pelanggaran')) {
        switchTab('pelanggaran');
    }
    // Default tab adalah 'booking' (sudah aktif di HTML)
});

// ==================== MODAL GANTI PASSWORD ====================

const modalChangePassword = document.getElementById('modalChangePassword');
const btnChangePassword = document.getElementById('btn-change-password');
const closeModalChangePassword = document.getElementById('closeModalChangePassword');
const cancelChangePassword = document.getElementById('cancelChangePassword');
const formChangePassword = document.getElementById('formChangePassword');

// Open modal when "Ganti Password" button is clicked
if (btnChangePassword) {
    btnChangePassword.addEventListener('click', () => {
        modalChangePassword.classList.remove('hidden');
        modalChangePassword.classList.add('flex');
        
        // Clear form inputs
        document.getElementById('oldPassword').value = '';
        document.getElementById('newPasswordProfile').value = '';
        document.getElementById('confirmPasswordProfile').value = '';
    });
}

// Close modal handlers
if (closeModalChangePassword) {
    closeModalChangePassword.addEventListener('click', () => {
        modalChangePassword.classList.add('hidden');
        modalChangePassword.classList.remove('flex');
    });
}

if (cancelChangePassword) {
    cancelChangePassword.addEventListener('click', () => {
        modalChangePassword.classList.add('hidden');
        modalChangePassword.classList.remove('flex');
    });
}

// Close modal when clicking outside
if (modalChangePassword) {
    modalChangePassword.addEventListener('click', (e) => {
        if (e.target === modalChangePassword) {
            modalChangePassword.classList.add('hidden');
            modalChangePassword.classList.remove('flex');
        }
    });
}

// Form validation before submit
if (formChangePassword) {
    formChangePassword.addEventListener('submit', (e) => {
        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPasswordProfile').value;
        const confirmPassword = document.getElementById('confirmPasswordProfile').value;
        
        // Client-side validation
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password baru minimal 8 karakter');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Password baru dan konfirmasi password tidak cocok');
            return;
        }
        
        if (oldPassword === newPassword) {
            e.preventDefault();
            alert('Password baru tidak boleh sama dengan password lama');
            return;
        }
        
        // Form will be submitted to ProfileController->change_password()
    });
}
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
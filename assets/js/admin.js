// ==================== DATA INITIALIZATION ====================

// Initialize global variables from data attributes
document.addEventListener('DOMContentLoaded', function() {
    const dataContainer = document.getElementById('booking-external-data') || document.getElementById('profile-data');
    if (dataContainer) {
        window.ASSET_BASE_PATH = dataContainer.dataset.basePath || '';
    }
    
    initAdminPage();
});

function initAdminPage() {
    // Setup event listeners for booking external form toggle
    const bookingCard = document.getElementById('booking-card');
    if (bookingCard) {
        bookingCard.addEventListener('click', function() {
            toggleForm(true);
        });
    }
    
    const btnKembali = document.querySelector('[data-toggle-form="false"]');
    if (btnKembali) {
        btnKembali.addEventListener('click', function(e) {
            e.preventDefault();
            toggleForm(false);
        });
    }
    
    // Setup tab switching buttons
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnHistory = document.getElementById('btn-history');
    
    if (btnUpcoming) {
        btnUpcoming.addEventListener('click', function() {
            switchTab('upcoming');
        });
    }
    
    if (btnHistory) {
        btnHistory.addEventListener('click', function() {
            switchTab('history');
        });
    }
}

//#region Tab Switching (booking external)
// Fungsi untuk Mengganti Tab
function switchTab(tabName) {
    const btnUpcoming = document.getElementById('btn-upcoming');
    const btnHistory = document.getElementById('btn-history');
    const contentUpcoming = document.getElementById('tab-upcoming');
    const contentHistory = document.getElementById('tab-history');

    if (tabName === 'upcoming') {
        // Style Button
        btnUpcoming.classList.add('active-tab');
        btnUpcoming.classList.remove('inactive-tab');
        btnHistory.classList.remove('active-tab');
        btnHistory.classList.add('inactive-tab');

        // Show/Hide Content
        contentUpcoming.classList.remove('hidden');
        contentHistory.classList.add('hidden');
    } else {
        // Style Button
        btnHistory.classList.add('active-tab');
        btnHistory.classList.remove('inactive-tab');
        btnUpcoming.classList.remove('active-tab');
        btnUpcoming.classList.add('inactive-tab');

        // Show/Hide Content
        contentHistory.classList.remove('hidden');
        contentUpcoming.classList.add('hidden');
    }
}

function gantiTabLaporan(tabName) {
            // 1. Sembunyikan semua konten tab
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });

            // 2. Reset style semua tombol tab
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => {
                btn.classList.remove('active', 'text-sky-600');
                btn.classList.add('text-slate-500');
                btn.style.borderBottom = "2px solid transparent";
            });

            // 3. Tampilkan konten yang dipilih
            document.getElementById('content-' + tabName).classList.add('active');

            // 4. Update style semua tombol yang sesuai dengan tab aktif
            const allButtons = document.querySelectorAll('[id^="btn-' + tabName + '"]');
            allButtons.forEach(btn => {
                btn.classList.remove('text-slate-500');
                btn.classList.add('active', 'text-sky-600');
                btn.style.borderBottom = "2px solid #0284c7"; // Sky-600
            });
        }

// Fungsi untuk Menampilkan/Menyembunyikan Form Booking
function toggleForm(show) {
    const card = document.getElementById('booking-card');
    const form = document.getElementById('booking-form');

    if (show) {
        card.classList.add('hidden');
        form.classList.remove('hidden');
    } else {
        form.classList.add('hidden');
        card.classList.remove('hidden');
    }
}
//#endregion
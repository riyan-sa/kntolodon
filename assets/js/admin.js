/**
 * ============================================================================
 * ADMIN.JS - Admin Dashboard Scripts
 * ============================================================================
 * 
 * Module untuk menangani admin-specific functionality:
 * - Booking External form toggle (card ↔ form view)
 * - Tab switching (Mendatang vs Histori untuk booking external)
 * - Tab switching untuk Laporan page
 * 
 * FUNGSI UTAMA:
 * 1. BOOKING EXTERNAL FORM TOGGLE
 *    - toggleForm(show): Switch between card view dan form view
 *    - Card view: Empty state dengan "Buat Booking" button
 *    - Form view: Full booking form dengan input fields
 * 
 * 2. TAB SWITCHING (BOOKING EXTERNAL)
 *    - switchTab(tabName): Toggle between 'upcoming' dan 'history'
 *    - Updates button styles (active-tab vs inactive-tab classes)
 *    - Shows/hides corresponding content containers
 * 
 * 3. TAB SWITCHING (LAPORAN)
 *    - gantiTabLaporan(tabName): Switch between report period tabs
 *    - Hides all tab contents
 *    - Shows selected tab content
 *    - Updates button styles dengan border-bottom highlight
 * 
 * TARGET ELEMENTS (BOOKING EXTERNAL):
 * - #booking-card: Card view dengan "Buat Booking" CTA
 * - #booking-form: Form view dengan input fields
 * - [data-toggle-form="false"]: Kembali button (form → card)
 * - #btn-upcoming: Mendatang tab button
 * - #btn-history: History tab button
 * - #tab-upcoming: Upcoming bookings content
 * - #tab-history: Historical bookings content
 * 
 * TARGET ELEMENTS (LAPORAN):
 * - .tab-btn: Report tab buttons
 * - .tab-content: Report content containers
 * - #content-{tabName}: Specific tab content (harian, mingguan, bulanan, tahunan)
 * 
 * DATA INITIALIZATION:
 * - Reads ASSET_BASE_PATH from data attributes
 * - Supports multiple data containers (#booking-external-data, #profile-data)
 * - Sets window.ASSET_BASE_PATH for global access
 * 
 * USAGE:
 * - Included in: view/admin/booking_external.php, view/admin/laporan.php
 * - Initializes on DOM ready
 * - Event listeners attached via initAdminPage()
 * 
 * CSS CLASSES:
 * - active-tab: Active tab styling (colored, highlighted)
 * - inactive-tab: Inactive tab styling (muted)
 * - hidden: Display none
 * 
 * @module admin
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

// ==================== DATA INITIALIZATION ====================

/**
 * Initialize global variables from data attributes
 * 
 * Reads ASSET_BASE_PATH dari hidden div dengan data attributes.
 * Supports multiple container IDs untuk reusability across admin pages.
 */
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
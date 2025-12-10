/**
 * ============================================================================
 * PENGATURAN.JS - System Settings Page Scripts
 * ============================================================================
 * 
 * Module untuk menangani system settings page (Super Admin only):
 * - Tab switching (Waktu Operasi vs Hari Libur)
 * - Modal management untuk CRUD operations
 * - Event delegation untuk dynamic content
 * 
 * FUNGSI UTAMA:
 * 1. TAB SWITCHING
 *    - switchTab(tabName): Toggle between 'waktu-operasi' dan 'hari-libur'
 *    - Initial state: waktu-operasi tab active
 * 
 * 2. WAKTU OPERASI MODALS
 *    - openEditWaktuModal(): Open edit modal dengan form fields
 *    - closeEditWaktuModal(): Close edit modal
 *    - Form fields: hari, jam_buka, jam_tutup, is_aktif
 *    - Submit to: ?page=admin&action=update_waktu_operasi
 * 
 * 3. HARI LIBUR MODALS
 *    - openAddLiburModal(): Open add modal (empty form)
 *    - openEditLiburModal(): Open edit modal (populated form)
 *    - openDeleteLiburModal(): Open delete confirmation modal
 *    - Close functions untuk each modal type
 *    - Submit to: create/update/delete hari_libur actions
 * 
 * 4. MODAL OVERLAY HANDLERS
 *    - setupModalOverlays(): Attach click handlers to overlays
 *    - Click overlay → close corresponding modal
 *    - Prevents event bubbling dari modal content
 * 
 * 5. EVENT DELEGATION
 *    - [data-tab-action]: Tab switching buttons
 *    - [data-modal-edit-waktu]: Edit waktu operasi buttons
 *    - [data-modal-add-libur]: Add hari libur button
 *    - [data-modal-edit-libur]: Edit hari libur buttons
 *    - [data-modal-delete-libur]: Delete hari libur buttons
 *    - Cancel/Close buttons untuk all modals
 * 
 * TAB STRUCTURE:
 * - waktu-operasi: 7 records (Senin-Minggu) dengan edit functionality
 * - hari-libur: Dynamic list dengan add/edit/delete operations
 * 
 * TARGET ELEMENTS (TABS):
 * - [data-tab-action]: Tab button elements
 * - #btn-tab-waktu: Waktu Operasi tab button
 * - #btn-tab-libur: Hari Libur tab button
 * - #tab-waktu-operasi: Waktu Operasi content
 * - #tab-hari-libur: Hari Libur content
 * 
 * TARGET ELEMENTS (WAKTU OPERASI MODAL):
 * - #modal-edit-waktu: Modal container
 * - #overlay-edit-waktu: Modal overlay
 * - #form-edit-waktu: Form element
 * - #edit-hari: Hari input (hidden, read-only)
 * - #edit-jam-buka: Jam Buka input (time)
 * - #edit-jam-tutup: Jam Tutup input (time)
 * - #edit-is-aktif: Is Aktif checkbox
 * - #btn-cancel-edit-waktu: Cancel button
 * 
 * TARGET ELEMENTS (HARI LIBUR MODALS):
 * - #modal-add-libur: Add modal container
 * - #modal-edit-libur: Edit modal container
 * - #modal-delete-libur: Delete confirmation modal
 * - Corresponding overlays, forms, inputs, buttons
 * 
 * DATA ATTRIBUTES PATTERN:
 * - data-hari: Hari name (Senin, Selasa, etc.)
 * - data-jam-buka: Jam Buka value (HH:MM)
 * - data-jam-tutup: Jam Tutup value (HH:MM)
 * - data-is-aktif: Is Aktif value (1 or 0)
 * - data-id: Hari Libur ID
 * - data-tanggal: Tanggal value (YYYY-MM-DD)
 * - data-tanggal-formatted: Formatted date for display
 * - data-keterangan: Keterangan/description
 * 
 * FORM SUBMISSIONS:
 * - All forms use traditional POST submission (no AJAX)
 * - Server-side validation dan database updates
 * - Redirect back to pengaturan page after success
 * 
 * VALIDATION:
 * - Server-side only (no client-side validation)
 * - Jam Tutup must be after Jam Buka
 * - Tanggal must not be past date
 * - Required fields enforced by HTML attributes
 * 
 * CSS CLASSES:
 * - tab-active: Active tab styling
 * - tab-inactive: Inactive tab styling
 * - hidden: Display none
 * 
 * USAGE:
 * - Included in: view/admin/pengaturan.php
 * - Access: Super Admin only
 * - Initializes on DOM ready
 * 
 * INTEGRATION:
 * - Server: AdminController pengaturan methods
 * - Database: waktu_operasi, hari_libur tables
 * - Validation: PengaturanModel::validateWaktuOperasi(), validateHariLibur()
 * - Impact: BookingController uses validation untuk block bookings
 * 
 * @module pengaturan
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initial state: show waktu operasi tab
    showTab('waktu-operasi');
    
    // Setup modal overlay click handlers
    setupModalOverlays();
    
    // Event delegation untuk tab buttons
    document.querySelectorAll('[data-tab-action]').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab-name');
            switchTab(tabName);
        });
    });
    
    // Event delegation untuk edit waktu operasi buttons
    document.querySelectorAll('[data-modal-edit-waktu]').forEach(btn => {
        btn.addEventListener('click', function() {
            const hari = this.getAttribute('data-hari');
            const jamBuka = this.getAttribute('data-jam-buka');
            const jamTutup = this.getAttribute('data-jam-tutup');
            const isAktif = this.getAttribute('data-is-aktif');
            openEditWaktuModal(hari, jamBuka, jamTutup, isAktif);
        });
    });
    
    // Event delegation untuk add libur button
    const btnAddLibur = document.querySelector('[data-modal-add-libur]');
    if (btnAddLibur) {
        btnAddLibur.addEventListener('click', openAddLiburModal);
    }
    
    // Event delegation untuk edit libur buttons
    document.querySelectorAll('[data-modal-edit-libur]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const tanggal = this.getAttribute('data-tanggal');
            const keterangan = this.getAttribute('data-keterangan');
            openEditLiburModal(id, tanggal, keterangan);
        });
    });
    
    // Event delegation untuk delete libur buttons
    document.querySelectorAll('[data-modal-delete-libur]').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const tanggal = this.getAttribute('data-tanggal-formatted');
            const keterangan = this.getAttribute('data-keterangan');
            openDeleteLiburModal(id, tanggal, keterangan);
        });
    });
    
    // Cancel/Close buttons for modals
    const btnCancelEditWaktu = document.getElementById('btn-cancel-edit-waktu');
    if (btnCancelEditWaktu) {
        btnCancelEditWaktu.addEventListener('click', closeEditWaktuModal);
    }
    
    const btnCancelAddLibur = document.getElementById('btn-cancel-add-libur');
    if (btnCancelAddLibur) {
        btnCancelAddLibur.addEventListener('click', closeAddLiburModal);
    }
    
    const btnCancelEditLibur = document.getElementById('btn-cancel-edit-libur');
    if (btnCancelEditLibur) {
        btnCancelEditLibur.addEventListener('click', closeEditLiburModal);
    }
    
    const btnCancelDeleteLibur = document.getElementById('btn-cancel-delete-libur');
    if (btnCancelDeleteLibur) {
        btnCancelDeleteLibur.addEventListener('click', closeDeleteLiburModal);
    }
});

// ==================== TAB SWITCHING ====================

function switchTab(tabName) {
    const tabs = {
        'waktu-operasi': {
            content: document.getElementById('tab-waktu-operasi'),
            button: document.getElementById('btn-tab-waktu')
        },
        'hari-libur': {
            content: document.getElementById('tab-hari-libur'),
            button: document.getElementById('btn-tab-libur')
        }
    };

    // Hide all tabs and reset button styles
    Object.values(tabs).forEach(tab => {
        tab.content.classList.add('hidden');
        tab.button.classList.remove('tab-active');
        tab.button.classList.add('tab-inactive');
    });

    // Show selected tab
    if (tabs[tabName]) {
        tabs[tabName].content.classList.remove('hidden');
        tabs[tabName].button.classList.remove('tab-inactive');
        tabs[tabName].button.classList.add('tab-active');
    }
}

function showTab(tabName) {
    switchTab(tabName);
}

// ==================== WAKTU OPERASI MODALS ====================

function openEditWaktuModal(hari, jamBuka, jamTutup, isAktif) {
    document.getElementById('edit-hari').value = hari;
    document.getElementById('edit-hari-display').value = hari;
    document.getElementById('edit-jam-buka').value = jamBuka;
    document.getElementById('edit-jam-tutup').value = jamTutup;
    
    if (isAktif == 1) {
        document.getElementById('edit-status-buka').checked = true;
    } else {
        document.getElementById('edit-status-tutup').checked = true;
    }
    
    document.getElementById('modal-edit-waktu').classList.remove('hidden');
}

function closeEditWaktuModal() {
    document.getElementById('modal-edit-waktu').classList.add('hidden');
}

// ==================== HARI LIBUR MODALS ====================

function openAddLiburModal() {
    // Reset form
    document.getElementById('add-tanggal').value = '';
    document.getElementById('add-keterangan').value = '';
    
    document.getElementById('modal-add-libur').classList.remove('hidden');
}

function closeAddLiburModal() {
    document.getElementById('modal-add-libur').classList.add('hidden');
}

function openEditLiburModal(id, tanggal, keterangan) {
    document.getElementById('edit-libur-id').value = id;
    document.getElementById('edit-libur-tanggal').value = tanggal;
    document.getElementById('edit-libur-keterangan').value = keterangan;
    
    document.getElementById('modal-edit-libur').classList.remove('hidden');
}

function closeEditLiburModal() {
    document.getElementById('modal-edit-libur').classList.add('hidden');
}

function openDeleteLiburModal(id, tanggal, keterangan) {
    document.getElementById('delete-libur-id').value = id;
    document.getElementById('delete-libur-tanggal').textContent = tanggal;
    document.getElementById('delete-libur-keterangan').textContent = keterangan;
    
    document.getElementById('modal-delete-libur').classList.remove('hidden');
}

function closeDeleteLiburModal() {
    document.getElementById('modal-delete-libur').classList.add('hidden');
}

// ==================== CLOSE MODALS ON OVERLAY CLICK ====================

function setupModalOverlays() {
    // Get all modal overlays
    const modals = [
        { overlay: document.getElementById('modal-edit-waktu'), closeFunc: closeEditWaktuModal },
        { overlay: document.getElementById('modal-add-libur'), closeFunc: closeAddLiburModal },
        { overlay: document.getElementById('modal-edit-libur'), closeFunc: closeEditLiburModal },
        { overlay: document.getElementById('modal-delete-libur'), closeFunc: closeDeleteLiburModal }
    ];

    // Add click event to each modal overlay
    modals.forEach(modal => {
        if (modal.overlay) {
            modal.overlay.addEventListener('click', (e) => {
                // Only close if clicked directly on overlay (not on modal content)
                if (e.target === modal.overlay) {
                    modal.closeFunc();
                }
            });
        }
    });
}

// ==================== CLOSE MODALS ON ESC KEY ====================

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeEditWaktuModal();
        closeAddLiburModal();
        closeEditLiburModal();
        closeDeleteLiburModal();
    }
});

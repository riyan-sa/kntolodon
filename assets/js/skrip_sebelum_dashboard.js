/**
 * ============================================================================
 * SKRIP_SEBELUM_DASHBOARD.JS - Pre-Dashboard Scripts (Legacy)
 * ============================================================================
 * 
 * DEPRECATED/LEGACY MODULE
 * 
 * NOTE: This file appears to be legacy code for a pre-dashboard landing page.
 * Some functionality overlaps dengan startpage.js.
 * 
 * FUNGSI UTAMA:
 * 1. NAVIGATION BUTTONS - Login, Register, Guest routing
 * 2. ROOM DESCRIPTION MODAL - Show/hide room description modal
 * 
 * NAVIGATION BUTTONS:
 * - #btn-login → redirect to login page
 * - #btn-register → redirect to register page  
 * - #btn-guest → redirect to guest page (note: guest feature may not be implemented)
 * 
 * ROOM DESCRIPTION MODAL:
 * - #room-desc-toggle: Button dengan data-description attribute
 * - #roomDescModal: Modal container
 * - #roomDescOverlay: Modal overlay (clickable untuk close)
 * - #roomDescClose: Close button
 * - #roomDescContent: Content container untuk description text
 * 
 * MODAL PATTERN:
 * - Click toggle button → read data-description → populate modal → show
 * - Click overlay or close button → hide modal
 * - Uses hidden/flex classes untuk show/hide
 * 
 * CURRENT STATUS:
 * - May be deprecated in favor of dashboard.js room modals
 * - Guest feature (#btn-guest) tidak diimplementasi di routing
 * - Room modal pattern duplicated di dashboard.js
 * 
 * POTENTIAL REFACTORING:
 * - Consolidate dengan startpage.js untuk navigation
 * - Remove guest button if feature not implemented
 * - Standardize room modal pattern across pages
 * 
 * USAGE:
 * - Include location uncertain (may not be actively used)
 * - Check for references in view templates
 * 
 * @module skrip_sebelum_dashboard
 * @version 1.0
 * @deprecated Consider consolidating with startpage.js and dashboard.js
 * @author PBL-Perpustakaan Team
 */

document.addEventListener('DOMContentLoaded', () => {
    // ==================== NAVIGATION BUTTONS ====================
    
    /**
     * Login button reference
     * @type {HTMLButtonElement}
     */
    const btnLogin = document.getElementById('btn-login');
    
    /**
     * Register button reference
     * @type {HTMLButtonElement}
     */
    const btnRegister = document.getElementById('btn-register');
    
    /**
     * Guest button reference (feature may not be implemented)
     * @type {HTMLButtonElement}
     */
    const btnGuest = document.getElementById('btn-guest');

    if (btnLogin) {
        btnLogin.addEventListener('click', (event) => {
            event.preventDefault();
            window.location.href = 'index.php?page=login';
        });
    }
    if (btnRegister) {
        btnRegister.addEventListener('click', (event) => {
            event.preventDefault();
            window.location.href = 'index.php?page=register';
        });
    }
    if (btnGuest) {
        btnGuest.addEventListener('click', (event) => {
            event.preventDefault();
            window.location.href = 'index.php?page=guest';
        });
    }

    // Room description modal handler
    const toggle = document.getElementById('room-desc-toggle');
    const modal = document.getElementById('roomDescModal');
    const overlay = document.getElementById('roomDescOverlay');
    const closeBtn = document.getElementById('roomDescClose');
    const content = document.getElementById('roomDescContent');

    if (toggle && modal && overlay && closeBtn && content) {
        function openModal(text) {
            content.textContent = text;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        toggle.addEventListener('click', function() {
            const desc = toggle.getAttribute('data-description') || 'Tidak ada deskripsi.';
            openModal(desc);
        });

        overlay.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    }
});
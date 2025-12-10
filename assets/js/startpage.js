/**
 * ============================================================================
 * STARTPAGE.JS - Landing Page Navigation Handler
 * ============================================================================
 * 
 * Module untuk menangani navigation buttons di landing page (startpage).
 * Provides routing ke login dan registration pages.
 * 
 * FUNGSI UTAMA:
 * - Handle login button click → redirect to login page
 * - Handle register button click → redirect to register page
 * 
 * PATTERN:
 * - Simple event delegation
 * - Direct window.location.href navigation (no AJAX)
 * - DOMContentLoaded initialization
 * 
 * TARGET ELEMENTS:
 * - #btn-login: Login button di landing page
 * - #btn-register: Register/Sign Up button di landing page
 * 
 * NAVIGATION FLOW:
 * 1. User visits root → shows startpage.php (if not logged in)
 * 2. Click "Masuk" → redirect to ?page=login
 * 3. Click "Daftar" → redirect to ?page=register
 * 
 * ROUTING:
 * - Login: index.php?page=login (LoginController::index)
 * - Register: index.php?page=register (RegisterController::index)
 * 
 * LANDING PAGE STRUCTURE:
 * - Hero section dengan product description
 * - Call-to-action buttons (Login & Register)
 * - Branding dan welcome message
 * 
 * USAGE:
 * - Included in: view/startpage.php
 * - Loaded only on unauthenticated root access
 * - Auto-initializes on DOM ready
 * 
 * AUTHENTICATION CHECK:
 * - Server-side: index.php checks $_SESSION['user']
 * - If logged in → redirect to dashboard
 * - If not logged in → show startpage
 * 
 * @module startpage
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

document.addEventListener('DOMContentLoaded', function() {
    // Login button
    const btnLogin = document.getElementById('btn-login');
    if (btnLogin) {
        btnLogin.addEventListener('click', function() {
            window.location.href = 'index.php?page=login';
        });
    }

    // Register button
    const btnRegister = document.getElementById('btn-register');
    if (btnRegister) {
        btnRegister.addEventListener('click', function() {
            window.location.href = 'index.php?page=register';
        });
    }
});

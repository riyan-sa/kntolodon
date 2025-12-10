/**
 * ============================================================================
 * AUTH.JS - Authentication & Logout Handler
 * ============================================================================
 * 
 * Global authentication module untuk menangani logout functionality.
 * Menggunakan IIFE (Immediately Invoked Function Expression) untuk encapsulation.
 * 
 * FUNGSI UTAMA:
 * - Handle logout button click event
 * - Redirect ke logout endpoint
 * 
 * PATTERN:
 * - IIFE: Prevent global scope pollution
 * - Event delegation: Single event listener for logout button
 * - Safe DOM ready check: Works for both loading and loaded states
 * 
 * TARGET ELEMENT:
 * - #btn-logout: Logout button (typically in navbar)
 * 
 * LOGOUT FLOW:
 * 1. User clicks logout button (#btn-logout)
 * 2. Redirect to: index.php?page=login&action=logout
 * 3. LoginController::logout() destroys session
 * 4. User redirected to login page
 * 
 * USAGE:
 * - Included in: view/components/navbar_admin.php, view layouts
 * - Automatically initializes on page load
 * - No configuration required
 * 
 * DOM READY PATTERN:
 * - Checks document.readyState to handle both:
 *   a. Document still loading → wait for DOMContentLoaded
 *   b. Document already loaded → execute immediately
 * 
 * SECURITY:
 * - No sensitive data handled in client-side
 * - Session destruction handled server-side
 * - Simple redirect-based logout (no AJAX)
 * 
 * @module auth
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

(function(){
  /**
   * Initialize logout button event listener
   * 
   * Attaches click handler to logout button yang redirect ke logout endpoint.
   * Uses vanilla JavaScript (no jQuery) untuk better performance.
   * 
   * @private
   * @function init
   */
  function init(){
    var btn = document.getElementById('btn-logout');
    if(btn){
      btn.addEventListener('click', function(){
        window.location.href = 'index.php?page=login&action=logout';
      });
    }
  }
  
  // Execute init when DOM is ready (handles both loading and loaded states)
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

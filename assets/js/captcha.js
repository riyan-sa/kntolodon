/**
 * ============================================================================
 * CAPTCHA.JS - CAPTCHA Refresh Handler
 * ============================================================================
 * 
 * Module untuk menangani refresh CAPTCHA image dengan cache-busting.
 * Supports both click-on-image dan refresh button triggers.
 * 
 * FUNGSI UTAMA:
 * - Refresh CAPTCHA image dengan cache-busting (query param timestamp)
 * - Handle click events pada image dan refresh button
 * 
 * PATTERN:
 * - IIFE: Prevent global scope pollution
 * - Cache-busting: Append timestamp query param untuk force reload
 * - Event delegation: Multiple triggers (image click + button click)
 * 
 * TARGET ELEMENTS:
 * - #captchaImage: CAPTCHA image element (<img>)
 * - #refresh-captcha: Refresh button (optional)
 * 
 * CACHE-BUSTING STRATEGY:
 * 1. Extract base URL (remove existing query params)
 * 2. Append ?r={timestamp} ke base URL
 * 3. Update img.src dengan new URL
 * 4. Store base URL in data-src untuk future refreshes
 * 
 * CAPTCHA GENERATION:
 * - Server endpoint: view/components/captcha.php
 * - Session storage: $_SESSION['code'] (server-side)
 * - Validation: Case-insensitive comparison in controllers
 * 
 * USAGE:
 * - Included in: view/login.php, view/register.php
 * - Auto-initializes on page load
 * - Auto-refreshes on first load to ensure fresh captcha
 * 
 * TRIGGERS:
 * - Click on CAPTCHA image itself
 * - Click on refresh button (#refresh-captcha)
 * 
 * DATA ATTRIBUTES:
 * - data-src: Stores base URL without query params
 * 
 * BROWSER COMPATIBILITY:
 * - Uses vanilla JavaScript (no polyfills needed)
 * - Works in all modern browsers
 * - Gracefully degrades if elements not found
 * 
 * @module captcha
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

(function(){
  /**
   * Refresh CAPTCHA image dengan cache-busting
   * 
   * Menggunakan timestamp sebagai query parameter untuk force browser
   * reload image tanpa cache. Base URL disimpan in data-src attribute.
   * 
   * @private
   * @function refresh
   * @param {HTMLImageElement} img - CAPTCHA image element
   */
  function refresh(img){
    if(!img) return;
    var src = img.getAttribute('data-src') || img.getAttribute('src') || '';
    var base = src.split('?')[0];
    var eek = Date.now().toString();
    img.setAttribute('src', base + '?r=' + eek);
    img.setAttribute('data-src', base); 
  }

  /**
   * Initialize CAPTCHA refresh functionality
   * 
   * Attaches event listeners dan triggers initial refresh.
   * Works dengan both image click dan dedicated refresh button.
   * 
   * @private
   * @function init
   */
  function init(){
    var img = document.getElementById('captchaImage');
    var btn = document.getElementById('refresh-captcha');
    if(img){
      refresh(img); // Initial refresh untuk ensure fresh captcha
      img.addEventListener('click', function(){ refresh(img); });
    }
    if(btn){
      btn.addEventListener('click', function(e){ e.preventDefault(); refresh(img); });
    }
  }

  // Execute init when DOM is ready
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

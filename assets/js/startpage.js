/**
 * Start Page Scripts
 * Handles login and register button navigation
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

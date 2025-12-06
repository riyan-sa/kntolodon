document.addEventListener('DOMContentLoaded', () => {
    // Login/Register/Guest navigation
    const btnLogin = document.getElementById('btn-login');
    const btnRegister = document.getElementById('btn-register');
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
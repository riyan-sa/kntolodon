// feedback.js - Handle feedback form interactions

// Handle rating selection
const ratingBtns = document.querySelectorAll('.rating-btn');
const ratingInput = document.getElementById('rating-input');
const submitBtn = document.getElementById('submit-btn');
const feedbackText = document.getElementById('feedback-text');

ratingBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        ratingInput.value = rating;

        // Remove selected class from all buttons
        ratingBtns.forEach(b => {
            b.classList.remove('selected');
            b.querySelector('div').classList.remove('ring-4', 'ring-blue-400');
        });

        // Add selected class to clicked button
        this.classList.add('selected');
        this.querySelector('div').classList.add('ring-4', 'ring-blue-400');

        // Enable submit button
        submitBtn.disabled = false;
        submitBtn.classList.remove('bg-gray-300', 'text-gray-500', 'border-gray-300', 'cursor-not-allowed');
        submitBtn.classList.add('bg-white', 'text-gray-600', 'border-gray-600', 'hover:bg-gray-50');

        // Optional: Auto-scroll to feedback text
        feedbackText.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
});

// Form validation
document.getElementById('feedback-form').addEventListener('submit', function(e) {
    if (!ratingInput.value) {
        e.preventDefault();
        alert('Silakan pilih rating terlebih dahulu (😊 atau ☹️)');
    }
});

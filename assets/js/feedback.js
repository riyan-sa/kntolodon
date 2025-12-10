/**
 * ============================================================================
 * FEEDBACK.JS - Feedback Form Handler
 * ============================================================================
 * 
 * Module untuk menangani feedback submission setelah booking selesai.
 * Support emoji-based rating (Senyum/Sedih) dengan form validation.
 * 
 * FUNGSI UTAMA:
 * - Handle rating selection (1 = Sedih, 5 = Senyum)
 * - Visual feedback untuk selected rating
 * - Enable/disable submit button based on rating selection
 * - Form validation before submission
 * 
 * RATING SCALE:
 * - 1 (☹️): Poor experience (Sedih/Red)
 * - 5 (😊): Excellent experience (Senyum/Green)
 * - Note: Only 2 options (no middle ratings)
 * 
 * FEEDBACK FLOW:
 * 1. User completes booking → DashboardController::selesai_booking()
 * 2. Redirect to feedback page → DashboardController::feedback()
 * 3. User selects rating (clicks emoji button)
 * 4. Rating value set in hidden input (#rating-input)
 * 5. Submit button enabled
 * 6. Optional: Auto-scroll to feedback text area
 * 7. User submits form → DashboardController::submit_feedback()
 * 8. Feedback saved to database → redirect to dashboard
 * 
 * VISUAL FEEDBACK:
 * - Selected button: ring-4 ring-blue-400 (blue ring highlight)
 * - Unselected buttons: No ring
 * - Submit button: Disabled until rating selected
 * 
 * FORM VALIDATION:
 * - Rating required: Must select 1 or 5
 * - Feedback text: Optional but encouraged
 * - Alert shown if no rating selected on submit
 * 
 * TARGET ELEMENTS:
 * - .rating-btn: Rating button elements (2 buttons: Senyum & Sedih)
 * - #rating-input: Hidden input field untuk store rating value
 * - #submit-btn: Form submit button (disabled by default)
 * - #feedback-text: Textarea untuk kritik & saran
 * - #feedback-form: Form element
 * 
 * DATABASE STORAGE:
 * - Table: feedback
 * - Fields: id_booking, skala_kepuasan (1 or 5), kritik_saran (text)
 * - One feedback per booking (enforced server-side)
 * 
 * USAGE:
 * - Included in: view/feedback.php
 * - Triggered after: Selesai booking workflow
 * - Session tracking: $_SESSION['feedback_booking_id']
 * 
 * @module feedback
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

// ==================== DOM ELEMENTS ====================

/**
 * Rating button elements (Senyum & Sedih)
 * @type {NodeList}
 */
const ratingBtns = document.querySelectorAll('.rating-btn');

/**
 * Hidden input field untuk store selected rating value
 * @type {HTMLInputElement}
 */
const ratingInput = document.getElementById('rating-input');

/**
 * Submit button (disabled until rating selected)
 * @type {HTMLButtonElement}
 */
const submitBtn = document.getElementById('submit-btn');

/**
 * Textarea untuk feedback text (kritik & saran)
 * @type {HTMLTextAreaElement}
 */
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

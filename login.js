/**
 * Login Page JavaScript
 * UZRS MOI Collection System
 */

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessage();

        // Get form data
        const formData = formToObject(loginForm);

        // Client-side validation
        if (!validateField('phone', formData.phone, 'phone')) {
            showMessage('சரியான கைபேசி எண்ணை உள்ளிடவும்', 'error');
            return;
        }

        if (!formData.password || formData.password.trim() === '') {
            showMessage('உங்கள் கடவுச்சொல்லை உள்ளிடவும்', 'error');
            return;
        }

        // Submit form
        const submitButton = loginForm.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const result = await apiCall('api/login.php', 'POST', formData);

        setButtonLoading(submitButton, false);

        if (result.success) {
            showMessage(result.message, 'success');
            
            // Check if mobile
            if (window.innerWidth <= 768) {
                redirectWithDelay('mobile_dashboard.php', 1500);
            } else {
                redirectWithDelay('dashboard.php', 1500);
            }
        } else {
            showMessage(result.message, 'error');
        }
    });
});

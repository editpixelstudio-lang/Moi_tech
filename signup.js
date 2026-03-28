/**
 * Signup Page JavaScript
 * UZRS MOI Collection System
 */

document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');

    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideMessage();

        // Get form data
        const formData = formToObject(signupForm);

        // Client-side validation
        if (!formData.full_name || formData.full_name.trim() === '') {
            showMessage('உங்கள் முழு பெயரை உள்ளிடவும்', 'error');
            return;
        }

        if (!validateField('phone', formData.phone.replace(/[^0-9]/g, ''), 'phone')) {
            showMessage('சரியான கைபேசி எண்ணை உள்ளிடவும் (10-15 இலக்கங்கள்)', 'error');
            return;
        }

        if (!validateField('password', formData.password, 'password')) {
            showMessage('கடவுச்சொல் குறைந்தது 6 எழுத்துக்கள் இருக்க வேண்டும்', 'error');
            return;
        }

        if (formData.password !== formData.confirm_password) {
            showMessage('கடவுச்சொற்கள் பொருந்தவில்லை', 'error');
            return;
        }

        // Submit form
        const submitButton = signupForm.querySelector('button[type="submit"]');
        setButtonLoading(submitButton, true);

        const result = await apiCall('api/signup.php', 'POST', formData);

        setButtonLoading(submitButton, false);

        if (result.success) {
            showMessage(result.message, 'success');
            signupForm.reset();
            redirectWithDelay('login.php', 2000);
        } else {
            showMessage(result.message, 'error');
        }
    });

    // Real-time validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');

    confirmPasswordField.addEventListener('input', () => {
        if (confirmPasswordField.value !== passwordField.value) {
            confirmPasswordField.classList.add('error');
        } else {
            confirmPasswordField.classList.remove('error');
        }
    });
});

/**
 * Main JavaScript Functions
 * UZRS MOI Collection System
 */

// Show message function
function showMessage(message, type = 'error') {
    const messageDiv = document.getElementById('message');
    if (!messageDiv) return;

    messageDiv.textContent = message;
    messageDiv.className = `message ${type} show`;

    // Auto hide after 5 seconds
    setTimeout(() => {
        messageDiv.classList.remove('show');
    }, 5000);
}

// Hide message function
function hideMessage() {
    const messageDiv = document.getElementById('message');
    if (messageDiv) {
        messageDiv.classList.remove('show');
    }
}

// Form validation helper
function validateField(field, value, type) {
    const patterns = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        phone: /^[0-9]{10,15}$/,
        password: /.{6,}/
    };

    if (patterns[type]) {
        return patterns[type].test(value);
    }

    return value.trim() !== '';
}

// API call helper
async function apiCall(url, method = 'POST', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        const result = await response.json();

        return result;
    } catch (error) {
        console.error('API Error:', error);
        return {
            success: false,
            message: 'பிணைய பிழை. மீண்டும் முயற்சிக்கவும்.'
        };
    }
}

// Disable/Enable button with loading state
function setButtonLoading(button, isLoading) {
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.innerHTML = '<span class="spinner"></span> செயலாக்குகிறது...';
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'சமர்ப்பி';
    }
}

// Form data to object
function formToObject(formElement) {
    const formData = new FormData(formElement);
    const data = {};

    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }

    return data;
}

// Redirect with delay
function redirectWithDelay(url, delay = 1500) {
    setTimeout(() => {
        window.location.href = url;
    }, delay);
}

// Sync Data Function
async function syncData() {
    const btn = document.getElementById('btnSyncData');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '🔄 Syncing...';
    
    try {
        const response = await fetch('api/sync_data.php');
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ ${result.message}\n\nUsers: ${result.stats.users}\nFunctions: ${result.stats.functions}\nCollections: ${result.stats.collections}\nExpenses: ${result.stats.expenses}`);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Sync Error:', error);
        alert('❌ Sync failed: Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

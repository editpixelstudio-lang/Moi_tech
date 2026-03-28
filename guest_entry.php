<?php
/**
 * Guest Collection Entry Page
 * UZRS MOI Collection System - For guests to enter their collection details via QR code
 */

require_once 'config/database.php';

// Get function ID from URL
$function_id = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;

if ($function_id <= 0) {
    die('<div style="padding:40px; text-align:center; font-family:Arial;"><h2>❌ தவறான QR குறியீடு</h2><p>சரியான விசேஷ இணைப்பை பயன்படுத்தவும்.</p></div>');
}

// Get function details from database
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT f.*, u.full_name as organizer_name FROM functions f JOIN users u ON f.user_id = u.id WHERE f.id = ?");

if (!$stmt) {
    die('<div style="padding:40px; text-align:center; font-family:Arial;"><h2>❌ Database Error</h2><p>Error: ' . htmlspecialchars($conn->error) . '</p></div>');
}

$stmt->bind_param("i", $function_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeDBConnection($conn);
    die('<div style="padding:40px; text-align:center; font-family:Arial;"><h2>❌ விசேஷம் கிடைக்கவில்லை</h2><p>இந்த விசேஷம் இருப்பதில் இல்லை அல்லது நீக்கப்பட்டது.</p></div>');
}

$function = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>மொய் பதிவு - <?php echo htmlspecialchars($function['function_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 15px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header .subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        .function-card {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
        }
        .function-card h2 {
            font-size: 20px;
            margin-bottom: 12px;
        }
        .function-detail {
            display: flex;
            align-items: center;
            margin: 8px 0;
            font-size: 15px;
        }
        .function-detail span {
            margin-right: 8px;
        }
        .form-container {
            padding: 30px 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 15px;
        }
        .form-group label .required {
            color: #d32f2f;
            margin-left: 3px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .checkbox-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .checkbox-label:hover {
            border-color: #667eea;
            background: #f5f7ff;
        }
        .checkbox-label input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"]:checked + span {
            color: #667eea;
            font-weight: bold;
        }
        .amount-highlight {
            background: #fff3e0;
            border-color: #ff9800 !important;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        .success-animation {
            text-align: center;
            padding: 40px 20px;
        }
        .success-animation .checkmark {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #4caf50;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        .success-animation .checkmark::before {
            content: "✓";
            color: white;
            font-size: 50px;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 15px 25px;
            text-align: center;
            color: #666;
            font-size: 13px;
            border-top: 1px solid #e0e0e0;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1565c0;
        }
        @media (max-width: 480px) {
            .header h1 {
                font-size: 20px;
            }
            .function-card h2 {
                font-size: 18px;
            }
            .form-container {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 மொய் பதிவு</h1>
            <p class="subtitle">UZRS MOI Collection System</p>
            
            <div class="function-card">
                <h2><?php echo htmlspecialchars($function['function_name']); ?></h2>
                <div class="function-detail">
                    <span>📅</span>
                    <span><?php echo date('d F Y', strtotime($function['function_date'])); ?></span>
                </div>
                <div class="function-detail">
                    <span>📍</span>
                    <span><?php echo htmlspecialchars($function['place']); ?></span>
                </div>
                <?php if (!empty($function['function_details'])): ?>
                <div class="function-detail">
                    <span>📝</span>
                    <span><?php echo htmlspecialchars($function['function_details']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-container" id="formContainer">
            <div class="info-box">
                💡 உங்கள் விவரங்களையும் மொய் தொகையையும் கீழே உள்ளிடவும். அனைத்து புலங்களும் கட்டாயம் நிரப்பப்பட வேண்டும்.
            </div>

            <div id="messageDiv" class="message"></div>

            <form id="guestForm">
                <input type="hidden" name="function_id" value="<?php echo $function_id; ?>">
                
                <div class="form-group">
                    <label>ஊர் (Village/Town) <span class="required">*</span></label>
                    <input type="text" name="location" id="location" required placeholder="உங்கள் ஊர் பெயர்">
                </div>

                <div class="form-group">
                    <label>இனிஷியல் (Initial)</label>
                    <input type="text" name="initial" id="initial" placeholder="உதாரணம்: A, S">
                </div>

                <div class="form-group">
                    <label>பெயர் (Name) <span class="required">*</span></label>
                    <input type="text" name="name1" id="name1" required placeholder="உங்கள் முழு பெயர்">
                </div>

                <div class="form-group">
                    <label>இரண்டாவது பெயர் (Second Name)</label>
                    <input type="text" name="name2" id="name2" placeholder="கூடுதல் பெயர் (விருப்பம்)">
                </div>

                <div class="form-group">
                    <label>தொழில் (Occupation)</label>
                    <input type="text" name="occupation" id="occupation" placeholder="உதாரணம்: மருத்துவர், ஆசிரியர்">
                </div>

                <div class="form-group">
                    <label>உறவு முறை (Relationship)</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="relationship" value="1" id="rel1">
                            <span>தாய்மாமன்</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="relationship" value="2" id="rel2">
                            <span>அத்தை - மாமா</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>வசிக்கும் ஊர் (Current Residence)</label>
                    <input type="text" name="village_going_to" id="village" placeholder="தற்போது வசிக்கும் ஊர்">
                </div>

                <div class="form-group">
                    <label>போன் எண் (Phone Number) <span class="required">*</span></label>
                    <input type="tel" name="phone" id="phone" required placeholder="உதாரணம்: 9876543210" pattern="[0-9]{10}" maxlength="10">
                </div>

                <div class="form-group">
                    <label>வாடிக்கையாளர் எண் (Customer Number)</label>
                    <input type="text" name="customer_number" id="customerNo" placeholder="குறிப்பு எண் (விருப்பம்)">
                </div>

                <div class="form-group">
                    <label>விவரம் (Description/Notes)</label>
                    <textarea name="description" id="description" placeholder="கூடுதல் விவரங்கள் (விருப்பம்)"></textarea>
                </div>

                <div class="form-group">
                    <label>மொய் தொகை (Collection Amount) <span class="required">*</span></label>
                    <input type="number" name="amount" id="amount" required placeholder="₹ 0.00" min="0" step="0.01" class="amount-highlight">
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    ✓ சமர்ப்பிக்கவும்
                </button>
            </form>
        </div>

        <div class="footer">
            <p>விசேஷம் ஏற்பாடு: <?php echo htmlspecialchars($function['organizer_name']); ?></p>
            <p style="margin-top: 5px; font-size: 12px;">Powered by UZRS MOI Collection System</p>
        </div>
    </div>

    <script>
        const form = document.getElementById('guestForm');
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('messageDiv');
        const formContainer = document.getElementById('formContainer');

        // Relationship checkboxes - allow only one selection
        const rel1 = document.getElementById('rel1');
        const rel2 = document.getElementById('rel2');
        
        rel1.addEventListener('change', function() {
            if (this.checked) rel2.checked = false;
        });
        
        rel2.addEventListener('change', function() {
            if (this.checked) rel1.checked = false;
        });

        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validation
            const location = document.getElementById('location').value.trim();
            const name1 = document.getElementById('name1').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const amount = document.getElementById('amount').value;

            if (!location || !name1 || !phone || !amount) {
                showMessage('கட்டாய புலங்களை நிரப்பவும்!', 'error');
                return;
            }

            if (phone.length !== 10) {
                showMessage('சரியான 10 இலக்க போன் எண்ணை உள்ளிடவும்!', 'error');
                return;
            }

            if (parseFloat(amount) <= 0) {
                showMessage('சரியான தொகையை உள்ளிடவும்!', 'error');
                return;
            }

            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite;">⏳</span> சமர்ப்பிக்கிறது...';

            // Get relationship value
            let relationship = 0;
            if (rel1.checked) relationship = 1;
            if (rel2.checked) relationship = 2;

            // Prepare form data
            const formData = new FormData(form);
            formData.set('relationship', relationship);
            formData.set('guest_entry', '1'); // Mark as guest entry

            try {
                const response = await fetch('api/save_collection.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Show success message
                    showSuccess();
                } else {
                    showMessage(result.message || 'பிழை ஏற்பட்டது. மீண்டும் முயற்சிக்கவும்.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '✓ சமர்ப்பிக்கவும்';
                }
            } catch (error) {
                console.error('Error:', error);
                showMessage('இணைய இணைப்பு பிழை. மீண்டும் முயற்சிக்கவும்.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '✓ சமர்ப்பிக்கவும்';
            }
        });

        function showMessage(message, type) {
            messageDiv.className = 'message ' + type;
            messageDiv.textContent = message;
            messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            if (type === 'error') {
                setTimeout(() => {
                    messageDiv.className = 'message';
                }, 5000);
            }
        }

        function showSuccess() {
            formContainer.innerHTML = `
                <div class="success-animation">
                    <div class="checkmark"></div>
                    <h2 style="color: #4caf50; margin-bottom: 15px;">வெற்றி! 🎉</h2>
                    <p style="font-size: 16px; color: #333; margin-bottom: 10px;">உங்கள் மொய் விவரங்கள் வெற்றிகரமாக பதிவு செய்யப்பட்டது!</p>
                    <p style="font-size: 14px; color: #666; margin-bottom: 25px;">விசேஷத்தில் கலந்துகொண்டமைக்கு மிக்க நன்றி! 🙏</p>
                    <button onclick="location.reload()" style="padding: 12px 30px; background: #4caf50; color: white; border: none; border-radius: 8px; font-size: 16px; cursor: pointer;">
                        மற்றொரு பதிவு செய்ய
                    </button>
                </div>
            `;
        }
    </script>
    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</body>
</html>

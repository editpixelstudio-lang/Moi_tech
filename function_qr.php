<?php
/**
 * Function QR Code Display Page
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get function ID from URL
$function_id = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;

if ($function_id <= 0) {
    die('விசேஷம் தேர்வு செய்யப்படவில்லை');
}

// Get function details from database
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM functions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $function_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('விசேஷம் கிடைக்கவில்லை');
}

$function = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);

// Build the guest entry URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
$guest_url = $base_url . '/guest_entry.php?function_id=' . $function_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR குறியீடு - <?php echo htmlspecialchars($function['function_name']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 10mm;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .qr-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 12mm;
            width: 210mm;
            height: 297mm;
            max-height: 297mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
            overflow: hidden;
        }
        .qr-header {
            margin-bottom: 5px;
            flex-shrink: 0;
        }
        .qr-header h1 {
            color: #333;
            margin: 0 0 5px 0;
            font-size: 26px;
        }
        .qr-header .subtitle {
            color: #666;
            font-size: 13px;
            margin: 0;
        }
        .function-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 12px;
            margin: 5px 0;
            text-align: left;
            flex-shrink: 0;
        }
        .function-info h2 {
            color: #667eea;
            margin: 0 0 6px 0;
            font-size: 20px;
        }
        .info-row {
            display: flex;
            margin: 4px 0;
            padding: 3px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            min-width: 100px;
        }
        .info-value {
            color: #333;
            flex: 1;
        }
        #qrcode {
            display: flex;
            justify-content: center;
            margin: 10px auto;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 3px solid #667eea;
            flex-shrink: 0;
        }
        .instructions {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 5px;
            text-align: left;
            flex-shrink: 0;
        }
        .instructions h3 {
            font-size: 15px;
            margin: 0 0 5px 0;
        }
        .instructions p {
            font-size: 12px;
            margin: 3px 0;
        }
        .instructions h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .instructions p {
            margin: 5px 0;
            color: #555;
        }
        .button-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
            flex-shrink: 0;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .url-display {
            background: #f5f5f5;
            padding: 6px 8px;
            border-radius: 4px;
            word-break: break-all;
            font-family: monospace;
            font-size: 10px;
            color: #666;
            margin-top: 8px;
            flex-shrink: 0;
        }
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .button-group {
                display: none;
            }
            .qr-container {
                box-shadow: none;
                border: none;
                border-radius: 0;
                width: 210mm;
                min-height: 297mm;
                padding: 15mm;
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <div class="qr-header">
            <h1>📱 மொய் சேகரிப்பு QR குறியீடு</h1>
            <p class="subtitle">UZRS MOI Collection System</p>
        </div>

        <div class="function-info">
            <h2><?php echo htmlspecialchars($function['function_name']); ?></h2>
            <div class="info-row">
                <span class="info-label">📅 தேதி:</span>
                <span class="info-value"><?php echo date('d F Y', strtotime($function['function_date'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">📍 இடம்:</span>
                <span class="info-value"><?php echo htmlspecialchars($function['place']); ?></span>
            </div>
            <?php if (!empty($function['function_details'])): ?>
            <div class="info-row">
                <span class="info-label">📝 விவரம்:</span>
                <span class="info-value"><?php echo nl2br(htmlspecialchars($function['function_details'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="instructions">
            <h3>🔍 விருந்தினர்களுக்கான வழிமுறைகள்:</h3>
            <p>1. மேலுள்ள QR குறியீட்டை உங்கள் கைபேசியின் கேமராவால் ஸ்கேன் செய்யவும்</p>
            <p>2. திறக்கும் இணைப்பை கிளிக் செய்யவும்</p>
            <p>3. உங்கள் விவரங்களையும் மொய் தொகையையும் உள்ளிடவும்</p>
            <p>4. சமர்ப்பிக்கவும்!</p>
        </div>

        <div id="qrcode"></div>

        <div class="url-display">
            <strong>இணைப்பு:</strong> <?php echo htmlspecialchars($guest_url); ?>
        </div>

        <div class="button-group">
            <button onclick="window.print()" class="btn btn-primary">🖨️ அச்சிடு</button>
            <a href="index.php" class="btn btn-secondary">← திரும்பு</a>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR Code
        const guestUrl = <?php echo json_encode($guest_url); ?>;
        
        new QRCode(document.getElementById("qrcode"), {
            text: guestUrl,
            width: 280,
            height: 280,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>

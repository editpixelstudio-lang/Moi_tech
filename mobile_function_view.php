<?php
/**
 * Mobile Function View - List Collections
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;
if ($functionId <= 0) {
    redirect('mobile_dashboard.php');
}

$user = getCurrentUser();
$conn = getDBConnection();

// Get function details
$stmt = $conn->prepare("SELECT * FROM functions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $functionId, $user['id']);
$stmt->execute();
$function = $stmt->get_result()->fetch_assoc();

if (!$function) {
    redirect('mobile_dashboard.php');
}

// Get collections
$colStmt = $conn->prepare("SELECT * FROM collections WHERE function_id = ? ORDER BY id DESC");
$colStmt->bind_param("i", $functionId);
$colStmt->execute();
$collectionsResult = $colStmt->get_result();
$collections = [];
while ($row = $collectionsResult->fetch_assoc()) {
    $collections[] = $row;
}

$stmt->close();
$colStmt->close();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($function['function_name']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
    <style>
        body {
            background-color: #ffffff;
            padding-bottom: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .header {
            background: white;
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .back-btn {
            font-size: 22px;
            text-decoration: none;
            color: #1f2937;
        }
        .title-area h1 {
            font-size: 18px;
            margin: 0;
            font-weight: 700;
            color: #111827;
        }
        .title-area p {
            font-size: 12px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }
        .search-bar {
            padding: 10px 20px;
            background: white;
            border-bottom: 1px solid #f3f4f6;
        }
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #f9fafb;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-input:focus {
            border-color: #4f46e5;
            background: white;
        }
        .list-container {
            padding: 10px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .collection-item {
            background: white;
            padding: 12px 15px;
            border: 1px solid #f3f4f6;
            border-radius: 10px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        .collection-item:active {
            background: #f9fafb;
        }
        .item-info h3 {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }
        .item-info p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        .item-amount {
            font-weight: 700;
            color: #059669;
            font-size: 16px;
            text-align: right;
        }
        .pay-back-btn {
            background: #eff6ff;
            color: #4f46e5;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .pay-back-btn:active {
            background: #e0e7ff;
        }
    </style>
</head>
<body>

    <div class="header">
        <a href="mobile_dashboard.php" class="back-btn">←</a>
        <!-- Function details and add button removed -->
    </div>

    <div class="search-bar">
        <input type="text" class="search-input" placeholder="பெயர் அல்லது ஊர் தேட..." onkeyup="filterList(this.value)">
    </div>

    <div class="list-container" id="collectionList">
        <?php foreach ($collections as $col): 
            $fullName = trim($col['initial_name'] . ' ' . $col['name1'] . ' ' . $col['name2']);
            $location = $col['location'] ?: 'ஊர் இல்லை';
        ?>
        <div class="collection-item" data-search="<?php echo strtolower($fullName . ' ' . $location); ?>">
            <div class="item-info">
                <h3><?php echo htmlspecialchars($fullName); ?></h3>
                <p>📍 <?php echo htmlspecialchars($location); ?></p>
                <button class="pay-back-btn" onclick='openPayBackModal(<?php echo json_encode($col); ?>)'>திருப்பிச் செய்</button>
            </div>
            <div class="item-amount">
                ₹<?php echo number_format($col['total_amount']); ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($collections)): ?>
        <div class="text-center p-4 text-gray-500">பதிவுகள் இல்லை</div>
        <?php endif; ?>
    </div>

    <!-- Pay Back Modal -->
    <div id="payBackModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>மொய் திருப்பிச் செய்தல்</h2>
                <span class="close" onclick="closeModal('payBackModal')">&times;</span>
            </div>
            <form id="payBackForm">
                <input type="hidden" name="related_collection_id" id="pb_relatedId">
                
                <div class="form-group">
                    <label>யாருக்கு</label>
                    <input type="text" name="to_name" id="pb_toName" required readonly style="background: #f3f4f6;">
                </div>
                
                <!-- Function and Place fields removed as per request -->
                
                <div class="form-group">
                    <label>தேதி</label>
                    <input type="date" name="expense_date" id="pb_date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>தொகை (₹)</label>
                    <input type="number" name="amount" id="pb_amount" required placeholder="0.00">
                    <small style="color: #666;">அவர்கள் செய்தது: ₹<span id="pb_receivedAmount"></span></small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">சேமி</button>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        function filterList(query) {
            const items = document.querySelectorAll('.collection-item');
            query = query.toLowerCase();
            
            items.forEach(item => {
                const text = item.getAttribute('data-search');
                if (text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function openPayBackModal(data) {
            const fullName = [data.initial_name, data.name1, data.name2].filter(Boolean).join(' ');
            
            document.getElementById('pb_relatedId').value = data.id;
            document.getElementById('pb_toName').value = fullName;
            // document.getElementById('pb_place').value = data.location;
            document.getElementById('pb_receivedAmount').textContent = data.total_amount;
            
            document.getElementById('payBackModal').style.display = 'block';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        document.getElementById('payBackForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const response = await fetch('api/save_expense.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('payBackModal');
                form.reset();
                showMessage('மொய் விபரம் சேமிக்கப்பட்டது', 'success');
                // Optional: Mark the item as paid back in UI
            } else {
                showMessage(result.message, 'error');
            }
        });
    </script>
</body>
</html>

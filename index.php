<?php
/**
 * Index Page - Function Management
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Functions - UZRS MOI Collection</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mobile.css">
    <link rel="stylesheet" href="css/status.css">
</head>
<body class="dashboard-body">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>UZRS மொய் கணக்கு</h2>
            </div>
            <div class="nav-user">
                <span>வணக்கம், <?php echo htmlspecialchars($user['name']); ?>!</span>
                <!-- Automatic sync - status indicator replaces manual button -->
                <div id="connectionStatus" class="status-indicator status-offline" title="Connection Status">
                    <span class="status-icon">●</span> <span class="status-text">OFFLINE</span>
                </div>
                <?php if (isset($user['role']) && $user['role'] === 'super_admin'): ?>
                <a href="user_management.php" class="btn-dashboard">👥 Users</a>
                <a href="function_management.php" class="btn-dashboard">🎉 Functions</a>
                <?php endif; ?>
                <a href="index.php" class="btn-dashboard">விசேஷங்கள்</a>
                <a href="api/logout.php" class="btn-logout">வெளியேறு</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1>விசேஷங்கள் நிர்வாகம்</h1>
                    <p class="subtitle">உங்கள் விசேஷங்களை உருவாக்கி நிர்வகிக்கவும்</p>
                </div>
                <button id="createNewBtn" class="btn btn-primary" style="width: auto; padding: 12px 30px;">
                    <span style="font-size: 20px; margin-right: 5px;">+</span> புதிய விசேஷம்
                </button>
            </div>

            <div class="recent-events">
                <h2>உங்கள் விசேஷங்கள்</h2>
                <div id="functionsContainer">
                    <p class="empty-state">இதுவரை எந்த விசேஷமும் உருவாக்கப்படவில்லை. புதிய விசேஷத்தை சேர்க்க "புதிய விசேஷம்" பட்டனை கிளிக் செய்யவும்!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>புதிய விசேஷம் உருவாக்கு</h2>
                <span class="close">&times;</span>
            </div>
            <form id="createFunctionForm">
                <div id="modalMessage" class="message"></div>
                
                <div class="form-group">
                    <label for="functionName">விசேஷத்தின் பெயர் *</label>
                    <input type="text" id="functionName" name="functionName" required placeholder="விசேஷத்தின் பெயரை உள்ளிடவும்">
                </div>

                <div class="form-group">
                    <label for="functionDate">தேதி *</label>
                    <input type="date" id="functionDate" name="functionDate" required>
                </div>

                <div class="form-group">
                    <label for="place">இடம் *</label>
                    <input type="text" id="place" name="place" required placeholder="இடத்தை உள்ளிடவும்">
                </div>

                <div class="form-group">
                    <label for="functionDetails">விசேஷ விவரங்கள்</label>
                    <textarea id="functionDetails" name="functionDetails" rows="4" placeholder="கூடுதல் விவரங்கள் (விருப்பினால்)"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">உருவாக்கு</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>விசேஷத்தை திருத்து</h2>
                <span class="close" id="closeEditModal">&times;</span>
            </div>
            <form id="editFunctionForm">
                <div id="editModalMessage" class="message"></div>
                <input type="hidden" id="editFunctionId" name="functionId">
                
                <div class="form-group">
                    <label for="editFunctionName">விசேஷத்தின் பெயர் *</label>
                    <input type="text" id="editFunctionName" name="functionName" required placeholder="விசேஷத்தின் பெயரை உள்ளிடவும்">
                </div>

                <div class="form-group">
                    <label for="editFunctionDate">தேதி *</label>
                    <input type="date" id="editFunctionDate" name="functionDate" required>
                </div>

                <div class="form-group">
                    <label for="editPlace">இடம் *</label>
                    <input type="text" id="editPlace" name="place" required placeholder="இடத்தை உள்ளிடவும்">
                </div>

                <div class="form-group">
                    <label for="editFunctionDetails">விசேஷ விவரங்கள்</label>
                    <textarea id="editFunctionDetails" name="functionDetails" rows="4" placeholder="கூடுதல் விவரங்கள் (விருப்பினால்)"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">புதுப்பி</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>விசேஷத்தை நீக்கு</h2>
                <span class="close" id="closeDeleteModal">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px; text-align: center;">
                <p>இந்த விசேஷத்தை நிச்சயமாக நீக்க விரும்புகிறீர்களா?</p>
                <p style="color: #d32f2f; font-weight: bold; margin-top: 10px;">⚠️ எச்சரிக்கை: இந்த விசேஷம் தொடர்பான அனைத்து மொய் விவரங்களும் நிரந்தரமாக நீக்கப்படும்!</p>
                <div id="deleteModalMessage" class="message"></div>
                <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                    <button id="confirmDeleteBtn" class="btn" style="background-color: #d32f2f; color: white;">ஆம், நீக்கு</button>
                    <button id="cancelDeleteBtn" class="btn btn-secondary">ரத்து</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Handover Modal (ஒப்படைத்தல்) -->
    <div id="handoverModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>💰 ஒப்படைத்தல் - காசு கணக்கு</h2>
                <span class="close" id="closeHandoverModal">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <input type="hidden" id="handoverFunctionId">
                
                <!-- Function Summary Section -->
                <div id="handoverFunctionSummary" style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 id="handoverFunctionName" style="margin: 0 0 10px 0; color: #333;"></h3>
                    <p id="handoverFunctionDate" style="margin: 5px 0;"><strong>📅 தேதி:</strong> <span></span></p>
                    <p id="handoverFunctionPlace" style="margin: 5px 0;"><strong>📍 இடம்:</strong> <span></span></p>
                </div>
                
                <!-- Collection Summary -->
                <div id="handoverCollectionSummary" style="background: #e8f5e9; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #2e7d32;">📊 சேகரிப்பு சுருக்கம்</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <p style="margin: 5px 0;"><strong>மொத்த பதிவுகள்:</strong> <span id="summaryTotalEntries">0</span></p>
                        <p style="margin: 5px 0;"><strong>மொத்த தொகை:</strong> ₹<span id="summaryTotalAmount">0</span></p>
                        <p style="margin: 5px 0;"><strong>மொத்த செலவுகள்:</strong> ₹<span id="summaryTotalExpense">0</span></p>
                        <p style="margin: 5px 0;"><strong>இருப்பு தொகை:</strong> ₹<span id="summaryNetAmount">0</span></p>
                    </div>
                </div>
                
                <!-- System Denomination Summary -->
                <div id="systemDenomSummary" style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #e65100;">📋 சேகரித்த நோட்டு விவரம்</h4>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background: #ffe0b2;">
                                <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">நோட்டு</th>
                                <th style="padding: 8px; text-align: center; border: 1px solid #ddd;">எண்ணிக்கை</th>
                                <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">தொகை</th>
                            </tr>
                        </thead>
                        <tbody id="systemDenomBody">
                        </tbody>
                    </table>
                </div>

                <!-- Manual Denomination Entry -->
                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 15px 0; color: #1565c0;">✏️ கையில் உள்ள நோட்டு விவரம்</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #bbdefb;">
                                <th style="padding: 8px; text-align: left; border: 1px solid #ddd;">நோட்டு</th>
                                <th style="padding: 8px; text-align: center; border: 1px solid #ddd;">எண்ணிக்கை</th>
                                <th style="padding: 8px; text-align: right; border: 1px solid #ddd;">தொகை</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹500</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover500" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover500Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹200</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover200" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover200Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹100</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover100" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover100Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹50</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover50" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover50Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹20</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover20" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover20Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹10</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover10" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover10Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹5</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover5" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover5Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹2</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover2" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover2Total">₹0</td>
                            </tr>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹1</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                                    <input type="number" id="handover1" class="handover-denom-input" value="0" min="0" style="width: 80px; text-align: center; padding: 5px;">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;" id="handover1Total">₹0</td>
                            </tr>
                            <tr style="background: #1565c0; color: white; font-weight: bold;">
                                <td style="padding: 10px; border: 1px solid #ddd;">மொத்தம்</td>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;" id="handoverTotalCount">0</td>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: right;" id="handoverGrandTotal">₹0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Difference Section -->
                <div id="handoverDifference" style="background: #ffebee; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 10px 0; color: #c62828;">⚖️ வித்தியாசம்</h4>
                    <p style="margin: 5px 0; font-size: 18px;"><strong>கணக்கு தொகை:</strong> ₹<span id="diffSystemAmount">0</span></p>
                    <p style="margin: 5px 0; font-size: 18px;"><strong>கையில் உள்ள தொகை:</strong> ₹<span id="diffHandAmount">0</span></p>
                    <p style="margin: 5px 0; font-size: 20px; border-top: 2px solid #c62828; padding-top: 10px;">
                        <strong>வித்தியாசம்:</strong> <span id="diffAmount" style="font-weight: bold;">₹0</span>
                    </p>
                </div>

                <div style="display: flex; justify-content: center; gap: 15px;">
                    <button id="printHandoverBtn" class="btn btn-primary" style="background: #4caf50; padding: 12px 30px;">
                        🖨️ அச்சிடு
                    </button>
                    <button id="closeHandoverBtn" class="btn btn-secondary" style="padding: 12px 30px;">
                        ❌ மூடு
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Print Content for Thermal Printer -->
    <div id="handoverPrintContent" style="display: none;"></div>

    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/connection_manager.js?v=<?php echo time(); ?>"></script>
    <script src="js/functions.js?v=<?php echo time(); ?>"></script>
</body>
</html>

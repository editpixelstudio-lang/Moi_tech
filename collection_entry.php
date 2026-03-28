<?php
/**
 * Collection Entry Page - Tamil Cash Handling / Collection Entry Screen
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if function_id is provided
if (!isset($_GET['function_id']) || empty($_GET['function_id'])) {
    redirect('index.php');
}

$functionId = intval($_GET['function_id']);
$user = getCurrentUser();
$userId = $user['id'];

// Get function details
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT function_name, function_date, place, function_details FROM functions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $functionId, $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeDBConnection($conn);
    redirect('index.php');
}

$function = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Entry - <?php echo htmlspecialchars($function['function_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/collection.css">
    <link rel="stylesheet" href="css/mobile.css">
    <link rel="stylesheet" href="css/receipt.css">
    <link rel="stylesheet" href="css/status.css">
    <link href="https://fonts.googleapis.com/css2?family=Kavivanar&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>UZRS மொய் வசூல்</h2>
            </div>
            <div class="nav-user">
                <span>வணக்கம், <?php echo htmlspecialchars($user['name']); ?>!</span>
                <button id="btnComputerNumber" class="btn-dashboard" style="background-color: #607d8b; border: none; cursor: pointer;" title="கணினி எண்ணை மாற்ற">
                    🖥️ <?php echo isset($_SESSION['computer_number']) && !empty($_SESSION['computer_number']) ? htmlspecialchars($_SESSION['computer_number']) : 'Set System #'; ?>
                </button>
                <!-- Sync button removed for automatic sync -->
                <div id="connectionStatus" class="status-indicator status-offline" title="Connection Status">
                    <span class="status-icon">●</span> <span class="status-text">OFFLINE</span>
                </div>
                <a href="index.php" class="btn-dashboard">விசேஷங்கள்</a>
                <a href="api/logout.php" class="btn-logout">வெளியேறு</a>
            </div>
        </div>
    </nav>

    <!-- Computer Number Modal -->
    <div id="computerNumberModal" class="modal-overlay" style="display:flex;">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header">
                <h2>கணினி எண்ணை உள்ளிடவும்</h2>
            </div>
            <div class="modal-body">
                <p style="margin-bottom: 15px;">இந்த விசேஷத்திற்கு கணினி எண்ணை உள்ளிடவும்:</p>
                <input type="text" id="computerNumberInput" class="input-field" placeholder="உதாரணம்: 001" style="width: 100%; padding: 10px;">
                <div id="computerNumberMessage" style="color: red; margin-top: 10px; display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="window.location.href='index.php'">ரத்து செய்</button>
                <button class="btn-confirm" id="btnSetComputerNumber">தொடரவும்</button>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="dashboard-content">
            <!-- Function Header -->
            <div class="function-header">
                <div class="function-info">
                    <h1><?php echo htmlspecialchars($function['function_name']); ?> <span class="function-meta-inline">📅 <?php echo date('d-m-Y', strtotime($function['function_date'])); ?> 📍 <?php echo htmlspecialchars($function['place']); ?></span></h1>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary">← விசேஷங்களுக்கு திரும்ப</a>
                </div>
            </div>

            <!-- Main Content Flex Container -->
            <div style="display: flex; gap: 15px; align-items: stretch; flex: 1; overflow: hidden;">
                <!-- Left Side: Collection Entry Form (55%) -->
                <div style="flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding-right: 10px; height: 100%;">
            <!-- Collection Entry Form - Exact Layout Match -->
            <div class="collection-form-container">
                <form id="collectionForm">
                    <input type="hidden" name="function_id" value="<?php echo $functionId; ?>">
                    <input type="hidden" id="collectionDate" name="collectionDate" value="<?php echo date('Y-m-d'); ?>">

                    <!-- Quick Actions moved to Denomination Panel -->
                    <div style="margin-bottom: 5px;"></div>

                    <!-- Traditional Two-Column Form Layout -->
                    <div style="background: #e8f5e9; padding: 15px; border-radius: 8px; border: 2px solid #4caf50;">
                        <!-- Serial Number Row with Checkboxes -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">வரிசை எண் *</label>
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <input type="text" id="serialNo" name="serialNo" class="input-field notranslate" autocomplete="off" style="width: 120px; padding: 6px; font-size: 14px; text-align: center; font-weight: bold; color: #1976d2;" value="" readonly>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; background: #fff; padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc;">
                                    <input type="checkbox" id="relationship1" name="relationship" value="1" style="width: 16px; height: 16px;">
                                    <span style="font-size: 13px;">தாய்மாமன்</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; background: #fff; padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc;">
                                    <input type="checkbox" id="relationship2" name="relationship" value="2" style="width: 16px; height: 16px;">
                                    <span style="font-size: 13px;">அத்தை - மாமா</span>
                                </label>
                            </div>
                        </div>

                        <!-- Contact Number Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">தொடர்பு எண்</label>
                            <div style="position: relative;">
                                <input type="tel" id="phone" name="phone" class="input-field notranslate" autocomplete="off" style="width: 100%;" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length > 10) this.value=this.value.slice(0,10);">
                                <div id="autocompletePhone" class="autocomplete-dropdown"></div>
                            </div>
                        </div>

                        <!-- Oor (Town) Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">ஊர் *</label>
                            <div style="position: relative;">
                                <input type="text" id="location" name="location" class="input-field" autocomplete="off" required style="width: 100%;">
                                <div id="autocompleteLocation" class="autocomplete-dropdown"></div>
                            </div>
                        </div>

                        <!-- Vasikkum Oor Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">வசிக்கும் ஊர்</label>
                            <div style="position: relative;">
                                <input type="text" id="village" name="villageGoingTo" class="input-field" autocomplete="off" style="width: 100%;">
                                <div id="autocompleteVillage" class="autocomplete-dropdown"></div>
                            </div>
                        </div>

                        <!-- First Name (Initial + Name) Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">பெயர் 1 *</label>
                            <div style="display: flex; gap: 8px;">
                                <div style="flex: 0 0 100px; position: relative;">
                                    <input type="text" id="initial" name="initial" class="input-field" autocomplete="off" style="width: 100%;">
                                    <div id="autocompleteInitial" class="autocomplete-dropdown"></div>
                                </div>
                                <div style="flex: 1; position: relative;">
                                    <input type="text" id="name1" name="name1" class="input-field" autocomplete="off" style="width: 100%;">
                                    <div id="autocompleteName1" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Occupation 1 Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">தொழில் 1</label>
                            <div style="position: relative;">
                                <input type="text" id="occupation" name="occupation" class="input-field" autocomplete="off" style="width: 100%;">
                                <div id="autocompleteOccupation" class="autocomplete-dropdown"></div>
                            </div>
                        </div>

                        <!-- Entry Name (Second Person Initial + Name) Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">பெயர் 2</label>
                            <div style="display: flex; gap: 8px;">
                                <div style="flex: 0 0 100px; position: relative;">
                                    <input type="text" id="initial2" name="initial2" class="input-field" autocomplete="off" style="width: 100%;">
                                    <div id="autocompleteInitial2" class="autocomplete-dropdown"></div>
                                </div>
                                <div style="flex: 1; position: relative;">
                                    <input type="text" id="name2" name="name2" class="input-field" autocomplete="off" style="width: 100%;">
                                    <div id="autocompleteName2" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Occupation 2 Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">தொழில் 2</label>
                            <input type="text" id="occupation2" name="occupation2" class="input-field" autocomplete="off" style="width: 100%;">
                        </div>



                        <!-- Description Row -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px;">விவரம்</label>
                            <div style="position: relative;">
                                <input type="text" id="description" name="description" class="input-field" autocomplete="off" style="width: 100%;">
                                <div id="autocompleteDescription" class="autocomplete-dropdown"></div>
                            </div>
                        </div>

                        <!-- Amount Row (Thogai) -->
                        <div style="display: grid; grid-template-columns: 150px 1fr; gap: 10px; margin-bottom: 6px; align-items: center;">
                            <label style="font-size: 14px; font-weight: 600; text-align: right; padding-right: 10px; color: #d81b60;">தொகை *</label>
                            <input type="tel" id="amount" name="amount" class="input-field notranslate" placeholder="0" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="en" inputmode="decimal" data-lpignore="true" style="width: 220px; color: #d81b60; border: 2px solid #d81b60; text-align: center;">
                        </div>

                    </div>

                    <div id="formMessage" class="message"></div>
                </form>
                
                <!-- Edit Mode Indicator -->
                <div id="editModeIndicator" class="edit-mode-indicator" style="display:none;">
                    <span>📝 பதிவை திருத்துகிறது</span>
                </div>

                <!-- Hidden Buttons (referenced by modal) -->
                <div style="display: none;">
                    <button type="button" id="btnGuestEntries"></button>
                    <button type="button" id="btnViewAll"></button>
                    <button type="button" id="btnSummary"></button>
                    <button type="button" id="btnDeletedHistory"></button>
                    <button type="button" id="btnHelp"></button>
                </div>

                <!-- Collections Table - Hidden for single entry mode -->
                <div class="collections-table" style="display:none;">
                    <table>
                        <thead>
                            <tr>
                                <th>வ.எண்</th>
                                <th>ஊர்</th>
                                <th>இனி.</th>
                                <th>பெயர் 1</th>
                                <th>பெயர் 2</th>
                                <th>தொழில்</th>
                                <th>உறவு</th>
                                <th>வசிக்கும் ஊர்</th>
                                <th>போன்</th>
                                <th>எண்</th>
                                <th>விவரம்</th>
                                <th>தொகை</th>
                            </tr>
                        </thead>
                        <tbody id="collectionsTableBody">
                            <tr class="empty-row">
                                <td colspan="12">பதிவுகள் இல்லை...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
                </div>
                <!-- End Left Side -->

                <!-- Middle: Denomination Section (Compact 12%) -->
                <div style="flex: 0 0 12%; max-width: 12%; height: 100%;">
                    <div style="background: #fff; border: 2px solid #1976d2; border-radius: 6px; padding: 10px; height: 100%; overflow-y: auto;">
                        <h3 style="margin: 0 0 12px 0; color: #1976d2; font-size: 14px; text-align: center; border-bottom: 2px solid #1976d2; padding-bottom: 6px;">நோட்டு</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">1</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="1" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">500</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="500" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">200</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="200" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">100</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="100" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">50</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="50" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">20</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="20" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                            <div style="display: flex; align-items: center; gap: 6px; padding: 4px;">
                                <label style="font-weight: bold; font-size: 14px; min-width: 40px;">10</label>
                                <span style="font-size: 14px; font-weight: bold;">×</span>
                                <input type="number" class="denom-input" data-val="10" placeholder="0" style="flex: 1; padding: 6px; text-align: center; border: 1px solid #999; border-radius: 3px; font-size: 14px;">
                            </div>
                        </div>
                        
                        <div style="margin-top: 12px; padding-top: 10px; border-top: 2px solid #1976d2;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                <span style="font-weight: bold; font-size: 13px;">மொத்தம்:</span>
                                <span id="denomTotal" style="font-weight: bold; font-size: 14px; color: #1976d2;">₹0</span>
                            </div>
                            </div>


                        <!-- Compact Controls Section -->
                        <div style="margin-top: 15px; border-top: 2px solid #eee; padding-top: 10px; display: flex; flex-direction: column; gap: 8px;">
                            <div style="display:flex; flex-direction:column; gap:5px;">
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: bold; cursor: pointer; white-space: nowrap;" title="Print Helper">
                                    <input type="checkbox" id="chkEnablePrintHelper" checked style="accent-color: #2196f3; width:14px; height:14px;"> Print Helper
                                </label>
                                <label style="display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: bold; cursor: pointer;">
                                    <input type="checkbox" id="chkEnableDenom" style="accent-color: #2196f3; width:14px; height:14px;"> Enable Denom
                                </label>
                            </div>
                            
                            <button type="button" id="btnAddToList" style="width: 100%; padding: 8px; background:#4caf50; border: none; color:white; border-radius: 4px; font-size: 13px; font-weight: bold;">SAVE</button>
                            <button type="button" id="btnUpdateCollection" style="display:none; width: 100%; padding: 8px; background:#ff9800; border: none; color:white; border-radius: 4px; font-size: 13px; font-weight: bold;">UPDATE</button>
                            <button type="button" id="btnCancelEdit" style="display:none; width: 100%; padding: 8px; background:#9e9e9e; border: none; color:white; border-radius: 4px; font-size: 13px; font-weight: bold;">CANCEL</button>
                            
                            <button type="button" id="btnTestPrint" style="width: 100%; padding: 8px; background:#607d8b; border: none; color:white; border-radius: 4px; font-size: 12px; font-weight: bold;">TEST PRINT</button>
                            <button type="button" id="btnOptionsMenu" style="width: 100%; padding: 8px; background:#673ab7; border: none; color:white; border-radius: 4px; font-size: 12px; font-weight: bold;">OPTIONS</button>
                        </div>
                    </div>
                </div>
                <!-- End Middle -->

                <!-- Right Side: Recent Collections (33%) -->
                <div style="flex: 0 0 33%; max-width: 33%; height: 100%;">
                    <div class="recent-collections" id="recentCollections" style="height: 100%; overflow-y: auto; display:flex; flex-direction:column;">
                        <h2>சமீபத்திய வரலாறு (Recent History)</h2>
                        
                        <!-- Search Options -->
                        <div class="recent-search" style="padding: 10px; background: #f5f5f5; border-bottom: 1px solid #ddd; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                            <input type="text" id="searchRecentBill" placeholder="Bill No" style="padding: 5px; width: 80px; border: 1px solid #ccc; border-radius: 4px;">
                            <input type="text" id="searchRecentName" placeholder="Name / Place" style="padding: 5px; flex: 1; border: 1px solid #ccc; border-radius: 4px;">
                            <label style="display: flex; align-items: center; gap: 5px; font-size: 13px; cursor: pointer; background: #e3f2fd; color: #1565c0; padding: 5px 8px; border-radius: 4px; border: 1px solid #90caf9;" title="Show UPI only">
                                <input type="checkbox" id="filterRecentUPI" style="accent-color: #1565c0; width: 14px; height: 14px;"> UPI
                            </label>
                        </div>

                        <!-- Header for list -->
                        <div style="display: flex; padding: 5px 10px; font-weight: bold; font-size: 0.8em; color: #666; border-bottom: 1px solid #eee;">
                            <div style="flex: 1;">Place / Name</div>
                            <div style="width: 80px; text-align: right;">Amount</div>
                        </div>

                        <div id="collectionsContainer" style="flex: 1; overflow-y: auto;">
                            <p class="empty-state">இதுவரை எந்த பதிவும் இல்லை.</p>
                        </div>
                    </div>
                </div>
                <!-- End Right Side -->
            </div>
            <!-- End Main Content Flex Container -->
        </div>
    </div>

    <!-- Denomination Modal -->
    <div id="denomModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 500px;">
            <div class="modal-header">
                <h2>பணத்தாள் விவரங்கள்</h2>
                <span class="modal-close" onclick="closeModal('denomModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 15px; text-align: center;">
                    <span style="font-size: 1.2em;">மொத்த தொகை: </span>
                    <span id="modalRequiredTotal" style="font-size: 1.5em; font-weight: bold; color: #1976d2;">0.00</span>
                </div>
                
                <div class="denom-grid">
                    <div class="denom-row">
                        <label>1 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="1" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>500 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="500" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>200 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="200" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>100 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="100" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>50 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="50" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>20 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="20" placeholder="0">
                    </div>
                    <div class="denom-row">
                        <label>10 x</label>
                        <input type="number" class="denom-input modal-denom" data-val="10" placeholder="0">
                    </div>
                </div>
                
                <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px;">
                    <div style="display: flex; justify-content: space-between; font-size: 1.1em; margin-bottom: 5px;">
                        <span>உள்ளிட்ட தொகை:</span>
                        <span id="modalEnteredTotal" style="font-weight: bold;">0.00</span>
                    </div>
                    <div id="modalBalanceContainer" style="display: none; justify-content: space-between; color: #d32f2f; font-weight: bold;">
                        <span>மீதி:</span>
                        <span id="modalBalance">0.00</span>
                    </div>
                    <div id="modalMessage" style="color: #d32f2f; text-align: center; margin-top: 5px; font-weight: bold;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('denomModal')">ரத்து செய்</button>
                <button class="btn-confirm" id="btnConfirmPayment">உறுதி செய் (Shift+Enter)</button>
            </div>
        </div>
    </div>


    <!-- Invoice Modal Removed - Using Print Helper Only -->

    <!-- Edit Collection Modal -->
    <div id="editCollectionModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 800px; width: 90%;">
            <div class="modal-header">
                <h2>பதிவை திருத்து</h2>
                <span class="modal-close" onclick="closeModal('editCollectionModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editCollectionForm">
                    <input type="hidden" id="editCollectionId" name="collection_id">
                    <div class="input-grid">
                        <div class="field-group">
                            <label>ஊர்</label>
                            <input type="text" id="editLocation" name="location" class="input-field" lang="ta">
                        </div>
                        <div class="field-group">
                            <label>இனிஷியல்</label>
                            <input type="text" id="editInitial" name="initial" class="input-field" lang="ta">
                        </div>
                        <div class="field-group">
                            <label>பெயர் 1</label>
                            <input type="text" id="editName1" name="name1" class="input-field" lang="ta">
                        </div>
                        <div class="field-group">
                            <label>பெயர் 2</label>
                            <input type="text" id="editName2" name="name2" class="input-field" lang="ta">
                        </div>
                        <div class="field-group">
                            <label>தொழில் 1</label>
                            <input type="text" id="editOccupation" name="occupation" class="input-field" lang="ta">
                        </div>
                        <div class="field-group">
                            <label>தொழில் 2</label>
                            <input type="text" id="editOccupation2" name="occupation2" class="input-field" lang="ta">
                        </div>
                        <div class="field-group field-group-inline">
                            <label>வசிக்கும் ஊர்</label>
                            <input type="text" id="editVillage" name="villageGoingTo" class="input-field" lang="ta">

                            <label>போன்</label>
                            <input type="tel" id="editPhone" name="phone" class="input-field" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,''); if(this.value.length > 10) this.value=this.value.slice(0,10);">



                            <label>விவரம்</label>
                            <input type="text" id="editDescription" name="description" class="input-field" lang="ta">

                            <label>தொகை</label>
                            <input type="tel" id="editAmount" name="amount" class="input-field notranslate" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="en" inputmode="decimal" data-lpignore="true">
                        </div>
                        <div class="field-group">
                            <label>உறவு முறை</label>
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="editRelationship1" name="relationship" value="1" style="width: 18px; height: 18px; cursor: pointer;">
                                    <span style="font-size: 14px;">தாய்மாமன்</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="checkbox" id="editRelationship2" name="relationship" value="2" style="width: 18px; height: 18px; cursor: pointer;">
                                    <span style="font-size: 14px;">அத்தை - மாமா</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="edit-meta-info" style="margin-top: 15px; font-size: 0.9em; color: #666; font-style: italic;">
                        <span id="editLastUpdated"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('editCollectionModal')">ரத்து செய்</button>
                <button class="btn-confirm" id="btnUpdateCollectionModal">புதுப்பி</button>
            </div>
        </div>
    </div>

    <!-- Function Summary Modal -->
    <div id="summaryModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <div class="modal-header">
                <h2>📊 விசேஷ சுருக்கம்</h2>
                <span class="modal-close" onclick="closeModal('summaryModal')">&times;</span>
            </div>
            <div class="modal-body" style="background: white;">
                <div class="summary-container">
                    <div class="summary-section">
                        <h3>விசேஷ விவரங்கள்</h3>
                        <div class="summary-grid">
                            <div class="summary-item">
                                <span class="summary-label">விசேஷ பெயர்:</span>
                                <span class="summary-value" id="summaryFunctionName"><?php echo htmlspecialchars($function['function_name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">தேதி:</span>
                                <span class="summary-value" id="summaryDate"><?php echo date('d-m-Y', strtotime($function['function_date'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">இடம்:</span>
                                <span class="summary-value" id="summaryPlace"><?php echo htmlspecialchars($function['place']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-section">
                        <h3>வசூல் புள்ளிவிவரங்கள்</h3>
                        <div class="summary-stats">
                            <div class="stat-card">
                                <div class="stat-icon">📝</div>
                                <div class="stat-content">
                                    <div class="stat-label">மொத்த பதிவுகள்</div>
                                    <div class="stat-value" id="summaryTotalEntries">0</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">💰</div>
                                <div class="stat-content">
                                    <div class="stat-label">மொத்த தொகை</div>
                                    <div class="stat-value" id="summaryTotalAmount">₹0.00</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">⏱️</div>
                                <div class="stat-content">
                                    <div class="stat-label">நிலுவையில்</div>
                                    <div class="stat-value" id="summaryPendingCount">0</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">✅</div>
                                <div class="stat-content">
                                    <div class="stat-label">சேமிக்கப்பட்டது</div>
                                    <div class="stat-value" id="summarySavedCount">0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="summary-section">
                        <h3>பணத்தாள் விவரங்கள் (சேமிக்கப்பட்டவை)</h3>
                        <div id="summaryDenoms" style="margin-top: 10px;">
                            <p style="color: #666; font-style: italic;">ஏற்றுகிறது...</p>
                        </div>
                    </div>
                    
                    <div class="summary-section">
                        <h3>விரைவு செயல்கள்</h3>
                        <div class="summary-actions">
                            <button class="summary-action-btn" onclick="closeModal('summaryModal'); document.getElementById('location').focus();">📝 புதிய பதிவு</button>
                            <button class="summary-action-btn" onclick="closeModal('summaryModal'); window.open('collection_report.php?function_id=<?php echo $functionId; ?>', '_blank');">📄 அறிக்கையை காண்க</button>
                            <button class="summary-action-btn" onclick="location.reload();">🔄 தரவை புதுப்பி</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-confirm" onclick="closeModal('summaryModal')">மூடு</button>
            </div>
        </div>
    </div>

    <!-- Denomination Summary Modal (New Enhanced Modal) -->
    <div id="denomSummaryModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 950px; width: 95%;">
            <div class="modal-header" style="background: linear-gradient(135deg, #1976d2, #42a5f5); color: white;">
                <h2>💰 Denomination Summary</h2>
                <button id="btnPrintDenomSummary" style="background: #4caf50; color: white; border: none; padding: 8px 20px; border-radius: 20px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                    🖨️ Print
                </button>
                <span class="modal-close" onclick="closeModal('denomSummaryModal')" style="color: white;">&times;</span>
            </div>
            <div class="modal-body" style="background: #f0f4f8; padding: 0;">
                <!-- Function Name Header -->
                <div style="background: #fff8e1; padding: 15px; text-align: center; border-bottom: 3px solid #ffc107;">
                    <h3 id="denomSummaryFunctionName" style="margin: 0; color: #e65100; font-size: 1.5em;"><?php echo htmlspecialchars($function['function_name']); ?></h3>
                </div>                
                <!-- Current Computer Section (This Computer) -->
                <div id="currentComputerSection" style="margin: 15px; background: #e3f2fd; border: 2px solid #1976d2; border-radius: 8px; overflow: hidden;">
                    <div style="background: #1976d2; color: white; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: bold;">👤 <span id="currentComputerLabel">twikibot (You)</span> <span style="background: #ffeb3b; color: #333; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; margin-left: 10px;">(You)</span></span>
                        <span id="currentComputerTransactions" style="font-size: 0.9em;">0 transactions</span>
                    </div>
                    <div id="currentComputerMismatch" style="background: #ffcc80; padding: 8px 15px; color: #e65100; font-weight: bold; display: none;">
                        ⚠️ Mismatch: <span id="mismatchDetails"></span>
                    </div>
                    <div style="padding: 15px;">
                        <table style="width: 100%; border-collapse: collapse; background: #fffde7;">
                            <thead>
                                <tr style="background: #fff9c4;">
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹500</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹200</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹100</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹50</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹20</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹10</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹5</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹2</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">₹1</th>
                                    <th style="padding: 10px; border: 1px solid #ddd; text-align: center; background: #c5e1a5; font-weight: bold;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom500" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom200" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom100" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom50" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom20" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom10" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom5" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom2" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"><input type="number" id="editDenom1" class="edit-denom-input" style="width: 60px; text-align: center; padding: 5px; border: 1px solid #999; border-radius: 3px;" value="0"></td>
                                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center; background: #c5e1a5; font-weight: bold; font-size: 1.1em;" id="currentComputerTotal">₹0</td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="text-align: right; margin-top: 10px;">
                            <button id="btnUpdateDenom" style="background: #ff9800; color: white; border: none; padding: 8px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">Update</button>
                        </div>
                    </div>
                </div>
                
                <!-- All Computers Section -->
                <div id="allComputersSection" style="margin: 15px; background: white; border: 2px solid #4caf50; border-radius: 8px; overflow: hidden;">
                    <div style="background: #4caf50; color: white; padding: 10px 15px;">
                        <span style="font-weight: bold;">📊 All Computers Combined</span>
                    </div>
                    <div id="allComputersList" style="padding: 15px; max-height: 300px; overflow-y: auto;">
                        <p style="text-align: center; color: #666;">Loading...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px;">
                <button class="btn-confirm" onclick="closeModal('denomSummaryModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- View All Records Modal -->
    <div id="viewAllModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 95%; width: 1400px;">
            <div class="modal-header">
                <h2>📋 அனைத்து பதிவு செய்யப்பட்ட விவரங்கள்</h2>
                <span class="modal-close" onclick="closeModal('viewAllModal')">&times;</span>
            </div>
            <div class="modal-body" style="background: white; max-height: 70vh; overflow-y: auto;">
                <div style="padding: 10px; background: #f9f9f9; border-bottom: 1px solid #eee; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; justify-content: space-between;">
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <span style="font-weight: bold;">Find & Replace:</span>
                        <select id="findReplaceField" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="all">All Fields</option>
                            <option value="location">Location (ஊர்)</option>
                            <option value="name1">Name 1 (பெயர் 1)</option>
                            <option value="name2">Name 2 (பெயர் 2)</option>
                            <option value="initial_name">Initial (இனிஷியல்)</option>
                            <option value="occupation">Occupation (தொழில்)</option>
                            <option value="village_going_to">Village (வசிக்கும் ஊர்)</option>
                        </select>
                        <input type="text" id="findText" placeholder="Find what..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <input type="text" id="replaceText" placeholder="Replace with..." style="padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <button id="btnFindReplace" style="padding: 8px 15px; background: #e91e63; color: white; border: none; border-radius: 4px; cursor: pointer;">Replace All</button>
                    </div>
                    <div style="text-align: right;">
                        <input type="text" id="searchAllRecords" placeholder="🔍 தேடுக (பெயர், ஊர், எண்...)" style="padding: 8px; width: 300px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>
                <div id="allRecordsContainer">
                    <p style="text-align: center; padding: 20px;">பதிவுகளை ஏற்றுகிறது...</p>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <div id="recordsInfo" style="font-size: 14px; color: #666;"></div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn-secondary" id="btnPrevPage" style="display:none;">← முந்தைய</button>
                    <button class="btn-secondary" id="btnNextPage" style="display:none;">அடுத்த →</button>
                    <button class="btn-cancel" onclick="closeModal('viewAllModal')">மூடு</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Guest Entries Modal -->
    <div id="guestEntriesModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 95%; width: 1400px;">
            <div class="modal-header">
                <h2>👥 விருந்தினர் பதிவுகள் (நிலுவையில்)</h2>
                <span class="modal-close" onclick="closeModal('guestEntriesModal')">&times;</span>
            </div>
            <div class="modal-body" style="background: white; max-height: 70vh; overflow-y: auto;">
                <div class="info-box" style="background: #fff3e0; border-left: 4px solid #ff9800; padding: 12px 15px; margin-bottom: 15px;">
                    💡 விருந்தினர்கள் QR குறியீடு மூலம் சமர்ப்பித்த பதிவுகள் கீழே காட்டப்பட்டுள்ளன. பணம் வசூலித்த பின் "பணம் பெறப்பட்டது" பட்டனை கிளிக் செய்யவும்.
                </div>
                <div id="guestEntriesContainer">
                    <p style="text-align: center; padding: 20px;">பதிவுகளை ஏற்றுகிறது...</p>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <div id="guestEntriesInfo" style="font-size: 14px; color: #666;"></div>
                <button class="btn-cancel" onclick="closeModal('guestEntriesModal')">மூடு</button>
            </div>
        </div>
    </div>

    <!-- Keyboard Help Modal -->
    <div id="keyboardHelpModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #5c6bc0, #7986cb); color: white;">
                <h2>⌨️ விசைப்பலகை குறுக்குவழிகள்</h2>
                <span class="modal-close" onclick="closeModal('keyboardHelpModal')" style="color: white;">&times;</span>
            </div>
            <div class="modal-body" style="background: white; padding: 0;">
                <!-- Navigation Section -->
                <div class="shortcut-section" style="padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="color: #5c6bc0; margin-bottom: 12px; font-size: 1.1em;">🧭 வழிசெலுத்தல் (Navigation)</h3>
                    <div class="shortcut-list" style="display: grid; gap: 8px;">
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #f5f5f5; border-radius: 6px;">
                            <div style="min-width: 120px;"><kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Enter</kbd></div>
                            <span>அடுத்த புலத்திற்கு நகர / சேமி</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #f5f5f5; border-radius: 6px;">
                            <div style="min-width: 120px;"><kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">↑</kbd> <kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">↓</kbd></div>
                            <span>புலங்களுக்கு இடையில் நகர</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #f5f5f5; border-radius: 6px;">
                            <div style="min-width: 120px;"><kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Shift</kbd> + <kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Enter</kbd></div>
                            <span>தொகை புலத்திற்கு குதி / உடனடி சேமி</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #f5f5f5; border-radius: 6px;">
                            <div style="min-width: 120px;"><kbd style="background: #333; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Esc</kbd></div>
                            <span>மாடல் / தானியங்குநிரப்பு மூடு</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Focus Section -->
                <div class="shortcut-section" style="padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="color: #43a047; margin-bottom: 12px; font-size: 1.1em;">🎯 விரைவு கவனம் (Quick Focus)</h3>
                    <div class="shortcut-list" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #e8f5e9; border-radius: 6px;">
                            <kbd style="background: #2e7d32; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85em;">Alt+L</kbd>
                            <span style="font-size: 0.95em;">ஊர் (Location)</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #e8f5e9; border-radius: 6px;">
                            <kbd style="background: #2e7d32; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85em;">Alt+P</kbd>
                            <span style="font-size: 0.95em;">தொடர்பு எண் (Phone)</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #e8f5e9; border-radius: 6px;">
                            <kbd style="background: #2e7d32; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85em;">Alt+N</kbd>
                            <span style="font-size: 0.95em;">பெயர் 1 (Name 1)</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 10px; padding: 8px; background: #e8f5e9; border-radius: 6px;">
                            <kbd style="background: #2e7d32; color: white; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 0.85em;">Alt+A</kbd>
                            <span style="font-size: 0.95em;">தொகை (Amount)</span>
                        </div>
                    </div>
                </div>

                <!-- Actions Section -->
                <div class="shortcut-section" style="padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                    <h3 style="color: #e65100; margin-bottom: 12px; font-size: 1.1em;">⚡ முக்கிய செயல்கள் (Actions)</h3>
                    <div class="shortcut-list" style="display: grid; gap: 8px;">
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #fff3e0; border-radius: 6px;">
                            <div style="min-width: 100px;"><kbd style="background: #e65100; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Alt+S</kbd></div>
                            <span>சேமி / கட்டணத்தை உறுதி செய்</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #fff3e0; border-radius: 6px;">
                            <div style="min-width: 100px;"><kbd style="background: #e65100; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Alt+V</kbd></div>
                            <span>அனைத்து பதிவுகளையும் காண்க</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #fff3e0; border-radius: 6px;">
                            <div style="min-width: 100px;"><kbd style="background: #e65100; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">Alt+O</kbd></div>
                            <span>செயல்கள் மெனு திறக்க</span>
                        </div>
                        <div class="shortcut-item" style="display: flex; align-items: center; gap: 15px; padding: 8px; background: #e3f2fd; border-radius: 6px;">
                            <div style="min-width: 100px;"><kbd style="background: #1565c0; color: white; padding: 4px 10px; border-radius: 4px; font-family: monospace;">F1</kbd></div>
                            <span>இந்த உதவி திரையை காட்டு</span>
                        </div>
                    </div>
                </div>

                <!-- Tips Section -->
                <div class="shortcut-section" style="padding: 15px 20px; background: #f9fbe7;">
                    <h3 style="color: #827717; margin-bottom: 10px; font-size: 1em;">💡 குறிப்புகள் (Tips)</h3>
                    <ul style="margin: 0; padding-left: 20px; font-size: 0.9em; color: #555; line-height: 1.6;">
                        <li>தொகை புலத்தில் <kbd style="background: #ddd; padding: 2px 5px; border-radius: 3px; font-size: 0.85em;">Enter</kbd> அழுத்தினால் உடனடியாக சேமிக்கும்</li>
                        <li>நோட்டு எண்கிக்கை புலத்தில் <kbd style="background: #ddd; padding: 2px 5px; border-radius: 3px; font-size: 0.85em;">Enter</kbd> அழுத்தினால் சேமிக்கும்</li>
                        <li>தானியங்குநிரப்பு காட்டும்போது <kbd style="background: #ddd; padding: 2px 5px; border-radius: 3px; font-size: 0.85em;">↑↓</kbd> விருப்பங்களை தேர்வு செய்ய</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-confirm" onclick="closeModal('keyboardHelpModal')">புரிந்தது! 👍</button>
            </div>
        </div>
    </div>

    <!-- Deleted History Modal -->
    <div id="deletedHistoryModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 95%; width: 1200px;">
            <div class="modal-header">
                <h2>🗑️ நீக்கப்பட்ட பதிவுகள் (Deleted History)</h2>
                <span class="modal-close" onclick="closeModal('deletedHistoryModal')">&times;</span>
            </div>
            <div class="modal-body" style="background: white; max-height: 70vh; overflow-y: auto;">
                <div id="deletedHistoryContainer">
                    <p style="text-align: center; padding: 20px;">ஏற்றுகிறது...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('deletedHistoryModal')">மூடு</button>
            </div>
        </div>
    </div>

    <!-- Options Menu Modal -->
    <div id="optionsMenuModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 700px;">
            <div class="modal-header">
                <h2>⚙️ செயல்கள் (Actions)</h2>
                <span class="modal-close" onclick="closeModal('optionsMenuModal')">&times;</span>
            </div>
            <div class="modal-body" style="background: white;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; padding: 10px;">
                    <!-- Guest Entries -->
                    <button type="button" id="btnGuestEntriesModal" onclick="closeModal('optionsMenuModal'); document.getElementById('btnGuestEntries').click();" style="padding: 12px 16px; background:#ff6f00; border: 1px solid #e65100; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        👥 விருந்தினர்
                    </button>
                    
                    <!-- View All -->
                    <button type="button" id="btnViewAllModal" onclick="closeModal('optionsMenuModal'); document.getElementById('btnViewAll').click();" style="padding: 12px 16px; background:#1976d2; border: 1px solid #1565c0; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        அனைத்து
                    </button>
                    
                    <!-- Summary -->
                    <button type="button" id="btnSummaryModal" onclick="closeModal('optionsMenuModal'); document.getElementById('btnSummary').click();" style="padding: 12px 16px; background:#00897b; border: 1px solid #00695c; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        சுருக்கம்
                    </button>

                    <!-- City Rename -->
                    <button type="button" id="btnCityRenameModal" onclick="closeModal('optionsMenuModal'); window.open('city_rename.php?function_id=<?php echo $functionId; ?>', '_blank');" style="padding: 12px 16px; background:#e91e63; border: 1px solid #c2185b; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        🏙️ City Rename
                    </button>
                    
                    <!-- Denomination Summary -->
                    <button type="button" id="btnDenomSummaryModal" onclick="closeModal('optionsMenuModal'); openDenomSummaryModal();" style="padding: 12px 16px; background:#ff6f00; border: 1px solid #e65100; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        💰 பணத்தாள்
                    </button>
                    
                    <!-- Deleted History -->
                    <button type="button" id="btnDeletedHistoryModal" onclick="closeModal('optionsMenuModal'); document.getElementById('btnDeletedHistory').click();" style="padding: 12px 16px; background:#d32f2f; border: 1px solid #b71c1c; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        🗑️ நீக்கப்பட்டவை
                    </button>
                    
                    <!-- Help -->
                    <button type="button" id="btnHelpModal" onclick="closeModal('optionsMenuModal'); document.getElementById('btnHelp').click();" style="padding: 12px 16px; background:#607d8b; border: 1px solid #455a64; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        உதவி (F1)
                    </button>
                    
                    <!-- Report -->
                    <a href="collection_report.php?function_id=<?php echo $functionId; ?>" target="_blank" rel="noopener" onclick="closeModal('optionsMenuModal');" style="padding: 12px 16px; background:#4caf50; border: 1px solid #388e3c; color:white; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; white-space: nowrap; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        அறிக்கை
                    </a>
                    
                    <!-- PDF -->
                    <a href="collection_report.php?function_id=<?php echo $functionId; ?>&pdf=1" target="_blank" onclick="closeModal('optionsMenuModal');" style="padding: 12px 16px; background:#e53935; border: 1px solid #c62828; color:white; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; white-space: nowrap; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
                        PDF
                    </a>
                    
                    <!-- Export -->
                    <button type="button" id="btnExportModal" onclick="closeModal('optionsMenuModal');" style="padding: 12px 16px; background:#9c27b0; border: 1px solid #7b1fa2; color:white; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; white-space: nowrap; transition: all 0.2s;">
                        ஏற்றுமதி
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-confirm" onclick="closeModal('optionsMenuModal')">மூடு</button>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const functionId = <?php echo $functionId; ?>;
        const functionDetails = <?php echo json_encode($function['function_details'] ?? ''); ?>;
        let computerNumber = '<?php echo isset($_SESSION['computer_number']) ? htmlspecialchars($_SESSION['computer_number']) : ''; ?>';
        
        // Computer Number Modal Logic
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('computerNumberModal');
            const input = document.getElementById('computerNumberInput');
            const btn = document.getElementById('btnSetComputerNumber');
            const msg = document.getElementById('computerNumberMessage');
            
            // Check if computer number is set in session
            if (!computerNumber || computerNumber === '') {
                modal.style.display = 'flex';
                // Default to empty or generic if nothing set
                if (!input.value) input.value = '';
                input.focus();
            } else {
                modal.style.display = 'none';
            }
            
            // Allow manual change
            const btnComp = document.getElementById('btnComputerNumber');
            if (btnComp) {
                btnComp.addEventListener('click', () => {
                    input.value = computerNumber || '';
                    modal.style.display = 'flex';
                    input.select();
                });
            }
            
            // Options Menu Button Handler
            const btnOptionsMenu = document.getElementById('btnOptionsMenu');
            if (btnOptionsMenu) {
                btnOptionsMenu.addEventListener('click', () => {
                    document.getElementById('optionsMenuModal').style.display = 'flex';
                });
            }
            
            btn.addEventListener('click', async () => {
                const value = input.value.trim();
                if (!value) {
                    msg.textContent = 'கணினி எண்ணை உள்ளிடவும்';
                    msg.style.display = 'block';
                    return;
                }
                
                // Set computer number in session
                const response = await fetch('api/set_computer_number.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ computer_number: value })
                });
                
                const result = await response.json();
                if (result.success) {
                    computerNumber = value;
                    modal.style.display = 'none';
                    // Reload the entire page to fetch data with new computer number
                    window.location.reload();
                } else {
                    msg.textContent = result.message || 'பிழை ஏற்பட்டது';
                    msg.style.display = 'block';
                }
            });
            
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    btn.click();
                }
            });

            // Input method switching logic
            const tamilInputs = ['location', 'initial', 'name1', 'name2', 'occupation', 'occupation2', 'village', 'description', 'editLocation', 'editInitial', 'editName1', 'editName2', 'editOccupation', 'editOccupation2', 'editVillage', 'editDescription'];
            const numericInputs = ['phone', 'customerNo', 'amount', 'editPhone', 'editCustomerNo', 'editAmount'];

            tamilInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('focus', function() {
                        this.lang = 'ta';
                    });
                }
            });

            numericInputs.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('focus', function() {
                        this.lang = 'en';
                    });
                }
            });
        });
    </script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/connection_manager.js?v=<?php echo time(); ?>"></script>
    <script src="js/collection.js?v=<?php echo time(); ?>"></script>
    <script src="js/denom_summary.js?v=<?php echo time(); ?>"></script>
</body>
</html>

<?php
/**
 * Mobile Dashboard - Customer View
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
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>மொய் கணக்கு - முகப்பு</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
    <style>
        /* Mobile Dashboard Specific Styles */
        body {
            background-color: #ffffff;
            padding-bottom: 80px; /* Space for bottom nav or FAB */
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        .mobile-body {
            background: #ffffff !important;
        }
        
        .mobile-header {
            background: #ffffff;
            color: #1f2937;
            padding: 15px 20px 0 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .user-welcome {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 2px;
            font-weight: 500;
        }
        
        .app-title {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 15px;
            color: #111827;
            letter-spacing: -0.5px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .tab-btn {
            flex: 1;
            padding: 12px;
            text-align: center;
            color: #6b7280;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 2px solid transparent;
        }
        
        .tab-btn.active {
            color: #4f46e5;
            border-bottom: 2px solid #4f46e5;
        }
        
        .tab-content {
            display: none;
            padding: 10px 15px;
        }
        
        .tab-content.active {
            display: block;
        }

        .summary-strip {
            margin: 10px 15px 0 15px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #4b5563;
        }

        .summary-strip strong {
            font-size: 18px;
            color: #111827;
        }
        
        /* Card Styles */
        .card-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .mobile-card {
            background: white;
            border: 1px solid #f3f4f6;
            border-radius: 10px;
            padding: 12px 15px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
            position: relative;
            transition: background 0.2s;
        }
        
        .mobile-card:active {
            background: #f9fafb;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4px;
        }
        
        .card-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
        }
        
        .card-date {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
            margin-left: 10px;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }
        
        .card-meta {
            font-size: 13px;
            color: #4b5563;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .card-amount {
            font-size: 16px;
            font-weight: 700;
            color: #059669;
        }
        
        .expense-amount {
            color: #dc2626;
        }
        
        /* FAB */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: #4f46e5;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            cursor: pointer;
            z-index: 90;
            transition: transform 0.2s;
        }
        
        .history-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: white;
            color: #4f46e5;
            padding: 8px 16px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            z-index: 90;
            display: flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #e5e7eb;
        }
        
        .fab:active {
            transform: scale(0.95);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-icon {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
            opacity: 0.5;
        }
    </style>
</head>
<body class="mobile-body">

    <div class="mobile-header">
        <div class="user-welcome">வணக்கம், <?php echo htmlspecialchars($user['name']); ?></div>
        <div class="app-title">மொய் கணக்கு</div>
        
        <div class="tabs">
            <div class="tab-btn active" onclick="switchTab('income')">மொய் வந்தது</div>
            <div class="tab-btn" onclick="switchTab('expense')">மொய் செய்தது</div>
        </div>
    </div>

    <!-- Income Tab (Functions) -->
    <div id="income-tab" class="tab-content active">
        <div id="incomeSummary" class="summary-strip" style="display:none;"></div>
        <div id="functionsList" class="card-list">
            <!-- Loaded via JS -->
            <div class="text-center p-4">ஏற்றுகிறது...</div>
        </div>
    </div>

    <!-- Expense Tab (Moi Seithathu) -->
    <div id="expense-tab" class="tab-content">
        <div id="expensesList" class="card-list">
            <!-- Loaded via JS -->
            <div class="text-center p-4">ஏற்றுகிறது...</div>
        </div>
    </div>

    <!-- FAB -->
    <a href="transaction_history.php" class="history-btn">
        <span>📜</span> வரலாறு
    </a>

    <div class="fab" id="mainFab" onclick="handleFabClick()">
        +
    </div>

    <!-- Create Function Modal (Same as index.php but mobile styled) -->
    <div id="createFunctionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>புதிய விசேஷம்</h2>
                <span class="close" onclick="closeModal('createFunctionModal')">&times;</span>
            </div>
            <form id="createFunctionForm">
                <div class="form-group">
                    <label>விசேஷத்தின் பெயர்</label>
                    <input type="text" name="functionName" required placeholder="எ.கா: காதுகுத்து">
                </div>
                <div class="form-group">
                    <label>தேதி</label>
                    <input type="date" name="functionDate" required>
                </div>
                <div class="form-group">
                    <label>இடம்</label>
                    <input type="text" name="place" required placeholder="ஊர் / மண்டபம்">
                </div>
                <button type="submit" class="btn btn-primary btn-block">உருவாக்கு</button>
            </form>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>மொய் செய்தது</h2>
                <span class="close" onclick="closeModal('addExpenseModal')">&times;</span>
            </div>
            <form id="addExpenseForm">
                <input type="hidden" name="related_collection_id" id="relatedCollectionId">
                
                <div class="form-group">
                    <label>யாருக்கு</label>
                    <input type="text" name="to_name" id="expenseToName" required placeholder="பெயர்">
                </div>
                
                <div class="form-group">
                    <label>விசேஷம்</label>
                    <input type="text" name="function_name" id="expenseFunctionName" placeholder="எ.கா: திருமணம்">
                </div>
                
                <div class="form-group">
                    <label>இடம்</label>
                    <input type="text" name="place" id="expensePlace" placeholder="ஊர்">
                </div>
                
                <div class="form-group">
                    <label>தேதி</label>
                    <input type="date" name="expense_date" id="expenseDate" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label>தொகை (₹)</label>
                    <input type="number" name="amount" id="expenseAmount" required placeholder="0.00">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">சேமி</button>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Mobile Dashboard Logic
        let currentTab = 'income';

        document.addEventListener('DOMContentLoaded', () => {
            loadFunctions();
            loadExpenses();
        });

        function switchTab(tab) {
            currentTab = tab;
            
            // Update UI
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            if (tab === 'income') {
                document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
                document.getElementById('income-tab').classList.add('active');
            } else {
                document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
                document.getElementById('expense-tab').classList.add('active');
            }
        }

        function handleFabClick() {
            if (currentTab === 'income') {
                document.getElementById('createFunctionModal').style.display = 'block';
            } else {
                // Reset form
                document.getElementById('addExpenseForm').reset();
                document.getElementById('relatedCollectionId').value = '';
                document.getElementById('addExpenseModal').style.display = 'block';
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Load Functions (Moi Vanthathu)
        async function loadFunctions() {
            const container = document.getElementById('functionsList');
            const summaryBar = document.getElementById('incomeSummary');
            try {
                const response = await fetch('api/get_functions.php');
                const data = await response.json();
                
                if (data.success) {
                    const totalAmount = data.functions.reduce((sum, func) => sum + parseFloat(func.total_amount || 0), 0);
                    if (summaryBar) {
                        summaryBar.style.display = 'flex';
                        summaryBar.innerHTML = `
                            <span>மொத்த வரவு</span>
                            <strong>₹${totalAmount.toLocaleString()}</strong>
                        `;
                    }

                    if (data.functions.length > 0) {
                        container.innerHTML = data.functions.map(func => `
                            <div class="mobile-card" onclick="window.location.href='mobile_function_view.php?function_id=${func.id}'">
                                <div class="card-header">
                                    <div class="card-title">${func.function_name}</div>
                                    <div class="card-amount">₹${parseFloat(func.total_amount || 0).toLocaleString()}</div>
                                </div>
                                <div class="card-meta">
                                    <span>📅 ${formatDate(func.function_date)}</span>
                                    <span>📍 ${func.place}</span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <span class="empty-icon">📅</span>
                                <p>விசேஷங்கள் இல்லை</p>
                            </div>
                        `;
                    }
                } else {
                    if (summaryBar) {
                        summaryBar.style.display = 'flex';
                        summaryBar.innerHTML = `<span>${data.message}</span>`;
                    }
                    container.innerHTML = `<div class="text-center text-red-500 p-4">${data.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                if (summaryBar) {
                    summaryBar.style.display = 'flex';
                    summaryBar.innerHTML = '<span>ஏற்றுவதில் பிழை</span>';
                }
                container.innerHTML = '<div class="text-center text-red-500 p-4">ஏற்றுவதில் பிழை</div>';
            }
        }

        // Load Expenses (Moi Seithathu)
        async function loadExpenses() {
            const container = document.getElementById('expensesList');
            try {
                const response = await fetch('api/get_expenses.php');
                const data = await response.json();
                
                if (data.success) {
                    if (data.expenses.length > 0) {
                        container.innerHTML = data.expenses.map(exp => `
                            <div class="mobile-card expense-card">
                                <div class="card-header">
                                    <div class="card-title">${exp.to_name}</div>
                                    <div class="expense-amount">₹${parseFloat(exp.amount).toLocaleString()}</div>
                                </div>
                                <div class="card-meta">
                                    <span>${exp.function_name || 'விசேஷம்'}</span>
                                    <span>• ${exp.place || '-'}</span>
                                </div>
                                <div class="card-meta" style="margin-top: 4px;">
                                    <span>📅 ${formatDate(exp.expense_date)}</span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="empty-state">
                                <span class="empty-icon">💸</span>
                                <p>செலவுகள் இல்லை</p>
                            </div>
                        `;
                    }
                } else {
                    container.innerHTML = `<div class="text-center text-red-500 p-4">${data.message}</div>`;
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = '<div class="text-center text-red-500 p-4">ஏற்றுவதில் பிழை</div>';
            }
        }

        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('ta-IN', options);
        }

        // Form Submissions
        document.getElementById('createFunctionForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            // Use existing API
            const response = await fetch('api/create_function.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('createFunctionModal');
                form.reset();
                loadFunctions();
                showMessage('விசேஷம் உருவாக்கப்பட்டது', 'success');
            } else {
                showMessage(result.message, 'error');
            }
        });

        document.getElementById('addExpenseForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            const response = await fetch('api/save_expense.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                closeModal('addExpenseModal');
                form.reset();
                loadExpenses();
                showMessage('மொய் விபரம் சேமிக்கப்பட்டது', 'success');
            } else {
                showMessage(result.message, 'error');
            }
        });
    </script>
</body>
</html>

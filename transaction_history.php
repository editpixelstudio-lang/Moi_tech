<?php
/**
 * Transaction History Report (GPay Style)
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

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
    <title>வரலாறு - மொய் கணக்கு</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
    <style>
        body {
            background-color: #ffffff;
            padding-bottom: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        .history-body {
            background: #ffffff !important;
            min-height: 100vh;
        }
        
        /* Header */
        .history-header {
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .top-bar {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            gap: 15px;
        }
        
        .back-btn {
            font-size: 22px;
            text-decoration: none;
            color: #1f2937;
        }
        
        .page-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            flex: 1;
        }
        
        /* Search & Filter */
        .filter-section {
            padding: 0 20px 15px 20px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
        }
        .filter-section::-webkit-scrollbar {
            display: none; /* Chrome/Safari */
        }
        
        .search-box {
            padding: 0 20px 10px 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            background: white;
            border-color: #4f46e5;
        }
        
        .filter-chip {
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: white;
            font-size: 13px;
            white-space: nowrap;
            cursor: pointer;
            color: #4b5563;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .filter-chip.active {
            background: #eff6ff;
            color: #4f46e5;
            border-color: #4f46e5;
        }
        
        /* Transaction List */
        .transaction-list {
            padding: 0;
        }
        
        .date-header {
            padding: 15px 20px 8px 20px;
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            background: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #f9fafb;
        }
        
        .transaction-item:active {
            background: #f9fafb;
        }
        
        .t-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            margin-right: 12px;
            flex-shrink: 0;
            font-weight: bold;
            color: white;
        }
        
        .icon-income {
            background: #10b981; /* Green */
        }
        
        .icon-expense {
            background: #ef4444; /* Red */
        }
        
        .t-details {
            flex: 1;
            min-width: 0; /* For text truncation */
        }
        
        .t-name {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .t-meta {
            font-size: 13px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .t-amount {
            font-size: 15px;
            font-weight: 700;
            margin-left: 10px;
            white-space: nowrap;
        }
        
        .amount-income {
            color: #059669;
        }
        
        .amount-expense {
            color: #1f2937; 
        }
        
        /* Summary Card */
        .summary-card {
            margin: 15px 20px;
            padding: 15px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            color: #111827;
            display: flex;
            justify-content: space-between;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }
        
        .summary-item h3 {
            margin: 0 0 4px 0;
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-item p {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
            font-size: 14px;
        }
    </style>
</head>
<body class="history-body">

    <div class="history-header">
        <div class="top-bar">
            <a href="mobile_dashboard.php" class="back-btn">←</a>
            <div class="page-title">பரிவர்த்தனை வரலாறு</div>
        </div>
        
        <div class="search-box">
            <input type="text" class="search-input" id="searchInput" placeholder="பெயர், ஊர் அல்லது விசேஷம் தேட..." onkeyup="filterLocal()">
        </div>
        
        <div class="filter-section">
            <div class="filter-chip active" onclick="setFilter('all', this)">எல்லாம்</div>
            <div class="filter-chip" onclick="setFilter('income', this)">வரவு (Moi)</div>
            <div class="filter-chip" onclick="setFilter('expense', this)">செலவு (Sent)</div>
            <select id="monthFilter" class="filter-chip" onchange="loadTransactions()" style="padding-right: 30px;">
                <option value="0">எல்லா காலமும்</option>
                <!-- Options populated by JS -->
            </select>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-item">
            <h3>மொய் வந்தது</h3>
            <p id="totalIncome">₹0</p>
        </div>
        <div class="summary-item" style="text-align: right;">
            <h3>மொய் செய்ததது</h3>
            <p id="totalExpense">₹0</p>
        </div>
    </div>

    <div id="transactionContainer" class="transaction-list">
        <div class="loading">ஏற்றுகிறது...</div>
    </div>

    <script src="js/main.js"></script>
    <script>
        let allTransactions = [];
        let currentType = 'all';

        document.addEventListener('DOMContentLoaded', () => {
            populateMonthFilter();
            loadTransactions();
        });

        function populateMonthFilter() {
            const select = document.getElementById('monthFilter');
            const months = [
                'ஜனவரி', 'பிப்ரவரி', 'மார்ச்', 'ஏப்ரல்', 'மே', 'ஜூன்',
                'ஜூலை', 'ஆகஸ்ட்', 'செப்டம்பர்', 'அக்டோபர்', 'நவம்பர்', 'டிசம்பர்'
            ];
            const currentYear = new Date().getFullYear();
            
            // Add current year months
            for (let i = 0; i < 12; i++) {
                const option = document.createElement('option');
                option.value = `${currentYear}-${i + 1}`;
                option.text = `${months[i]} ${currentYear}`;
                select.appendChild(option);
            }
        }

        function setFilter(type, element) {
            currentType = type;
            
            // Update UI
            document.querySelectorAll('.filter-chip').forEach(el => {
                if (!el.tagName.match(/SELECT/i)) el.classList.remove('active');
            });
            element.classList.add('active');
            
            loadTransactions();
        }

        async function loadTransactions() {
            const container = document.getElementById('transactionContainer');
            const monthVal = document.getElementById('monthFilter').value;
            let month = 0, year = 0;
            
            if (monthVal !== '0') {
                [year, month] = monthVal.split('-');
            }

            container.innerHTML = '<div class="loading">ஏற்றுகிறது...</div>';

            try {
                const url = `api/get_all_transactions.php?type=${currentType}&month=${month}&year=${year}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    allTransactions = data.transactions;
                    renderTransactions(allTransactions);
                    calculateTotals(allTransactions);
                } else {
                    container.innerHTML = '<div class="loading">தரவை ஏற்ற முடியவில்லை</div>';
                }
            } catch (error) {
                console.error(error);
                container.innerHTML = '<div class="loading">பிழை ஏற்பட்டது</div>';
            }
        }

        function renderTransactions(transactions) {
            const container = document.getElementById('transactionContainer');
            
            if (transactions.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 50px 20px; color: #999;">
                        <div style="font-size: 40px; margin-bottom: 10px;">📝</div>
                        <p>பரிவர்த்தனைகள் இல்லை</p>
                    </div>
                `;
                return;
            }

            let html = '';
            let lastDate = '';

            transactions.forEach(t => {
                const dateObj = new Date(t.date);
                const dateStr = dateObj.toLocaleDateString('ta-IN', { day: 'numeric', month: 'long', year: 'numeric' });
                
                // Date Header
                if (dateStr !== lastDate) {
                    html += `<div class="date-header">${dateStr}</div>`;
                    lastDate = dateStr;
                }

                const isIncome = t.type === 'income';
                const amountClass = isIncome ? 'amount-income' : 'amount-expense';
                const amountPrefix = isIncome ? '+' : '-';
                const iconClass = isIncome ? 'icon-income' : 'icon-expense';
                const iconText = t.name.charAt(0).toUpperCase();
                
                html += `
                    <div class="transaction-item">
                        <div class="t-icon ${iconClass}">${iconText}</div>
                        <div class="t-details">
                            <div class="t-name">${t.name}</div>
                            <div class="t-meta">${t.description} • ${t.place}</div>
                        </div>
                        <div class="t-amount ${amountClass}">
                            ${amountPrefix} ₹${parseFloat(t.amount).toLocaleString()}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function calculateTotals(transactions) {
            let income = 0;
            let expense = 0;

            transactions.forEach(t => {
                if (t.type === 'income') {
                    income += parseFloat(t.amount);
                } else {
                    expense += parseFloat(t.amount);
                }
            });

            document.getElementById('totalIncome').textContent = '₹' + income.toLocaleString();
            document.getElementById('totalExpense').textContent = '₹' + expense.toLocaleString();
        }

        function filterLocal() {
            const query = document.getElementById('searchInput').value.toLowerCase();
            
            if (!query) {
                renderTransactions(allTransactions);
                return;
            }

            const filtered = allTransactions.filter(t => {
                return t.name.toLowerCase().includes(query) || 
                       t.place.toLowerCase().includes(query) || 
                       t.description.toLowerCase().includes(query);
            });

            renderTransactions(filtered);
        }
    </script>
</body>
</html>

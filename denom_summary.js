/**
 * Denomination Summary Modal JavaScript
 * UZRS MOI Collection System
 */

document.addEventListener('DOMContentLoaded', function () {
    let denomSummaryData = null;
    let originalCurrentComputerAmount = 0;

    // Get function ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const functionId = urlParams.get('function_id');

    // Open Denomination Summary Modal Handler
    window.openDenomSummaryModal = function () {
        loadDenomSummary();
        document.getElementById('denomSummaryModal').style.display = 'flex';
    };

    // Load Denomination Summary from API
    async function loadDenomSummary() {
        try {
            const response = await fetch(`api/get_denomination_summary.php?function_id=${functionId}`);
            const data = await response.json();

            if (data.success) {
                denomSummaryData = data;
                renderDenomSummary(data);
            } else {
                console.error('Error loading denom summary:', data.message);
                document.getElementById('allComputersList').innerHTML = `<p style="color: red; text-align: center;">Error: ${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('allComputersList').innerHTML = `<p style="color: red; text-align: center;">Network error occurred</p>`;
        }
    }

    // Render Denomination Summary
    function renderDenomSummary(data) {
        // Set function name
        const funcNameEl = document.getElementById('denomSummaryFunctionName');
        if (funcNameEl) funcNameEl.textContent = data.function_name;

        // Render current computer section
        const currentSummary = data.current_computer_summary;
        if (currentSummary) {
            const labelEl = document.getElementById('currentComputerLabel');
            if (labelEl) labelEl.textContent = data.current_computer;

            const transEl = document.getElementById('currentComputerTransactions');
            if (transEl) transEl.textContent = `${currentSummary.transaction_count} transactions`;

            // Set denomination input values
            setDenomValue('editDenom500', currentSummary.denom_500);
            setDenomValue('editDenom200', currentSummary.denom_200);
            setDenomValue('editDenom100', currentSummary.denom_100);
            setDenomValue('editDenom50', currentSummary.denom_50);
            setDenomValue('editDenom20', currentSummary.denom_20);
            setDenomValue('editDenom10', currentSummary.denom_10);
            setDenomValue('editDenom5', currentSummary.denom_5);
            setDenomValue('editDenom2', currentSummary.denom_2);
            setDenomValue('editDenom1', currentSummary.denom_1);

            // Calculate and show total
            originalCurrentComputerAmount = parseFloat(currentSummary.total_amount) || 0;
            updateEditDenomTotal();
            checkDenomMismatch();
        } else {
            const labelEl = document.getElementById('currentComputerLabel');
            if (labelEl) labelEl.textContent = data.current_computer || 'Not Set';

            const transEl = document.getElementById('currentComputerTransactions');
            if (transEl) transEl.textContent = '0 transactions';

            const totalEl = document.getElementById('currentComputerTotal');
            if (totalEl) totalEl.textContent = '₹0';
        }

        // Render all computers section
        renderAllComputersSummary(data);
    }

    function setDenomValue(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value || 0;
    }

    // Render All Computers Summary
    function renderAllComputersSummary(data) {
        const container = document.getElementById('allComputersList');
        if (!container) return;

        const allSummary = data.all_computers_summary;
        const byComputer = data.by_computer || [];

        if (!allSummary || byComputer.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #666;">No data available</p>';
            return;
        }

        let html = '';

        // Individual computers summary
        byComputer.forEach(comp => {
            const isCurrentComputer = comp.computer_number === data.current_computer;
            const denomTotal = calculateDenomTotalFromObj(comp);
            const collectionTotal = parseFloat(comp.total_amount) || 0;
            const hasMismatch = Math.abs(denomTotal - collectionTotal) > 0.01;

            html += `
                <div style="margin-bottom: 15px; padding: 10px; border: 1px solid ${isCurrentComputer ? '#1976d2' : '#e0e0e0'}; border-radius: 6px; background: ${isCurrentComputer ? '#e3f2fd' : '#fafafa'};">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: bold; color: ${isCurrentComputer ? '#1976d2' : '#333'};">
                            🖥️ Computer: ${escapeHtmlDenom(comp.computer_number)} ${isCurrentComputer ? '<span style="background: #ffeb3b; padding: 2px 6px; border-radius: 3px; font-size: 0.8em;">(You)</span>' : ''}
                        </span>
                        <span style="color: #666; font-size: 0.9em;">${comp.transaction_count} transactions</span>
                    </div>
                    ${hasMismatch ? `<div style="background: #ffcc80; padding: 5px 10px; border-radius: 4px; margin-bottom: 10px; font-size: 0.9em; color: #e65100;">
                        ⚠️ Mismatch: Expected ₹${formatNumberIN(collectionTotal)}, Got ₹${formatNumberIN(denomTotal)} (${denomTotal > collectionTotal ? '+' : ''}₹${formatNumberIN(denomTotal - collectionTotal)})
                    </div>` : ''}
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
                        <tr style="background: #fff;">
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹500</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹200</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹100</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹50</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹20</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹10</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹5</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹2</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #f5f5f5;">₹1</th>
                            <th style="padding: 5px; border: 1px solid #ddd; text-align: center; background: #c5e1a5; font-weight: bold;">Total</th>
                        </tr>
                        <tr>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_500 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_200 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_100 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_50 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_20 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_10 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_5 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_2 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center;">${comp.denom_1 || 0}</td>
                            <td style="padding: 5px; border: 1px solid #ddd; text-align: center; font-weight: bold; background: #c5e1a5;">₹${formatNumberIN(collectionTotal)}</td>
                        </tr>
                    </table>
                </div>
            `;
        });

        // Grand total at the bottom
        const grandCollectionTotal = parseFloat(allSummary.total_amount) || 0;

        html += `
            <div style="margin-top: 15px; padding: 15px; border: 3px solid #4caf50; border-radius: 8px; background: linear-gradient(135deg, #e8f5e9, #c8e6c9);">
                <div style="text-align: center; margin-bottom: 10px;">
                    <span style="font-weight: bold; font-size: 1.2em; color: #2e7d32;">📊 Grand Total (All ${byComputer.length} Computers)</span>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹500</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹200</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹100</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹50</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹20</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹10</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹5</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹2</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #a5d6a7;">₹1</th>
                        <th style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #66bb6a; font-weight: bold;">Total</th>
                    </tr>
                    <tr style="font-weight: bold; font-size: 1.1em;">
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_500 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_200 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_100 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_50 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_20 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_10 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_5 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_2 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #fff;">${allSummary.denom_1 || 0}</td>
                        <td style="padding: 8px; border: 2px solid #4caf50; text-align: center; background: #66bb6a; color: white; font-size: 1.2em;">₹${formatNumberIN(grandCollectionTotal)}</td>
                    </tr>
                </table>
                <div style="text-align: center; margin-top: 10px; font-size: 0.9em; color: #555;">
                    Total Transactions: <strong>${allSummary.transaction_count}</strong>
                </div>
            </div>
        `;

        container.innerHTML = html;
    }

    // Calculate denomination total from a summary object
    function calculateDenomTotalFromObj(summary) {
        return (parseInt(summary.denom_500 || 0) * 500) +
            (parseInt(summary.denom_200 || 0) * 200) +
            (parseInt(summary.denom_100 || 0) * 100) +
            (parseInt(summary.denom_50 || 0) * 50) +
            (parseInt(summary.denom_20 || 0) * 20) +
            (parseInt(summary.denom_10 || 0) * 10) +
            (parseInt(summary.denom_5 || 0) * 5) +
            (parseInt(summary.denom_2 || 0) * 2) +
            (parseInt(summary.denom_1 || 0) * 1);
    }

    // Format number with Indian numbering
    function formatNumberIN(num) {
        return new Intl.NumberFormat('en-IN').format(num);
    }

    // Escape HTML to prevent XSS
    function escapeHtmlDenom(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Update editable denomination total
    function updateEditDenomTotal() {
        const total =
            (parseInt(document.getElementById('editDenom500')?.value) || 0) * 500 +
            (parseInt(document.getElementById('editDenom200')?.value) || 0) * 200 +
            (parseInt(document.getElementById('editDenom100')?.value) || 0) * 100 +
            (parseInt(document.getElementById('editDenom50')?.value) || 0) * 50 +
            (parseInt(document.getElementById('editDenom20')?.value) || 0) * 20 +
            (parseInt(document.getElementById('editDenom10')?.value) || 0) * 10 +
            (parseInt(document.getElementById('editDenom5')?.value) || 0) * 5 +
            (parseInt(document.getElementById('editDenom2')?.value) || 0) * 2 +
            (parseInt(document.getElementById('editDenom1')?.value) || 0) * 1;

        const totalEl = document.getElementById('currentComputerTotal');
        if (totalEl) totalEl.textContent = '₹' + formatNumberIN(total);
        checkDenomMismatch();
    }

    // Check for mismatch between denomination total and collection total
    function checkDenomMismatch() {
        const denomTotal =
            (parseInt(document.getElementById('editDenom500')?.value) || 0) * 500 +
            (parseInt(document.getElementById('editDenom200')?.value) || 0) * 200 +
            (parseInt(document.getElementById('editDenom100')?.value) || 0) * 100 +
            (parseInt(document.getElementById('editDenom50')?.value) || 0) * 50 +
            (parseInt(document.getElementById('editDenom20')?.value) || 0) * 20 +
            (parseInt(document.getElementById('editDenom10')?.value) || 0) * 10 +
            (parseInt(document.getElementById('editDenom5')?.value) || 0) * 5 +
            (parseInt(document.getElementById('editDenom2')?.value) || 0) * 2 +
            (parseInt(document.getElementById('editDenom1')?.value) || 0) * 1;

        const mismatchDiv = document.getElementById('currentComputerMismatch');
        const mismatchDetails = document.getElementById('mismatchDetails');

        if (!mismatchDiv || !mismatchDetails) return;

        if (Math.abs(denomTotal - originalCurrentComputerAmount) > 0.01) {
            const diff = denomTotal - originalCurrentComputerAmount;
            mismatchDetails.textContent = `Expected ₹${formatNumberIN(originalCurrentComputerAmount)}, Got ₹${formatNumberIN(denomTotal)} (${diff > 0 ? '+' : ''}₹${formatNumberIN(diff)})`;
            mismatchDiv.style.display = 'block';
        } else {
            mismatchDiv.style.display = 'none';
        }
    }

    // Add event listeners for edit denomination inputs
    document.querySelectorAll('.edit-denom-input').forEach(input => {
        input.addEventListener('input', updateEditDenomTotal);
    });

    // Print Denomination Summary Button Handler
    const btnPrintDenomSummary = document.getElementById('btnPrintDenomSummary');
    if (btnPrintDenomSummary) {
        btnPrintDenomSummary.addEventListener('click', function () {
            printDenomSummary();
        });
    }

    // Print denomination summary
    function printDenomSummary() {
        if (!denomSummaryData) {
            alert('No data to print');
            return;
        }

        // Prepare print data for C# Windows app
        const printData = {
            action: 'printDenomSummary',
            data: {
                functionName: denomSummaryData.function_name,
                currentComputer: denomSummaryData.current_computer,
                currentSummary: denomSummaryData.current_computer_summary,
                allSummary: denomSummaryData.all_computers_summary,
                byComputer: denomSummaryData.by_computer,
                editedDenoms: {
                    denom_500: parseInt(document.getElementById('editDenom500')?.value) || 0,
                    denom_200: parseInt(document.getElementById('editDenom200')?.value) || 0,
                    denom_100: parseInt(document.getElementById('editDenom100')?.value) || 0,
                    denom_50: parseInt(document.getElementById('editDenom50')?.value) || 0,
                    denom_20: parseInt(document.getElementById('editDenom20')?.value) || 0,
                    denom_10: parseInt(document.getElementById('editDenom10')?.value) || 0,
                    denom_5: parseInt(document.getElementById('editDenom5')?.value) || 0,
                    denom_2: parseInt(document.getElementById('editDenom2')?.value) || 0,
                    denom_1: parseInt(document.getElementById('editDenom1')?.value) || 0
                }
            }
        };

        // Try to send to C# Windows app via WebView2
        if (window.chrome && window.chrome.webview) {
            window.chrome.webview.postMessage(printData);
            alert('Print request sent to printer!');
        } else {
            // Fallback: Browser print
            printDenomSummaryBrowser();
        }
    }

    // Browser print fallback
    function printDenomSummaryBrowser() {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            alert('Popup blocked! Please allow popups.');
            return;
        }

        const data = denomSummaryData;
        const currentSummary = data.current_computer_summary;
        const allSummary = data.all_computers_summary;

        let html = `<!DOCTYPE html><html><head><title>Denomination Summary - ${data.function_name}</title>
            <style>
                body { font-family: 'Nirmala UI', Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
                h1 { text-align: center; color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
                h2 { color: #e65100; text-align: center; margin-top: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
                th { background: #f5f5f5; }
                .total { background: #c5e1a5; font-weight: bold; }
                .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
                @media print { body { padding: 10px; } }
            </style></head><body>
            <h1>Denomination Summary</h1>
            <h2>${escapeHtmlDenom(data.function_name)}</h2>
            <div class="section">
                <h3>Computer: ${escapeHtmlDenom(data.current_computer)} (You)</h3>
                <p>Transactions: ${currentSummary ? currentSummary.transaction_count : 0}</p>
                <table>
                    <tr><th>500</th><th>200</th><th>100</th><th>50</th><th>20</th><th>10</th><th>5</th><th>2</th><th>1</th><th class="total">Total</th></tr>
                    <tr>
                        <td>${document.getElementById('editDenom500')?.value || 0}</td>
                        <td>${document.getElementById('editDenom200')?.value || 0}</td>
                        <td>${document.getElementById('editDenom100')?.value || 0}</td>
                        <td>${document.getElementById('editDenom50')?.value || 0}</td>
                        <td>${document.getElementById('editDenom20')?.value || 0}</td>
                        <td>${document.getElementById('editDenom10')?.value || 0}</td>
                        <td>${document.getElementById('editDenom5')?.value || 0}</td>
                        <td>${document.getElementById('editDenom2')?.value || 0}</td>
                        <td>${document.getElementById('editDenom1')?.value || 0}</td>
                        <td class="total">${document.getElementById('currentComputerTotal')?.textContent || '₹0'}</td>
                    </tr>
                </table>
            </div>`;

        if (data.by_computer && data.by_computer.length > 0) {
            html += `<div class="section"><h3>All Computers</h3>`;
            data.by_computer.forEach(comp => {
                const collectionTotal = parseFloat(comp.total_amount) || 0;
                html += `<p><strong>Computer: ${escapeHtmlDenom(comp.computer_number)}</strong> - ${comp.transaction_count} transactions - Total: ₹${formatNumberIN(collectionTotal)}</p>`;
            });

            html += `<table><tr><th>500</th><th>200</th><th>100</th><th>50</th><th>20</th><th>10</th><th>5</th><th>2</th><th>1</th><th class="total">Grand Total</th></tr>
                <tr>
                    <td>${allSummary.denom_500 || 0}</td><td>${allSummary.denom_200 || 0}</td><td>${allSummary.denom_100 || 0}</td>
                    <td>${allSummary.denom_50 || 0}</td><td>${allSummary.denom_20 || 0}</td><td>${allSummary.denom_10 || 0}</td>
                    <td>${allSummary.denom_5 || 0}</td><td>${allSummary.denom_2 || 0}</td><td>${allSummary.denom_1 || 0}</td>
                    <td class="total">₹${formatNumberIN(allSummary.total_amount)}</td>
                </tr></table>
                <p><strong>Total Transactions: ${allSummary.transaction_count}</strong></p></div>`;
        }

        html += `<p style="text-align: center; margin-top: 20px; color: #666;">Printed on: ${new Date().toLocaleString('en-IN')}</p>
            <script>window.print(); window.onafterprint = function() { window.close(); };</script></body></html>`;

        printWindow.document.write(html);
        printWindow.document.close();
    }

    // Update Denomination Button Handler
    const btnUpdateDenom = document.getElementById('btnUpdateDenom');
    if (btnUpdateDenom) {
        btnUpdateDenom.addEventListener('click', function () {
            const denomTotal =
                (parseInt(document.getElementById('editDenom500')?.value) || 0) * 500 +
                (parseInt(document.getElementById('editDenom200')?.value) || 0) * 200 +
                (parseInt(document.getElementById('editDenom100')?.value) || 0) * 100 +
                (parseInt(document.getElementById('editDenom50')?.value) || 0) * 50 +
                (parseInt(document.getElementById('editDenom20')?.value) || 0) * 20 +
                (parseInt(document.getElementById('editDenom10')?.value) || 0) * 10 +
                (parseInt(document.getElementById('editDenom5')?.value) || 0) * 5 +
                (parseInt(document.getElementById('editDenom2')?.value) || 0) * 2 +
                (parseInt(document.getElementById('editDenom1')?.value) || 0) * 1;

            if (Math.abs(denomTotal - originalCurrentComputerAmount) > 0.01) {
                const diff = denomTotal - originalCurrentComputerAmount;
                if (!confirm(`⚠️ Denomination total (₹${formatNumberIN(denomTotal)}) doesn't match collection total (₹${formatNumberIN(originalCurrentComputerAmount)}). Difference: ${diff > 0 ? '+' : ''}₹${formatNumberIN(diff)}.\n\nThe values will be used for printing only. Collection amounts remain unchanged.\n\nContinue?`)) {
                    return;
                }
            }
            alert('✅ Values updated for printing. Collection amounts remain unchanged.');
        });
    }
});

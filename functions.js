/**
 * Functions Management JavaScript
 * UZRS MOI Collection System
 */

document.addEventListener('DOMContentLoaded', function () {
    let isCreateSubmitting = false;
    let isEditSubmitting = false;
    // Create Modal Elements
    const modal = document.getElementById('createModal');
    const createBtn = document.getElementById('createNewBtn');
    const closeBtn = document.querySelector('.close');
    const form = document.getElementById('createFunctionForm');
    const messageDiv = document.getElementById('modalMessage');

    // Edit Modal Elements
    const editModal = document.getElementById('editModal');
    const closeEditBtn = document.getElementById('closeEditModal');
    const editForm = document.getElementById('editFunctionForm');
    const editMessageDiv = document.getElementById('editModalMessage');

    // Delete Modal Elements
    const deleteModal = document.getElementById('deleteModal');
    const closeDeleteBtn = document.getElementById('closeDeleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const deleteMessageDiv = document.getElementById('deleteModalMessage');
    let functionToDeleteId = null;

    // Handover Modal Elements
    const handoverModal = document.getElementById('handoverModal');
    const closeHandoverBtn = document.getElementById('closeHandoverModal');
    const closeHandoverBtnAlt = document.getElementById('closeHandoverBtn');
    const printHandoverBtn = document.getElementById('printHandoverBtn');
    let currentHandoverData = null;

    // Open modal when clicking Create New button
    createBtn.addEventListener('click', function () {
        modal.style.display = 'block';
        form.reset();
        hideMessage();
    });

    // Close modal when clicking X
    closeBtn.addEventListener('click', function () {
        modal.style.display = 'none';
        hideMessage();
    });

    // Close Edit modal
    if (closeEditBtn) {
        closeEditBtn.addEventListener('click', function () {
            editModal.style.display = 'none';
            editMessageDiv.textContent = '';
            editMessageDiv.className = 'message';
        });
    }

    // Close Delete modal
    if (closeDeleteBtn) {
        closeDeleteBtn.addEventListener('click', function () {
            deleteModal.style.display = 'none';
            deleteMessageDiv.textContent = '';
            deleteMessageDiv.className = 'message';
        });
    }

    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function () {
            deleteModal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            hideMessage();
        }
        if (event.target === editModal) {
            editModal.style.display = 'none';
        }
        if (event.target === deleteModal) {
            deleteModal.style.display = 'none';
        }
        if (event.target === handoverModal) {
            handoverModal.style.display = 'none';
        }
    });

    // Handover Modal Close handlers
    if (closeHandoverBtn) {
        closeHandoverBtn.addEventListener('click', function () {
            handoverModal.style.display = 'none';
        });
    }
    if (closeHandoverBtnAlt) {
        closeHandoverBtnAlt.addEventListener('click', function () {
            handoverModal.style.display = 'none';
        });
    }

    // Handover denomination input handlers
    const denomInputs = document.querySelectorAll('.handover-denom-input');
    denomInputs.forEach(input => {
        input.addEventListener('input', calculateHandoverTotal);
    });

    // Print Handover button handler
    if (printHandoverBtn) {
        printHandoverBtn.addEventListener('click', printHandoverReceipt);
    }

    // Handle Create form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (isCreateSubmitting) return;
        isCreateSubmitting = true;

        // Get form data
        const formData = new FormData(form);

        // Disable submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> உருவாக்குகிறது...';

        // Send AJAX request
        fetch('api/create_function.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => {
                        modal.style.display = 'none';
                        form.reset();
                        loadFunctions(); // Reload functions list
                    }, 1500);
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('பிழை ஏற்பட்டது. மீண்டும் முயற்சிக்கவும்.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                isCreateSubmitting = false;
                submitBtn.disabled = false;
                submitBtn.textContent = 'உருவாக்கு';
            });
    });

    // Handle Edit form submission
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (isEditSubmitting) return;
            isEditSubmitting = true;

            const formData = new FormData(editForm);
            const submitBtn = editForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> புதுப்பிக்கிறது...';

            fetch('api/update_function.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        editMessageDiv.textContent = data.message;
                        editMessageDiv.className = 'message success show';
                        setTimeout(() => {
                            editModal.style.display = 'none';
                            loadFunctions();
                        }, 1500);
                    } else {
                        editMessageDiv.textContent = data.message;
                        editMessageDiv.className = 'message error show';
                    }
                })
                .catch(error => {
                    editMessageDiv.textContent = 'பிழை ஏற்பட்டது.';
                    editMessageDiv.className = 'message error show';
                    console.error('Error:', error);
                })
                .finally(() => {
                    isEditSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'புதுப்பி';
                });
        });
    }

    // Handle Delete Confirmation
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            if (!functionToDeleteId) return;

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'நீக்குகிறது...';

            const formData = new FormData();
            formData.append('functionId', functionToDeleteId);

            fetch('api/delete_function.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        deleteMessageDiv.textContent = data.message;
                        deleteMessageDiv.className = 'message success show';
                        setTimeout(() => {
                            deleteModal.style.display = 'none';
                            loadFunctions();
                        }, 1500);
                    } else {
                        deleteMessageDiv.textContent = data.message;
                        deleteMessageDiv.className = 'message error show';
                        btn.disabled = false;
                        btn.textContent = 'ஆம், நீக்கு';
                    }
                })
                .catch(error => {
                    deleteMessageDiv.textContent = 'பிழை ஏற்பட்டது.';
                    deleteMessageDiv.className = 'message error show';
                    console.error('Error:', error);
                    btn.disabled = false;
                    btn.textContent = 'ஆம், நீக்கு';
                });
        });
    }

    // Show message function
    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + type + ' show';
    }

    // Hide message function
    function hideMessage() {
        messageDiv.className = 'message';
        messageDiv.textContent = '';
    }

    // Load functions list
    function loadFunctions() {
        const container = document.getElementById('functionsContainer');
        container.innerHTML = '<p style="text-align:center;">ஏற்றுகிறது...</p>';

        // Add timestamp to prevent caching
        fetch('api/get_functions.php?t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                console.log('Functions API Response:', data);

                if (data.success && data.functions && data.functions.length > 0) {
                    let html = '<div class="functions-grid">';

                    data.functions.forEach(func => {
                        const formattedDate = new Date(func.function_date).toLocaleDateString('en-IN', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        // Store data in attributes for easy access
                        const safeName = escapeHtml(func.function_name).replace(/"/g, '&quot;');
                        const safePlace = escapeHtml(func.place).replace(/"/g, '&quot;');
                        const safeDetails = escapeHtml(func.function_details || '').replace(/"/g, '&quot;');

                        html += `
                            <div class="function-card">
                                <div class="clickable" onclick="window.location.href='collection_entry.php?function_id=${func.id}'" style="cursor: pointer;">
                                    <h3>${escapeHtml(func.function_name)}</h3>
                                    <div class="function-details">
                                        <p><strong>📅 தேதி:</strong> ${formattedDate}</p>
                                        <p><strong>📍 இடம்:</strong> ${escapeHtml(func.place)}</p>
                                        ${func.function_details ? `<div class="details-box"><strong>விவரம்:</strong><p>${escapeHtml(func.function_details)}</p></div>` : ''}
                                        <p class="created-date">உருவாக்கப்பட்டது: ${new Date(func.created_at).toLocaleDateString('en-IN')}</p>
                                    </div>
                                </div>
                                <div class="card-actions" style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px; display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
                                    <button class="btn-handover" 
                                        data-id="${func.id}"
                                        data-name="${safeName}" 
                                        data-date="${func.function_date}" 
                                        data-place="${safePlace}"
                                        style="background: #9c27b0; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                        💰 ஒப்படைத்தல்
                                    </button>
                                    <button class="btn-qr" 
                                        data-id="${func.id}"
                                        style="background: #4CAF50; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                        📱 QR குறியீடு
                                    </button>
                                    <button class="btn-edit" 
                                        data-id="${func.id}" 
                                        data-name="${safeName}" 
                                        data-date="${func.function_date}" 
                                        data-place="${safePlace}" 
                                        data-details="${safeDetails}"
                                        style="background: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                        ✏️ திருத்து
                                    </button>
                                    <button class="btn-delete" 
                                        data-id="${func.id}"
                                        style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">
                                        🗑️ நீக்கு
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                    container.innerHTML = html;

                    // Attach event listeners to new buttons
                    document.querySelectorAll('.btn-handover').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const id = this.dataset.id;
                            const name = this.dataset.name;
                            const date = this.dataset.date;
                            const place = this.dataset.place;
                            openHandoverModal(id, name, date, place);
                        });
                    });

                    document.querySelectorAll('.btn-qr').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const id = this.dataset.id;
                            window.open('function_qr.php?function_id=' + id, '_blank');
                        });
                    });

                    document.querySelectorAll('.btn-edit').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const id = this.dataset.id;
                            const name = this.dataset.name;
                            const date = this.dataset.date;
                            const place = this.dataset.place;
                            const details = this.dataset.details;

                            document.getElementById('editFunctionId').value = id;
                            document.getElementById('editFunctionName').value = name;
                            document.getElementById('editFunctionDate').value = date;
                            document.getElementById('editPlace').value = place;
                            document.getElementById('editFunctionDetails').value = details;

                            editMessageDiv.textContent = '';
                            editMessageDiv.className = 'message';
                            editModal.style.display = 'block';
                        });
                    });

                    document.querySelectorAll('.btn-delete').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.stopPropagation();
                            functionToDeleteId = this.dataset.id;
                            deleteMessageDiv.textContent = '';
                            deleteMessageDiv.className = 'message';
                            deleteModal.style.display = 'block';
                        });
                    });

                } else {
                    if (data.message) {
                        console.error('API Message:', data.message);
                    }
                    container.innerHTML = '<p class="empty-state">இதுவரை எந்த விசேஷமும் உருவாக்கப்படவில்லை. புதிய விசேஷத்தை சேர்க்க "புதிய விசேஷம்" பட்டனை கிளிக் செய்யவும்!</p>';
                }
            })
            .catch(error => {
                console.error('Error loading functions:', error);
                container.innerHTML = '<p class="empty-state" style="color:red;">விசேஷங்களை ஏற்றுவதில் பிழை. பக்கத்தை புதுப்பிக்கவும்.</p>';
            });
    }

    // Escape HTML function to prevent XSS
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Load functions on page load
    loadFunctions();

    // Open Handover Modal
    function openHandoverModal(functionId, functionName, functionDate, functionPlace) {
        // Reset inputs
        document.querySelectorAll('.handover-denom-input').forEach(input => {
            input.value = 0;
        });

        // Set function info
        document.getElementById('handoverFunctionId').value = functionId;
        document.getElementById('handoverFunctionName').textContent = functionName;
        document.getElementById('handoverFunctionDate').querySelector('span').textContent = formatDate(functionDate);
        document.getElementById('handoverFunctionPlace').querySelector('span').textContent = functionPlace;

        // Fetch function summary data
        fetch('api/get_function_handover.php?function_id=' + functionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentHandoverData = data;

                    // Update collection summary
                    const denomTotals = data.denomination_totals;
                    const expenseTotals = data.expense_totals;

                    document.getElementById('summaryTotalEntries').textContent = denomTotals.total_entries;
                    document.getElementById('summaryTotalAmount').textContent = denomTotals.total_amount.toLocaleString('en-IN');
                    document.getElementById('summaryTotalExpense').textContent = expenseTotals.total_expense_amount.toLocaleString('en-IN');

                    const netAmount = denomTotals.total_amount - expenseTotals.total_expense_amount;
                    document.getElementById('summaryNetAmount').textContent = netAmount.toLocaleString('en-IN');
                    document.getElementById('diffSystemAmount').textContent = netAmount.toLocaleString('en-IN');

                    // Build system denomination table
                    const denominations = [
                        { value: 500, count: denomTotals.denom_500 },
                        { value: 200, count: denomTotals.denom_200 },
                        { value: 100, count: denomTotals.denom_100 },
                        { value: 50, count: denomTotals.denom_50 },
                        { value: 20, count: denomTotals.denom_20 },
                        { value: 10, count: denomTotals.denom_10 },
                        { value: 5, count: denomTotals.denom_5 },
                        { value: 2, count: denomTotals.denom_2 },
                        { value: 1, count: denomTotals.denom_1 }
                    ];

                    let systemDenomHtml = '';
                    let systemTotal = 0;
                    let systemCount = 0;

                    denominations.forEach(denom => {
                        const total = denom.value * denom.count;
                        systemTotal += total;
                        systemCount += denom.count;
                        systemDenomHtml += `
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;">₹${denom.value}</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">${denom.count}</td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">₹${total.toLocaleString('en-IN')}</td>
                            </tr>
                        `;
                    });

                    systemDenomHtml += `
                        <tr style="background: #e65100; color: white; font-weight: bold;">
                            <td style="padding: 10px; border: 1px solid #ddd;">மொத்தம்</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">${systemCount}</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">₹${systemTotal.toLocaleString('en-IN')}</td>
                        </tr>
                    `;

                    document.getElementById('systemDenomBody').innerHTML = systemDenomHtml;

                    // Calculate initial difference
                    calculateHandoverTotal();

                    // Show modal
                    handoverModal.style.display = 'block';
                } else {
                    alert('தரவை ஏற்றுவதில் பிழை: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('தரவை ஏற்றுவதில் பிழை ஏற்பட்டது');
            });
    }

    // Calculate handover total
    function calculateHandoverTotal() {
        const denoms = [500, 200, 100, 50, 20, 10, 5, 2, 1];
        let grandTotal = 0;
        let totalCount = 0;

        denoms.forEach(denom => {
            const input = document.getElementById('handover' + denom);
            const count = parseInt(input.value) || 0;
            const total = denom * count;

            document.getElementById('handover' + denom + 'Total').textContent = '₹' + total.toLocaleString('en-IN');
            grandTotal += total;
            totalCount += count;
        });

        document.getElementById('handoverTotalCount').textContent = totalCount;
        document.getElementById('handoverGrandTotal').textContent = '₹' + grandTotal.toLocaleString('en-IN');

        // Update difference
        document.getElementById('diffHandAmount').textContent = grandTotal.toLocaleString('en-IN');

        if (currentHandoverData) {
            const netAmount = currentHandoverData.denomination_totals.total_amount -
                currentHandoverData.expense_totals.total_expense_amount;
            const difference = grandTotal - netAmount;

            const diffAmountEl = document.getElementById('diffAmount');
            diffAmountEl.textContent = (difference >= 0 ? '+' : '') + '₹' + difference.toLocaleString('en-IN');
            diffAmountEl.style.color = difference === 0 ? '#4caf50' : (difference > 0 ? '#2196f3' : '#d32f2f');
        }
    }

    // Format date for display
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('ta-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Print Handover Receipt for 3-inch thermal printer (80mm)
    function printHandoverReceipt() {
        if (!currentHandoverData) {
            alert('தரவு ஏற்றப்படவில்லை');
            return;
        }

        const func = currentHandoverData.function;
        const denomTotals = currentHandoverData.denomination_totals;
        const expenseTotals = currentHandoverData.expense_totals;
        const netAmount = denomTotals.total_amount - expenseTotals.total_expense_amount;

        // Get hand denomination values
        const denoms = [500, 200, 100, 50, 20, 10, 5, 2, 1];
        let handDenomRows = '';
        let handTotal = 0;
        let handCount = 0;

        denoms.forEach(denom => {
            const count = parseInt(document.getElementById('handover' + denom).value) || 0;
            if (count > 0) {
                const total = denom * count;
                handTotal += total;
                handCount += count;
                handDenomRows += `
                    <tr>
                        <td style="padding: 2px; border-bottom: 1px dashed #000;">₹${denom}</td>
                        <td style="padding: 2px; text-align: center; border-bottom: 1px dashed #000;">${count}</td>
                        <td style="padding: 2px; text-align: right; border-bottom: 1px dashed #000;">₹${total}</td>
                    </tr>
                `;
            }
        });

        // System denomination rows
        let sysDenomRows = '';
        let sysTotal = 0;
        let sysCount = 0;

        const systemDenoms = [
            { value: 500, count: denomTotals.denom_500 },
            { value: 200, count: denomTotals.denom_200 },
            { value: 100, count: denomTotals.denom_100 },
            { value: 50, count: denomTotals.denom_50 },
            { value: 20, count: denomTotals.denom_20 },
            { value: 10, count: denomTotals.denom_10 },
            { value: 5, count: denomTotals.denom_5 },
            { value: 2, count: denomTotals.denom_2 },
            { value: 1, count: denomTotals.denom_1 }
        ];

        systemDenoms.forEach(denom => {
            if (denom.count > 0) {
                const total = denom.value * denom.count;
                sysTotal += total;
                sysCount += denom.count;
                sysDenomRows += `
                    <tr>
                        <td style="padding: 2px; border-bottom: 1px dashed #000;">₹${denom.value}</td>
                        <td style="padding: 2px; text-align: center; border-bottom: 1px dashed #000;">${denom.count}</td>
                        <td style="padding: 2px; text-align: right; border-bottom: 1px dashed #000;">₹${total}</td>
                    </tr>
                `;
            }
        });

        const difference = handTotal - netAmount;
        const diffColor = difference === 0 ? '#000' : (difference > 0 ? '#000' : '#000');
        const diffSign = difference >= 0 ? '+' : '';

        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>ஒப்படைத்தல் - ${func.function_name}</title>
                <style>
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }
                    body {
                        font-family: 'Tamil Sangam MN', 'Nirmala UI', 'Latha', Arial, sans-serif;
                        font-size: 12px;
                        width: 76mm;
                        margin: 2mm;
                        padding: 0;
                        color: #000;
                    }
                    .header {
                        text-align: center;
                        border-bottom: 2px solid #000;
                        padding-bottom: 5px;
                        margin-bottom: 8px;
                    }
                    .header h1 {
                        font-size: 16px;
                        margin: 0 0 3px 0;
                    }
                    .header h2 {
                        font-size: 14px;
                        margin: 0;
                        font-weight: normal;
                    }
                    .section {
                        margin-bottom: 8px;
                        padding-bottom: 5px;
                        border-bottom: 1px dashed #000;
                    }
                    .section-title {
                        font-weight: bold;
                        font-size: 13px;
                        margin-bottom: 5px;
                        text-align: center;
                    }
                    .info-row {
                        display: flex;
                        justify-content: space-between;
                        padding: 2px 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 11px;
                    }
                    th {
                        text-align: left;
                        padding: 3px;
                        border-bottom: 1px solid #000;
                    }
                    .total-row {
                        font-weight: bold;
                        border-top: 2px solid #000;
                    }
                    .total-row td {
                        padding-top: 5px;
                    }
                    .summary-box {
                        background: #f0f0f0;
                        padding: 5px;
                        margin-top: 8px;
                    }
                    .diff-section {
                        margin-top: 8px;
                        padding: 5px;
                        border: 2px solid #000;
                        text-align: center;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 10px;
                        font-size: 10px;
                        border-top: 1px dashed #000;
                        padding-top: 5px;
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>UZRS மொய் கணக்கு</h1>
                    <h2>💰 ஒப்படைத்தல் - காசு கணக்கு</h2>
                </div>
                
                <div class="section">
                    <div class="section-title">📌 விசேஷ விவரம்</div>
                    <div class="info-row">
                        <span>பெயர்:</span>
                        <span><strong>${func.function_name}</strong></span>
                    </div>
                    <div class="info-row">
                        <span>📅 தேதி:</span>
                        <span>${formatDate(func.function_date)}</span>
                    </div>
                    <div class="info-row">
                        <span>📍 இடம்:</span>
                        <span>${func.place}</span>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">📊 சேகரிப்பு சுருக்கம்</div>
                    <div class="info-row">
                        <span>மொத்த பதிவுகள்:</span>
                        <span>${denomTotals.total_entries}</span>
                    </div>
                    <div class="info-row">
                        <span>மொத்த தொகை:</span>
                        <span>₹${denomTotals.total_amount.toLocaleString('en-IN')}</span>
                    </div>
                    <div class="info-row">
                        <span>மொத்த செலவுகள்:</span>
                        <span>₹${expenseTotals.total_expense_amount.toLocaleString('en-IN')}</span>
                    </div>
                    <div class="info-row" style="border-top: 1px solid #000; padding-top: 3px; font-weight: bold;">
                        <span>இருப்பு தொகை:</span>
                        <span>₹${netAmount.toLocaleString('en-IN')}</span>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">✏️ கையில் உள்ள நோட்டு</div>
                    <table>
                        <thead>
                            <tr>
                                <th>நோட்டு</th>
                                <th style="text-align: center;">எண்.</th>
                                <th style="text-align: right;">தொகை</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${handDenomRows || '<tr><td colspan="3" style="text-align: center; padding: 5px;">தரவு இல்லை</td></tr>'}
                            <tr class="total-row">
                                <td>மொத்தம்</td>
                                <td style="text-align: center;">${handCount}</td>
                                <td style="text-align: right;">₹${handTotal.toLocaleString('en-IN')}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="diff-section">
                    <div style="font-size: 13px; margin-bottom: 5px;"><strong>⚖️ வித்தியாசம்</strong></div>
                    <div class="info-row">
                        <span>கணக்கு தொகை:</span>
                        <span>₹${netAmount.toLocaleString('en-IN')}</span>
                    </div>
                    <div class="info-row">
                        <span>கையில் உள்ள தொகை:</span>
                        <span>₹${handTotal.toLocaleString('en-IN')}</span>
                    </div>
                    <div style="font-size: 14px; font-weight: bold; margin-top: 5px; border-top: 1px solid #000; padding-top: 5px;">
                        வித்தியாசம்: ${diffSign}₹${Math.abs(difference).toLocaleString('en-IN')}
                    </div>
                </div>
                
                <div class="footer">
                    <div>அச்சிட்ட நேரம்: ${new Date().toLocaleString('ta-IN')}</div>
                    <div style="margin-top: 3px;">UZRS MOI Collection System</div>
                </div>
            </body>
            </html>
        `;

        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=300,height=600');
        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load then print
        printWindow.onload = function () {
            printWindow.focus();
            printWindow.print();
            // Close after print dialog is closed (optional)
            // printWindow.close();
        };
    }
});

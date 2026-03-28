/**
 * Collection Entry JavaScript - Exact Layout Match
 * UZRS MOI Collection System
 */

document.addEventListener('DOMContentLoaded', function () {
    let currentGuestEntryId = null;
    const form = document.getElementById('collectionForm');
    const messageDiv = document.getElementById('formMessage');
    const denomInputs = document.querySelectorAll('.denom-input');

    // Restore checkbox states from localStorage
    const chkCounterCopy = document.getElementById('chkCounterCopy');
    const chkEnablePrintHelper = document.getElementById('chkEnablePrintHelper');
    const chkEnableDenom = document.getElementById('chkEnableDenom');

    if (chkCounterCopy) {
        const savedCounterCopy = localStorage.getItem('chkCounterCopy');
        if (savedCounterCopy !== null) {
            chkCounterCopy.checked = savedCounterCopy === 'true';
        }
        chkCounterCopy.addEventListener('change', function () {
            localStorage.setItem('chkCounterCopy', this.checked);
        });
    }

    if (chkEnablePrintHelper) {
        // Default to true if not set
        const savedHelper = localStorage.getItem('chkEnablePrintHelper');
        if (savedHelper !== null) {
            chkEnablePrintHelper.checked = savedHelper === 'true';
        }
        chkEnablePrintHelper.addEventListener('change', function () {
            localStorage.setItem('chkEnablePrintHelper', this.checked);
        });
    }

    if (chkEnableDenom) {
        const savedEnableDenom = localStorage.getItem('chkEnableDenom');
        if (savedEnableDenom !== null) {
            chkEnableDenom.checked = savedEnableDenom === 'true';
        }
        chkEnableDenom.addEventListener('change', function () {
            localStorage.setItem('chkEnableDenom', this.checked);
        });
    }

    // ==========================================
    // GLOBAL KEYBOARD SHORTCUTS (விசைப்பலகை குறுக்குவழிகள்)
    // ==========================================
    document.addEventListener('keydown', function (e) {
        // Check if any modal is open
        const openModals = document.querySelectorAll('.modal-overlay[style*="display: flex"], .modal-overlay[style*="display:flex"]');
        const isModalOpen = openModals.length > 0;

        // Check if currently typing in an input field
        const activeElement = document.activeElement;
        const isInputFocused = activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.isContentEditable
        );

        // F1 - Show keyboard help modal
        if (e.key === 'F1') {
            e.preventDefault();
            const helpModal = document.getElementById('keyboardHelpModal');
            if (helpModal) {
                helpModal.style.display = 'flex';
            }
            return;
        }

        // Escape - Close any open modal
        if (e.key === 'Escape') {
            if (isModalOpen) {
                // Close the topmost modal
                const modalsArray = Array.from(openModals);
                const topModal = modalsArray[modalsArray.length - 1];
                if (topModal && typeof closeModal === 'function') {
                    closeModal(topModal.id);
                } else {
                    topModal.style.display = 'none';
                }
            }
            // Also close any open autocomplete dropdowns
            document.querySelectorAll('.autocomplete-dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
            return;
        }

        // Alt + Key shortcuts (only when no modal is open or specific modals)
        if (e.altKey) {
            switch (e.key.toLowerCase()) {
                case 'l': // Alt+L - Focus Location field
                    e.preventDefault();
                    const locationField = document.getElementById('location');
                    if (locationField) {
                        locationField.focus();
                        locationField.select();
                    }
                    break;

                case 'p': // Alt+P - Focus Phone field
                    e.preventDefault();
                    const phoneField = document.getElementById('phone');
                    if (phoneField) {
                        phoneField.focus();
                        phoneField.select();
                    }
                    break;

                case 's': // Alt+S - Save / Confirm
                    e.preventDefault();
                    // If denom modal is open, confirm payment
                    const denomModal = document.getElementById('denomModal');
                    if (denomModal && denomModal.style.display === 'flex') {
                        const btnConfirm = document.getElementById('btnConfirmPayment');
                        if (btnConfirm && !btnConfirm.disabled) {
                            btnConfirm.click();
                        }
                    } else {
                        // Otherwise, trigger save
                        const btnSave = document.getElementById('btnAddToList');
                        if (btnSave && !btnSave.disabled && btnSave.style.display !== 'none') {
                            btnSave.click();
                        }
                    }
                    break;

                case 'a': // Alt+A - Focus Amount field
                    e.preventDefault();
                    const amountField = document.getElementById('amount');
                    if (amountField) {
                        amountField.focus();
                        amountField.select();
                    }
                    break;

                case 'n': // Alt+N - Focus Name 1 field
                    e.preventDefault();
                    const name1Field = document.getElementById('name1');
                    if (name1Field) {
                        name1Field.focus();
                        name1Field.select();
                    }
                    break;

                case 'v': // Alt+V - View All Records
                    e.preventDefault();
                    const btnViewAll = document.getElementById('btnViewAll');
                    if (btnViewAll) {
                        btnViewAll.click();
                    }
                    break;

                case 'o': // Alt+O - Open Options Menu
                    e.preventDefault();
                    const optionsModal = document.getElementById('optionsMenuModal');
                    if (optionsModal) {
                        optionsModal.style.display = 'flex';
                    }
                    break;
            }
            return;
        }

        // Arrow key navigation between form fields (when not in autocomplete dropdown)
        if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && isInputFocused && !isModalOpen) {
            // Check if autocomplete is showing for this field
            const fieldId = activeElement.id;
            const autocompleteDropdown = document.getElementById('autocomplete' + fieldId.charAt(0).toUpperCase() + fieldId.slice(1));
            if (autocompleteDropdown && autocompleteDropdown.classList.contains('show')) {
                // Let the autocomplete handle arrow keys
                return;
            }

            // Navigate between form fields
            const formInputs = Array.from(document.querySelectorAll('#collectionForm .input-field:not([type="hidden"]):not([readonly])'));
            const currentIndex = formInputs.indexOf(activeElement);

            if (currentIndex !== -1) {
                e.preventDefault();
                let nextIndex;
                if (e.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % formInputs.length;
                } else {
                    nextIndex = (currentIndex - 1 + formInputs.length) % formInputs.length;
                }

                if (formInputs[nextIndex]) {
                    formInputs[nextIndex].focus();
                    formInputs[nextIndex].select();
                }
            }
        }
    });

    // Get function ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const functionId = urlParams.get('function_id');

    // Initialize immediately to ensure data loads even if later scripts err
    if (functionId) {
        // Use setTimeout to allow function definitions (hoisted) to be ready
        setTimeout(() => {
            console.log('Initializing collection page for Function ID:', functionId);
            if (typeof loadCollections === 'function') loadCollections();
            if (typeof updateSerialNo === 'function') updateSerialNo();
        }, 50);
    } else {
        console.error('Function ID not found in URL');
    }

    // Get input elements
    const phoneInput = document.getElementById('phone');
    const initialInput = document.getElementById('initial');
    const initial2Input = document.getElementById('initial2');
    const name1Input = document.getElementById('name1');
    const name2Input = document.getElementById('name2');
    const occupationInput = document.getElementById('occupation');

    // Auto-capitalize initial field input (all alphabets to uppercase)
    if (initialInput) {
        initialInput.addEventListener('input', function (e) {
            const cursorPosition = this.selectionStart;
            const originalLength = this.value.length;
            this.value = this.value.toUpperCase();
            // Restore cursor position
            const newLength = this.value.length;
            const newPosition = cursorPosition + (newLength - originalLength);
            this.setSelectionRange(newPosition, newPosition);
        });
    }

    // Auto-capitalize initial2 field input (all alphabets to uppercase)
    if (initial2Input) {
        initial2Input.addEventListener('input', function (e) {
            const cursorPosition = this.selectionStart;
            const originalLength = this.value.length;
            this.value = this.value.toUpperCase();
            // Restore cursor position
            const newLength = this.value.length;
            const newPosition = cursorPosition + (newLength - originalLength);
            this.setSelectionRange(newPosition, newPosition);
        });
    }

    let serverCollections = [];
    const pendingCollections = [];
    const btnAddToList = document.getElementById('btnAddToList');
    const btnSaveAll = document.getElementById('btnSaveAll');
    const amountInput = document.getElementById('amount');

    // Get customer number input
    const customerNoInput = document.getElementById('customerNo');

    // Get relationship checkboxes
    const relationship1 = document.getElementById('relationship1');
    const relationship2 = document.getElementById('relationship2');

    // Restrict input to numerics only and disable Google Input Tools
    const numericFields = ['amount', 'customerNo', 'phone', 'editAmount', 'editCustomerNo', 'editPhone'];

    numericFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Disable Google Input Tools and other IMEs
            field.setAttribute('autocomplete', 'off');
            field.setAttribute('autocorrect', 'off');
            field.setAttribute('autocapitalize', 'off');
            field.setAttribute('spellcheck', 'false');
            field.setAttribute('data-lpignore', 'true'); // LastPass
            field.setAttribute('lang', 'en'); // Force English language
            field.classList.add('notranslate'); // Google Translate / Input Tools disable

            // Force numeric only
            field.addEventListener('input', function (e) {
                // For amount, allow decimals
                if (fieldId === 'amount' || fieldId === 'editAmount') {
                    field.setAttribute('inputmode', 'decimal');
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    // Prevent multiple decimals
                    const parts = this.value.split('.');
                    if (parts.length > 2) {
                        this.value = parts[0] + '.' + parts.slice(1).join('');
                    }
                } else {
                    // Phone and Customer No - strictly digits
                    field.setAttribute('inputmode', 'numeric');
                    this.value = this.value.replace(/[^0-9]/g, '');
                }
            });

            // Prevent paste of non-numerics
            field.addEventListener('paste', function (e) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                if (fieldId === 'amount' || fieldId === 'editAmount') {
                    // Allow numbers and one dot
                    if (/^[0-9]*\.?[0-9]*$/.test(text)) {
                        document.execCommand('insertText', false, text);
                    }
                } else {
                    // Only digits
                    if (/^[0-9]+$/.test(text)) {
                        document.execCommand('insertText', false, text);
                    }
                }
            });
        }
    });

    // Hard block IME composition so Google Input Tool cannot inject Tamil
    const imeLockedFields = ['amount', 'editAmount', 'customerNo', 'phone', 'editCustomerNo', 'editPhone'];
    imeLockedFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!field) return;

        const enforceEnglish = () => {
            field.style.imeMode = 'disabled';
            field.setAttribute('lang', 'en');
        };

        field.addEventListener('focus', enforceEnglish);
        field.addEventListener('compositionstart', () => {
            field.dataset.composingIme = 'true';
        });
        field.addEventListener('compositionend', () => {
            delete field.dataset.composingIme;
        });

        if ('onbeforeinput' in field) {
            field.addEventListener('beforeinput', event => {
                if (event.isComposing || field.dataset.composingIme === 'true') {
                    event.preventDefault();
                }
            });
        }
    });

    // Make relationship checkboxes mutually exclusive
    if (relationship1 && relationship2) {
        relationship1.addEventListener('change', function () {
            if (this.checked) {
                relationship2.checked = false;
            }
        });

        relationship2.addEventListener('change', function () {
            if (this.checked) {
                relationship1.checked = false;
            }
        });
    }

    // Autocomplete state for each field
    const autocompleteState = {
        location: { results: [], selectedIndex: -1, timeout: null },
        initial: { results: [], selectedIndex: -1, timeout: null },
        name1: { results: [], selectedIndex: -1, timeout: null },
        name2: { results: [], selectedIndex: -1, timeout: null },
        occupation: { results: [], selectedIndex: -1, timeout: null },
        village: { results: [], selectedIndex: -1, timeout: null },
        phone: { results: [], selectedIndex: -1, timeout: null },
        customerNo: { results: [], selectedIndex: -1, timeout: null },
        description: { results: [], selectedIndex: -1, timeout: null }
    };

    // Keyboard Navigation
    const inputs = document.querySelectorAll('.input-field');
    const locationInput = document.getElementById('location');

    // Setup autocomplete for each field
    setupAutocomplete('location', locationInput, document.getElementById('autocompleteLocation'));
    // setupAutocomplete('initial', initialInput, document.getElementById('autocompleteInitial'));
    setupAutocomplete('name1', name1Input, document.getElementById('autocompleteName1'));
    setupAutocomplete('name2', name2Input, document.getElementById('autocompleteName2'));
    setupAutocomplete('occupation', occupationInput, document.getElementById('autocompleteOccupation'));
    setupAutocomplete('village', document.getElementById('village'), document.getElementById('autocompleteVillage'));
    setupAutocomplete('phone', document.getElementById('phone'), document.getElementById('autocompletePhone'));
    setupAutocomplete('customerNo', document.getElementById('customerNo'), document.getElementById('autocompleteCustomerNo'));
    // setupAutocomplete('description', document.getElementById('description'), document.getElementById('autocompleteDescription'));

    function setupAutocomplete(fieldName, inputElement, dropdownElement) {
        if (!inputElement || !dropdownElement) return;

        const state = autocompleteState[fieldName];

        // Input event - trigger search
        inputElement.addEventListener('input', function () {
            clearTimeout(state.timeout);
            const query = this.value.trim();

            if (query.length >= 1) {
                state.timeout = setTimeout(() => {
                    searchField(fieldName, query, dropdownElement);
                }, 300);
            } else {
                hideAutocompleteDropdown(dropdownElement, state);
            }
        });

        // Keyboard navigation
        inputElement.addEventListener('keydown', function (e) {
            if (dropdownElement.classList.contains('show')) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    state.selectedIndex = Math.min(state.selectedIndex + 1, state.results.length - 1);
                    updateAutocompleteSelection(dropdownElement, state);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    state.selectedIndex = Math.max(state.selectedIndex - 1, -1);
                    updateAutocompleteSelection(dropdownElement, state);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (state.selectedIndex >= 0) {
                        selectAutocompleteItem(fieldName, state.results[state.selectedIndex], dropdownElement, state);
                    } else {
                        hideAutocompleteDropdown(dropdownElement, state);
                        moveToNextField(fieldName);
                    }
                } else if (e.key === 'Escape') {
                    hideAutocompleteDropdown(dropdownElement, state);
                }
            } else if (e.key === 'Enter') {
                e.preventDefault();
                moveToNextField(fieldName);
            }
        });

        // Click outside to close
        document.addEventListener('click', function (e) {
            if (!inputElement.contains(e.target) && !dropdownElement.contains(e.target)) {
                hideAutocompleteDropdown(dropdownElement, state);
            }
        });
    }

    function moveToNextField(currentField) {
        // Validation: Name 1 is mandatory
        if (currentField === 'name1') {
            const name1Val = document.getElementById('name1').value.trim();
            if (!name1Val) return;
        }

        const fieldOrder = ['phone', 'village', 'location', 'initial', 'name1', 'occupation', 'initial2', 'name2', 'occupation2', 'description', 'customerNo'];
        const currentIndex = fieldOrder.indexOf(currentField);

        if (currentIndex < fieldOrder.length - 1) {
            const nextField = fieldOrder[currentIndex + 1];
            const nextInput = document.getElementById(nextField);
            if (nextInput) {
                nextInput.focus();
                nextInput.select();
            }
        } else {
            // After description, go to amount
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        }
    }

    async function searchField(fieldName, query, dropdownElement) {
        const state = autocompleteState[fieldName];

        console.log('Search triggered:', fieldName, query);

        try {
            // Build search parameters based on context
            const params = new URLSearchParams();
            params.append('field', fieldName);
            params.append('q', query);

            // Add location context for filtering
            if (fieldName !== 'location' && locationInput && locationInput.value.trim()) {
                params.append('location', locationInput.value.trim());
            }

            // Add previous field context for progressive filtering
            if (fieldName === 'name1' && initialInput && initialInput.value.trim()) {
                params.append('initial', initialInput.value.trim());
            } else if (fieldName === 'name2' && name1Input && name1Input.value.trim()) {
                params.append('initial', initialInput?.value.trim() || '');
                params.append('name1', name1Input.value.trim());
            } else if (fieldName === 'occupation' && name2Input && name2Input.value.trim()) {
                params.append('initial', initialInput?.value.trim() || '');
                params.append('name1', name1Input?.value.trim() || '');
                params.append('name2', name2Input.value.trim());
            }

            // Check online status if ConnectionManager is available and initialized
            // Default to false if not yet initialized (will use local/cache)
            const isOnline = window.ConnectionManager && window.ConnectionManager.isInitialized && window.ConnectionManager.isOnline;
            if (isOnline) {
                params.append('is_online', 'true');
            }

            const response = await fetch(`api/search_collections.php?${params.toString()}`);
            const data = await response.json();

            console.log('Search response:', data);

            if (data.success && data.results.length > 0) {
                state.results = data.results;
                console.log('Showing dropdown with results:', data.results.length);
                showAutocompleteDropdown(dropdownElement, data.results, query, state, fieldName);
            } else {
                console.log('No results, hiding dropdown');
                hideAutocompleteDropdown(dropdownElement, state);
            }
        } catch (error) {
            console.error('Autocomplete search error:', error);
            hideAutocompleteDropdown(dropdownElement, state);
        }
    }

    function showAutocompleteDropdown(dropdown, results, query, state, fieldName) {
        console.log('showAutocompleteDropdown called', dropdown, results.length);
        state.selectedIndex = -1;
        dropdown.innerHTML = '';

        results.forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';

            let displayText = result.display || result[fieldName] || '';

            // Highlight matching text
            const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
            item.innerHTML = displayText.replace(regex, '<span class="autocomplete-highlight">$1</span>');

            item.addEventListener('click', () => {
                selectAutocompleteItem(fieldName, result, dropdown, state);
            });

            dropdown.appendChild(item);
        });

        dropdown.classList.add('show');
    }

    function hideAutocompleteDropdown(dropdown, state) {
        dropdown.classList.remove('show');
        state.results = [];
        state.selectedIndex = -1;
    }

    function updateAutocompleteSelection(dropdown, state) {
        const items = dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            if (index === state.selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    function selectAutocompleteItem(fieldName, result, dropdown, state) {
        // For location, village, and occupation, only fill that field and move to next
        if (fieldName === 'location' || fieldName === 'village' || fieldName === 'occupation') {
            if (fieldName === 'location' && locationInput && result.location) {
                locationInput.value = result.location;
            } else if (fieldName === 'village' && document.getElementById('village') && result.village) {
                document.getElementById('village').value = result.village;
            } else if (fieldName === 'occupation' && occupationInput && result.occupation) {
                occupationInput.value = result.occupation;
            }
            hideAutocompleteDropdown(dropdown, state);
            moveToNextField(fieldName);
        } else {
            // Fill in ALL fields from the matched row for other fields
            if (locationInput && result.location) locationInput.value = result.location;
            if (initialInput && result.initial) initialInput.value = result.initial;
            if (name1Input && result.name1) name1Input.value = result.name1;
            if (name2Input && result.name2) name2Input.value = result.name2;
            if (occupationInput && result.occupation) occupationInput.value = result.occupation;
            if (document.getElementById('village') && result.village) document.getElementById('village').value = result.village;
            if (phoneInput && result.phone) phoneInput.value = result.phone;
            if (customerNoInput && result.customerNumber) customerNoInput.value = result.customerNumber;
            if (document.getElementById('description') && result.description) document.getElementById('description').value = result.description;

            hideAutocompleteDropdown(dropdown, state);

            // Focus on amount field since all fields are filled
            if (amountInput) {
                amountInput.focus();
                amountInput.select();
            }
        }
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    inputs.forEach((input, index) => {
        input.addEventListener('keydown', function (e) {
            // Shift+Enter
            if (e.shiftKey && e.key === 'Enter') {
                e.preventDefault();

                // If in edit mode, trigger update
                if (isEditMode && btnUpdateCollection && btnUpdateCollection.style.display !== 'none') {
                    btnUpdateCollection.click();
                    return;
                }

                // If in amount field, Shift+Enter should do nothing (just prevent default)
                if (input.id === 'amount') {
                    return;
                }

                // For other fields, Shift+Enter jumps to Amount field
                if (amountInput) {
                    amountInput.focus();
                    amountInput.select();
                }
                return;
            }

            if (e.key === 'Enter') {
                // Skip if it's location (handled above)
                if (input.id === 'location') return;

                // Validation: Name 1 is mandatory
                if (input.id === 'name1' && !input.value.trim()) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                // If it's the amount field
                if (input.id === 'amount') {
                    const chkEnableDenom = document.getElementById('chkEnableDenom');
                    if (chkEnableDenom && chkEnableDenom.checked) {
                        // Focus the first denomination input (prioritize 1x if it exists, else 500)
                        let focusInput = document.querySelector('.denom-input:not(.modal-denom)[data-val="1"]');
                        if (!focusInput) {
                            focusInput = document.querySelector('.denom-input:not(.modal-denom)[data-val="500"]');
                        }

                        if (focusInput) {
                            focusInput.focus();
                            focusInput.select();
                        } else {
                            // Fallback if no specific input found
                            const anyDenom = document.querySelector('.denom-input:not(.modal-denom)');
                            if (anyDenom) {
                                anyDenom.focus();
                                anyDenom.select();
                            }
                        }
                    } else {
                        // If denom disabled, ENTER triggers SAVE directly
                        input.blur(); // Remove focus to ensure value is captured
                        if (isEditMode && document.getElementById('btnUpdateCollection') && document.getElementById('btnUpdateCollection').style.display !== 'none') {
                            document.getElementById('btnUpdateCollection').click();
                        } else {
                            btnAddToList.click(); // Triggers saveSingleEntry validation logic
                        }
                    }
                    return;
                } else {
                    // Move to next input
                    const nextInput = inputs[index + 1];
                    if (nextInput) {
                        nextInput.focus();
                    } else {
                        // If no next input (e.g. last one before amount), go to amount
                        if (amountInput) amountInput.focus();
                    }
                }
            }
        });
    });

    // Main Denomination Inputs Logic (Middle Panel)
    const mainDenomInputs = document.querySelectorAll('.denom-input:not(.modal-denom)');
    if (mainDenomInputs.length > 0) {
        mainDenomInputs.forEach((input, index) => {
            // Input Handler: Calculate Totals on the fly
            input.addEventListener('input', function () {
                updateMainDenomTotals();
            });

            // Keydown Handler: Navigation and Save
            input.addEventListener('keydown', function (e) {
                if (e.shiftKey && e.key === 'Enter') {
                    e.preventDefault();
                    // Trigger Save
                    if (isEditMode && document.getElementById('btnUpdateCollection') && document.getElementById('btnUpdateCollection').style.display !== 'none') {
                        document.getElementById('btnUpdateCollection').click();
                    } else {
                        btnAddToList.click();
                    }
                    return;
                }

                if (e.key === 'Enter') {
                    e.preventDefault();
                    // Requirement: Any denomination textbox ENTER key should save the record
                    if (isEditMode && document.getElementById('btnUpdateCollection') && document.getElementById('btnUpdateCollection').style.display !== 'none') {
                        document.getElementById('btnUpdateCollection').click();
                    } else {
                        btnAddToList.click();
                    }
                }
            });
        });
    }

    function updateMainDenomTotals() {
        let total = 0;
        mainDenomInputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const denom = parseInt(input.getAttribute('data-val'));
            total += val * denom;
        });

        // Update Total Display
        const denomTotalEl = document.getElementById('denomTotal');
        if (denomTotalEl) {
            denomTotalEl.textContent = '₹' + total.toFixed(2);
        }

        // Compare with Amount
        let amountVal = 0;
        if (amountInput) amountVal = parseFloat(amountInput.value) || 0;

        const balance = total - amountVal;

        const balanceContainer = document.getElementById('denomBalanceContainer');
        const balanceEl = document.getElementById('denomBalance');

        if (denomTotalEl) {
            // Tolerance for float comparison
            if (Math.abs(balance) < 0.01) {
                // Match
                denomTotalEl.style.color = '#2e7d32'; // Green
                if (balanceContainer) balanceContainer.style.display = 'none';
            } else {
                // Mismatch
                denomTotalEl.style.color = '#d32f2f'; // Red
                if (balanceContainer) {
                    balanceContainer.style.display = 'flex';
                    balanceEl.textContent = '₹' + balance.toFixed(2);
                }
                // Optional: Show +/- hint
            }
        }
    }

    // Save Entry Handler (formerly Add to List)
    if (btnAddToList) {
        btnAddToList.addEventListener('click', async function () {
            // Basic validation
            if (!document.getElementById('collectionDate').value) {
                showMessage('தேதியை தேர்ந்தெடுக்கவும்', 'error');
                return;
            }

            // Validate Name 1
            if (!name1Input.value.trim()) {
                showMessage('பெயர் 1-ஐ உள்ளிடவும்', 'error');
                name1Input.focus();
                return;
            }

            // Validate Amount
            const amountVal = parseFloat(amountInput.value);
            if (!amountVal || amountVal <= 0) {
                showMessage('சரியான தொகையை உள்ளிடவும்', 'error');
                amountInput.focus();
                return;
            }

            // Check if denomination is enabled
            const chkEnableDenom = document.getElementById('chkEnableDenom');
            if (chkEnableDenom && chkEnableDenom.checked) {
                // Validate Denomination Totals (Inline)
                let denomTotal = 0;
                const mainDenomInputs = document.querySelectorAll('.denom-input:not(.modal-denom)');
                mainDenomInputs.forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    const denom = parseInt(input.getAttribute('data-val'));
                    denomTotal += val * denom;
                });

                if (Math.abs(denomTotal - amountVal) > 0.1) {
                    showMessage('தொகை மற்றும் பணத்தாள் விவரம் பொருந்தவில்லை!', 'error');
                    // Highlight errors
                    document.getElementById('denomTotal').style.color = 'red';
                    // Focus first denom input
                    if (mainDenomInputs.length > 0) mainDenomInputs[0].focus();
                    return;
                }

                // If valid, save
                await saveSingleEntry();
            } else {
                // Save directly without denomination
                await saveSingleEntry();
            }
        });
    }

    // Single Entry Save Functions
    function openDenomModalForSingleEntry() {
        const amountVal = parseFloat(amountInput.value);

        const reqTotalEl = document.getElementById('modalRequiredTotal');
        if (reqTotalEl) reqTotalEl.textContent = amountVal.toFixed(2);

        // Auto-calculate denominations initially
        generateDenominationSuggestion(amountVal);

        document.getElementById('denomModal').style.display = 'flex';

        // Focus first input
        const firstInput = document.querySelector('#denomModal .modal-denom');
        if (firstInput) firstInput.focus();
    }

    async function saveSingleEntry() {
        // Get form data
        const formData = new FormData(form);
        const amountVal = parseFloat(amountInput.value);

        // Get relationship priority
        let relationshipPriority = null;
        if (relationship1 && relationship1.checked) {
            relationshipPriority = 1;
        } else if (relationship2 && relationship2.checked) {
            relationshipPriority = 2;
        }
        formData.append('relationship_priority', relationshipPriority);

        // Append missing customerNumber (since field was removed)
        if (!formData.has('customerNumber')) {
            const serialVal = document.getElementById('serialNo') ? document.getElementById('serialNo').value : '';
            formData.append('customerNumber', serialVal);
        }

        // Get denomination values if enabled
        const chkEnableDenom = document.getElementById('chkEnableDenom');
        if (chkEnableDenom && chkEnableDenom.checked) {
            // Use inputs from the main panel, not modal
            document.querySelectorAll('.denom-input:not(.modal-denom)').forEach(input => {
                const val = parseInt(input.dataset.val);
                const count = parseFloat(input.value) || 0;
                if (count > 0) {
                    formData.append('denom' + val, count);
                }
            });
        }

        // Check for duplicates in server collections
        const currentLocation = (formData.get('location') || '').trim().toLowerCase();
        const currentInitial = (formData.get('initial') || '').trim().toLowerCase();
        const currentName1 = (formData.get('name1') || '').trim().toLowerCase();
        const currentName2 = (formData.get('name2') || '').trim().toLowerCase();

        if (currentName1 || currentName2) {
            const duplicateInServer = serverCollections.some(item => {
                const itemLocation = (item.location || '').trim().toLowerCase();
                const itemInitial = (item.initial_name || '').trim().toLowerCase();
                const itemName1 = (item.name1 || '').trim().toLowerCase();
                const itemName2 = (item.name2 || '').trim().toLowerCase();

                return itemLocation === currentLocation &&
                    itemInitial === currentInitial &&
                    itemName1 === currentName1 &&
                    itemName2 === currentName2;
            });

            if (duplicateInServer) {
                if (!confirm('⚠️ இந்த பதிவு ஏற்கனவே உள்ளது! மீண்டும் சேர்க்க விரும்புகிறீர்களா?')) {
                    return;
                }
            }
        }

        // Disable save button to prevent double-click
        btnAddToList.disabled = true;
        showMessage('சேமிக்கிறது...', 'info');

        try {
            const response = await fetch('api/save_collection.php', {
                method: 'POST',
                body: formData
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON Parse Error:', e, text);
                showMessage('சர்வர் பிழை (தவறான JSON).', 'error');
                btnAddToList.disabled = false;
                return;
            }

            if (data.success) {
                const paymentInfo = data.payment_type === 'UPI' ? ' (UPI)' : '';
                showMessage('வெற்றிகரமாக சேமிக்கப்பட்டது' + paymentInfo, 'success');

                // Show invoice/receipt if Print Helper is enabled
                const chkEnablePrintHelper = document.getElementById('chkEnablePrintHelper');
                // Logic: If check box exists and IS CHECKED -> Print using helper
                // If it doesn't exist, assume we want to print? Or default safe.
                // Given the user wants print helper, assume yes if checked.
                if (chkEnablePrintHelper && chkEnablePrintHelper.checked) {
                    const savedEntry = {
                        location: formData.get('location'),
                        initial: formData.get('initial'),
                        name1: formData.get('name1'),
                        name2: formData.get('name2'),
                        initial2: document.getElementById('initial2') ? document.getElementById('initial2').value : '',
                        occupation: formData.get('occupation'),
                        occupation2: document.getElementById('occupation2') ? document.getElementById('occupation2').value : '',
                        villageGoingTo: formData.get('villageGoingTo'),
                        phone: formData.get('phone'),
                        customerNumber: data.customer_number || formData.get('customerNumber') || '',
                        description: formData.get('description'),
                        total_amount: amountVal,
                        relationship: (relationship1 && relationship1.checked) ? "தாய்மாமன்" : ((relationship2 && relationship2.checked) ? "அத்தை - மாமா" : ""),
                        payment_type: data.payment_type || 'CASH'
                    };

                    // Append relationship to Name 1 for printing (to ensure visibility on receipt)
                    if (savedEntry.relationship) {
                        savedEntry.name1 = (savedEntry.name1 || '') + ' (' + savedEntry.relationship + ')';
                    }

                    // Use server-returned bill/computer numbers for printing
                    savedEntry.computerNumberPrint = data.computerNumberPrint || '';
                    savedEntry.billNumberPrint = data.billNumberPrint || '';

                    // Fallback: extract from customer_number if server didn't return them
                    if (!savedEntry.billNumberPrint && savedEntry.customerNumber && savedEntry.customerNumber.includes(' - ')) {
                        const parts = savedEntry.customerNumber.split(' - ');
                        if (parts.length >= 2) {
                            savedEntry.billNumberPrint = parts[1].trim();
                            const compPart = parts[0].trim();
                            if (compPart.includes(' ')) {
                                const compWords = compPart.split(' ');
                                savedEntry.computerNumberPrint = compWords[compWords.length - 1];
                            } else {
                                savedEntry.computerNumberPrint = compPart;
                            }
                        }
                    }

                    showInvoiceAndPrint([savedEntry], amountVal);
                }

                // Reset form and reload collections
                resetForm();
                loadCollections();

                // Auto-focus on location for next entry
                if (locationInput) {
                    locationInput.focus();
                    locationInput.select();
                }
            } else {
                showMessage('சேமிப்பதில் தோல்வி: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error saving entry:', error);
            showMessage('நெட்வொர்க் பிழை: ' + error.message, 'error');
        } finally {
            btnAddToList.disabled = false;
        }
    }

    // Help Button Handler
    const btnHelp = document.getElementById('btnHelp');
    if (btnHelp) {
        btnHelp.addEventListener('click', function () {
            document.getElementById('keyboardHelpModal').style.display = 'flex';
        });
    }

    // Summary Button Handler
    const btnSummary = document.getElementById('btnSummary');
    if (btnSummary) {
        btnSummary.addEventListener('click', function () {
            updateSummaryModal();
            document.getElementById('summaryModal').style.display = 'flex';
        });
    }

    // Function to update summary modal with latest data
    function updateSummaryModal() {
        // Update saved count only (no pending in single entry mode)
        const savedCount = serverCollections.length;
        const pendingCount = 0; // No pending in single entry mode

        if (document.getElementById('summaryPendingCount')) {
            document.getElementById('summaryPendingCount').textContent = pendingCount;
        }
        if (document.getElementById('summarySavedCount')) {
            document.getElementById('summarySavedCount').textContent = savedCount;
        }

        // Update total entries
        const totalEntries = savedCount;
        if (document.getElementById('summaryTotalEntries')) {
            document.getElementById('summaryTotalEntries').textContent = totalEntries;
        }

        // Calculate total amount (saved only)
        const savedTotal = serverCollections.reduce((sum, item) => sum + parseFloat(item.total_amount || 0), 0);

        if (document.getElementById('summaryTotalAmount')) {
            document.getElementById('summaryTotalAmount').textContent = '₹' + savedTotal.toFixed(2);
        }

        // Calculate Denominations (Only for saved collections)
        const denoms = {
            2000: 0, 500: 0, 200: 0, 100: 0, 50: 0, 20: 0, 10: 0, 5: 0, 2: 0, 1: 0
        };

        serverCollections.forEach(item => {
            denoms[2000] += parseInt(item.denom_2000 || 0);
            denoms[500] += parseInt(item.denom_500 || 0);
            denoms[200] += parseInt(item.denom_200 || 0);
            denoms[100] += parseInt(item.denom_100 || 0);
            denoms[50] += parseInt(item.denom_50 || 0);
            denoms[20] += parseInt(item.denom_20 || 0);
            denoms[10] += parseInt(item.denom_10 || 0);
            denoms[5] += parseInt(item.denom_5 || 0);
            denoms[2] += parseInt(item.denom_2 || 0);
            denoms[1] += parseInt(item.denom_1 || 0);
        });

        let denomHtml = '<div class="denom-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">';

        const keys = [2000, 500, 200, 100, 50, 20, 10, 5, 2, 1];
        let totalCash = 0;
        let hasDenoms = false;

        keys.forEach(key => {
            const count = denoms[key];
            if (count > 0) {
                hasDenoms = true;
                const val = count * key;
                totalCash += val;
                denomHtml += `
                    <div class="denom-summary-item" style="background: #f5f5f5; padding: 8px; border-radius: 4px; text-align: center; border: 1px solid #e0e0e0;">
                        <div style="font-weight: bold; color: #1976d2; font-size: 0.9em;">${key === 1 ? 'Coins' : '₹' + key}</div>
                        <div style="font-size: 1.2em; font-weight: bold;">${count}</div>
                        <div style="font-size: 0.8em; color: #666;">= ₹${val}</div>
                    </div>
                `;
            }
        });

        denomHtml += '</div>';

        if (!hasDenoms) {
            denomHtml = '<p style="color: #666; font-style: italic; text-align: center;">பணத்தாள் விவரங்கள் இல்லை</p>';
        } else {
            // Add total check
            denomHtml += `<div style="margin-top: 15px; text-align: right; font-weight: bold; color: #2e7d32; border-top: 1px dashed #ccc; padding-top: 10px;">கணக்கிடப்பட்ட மொத்த ரொக்கம்: ₹${totalCash.toFixed(2)}</div>`;

            // Show warning if total cash doesn't match saved total
            if (Math.abs(totalCash - savedTotal) > 1) {
                denomHtml += `<div style="margin-top: 5px; text-align: right; font-size: 0.85em; color: #d32f2f;">(குறிப்பு: சேமிக்கப்பட்ட மொத்த தொகையுடன் வேறுபடுகிறது - ₹${(savedTotal - totalCash).toFixed(2)})</div>`;
            }
        }

        const denomContainer = document.getElementById('summaryDenoms');
        if (denomContainer) {
            denomContainer.innerHTML = denomHtml;
        }
    }

    // Save All Handler - Removed (single entry mode only)
    // if (btnSaveAll) {
    //     btnSaveAll.addEventListener('click', function() {
    //         if (pendingCollections.length === 0) return;
    //         
    //         const chkEnableDenom = document.getElementById('chkEnableDenom');
    //         if (chkEnableDenom && chkEnableDenom.checked) {
    //             openDenomModal();
    //         } else {
    //             saveDirectly(false);
    //         }
    //     });
    // }

    // openDenomModal - Removed (using openDenomModalForSingleEntry instead)
    // function openDenomModal() {
    //     const totalRequired = pendingCollections.reduce((sum, item) => sum + parseFloat(item.total_amount), 0);
    //     
    //     const reqTotalEl = document.getElementById('modalRequiredTotal');
    //     if (reqTotalEl) reqTotalEl.textContent = totalRequired.toFixed(2);
    //     
    //     // Auto-calculate denominations initially
    //     generateDenominationSuggestion(totalRequired);
    //     
    //     document.getElementById('denomModal').style.display = 'flex';
    //     
    //     // Focus first input
    //     const firstInput = document.querySelector('#denomModal .modal-denom');
    //     if (firstInput) firstInput.focus();
    // }

    const btnConfirmPayment = document.getElementById('btnConfirmPayment');
    if (btnConfirmPayment) {
        btnConfirmPayment.addEventListener('click', async function () {
            if (currentGuestEntryId !== null) {
                confirmGuestEntryPayment();
            } else {
                // For single entry mode, close modal and save
                closeModal('denomModal');
                await saveSingleEntry();
            }
        });
    }

    // Function to generate denomination suggestion
    function generateDenominationSuggestion(totalAmount) {
        const denoms = [
            { value: 500, input: null },
            { value: 200, input: null },
            { value: 100, input: null },
            { value: 50, input: null },
            { value: 20, input: null },
            { value: 10, input: null },
            { value: 1, input: null }
        ];

        // Get input elements
        document.querySelectorAll('.modal-denom').forEach(input => {
            const val = parseInt(input.dataset.val);
            const denom = denoms.find(d => d.value === val);
            if (denom) {
                denom.input = input;
            }
        });

        let remaining = totalAmount;
        const suggestion = {};

        // Greedy algorithm: start from highest denomination
        for (const denom of denoms) {
            if (denom.value > 1) {
                if (remaining >= denom.value) {
                    const count = Math.floor(remaining / denom.value);
                    suggestion[denom.value] = count;
                    remaining -= count * denom.value;
                    // Fix floating point issues
                    remaining = parseFloat(remaining.toFixed(2));
                }
            } else {
                // For coins (value 1), take the rest including decimals
                suggestion[denom.value] = remaining;
                remaining = 0;
            }
        }

        // Fill in the inputs with suggested values
        denoms.forEach(denom => {
            if (denom.input) {
                denom.input.value = suggestion[denom.value] || '';
            }
        });

        // Update the modal total (if elements exist)
        updateModalTotal();
    }

    // Modal Logic
    const modalDenoms = document.querySelectorAll('.modal-denom');
    modalDenoms.forEach(input => {
        input.addEventListener('input', updateModalTotal);
        input.addEventListener('keydown', function (e) {
            if (e.shiftKey && e.key === 'Enter') {
                e.preventDefault();
                const btn = document.getElementById('btnConfirmPayment');
                if (btn && !btn.disabled) {
                    btn.click();
                }
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                // Move to next
                const index = Array.from(modalDenoms).indexOf(this);
                if (modalDenoms[index + 1]) {
                    modalDenoms[index + 1].focus();
                } else {
                    // If last denom input, maybe focus back to location or save?
                    // For now, just blur
                    this.blur();
                }
            }
        });
    });

    function updateModalTotal() {
        let total = 0;
        modalDenoms.forEach(input => {
            const val = parseFloat(input.value) || 0;
            const denom = parseInt(input.dataset.val);
            total += val * denom;
        });

        const requiredEl = document.getElementById('modalRequiredTotal');
        if (!requiredEl) return; // Exit if elements don't exist (modal removed)

        const required = parseFloat(requiredEl.textContent);
        const enteredSpan = document.getElementById('modalEnteredTotal');
        const balanceContainer = document.getElementById('modalBalanceContainer');
        const balanceSpan = document.getElementById('modalBalance');

        enteredSpan.textContent = total.toFixed(2);

        const btn = document.getElementById('btnConfirmPayment');
        const msg = document.getElementById('modalMessage');

        // Enforce exact match: enable save only when entered total === required
        const diff = parseFloat((total - required).toFixed(2));
        if (diff === 0) {
            // Exact match
            balanceContainer.style.display = 'none';
            enteredSpan.className = 'total-match';
            if (btn) btn.disabled = false;
            if (msg) msg.textContent = '';
        } else {
            // Mismatch (either short or excess) - do not allow saving
            if (diff > 0) {
                // Excess
                balanceSpan.textContent = diff.toFixed(2);
                balanceContainer.style.display = 'flex';
                enteredSpan.className = 'total-mismatch';
                if (msg) msg.textContent = 'தொகை பொருந்தவில்லை! (அதிகம்: ₹' + diff.toFixed(2) + ')';
            } else {
                // Shortage
                balanceContainer.style.display = 'none';
                enteredSpan.className = 'total-mismatch';
                if (msg) msg.textContent = 'தொகை பொருந்தவில்லை! (குறைவு: ₹' + Math.abs(diff).toFixed(2) + ')';
            }
            if (btn) btn.disabled = true;
        }
    }

    // Confirm Payment Listener Removed (Modal Removed)


    // Direct Save Function - Removed (using saveSingleEntry instead for single entry mode)
    /*
    let isSaving = false;
    async function saveDirectly(skipAutoCalc = false) {
        if (isSaving) return;
        isSaving = true;

        // Disable buttons
        if (btnSaveAll) btnSaveAll.disabled = true;
        if (btnConfirmPayment) btnConfirmPayment.disabled = true;

        try {
            const totalRequired = pendingCollections.reduce((sum, item) => sum + parseFloat(item.total_amount), 0);
            
            // Update the required total element so validation works correctly
            const requiredEl = document.getElementById('modalRequiredTotal');
            if (requiredEl) {
                requiredEl.textContent = totalRequired.toFixed(2);
            }
            
            // Auto-calculate denominations only if not skipping (e.g. not coming from manual modal edit)
            if (!skipAutoCalc) {
                generateDenominationSuggestion(totalRequired);
            }

            // Validate denomination total vs required amount when denom modal is used
            const msgEl = document.getElementById('modalMessage');
            if (requiredEl) {
                const required = parseFloat(requiredEl.textContent) || 0;
                // Sum modal denominations
                let entered = 0;
                document.querySelectorAll('.modal-denom').forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    const denom = parseInt(input.dataset.val) || 0;
                    entered += val * denom;
                });

                if (parseFloat(entered.toFixed(2)) !== parseFloat(required.toFixed(2))) {
                    if (msgEl) {
                        const diff = entered - required;
                        if (diff > 0) msgEl.textContent = 'தொகை பொருந்தவில்லை! (அதிகம்: ₹' + diff.toFixed(2) + ')';
                        else msgEl.textContent = 'தொகை பொருந்தவில்லை! (குறைவு: ₹' + Math.abs(diff).toFixed(2) + ')';
                    }
                    alert('Denomination total does not match required amount. சரிசெய்து பிறகு மீண்டும் முயற்சிக்கவும்.');
                    return false;
                }
            }
            
            // Get denomination values from the inputs
            const denoms = {};
            document.querySelectorAll('.modal-denom').forEach(input => {
                const val = parseInt(input.dataset.val);
                const count = parseFloat(input.value) || 0;
                if (count > 0) {
                    denoms['denom' + val] = count;
                }
            });
            
            const itemsToPrint = [...pendingCollections];
            let successCount = 0;
            const totalToSave = pendingCollections.length;

            showMessage('சேமிக்கிறது...', 'info');

            for (let i = pendingCollections.length - 1; i >= 0; i--) {
                const entry = pendingCollections[i];
                const formData = new FormData();
                for (const key in entry) {
                    formData.append(key, entry[key]);
                }
                
                // Attach denominations to the first processed entry (which is the last in the list)
                // This ensures we capture the cash breakdown once for the batch
                if (i === pendingCollections.length - 1) {
                    for (const key in denoms) {
                        formData.append(key, denoms[key]);
                    }
                }

                try {
                    const response = await fetch('api/save_collection.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Handle response text/json safely
                    const text = await response.text();
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON Parse Error:', e, text);
                        alert('சர்வர் பிழை (தவறான JSON).');
                        continue;
                    }

                    if (data.success) {
                        successCount++;
                        pendingCollections.splice(i, 1);
                    } else {
                        console.error('Server error:', data.message);
                        alert('சேமிப்பதில் தோல்வி: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error saving entry:', error);
                    alert('நெட்வொர்க் பிழை: ' + error.message);
                }
            }

            if (successCount === totalToSave) {
                showInvoiceAndPrint(itemsToPrint, totalRequired);
                pendingCollections.length = 0;
                renderTable();
                btnSaveAll.style.display = 'none';
                showMessage('வெற்றிகரமாக சேமிக்கப்பட்டது', 'success');
            } else {
                renderTable();
                alert(`${totalToSave} இல் ${successCount} பதிவுகள் சேமிக்கப்பட்டன.`);
            }
            
            if (successCount > 0) {
                loadCollections();
            }
        } finally {
            isSaving = false;
            if (btnSaveAll) btnSaveAll.disabled = false;
            if (btnConfirmPayment) btnConfirmPayment.disabled = false;
        }
    }
    */


    // showInvoice function removed - using Print Helper only

    // Show invoice and prepare for printing (modal opens, user clicks print)
    // Show invoice and prepare for printing
    function showInvoiceAndPrint(savedCollections, total) {
        // Gather Function Metadata
        const funcNameEl = document.querySelector('.function-info h1');
        const functionName = funcNameEl ? funcNameEl.childNodes[0].textContent.trim() : "Moi Function";
        const metaEl = document.querySelector('.function-meta-inline');
        const metaText = metaEl ? metaEl.textContent.trim() : "";

        // Send data directly to C# Print Helper
        if (window.chrome && window.chrome.webview) {
            window.chrome.webview.postMessage({
                action: 'printReceipt',
                data: savedCollections,
                meta: {
                    functionName: functionName,
                    functionMeta: metaText,
                    printDate: new Date().toLocaleString('en-GB')
                }
            });
            console.log('Data sent to C# Print Helper');
        } else {
            console.log('Not in C# WebView - printing skipped');
        }

        const locationField = document.getElementById('location');
        if (locationField) {
            locationField.focus();
        }
    }

    // Global Close Modal
    window.closeModal = function (id) {
        document.getElementById(id).style.display = 'none';
    }

    // Print Receipt function - No longer needed, but keeping stub for compatibility
    window.printReceipt = function () {
        console.log('Browser print disabled - using Print Helper only');
    }

    // Calculate totals
    function calculateTotals() {
        let total = 0;

        denomInputs.forEach(input => {
            const count = parseFloat(input.value) || 0;
            const value = parseInt(input.dataset.value);
            total += count * value;
        });

        // Update row total (same as grand total for single entry)
        document.getElementById('rowTotal').textContent = total;
        document.getElementById('grandTotal').textContent = total.toFixed(2);
        document.getElementById('finalTotal').textContent = total.toFixed(2);
    }

    // Load collections for this function
    function loadCollections() {
        if (!functionId) return;

        // Update UI to show which function we are loading
        const funcIdSpan = document.getElementById('recentFuncId');
        if (funcIdSpan) funcIdSpan.textContent = `(Func #${functionId})`;

        // Add timestamp to prevent caching
        fetch('api/get_collections.php?function_id=' + functionId + '&t=' + new Date().getTime())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ensure we only keep collections for this function (double safety)
                    serverCollections = data.collections.filter(item => item.function_id == functionId);
                    console.log(`Loaded ${serverCollections.length} collections for function ${functionId}`);
                } else {
                    serverCollections = [];
                }
                renderTable();
            })
            .catch(error => {
                console.error('Error loading collections:', error);
                serverCollections = [];
                renderTable();
            });
    }

    // Render table - Simplified for single entry mode (cart removed)
    function renderTable() {
        // No longer needed - cart table is hidden
        // Just refresh recent collections
        renderRecentCollections();
    }

    // attachPendingRowHandlers - Removed (no cart table)
    /*
    function attachPendingRowHandlers() {
        const pendingRows = document.querySelectorAll('.pending-row[data-index]');
        
        pendingRows.forEach(row => {
            // Click to select row
            row.addEventListener('click', function() {
                // Remove selection from other rows
                document.querySelectorAll('.pending-row').forEach(r => {
                    r.style.outline = 'none';
                });
                // Highlight selected row
                this.style.outline = '2px solid #ff9800';
                this.focus();
            });
            
            // Delete key to remove row
            row.addEventListener('keydown', function(e) {
                if (e.key === 'Delete') {
                    e.preventDefault();
                    const index = parseInt(this.dataset.index);
                    
                    if (confirm('இந்த பதிவை பட்டியலிலிருந்து நீக்க விரும்புகிறீர்களா?')) {
                        // Remove from pending collections
                        pendingCollections.splice(index, 1);
                        
                        // Update UI
                        renderTable();
                        
                        // Hide Save button if no more pending collections
                        if (pendingCollections.length === 0) {
                            btnSaveAll.style.display = 'none';
                        }
                        
                        showMessage('பதிவு பட்டியலிலிருந்து நீக்கப்பட்டது.', 'success');
                    }
                }
            });
        });
    }
    */


    // Escape HTML function
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatCollectionDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (Number.isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    function renderRecentCollections() {
        const container = document.getElementById('collectionsContainer');
        if (!container) return;

        // Get filter values
        const searchBill = document.getElementById('searchRecentBill') ? document.getElementById('searchRecentBill').value.trim().toLowerCase() : '';
        const searchName = document.getElementById('searchRecentName') ? document.getElementById('searchRecentName').value.trim().toLowerCase() : '';
        const filterUPI = document.getElementById('filterRecentUPI') ? document.getElementById('filterRecentUPI').checked : false;

        // Filter collections
        let filtered = serverCollections;

        if (filterUPI) {
            filtered = filtered.filter(item => item.payment_type === 'UPI');
        }

        if (searchBill) {
            filtered = filtered.filter(item => (item.customer_number || '').toString().toLowerCase().includes(searchBill));
        }

        if (searchName) {
            filtered = filtered.filter(item => {
                const searchStr = [
                    item.initial_name,
                    item.name1,
                    item.name2,
                    item.location,
                    item.occupation,
                    item.village_going_to
                ].join(' ').toLowerCase();
                return searchStr.includes(searchName);
            });
        }

        if (!filtered.length) {
            container.innerHTML = '<p class="empty-state">பதிவுகள் இல்லை.</p>';
            return;
        }


        // Sort by ID in descending order (newest first)
        filtered.sort((a, b) => (b.id || 0) - (a.id || 0));

        const recentRows = filtered.slice(0, 20).map((item, index) => {
            const nameParts = [item.initial_name, item.name1, item.name2]
                .filter(Boolean)
                .join(' ');
            const amount = parseFloat(item.total_amount || 0).toFixed(2);
            const location = escapeHtml(item.location || '-');

            // Extract serial sequence number from customer_number (e.g., "S 001 - 5" -> "5")
            let serialDisplay = '-';
            const custNum = item.customer_number || '';
            if (custNum && custNum.includes(' - ')) {
                const parts = custNum.split(' - ');
                if (parts.length >= 2) {
                    serialDisplay = parts[parts.length - 1].trim();
                }
            } else if (custNum) {
                serialDisplay = escapeHtml(custNum);
            }

            // Payment type badge
            const paymentBadge = item.payment_type === 'UPI'
                ? '<span style="background:#e3f2fd;color:#1565c0;padding:1px 5px;border-radius:3px;font-size:10px;margin-left:4px;">UPI</span>'
                : '';

            return `
                <tr class="recent-row" data-id="${item.id}" style="cursor: pointer; transition: background-color 0.2s;">
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; text-align: center; font-weight: 500;">${serialDisplay}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; font-weight: 600;">${escapeHtml(nameParts || '-')}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0;">${location}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; text-align: right; font-weight: 600; color: #2e7d32;">₹${amount}${paymentBadge}</td>
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; text-align: center;">
                        <button onclick="event.stopPropagation(); window.reprintItem(${item.id})" style="background:none; border:none; cursor:pointer; font-size: 16px;" title="Re-print">🖨️</button>
                    </td>
                </tr>
            `;
        }).join('');

        container.innerHTML = `
            <table style="width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333; width: 50px;">S.No</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Name</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Place</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600; color: #333; width: 120px;">Amount</th>
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333; width: 40px;">Print</th>
                    </tr>
                </thead>
                <tbody>
                    ${recentRows}
                </tbody>
            </table>
        `;

        // Add click listeners and hover effects
        container.querySelectorAll('.recent-row').forEach(row => {
            row.addEventListener('mouseenter', function () {
                this.style.backgroundColor = '#f5f5f5';
            });
            row.addEventListener('mouseleave', function () {
                this.style.backgroundColor = 'white';
            });
            row.addEventListener('click', function () {
                const id = this.dataset.id;
                openEditModal(id);
            });
        });
    }

    // Search Event Listeners
    const searchBillInput = document.getElementById('searchRecentBill');
    const searchNameInput = document.getElementById('searchRecentName');
    const filterUPIInput = document.getElementById('filterRecentUPI');

    if (searchBillInput) {
        searchBillInput.addEventListener('input', renderRecentCollections);
    }
    if (searchNameInput) {
        searchNameInput.addEventListener('input', renderRecentCollections);
    }
    if (filterUPIInput) {
        filterUPIInput.addEventListener('change', renderRecentCollections);
    }

    // Edit Collection Logic - Use main form instead of modal
    let isEditMode = false;
    let editingCollectionId = null;

    window.openEditModal = async function (id) {
        try {
            const response = await fetch(`api/get_collection_details.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const collection = data.collection;

                // Set edit mode
                isEditMode = true;
                editingCollectionId = collection.id;

                // Populate main form fields
                // Populate main form fields
                if (document.getElementById('location')) document.getElementById('location').value = collection.location || '';
                if (document.getElementById('initial')) document.getElementById('initial').value = collection.initial_name || '';
                if (document.getElementById('name1')) document.getElementById('name1').value = collection.name1 || '';
                if (document.getElementById('name2')) document.getElementById('name2').value = collection.name2 || '';
                if (document.getElementById('occupation')) document.getElementById('occupation').value = collection.occupation || '';
                if (document.getElementById('occupation2')) document.getElementById('occupation2').value = collection.occupation2 || '';
                if (document.getElementById('initial2')) document.getElementById('initial2').value = collection.initial2 || '';
                if (document.getElementById('village')) document.getElementById('village').value = collection.village_going_to || '';
                if (document.getElementById('phone')) document.getElementById('phone').value = collection.phone || '';
                if (document.getElementById('serialNo')) document.getElementById('serialNo').value = collection.customer_number || '';
                if (document.getElementById('description')) document.getElementById('description').value = collection.description || '';
                if (document.getElementById('amount')) document.getElementById('amount').value = collection.total_amount || '';

                // Set relationship checkboxes
                const rel1 = document.getElementById('relationship1');
                const rel2 = document.getElementById('relationship2');
                if (rel1) rel1.checked = collection.relationship_priority == 1;
                if (rel2) rel2.checked = collection.relationship_priority == 2;

                // Hide "Add to List" button, show "Update" and "Cancel Edit" buttons
                document.getElementById('btnAddToList').style.display = 'none';
                document.getElementById('btnUpdateCollection').style.display = 'inline-block';
                document.getElementById('btnCancelEdit').style.display = 'inline-block';

                // Show edit mode indicator
                const editIndicator = document.getElementById('editModeIndicator');
                if (editIndicator) {
                    editIndicator.style.display = 'block';
                }

                // Show info message
                const updatedInfo = collection.updated_at
                    ? `கடைசியாக புதுப்பிக்கப்பட்டது: ${new Date(collection.updated_at).toLocaleString()}`
                    : `உருவாக்கப்பட்டது: ${new Date(collection.created_at).toLocaleString()}`;
                showMessage('திருத்தும் முறை: ' + updatedInfo, 'info');

                // Focus first field
                document.getElementById('location').focus();

                // Populate Denomination Inputs (Main Panel)
                const denomMap = {
                    2000: collection.denom_2000,
                    500: collection.denom_500,
                    200: collection.denom_200,
                    100: collection.denom_100,
                    50: collection.denom_50,
                    20: collection.denom_20,
                    10: collection.denom_10,
                    5: collection.denom_5,
                    2: collection.denom_2,
                    1: collection.denom_1
                };

                // Clear existing
                document.querySelectorAll('.denom-input:not(.modal-denom)').forEach(input => input.value = '');

                // Fill values
                for (const [val, count] of Object.entries(denomMap)) {
                    if (count > 0) {
                        const input = document.querySelector(`.denom-input:not(.modal-denom)[data-val="${val}"]`);
                        if (input) {
                            input.value = count;
                        }
                    }
                }

                // Update totals display
                if (typeof updateMainDenomTotals === 'function') {
                    updateMainDenomTotals();
                } else {
                    // Fallback check if function defined later
                    const evt = new Event('input');
                    const firstInput = document.querySelector('.denom-input:not(.modal-denom)');
                    if (firstInput) firstInput.dispatchEvent(evt);
                }

            } else {
                alert('பதிவு விவரங்களை ஏற்றுவதில் தோல்வி: ' + data.message);
            }
        } catch (error) {
            console.error('Error fetching collection details:', error);
            alert('பதிவு விவரங்களை ஏற்றுவதில் பிழை');
        }
    };

    // Update Collection Handler - Using main form
    const btnUpdateCollection = document.getElementById('btnUpdateCollection');
    if (btnUpdateCollection) {
        btnUpdateCollection.addEventListener('click', async function () {
            if (!isEditMode || !editingCollectionId) {
                alert('திருத்துவதற்கு எந்த பதிவும் தேர்ந்தெடுக்கப்படவில்லை');
                return;
            }

            // Validate Amount
            const amountVal = parseFloat(document.getElementById('amount').value);
            if (!amountVal || amountVal <= 0) {
                alert('சரியான தொகையை உள்ளிடவும்');
                return;
            }

            // Validate Denomination Totals (if enabled)
            const chkEnableDenom = document.getElementById('chkEnableDenom');
            if (chkEnableDenom && chkEnableDenom.checked) {
                let denomTotal = 0;
                document.querySelectorAll('.denom-input:not(.modal-denom)').forEach(input => {
                    const val = parseFloat(input.value) || 0;
                    const denom = parseInt(input.getAttribute('data-val'));
                    denomTotal += val * denom;
                });

                if (Math.abs(denomTotal - amountVal) > 0.1) {
                    alert('தொகை மற்றும் பணத்தாள் விவரம் பொருந்தவில்லை!');
                    return;
                }
            }

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'புதுப்பிக்கிறது...';

            // Prepare form data
            const formData = new FormData();
            formData.append('collection_id', editingCollectionId);
            formData.append('location', document.getElementById('location').value);
            formData.append('initial', document.getElementById('initial').value);
            formData.append('name1', document.getElementById('name1').value);
            formData.append('name2', document.getElementById('name2').value);
            formData.append('occupation', document.getElementById('occupation').value);

            // Append new split fields
            const init2 = document.getElementById('initial2');
            const occ2 = document.getElementById('occupation2');
            if (init2) formData.append('initial2', init2.value);
            if (occ2) formData.append('occupation2', occ2.value);

            formData.append('villageGoingTo', document.getElementById('village').value);
            formData.append('phone', document.getElementById('phone').value);
            if (document.getElementById('customerNo')) {
                formData.append('customerNumber', document.getElementById('customerNo').value);
            } else if (document.getElementById('serialNo')) {
                formData.append('customerNumber', document.getElementById('serialNo').value);
            } else {
                formData.append('customerNumber', '');
            }
            formData.append('description', document.getElementById('description').value);
            formData.append('amount', amountVal);

            // Get relationship priority
            const rel1 = document.getElementById('relationship1');
            const rel2 = document.getElementById('relationship2');
            if (rel1 && rel1.checked) {
                formData.append('relationship_priority', '1');
            } else if (rel2 && rel2.checked) {
                formData.append('relationship_priority', '2');
            }

            // Read Denominations from Inputs (Manual Entry)
            // Only if enabled or just always read them? 
            // Better to read them if they exist.
            if (chkEnableDenom && chkEnableDenom.checked) {
                document.querySelectorAll('.denom-input:not(.modal-denom)').forEach(input => {
                    const val = parseInt(input.dataset.val);
                    const count = parseFloat(input.value) || 0;
                    if (count > 0) {
                        formData.append('denom' + val, count);
                    } else {
                        formData.append('denom' + val, 0);
                    }
                });
            } else {
                // If not enabled, maybe we should not save? 
                // Or if user disabled entries during edit?
                // For now, let's assume we want to save whatever is in the inputs if enabled.
                // If disabled, maybe we should clear them? 
                // Let's stick to reading inputs.
                document.querySelectorAll('.denom-input:not(.modal-denom)').forEach(input => {
                    formData.append('denom' + parseInt(input.dataset.val), 0);
                });
            }

            try {
                const response = await fetch('api/update_collection.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showMessage('பதிவு வெற்றிகரமாக புதுப்பிக்கப்பட்டது!', 'success');

                    // Prepare updated collection for printing
                    const updatedCollection = {
                        location: document.getElementById('location').value,
                        initial: document.getElementById('initial').value,
                        name1: document.getElementById('name1').value,
                        name2: document.getElementById('name2').value,
                        initial2: document.getElementById('initial2') ? document.getElementById('initial2').value : '',
                        occupation: document.getElementById('occupation').value,
                        occupation2: document.getElementById('occupation2') ? document.getElementById('occupation2').value : '',
                        villageGoingTo: document.getElementById('village').value,
                        phone: document.getElementById('phone').value,
                        customerNumber: document.getElementById('serialNo') ? document.getElementById('serialNo').value : '',
                        description: document.getElementById('description').value,
                        total_amount: amountVal
                    };

                    // Extract Computer Number and Sequence from "Comp 001 - X"
                    let compNo = '';
                    let seqNo = '';
                    const fullSerial = updatedCollection.customerNumber || '';
                    if (fullSerial.includes(' - ')) {
                        const parts = fullSerial.split(' - ');
                        if (parts.length >= 2) {
                            seqNo = parts[1];
                            const compPart = parts[0]; // "Comp 001"
                            if (compPart.includes(' ')) {
                                compNo = compPart.split(' ')[1]; // "001"
                            } else {
                                compNo = compPart;
                            }
                        }
                    }
                    updatedCollection.computerNumberPrint = compNo;
                    updatedCollection.billNumberPrint = seqNo;

                    // Show invoice and print
                    const chkEnablePrintHelper = document.getElementById('chkEnablePrintHelper');
                    if (chkEnablePrintHelper && chkEnablePrintHelper.checked) {
                        showInvoiceAndPrint([updatedCollection], amountVal);
                    }

                    cancelEdit(); // Exit edit mode
                    loadCollections(); // Reload list
                } else {
                    alert('புதுப்பிப்பதில் தோல்வி: ' + data.message);
                }
            } catch (error) {
                console.error('Error updating collection:', error);
                alert('பதிவை புதுப்பிப்பதில் பிழை');
            } finally {
                btn.disabled = false;
                btn.textContent = 'புதுப்பி';
            }
        });
    }

    // Cancel Edit Handler
    const btnCancelEdit = document.getElementById('btnCancelEdit');
    if (btnCancelEdit) {
        btnCancelEdit.addEventListener('click', function () {
            cancelEdit();
        });
    }

    // Test Print Handler
    const btnTestPrint = document.getElementById('btnTestPrint');
    if (btnTestPrint) {
        btnTestPrint.addEventListener('click', function () {
            const testEntry = {
                location: "Test Town",
                initial: "T",
                name1: "Example Receipt",
                name2: "With Function Details",
                initial2: "",
                occupation: "Software Demo",
                occupation2: "",
                villageGoingTo: "Demo Village",
                phone: "9999999999",
                customerNumber: "TEST-001",
                description: "This is a test receipt to verify printer settings.",
                total_amount: 1.00,
                computerNumberPrint: "000",
                billNumberPrint: "1",
                relationship: "Friend"
            };

            showInvoiceAndPrint([testEntry], 1.00);
            showMessage('Test print sent!', 'success');
        });
    }

    // Reprint Item Function
    window.reprintItem = async function (id) {
        try {
            const response = await fetch(`api/get_collection_details.php?id=${id}`);
            const data = await response.json();

            if (data.success) {
                const collection = data.collection;

                // Prepare collection for printing
                const printCollection = {
                    location: collection.location || '',
                    initial: collection.initial_name || '',
                    name1: collection.name1 || '',
                    name2: collection.name2 || '',
                    initial2: collection.initial2 || '',
                    occupation: collection.occupation || '',
                    occupation2: collection.occupation2 || '',
                    villageGoingTo: collection.village_going_to || '',
                    phone: collection.phone || '',
                    customerNumber: collection.customer_number || '',
                    description: collection.description || '',
                    total_amount: parseFloat(collection.total_amount || 0),
                    relationship: collection.relationship_priority == 1 ? "தாய்மாமன்" : (collection.relationship_priority == 2 ? "அத்தை - மாமா" : ""),
                    payment_type: collection.payment_type || 'CASH'
                };

                // Extract Computer Number and Sequence
                let compNo = '';
                let seqNo = '';
                const fullSerial = printCollection.customerNumber;
                if (fullSerial && fullSerial.includes(' - ')) {
                    const parts = fullSerial.split(' - ');
                    if (parts.length >= 2) {
                        seqNo = parts[1].trim();
                        const compPart = parts[0].trim();
                        if (compPart.includes(' ')) {
                            compNo = compPart.split(' ')[1];
                        } else {
                            compNo = compPart;
                        }
                    }
                }
                printCollection.computerNumberPrint = compNo;
                printCollection.billNumberPrint = seqNo;

                // Append relationship to Name 1 for printing
                if (printCollection.relationship) {
                    printCollection.name1 = (printCollection.name1 || '') + ' (' + printCollection.relationship + ')';
                }

                showInvoiceAndPrint([printCollection], printCollection.total_amount);
            } else {
                alert('பதிவு விவரங்களை ஏற்றுவதில் தோல்வி: ' + data.message);
            }
        } catch (error) {
            console.error('Error fetching collection details for reprint:', error);
            alert('மறு அச்சுக்கு பதிவு விவரங்களை ஏற்றுவதில் பிழை');
        }
    };

    // Cancel edit function
    function cancelEdit() {
        isEditMode = false;
        editingCollectionId = null;

        // Reset form
        resetForm();

        // Show "Add to List" button, hide "Update" and "Cancel" buttons
        document.getElementById('btnAddToList').style.display = 'inline-block';
        document.getElementById('btnUpdateCollection').style.display = 'none';
        document.getElementById('btnCancelEdit').style.display = 'none';

        // Hide edit mode indicator
        const editIndicator = document.getElementById('editModeIndicator');
        if (editIndicator) {
            editIndicator.style.display = 'none';
        }

        hideMessage();
        document.getElementById('location').focus();
    }

    // Show message function
    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + type + ' show';

        setTimeout(() => {
            hideMessage();
        }, 5000);
    }

    // Hide message function
    function hideMessage() {
        messageDiv.className = 'message';
        messageDiv.textContent = '';
    }

    // Update Serial Number
    function updateSerialNo() {
        if (!functionId) return;

        fetch('api/get_next_serial.php?function_id=' + functionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const serialInput = document.getElementById('serialNo');
                    if (serialInput) {
                        serialInput.value = data.serial;
                    }
                }
            })
            .catch(err => console.error('Error fetching serial:', err));
    }

    // Reset form function
    function resetForm() {
        // Reset date to today
        const todayDate = document.getElementById('collectionDate');
        const today = new Date().toISOString().split('T')[0];

        // Clear all inputs
        if (initialInput) initialInput.value = '';
        if (initial2Input) initial2Input.value = '';
        if (name1Input) name1Input.value = '';
        if (name2Input) name2Input.value = '';
        if (occupationInput) occupationInput.value = '';
        if (document.getElementById('occupation2')) document.getElementById('occupation2').value = '';
        if (document.getElementById('village')) document.getElementById('village').value = '';
        if (phoneInput) phoneInput.value = '';
        if (document.getElementById('customerNo')) document.getElementById('customerNo').value = '';
        if (document.getElementById('description')) document.getElementById('description').value = '';
        if (amountInput) amountInput.value = '';

        // Clear relationship checkboxes
        if (relationship1) relationship1.checked = false;
        if (relationship2) relationship2.checked = false;

        // Clear Denomination Inputs (Main Panel & Modal)
        document.querySelectorAll('.denom-input').forEach(input => {
            input.value = '';
        });

        // Reset Denom Total Display
        const denomTotalEl = document.getElementById('denomTotal');
        if (denomTotalEl) {
            denomTotalEl.textContent = '₹0.00';
            denomTotalEl.style.color = '#1976d2';
        }
        const balanceContainer = document.getElementById('denomBalanceContainer');
        if (balanceContainer) {
            balanceContainer.style.display = 'none';
        }

        if (todayDate) todayDate.value = today;

        // Focus back to location for next entry
        if (locationInput) {
            locationInput.focus();
        }

        // Update serial number for next entry
        updateSerialNo();
    }

    // Make resetForm available globally
    window.resetForm = resetForm;

    // Initial setup handled at top of file


    // Auto-focus on location field when page loads
    setTimeout(() => {
        if (locationInput) {
            locationInput.focus();
        }
    }, 100);

    // Restore focus to location field when window regains focus (Alt+Tab support)
    window.addEventListener('focus', function () {
        // Check if any modal is open
        const isModalOpen = document.querySelector('.modal[style*="display: flex"]') ||
            document.querySelector('.modal[style*="display: block"]');

        if (!isModalOpen && locationInput) {
            // Only focus if no other input is currently focused
            const active = document.activeElement;
            const isInput = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');

            if (!isInput) {
                locationInput.focus();
            }
        }
    });

    // Global keyboard shortcuts
    document.addEventListener('keydown', function (e) {
        // F1 - Show keyboard help
        if (e.key === 'F1') {
            e.preventDefault();
            document.getElementById('keyboardHelpModal').style.display = 'flex';
            return;
        }

        // Ctrl + N - New Entry (Reload Page)
        if (e.ctrlKey && e.key.toLowerCase() === 'n') {
            e.preventDefault();
            window.location.reload();
            return;
        }

        // Ctrl + M - Check Thaimaman
        if (e.ctrlKey && e.key.toLowerCase() === 'm') {
            e.preventDefault();
            const rel1 = document.getElementById('relationship1');
            const rel2 = document.getElementById('relationship2');
            if (rel1) {
                rel1.checked = !rel1.checked;
                // Enforce mutual exclusivity
                if (rel1.checked && rel2) rel2.checked = false;
            }
            return;
        }

        // Alt + I - Show function summary
        if (e.altKey && e.key.toLowerCase() === 'i') {
            e.preventDefault();
            updateSummaryModal();
            document.getElementById('summaryModal').style.display = 'flex';
            return;
        }

        // Alt + L - Focus ஊர் (Location)
        if (e.altKey && e.key.toLowerCase() === 'l') {
            e.preventDefault();
            if (locationInput) {
                locationInput.focus();
                locationInput.select();
            }
            return;
        }

        // Alt + P - Focus போன் (Phone)
        if (e.altKey && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            if (phoneInput) {
                phoneInput.focus();
                phoneInput.select();
            }
            return;
        }

        // Alt + S - Save Entry (single entry mode)
        if (e.altKey && e.key.toLowerCase() === 's') {
            e.preventDefault();

            const denomModal = document.getElementById('denomModal');
            // If modal is open, confirm payment
            if (denomModal && denomModal.style.display === 'flex') {
                document.getElementById('btnConfirmPayment').click();
            }
            // If modal is closed, trigger Update or Save
            else if (isEditMode && document.getElementById('btnUpdateCollection') && document.getElementById('btnUpdateCollection').style.display !== 'none') {
                document.getElementById('btnUpdateCollection').click();
            }
            else if (btnAddToList) {
                btnAddToList.click();
            }
            return;
        }
    });

    // Shift Key Focus Logic - REMOVED per user request
    // User no longer wants Shift key to move focus to denomination fields

    // Arrow key navigation for input fields - Only Up/Down
    const allInputFields = Array.from(document.querySelectorAll('.input-field'));

    allInputFields.forEach((field) => {
        field.addEventListener('keydown', function (e) {
            // Skip if any autocomplete is open
            const anyDropdownOpen = document.querySelector('.autocomplete-dropdown.show');
            if (anyDropdownOpen) {
                return;
            }

            // Only handle Up/Down arrows, not Left/Right so users can edit text
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                const currentIndex = allInputFields.indexOf(field);
                let newIndex = currentIndex;

                if (e.key === 'ArrowUp') {
                    newIndex = Math.max(currentIndex - 1, 0);
                } else if (e.key === 'ArrowDown') {
                    newIndex = Math.min(currentIndex + 1, allInputFields.length - 1);
                }

                e.preventDefault();

                // Focus the new input field
                allInputFields[newIndex].focus();
            }
        });
    });

    // View All Records functionality
    let allRecordsData = [];
    let filteredRecordsData = [];
    let currentPage = 1;
    const recordsPerPage = 16;

    const btnViewAll = document.getElementById('btnViewAll');
    if (btnViewAll) {
        btnViewAll.addEventListener('click', async function () {
            try {
                const response = await fetch('api/get_collections.php?function_id=' + functionId);
                const data = await response.json();

                if (data.success) {
                    allRecordsData = data.collections;
                    filteredRecordsData = [...allRecordsData]; // Initialize filtered data
                    currentPage = 1;

                    // Reset search input
                    const searchInput = document.getElementById('searchAllRecords');
                    if (searchInput) searchInput.value = '';

                    renderAllRecords();
                    document.getElementById('viewAllModal').style.display = 'flex';

                    // Focus search input
                    if (searchInput) setTimeout(() => searchInput.focus(), 100);
                } else {
                    alert('Failed to load records: ' + data.message);
                }
            } catch (error) {
                console.error('Error loading all records:', error);
                alert('Error loading records');
            }
        });
    }

    // Find and Replace Handler
    const btnFindReplace = document.getElementById('btnFindReplace');
    if (btnFindReplace) {
        btnFindReplace.addEventListener('click', async function () {
            const findText = document.getElementById('findText').value.trim();
            const replaceText = document.getElementById('replaceText').value.trim();
            const field = document.getElementById('findReplaceField').value;

            if (!findText) {
                alert('Please enter text to find.');
                document.getElementById('findText').focus();
                return;
            }

            if (!confirm(`Are you sure you want to replace "${findText}" with "${replaceText}" in ${field === 'all' ? 'ALL fields' : field}? This cannot be undone.`)) {
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Replacing...';

            try {
                const formData = new FormData();
                formData.append('function_id', functionId);
                formData.append('find_text', findText);
                formData.append('replace_text', replaceText);
                formData.append('field', field);

                const response = await fetch('api/find_replace_collection.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message);
                    // Reload records
                    btnViewAll.click();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error executing find and replace:', error);
                alert('Network error occurred.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Replace All';
            }
        });
    }

    // Search functionality for View All Records
    const searchAllRecordsInput = document.getElementById('searchAllRecords');
    if (searchAllRecordsInput) {
        searchAllRecordsInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            if (!query) {
                filteredRecordsData = [...allRecordsData];
            } else {
                filteredRecordsData = allRecordsData.filter(item => {
                    const location = (item.location || '').toLowerCase();
                    const name1 = (item.name1 || '').toLowerCase();
                    const name2 = (item.name2 || '').toLowerCase();
                    const initial = (item.initial_name || '').toLowerCase();
                    const village = (item.village_going_to || '').toLowerCase();
                    const phone = (item.phone || '').toLowerCase();
                    const customerNo = (item.customer_number || '').toLowerCase();
                    const description = (item.description || '').toLowerCase();

                    return location.includes(query) ||
                        name1.includes(query) ||
                        name2.includes(query) ||
                        initial.includes(query) ||
                        village.includes(query) ||
                        phone.includes(query) ||
                        customerNo.includes(query) ||
                        description.includes(query);
                });
            }

            currentPage = 1;
            renderAllRecords();
        });
    }

    function renderAllRecords() {
        const container = document.getElementById('allRecordsContainer');
        const recordsInfo = document.getElementById('recordsInfo');
        const btnPrev = document.getElementById('btnPrevPage');
        const btnNext = document.getElementById('btnNextPage');

        if (!filteredRecordsData.length) {
            container.innerHTML = '<p style="text-align: center; padding: 20px; color: #999;">பதிவுகள் எதுவும் இல்லை.</p>';
            recordsInfo.textContent = '';
            btnPrev.style.display = 'none';
            btnNext.style.display = 'none';
            return;
        }

        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = Math.min(startIndex + recordsPerPage, filteredRecordsData.length);
        const pageRecords = filteredRecordsData.slice(startIndex, endIndex);
        const totalPages = Math.ceil(filteredRecordsData.length / recordsPerPage);

        // Update info
        recordsInfo.textContent = `${filteredRecordsData.length} பதிவுகளில் ${startIndex + 1}-${endIndex} காட்டப்படுகின்றன (பக்கம் ${currentPage} / ${totalPages})`;

        // Show/hide pagination buttons
        btnPrev.style.display = currentPage > 1 ? 'inline-block' : 'none';
        btnNext.style.display = currentPage < totalPages ? 'inline-block' : 'none';

        // Build table
        let html = `
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333; width: 80px;">Bill No</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Name</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Place</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600; color: #333; width: 100px;">Amount</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">உறவு</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">வசிக்கும் ஊர்</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">போன்</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">எண்</th>
                        <th style="padding: 10px; text-align: left; border: 1px solid #ddd;">விவரம்</th>
                        <th style="padding: 10px; text-align: right; border: 1px solid #ddd;">தொகை</th>
                        <th style="padding: 10px; text-align: center; border: 1px solid #ddd;">செயல்</th>
                    </tr>
                </thead>
                <tbody>
        `;

        pageRecords.forEach((record, index) => {
            const recordNum = startIndex + index + 1;
            const relationshipText = record.relationship_priority == 1 ? 'தாய்மாமன்' :
                record.relationship_priority == 2 ? 'அத்தை-மாமா' : '-';
            const amount = parseFloat(record.total_amount || 0).toFixed(2);

            html += `
                <tr style="border-bottom: 1px solid #eee;" onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                    <td style="padding: 8px; border: 1px solid #ddd;">${recordNum}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.location || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.initial_name || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.name1 || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.name2 || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.occupation || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${relationshipText}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.village_going_to || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.phone || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.customer_number || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd;">${escapeHtml(record.description || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: right; font-weight: bold;">₹${amount}</td>
                    <td style="padding: 8px; border: 1px solid #ddd; text-align: center;">
                        <button onclick="editFromViewAll(${record.id})" style="background: #ff9800; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px; margin-right: 5px;">திருத்து</button>
                        <button onclick="deleteFromViewAll(${record.id})" style="background: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">நீக்கு</button>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        container.innerHTML = html;
    }

    // Edit from view all
    window.editFromViewAll = function (id) {
        closeModal('viewAllModal');
        openEditModal(id);
    };

    // Delete from view all
    window.deleteFromViewAll = function (id) {
        if (confirm('இந்த பதிவை நிரந்தரமாக நீக்க விரும்புகிறீர்களா?')) {
            const formData = new FormData();
            formData.append('collection_id', id);

            fetch('api/delete_collection.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('பதிவு நீக்கப்பட்டது');
                        // Reload records
                        document.getElementById('btnViewAll').click();
                        // Also reload main collections list
                        loadCollections();
                    } else {
                        alert('நீக்குவதில் தோல்வி: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting record:', error);
                    alert('பிழை ஏற்பட்டது');
                });
        }
    };

    // Pagination handlers
    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');

    if (btnPrevPage) {
        btnPrevPage.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                renderAllRecords();
            }
        });
    }

    if (btnNextPage) {
        btnNextPage.addEventListener('click', function () {
            const totalPages = Math.ceil(filteredRecordsData.length / recordsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderAllRecords();
            }
        });
    }

    // Guest Entries Modal Handler
    const btnGuestEntries = document.getElementById('btnGuestEntries');
    if (btnGuestEntries) {
        btnGuestEntries.addEventListener('click', function () {
            loadGuestEntries();
        });
    }

    // Load guest entries
    function loadGuestEntries() {
        const modal = document.getElementById('guestEntriesModal');
        const container = document.getElementById('guestEntriesContainer');
        const infoDiv = document.getElementById('guestEntriesInfo');

        modal.style.display = 'flex';
        container.innerHTML = '<p style="text-align: center; padding: 20px;">பதிவுகளை ஏற்றுகிறது...</p>';

        fetch(`api/get_guest_entries.php?function_id=${functionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.entries && data.entries.length > 0) {
                    renderGuestEntries(data.entries);
                    infoDiv.textContent = `மொத்தம் ${data.count} நிலுவையில் உள்ள பதிவுகள்`;
                } else {
                    container.innerHTML = '<p style="text-align: center; padding: 40px; color: #666;">😊 நிலுவையில் உள்ள விருந்தினர் பதிவுகள் இல்லை!</p>';
                    infoDiv.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = '<p style="text-align: center; padding: 20px; color: red;">பிழை ஏற்பட்டது.</p>';
            });
    }

    // Render guest entries
    function renderGuestEntries(entries) {
        const container = document.getElementById('guestEntriesContainer');

        let html = `
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5;">
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333; width: 80px;">Bill No</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Name</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Place</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600; color: #333; width: 100px;">Amount</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">செயல்</th>
                    </tr>
                </thead>
                <tbody>
        `;

        entries.forEach((entry, index) => {
            const fullName = [entry.initial_name, entry.name1, entry.name2].filter(n => n).join(' ');
            const time = new Date(entry.created_at).toLocaleString('en-IN', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            html += `
                <tr style="background: ${index % 2 === 0 ? 'white' : '#f9f9f9'};">
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; text-align: center; font-weight: 500;">${billNo}</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">${escapeHtml(entry.location || '-')}</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">${escapeHtml(fullName)}</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">${escapeHtml(entry.occupation || '-')}</td>
                    <td style="padding: 10px; border: 1px solid #ddd;">${escapeHtml(entry.phone || '-')}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: bold; color: #2e7d32;">₹${parseFloat(entry.total_amount).toFixed(2)}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; font-size: 12px; color: #666;">${time}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                        <button onclick="approveGuestEntry(${entry.id}, ${entry.total_amount})" 
                                style="background: #4caf50; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                            💰 பணம் பெறப்பட்டது
                        </button>
                    </td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        container.innerHTML = html;
    }

    // Approve guest entry
    window.approveGuestEntry = function (entryId, amount) {
        if (confirm('இந்த விருந்தினர் பதிவை உறுதிப்படுத்த விரும்புகிறீர்களா? (தொகை: ₹' + amount + ')')) {
            const formData = new FormData();
            formData.append('collection_id', entryId);

            fetch('api/approve_guest_entry.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('வெற்றிகரமாக சேமிக்கப்பட்டது!');
                        if (typeof loadGuestEntries === 'function') {
                            loadGuestEntries();
                        }
                        loadCollections();
                    } else {
                        alert('பிழை: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('பிழை ஏற்பட்டது');
                });
        }
    };



    function confirmGuestEntryPayment() {
        const modalMessage = document.getElementById('modalMessage');
        const enteredTotal = parseFloat(document.getElementById('modalEnteredTotal').textContent);
        const requiredTotal = parseFloat(document.getElementById('modalRequiredTotal').textContent);

        if (Math.abs(enteredTotal - requiredTotal) > 0.01) {
            modalMessage.textContent = 'தொகை பொருந்தவில்லை!';
            return;
        }

        // Get denomination values
        const formData = new FormData();
        formData.append('collection_id', currentGuestEntryId);

        document.querySelectorAll('.modal-denom').forEach(input => {
            const val = parseInt(input.dataset.val);
            const count = parseFloat(input.value) || 0;

            if (val === 2000) formData.append('denom2000', count);
            else if (val === 500) formData.append('denom500', count);
            else if (val === 200) formData.append('denom200', count);
            else if (val === 100) formData.append('denom100', count);
            else if (val === 50) formData.append('denom50', count);
            else if (val === 20) formData.append('denom20', count);
            else if (val === 10) formData.append('denom10', count);
            else if (val === 5) formData.append('denom5', count);
            else if (val === 2) formData.append('denom2', count);
            else if (val === 1) formData.append('denom1', count);
        });

        // Disable button
        const btn = document.getElementById('btnConfirmPayment');
        btn.disabled = true;
        btn.textContent = 'சேமிக்கிறது...';

        fetch('api/approve_guest_entry.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalMessage.textContent = data.message;
                    modalMessage.style.color = 'green';

                    setTimeout(() => {
                        closeModal('denomModal');
                        currentGuestEntryId = null;
                        loadGuestEntries(); // Reload guest entries
                        loadCollections(); // Reload main collections

                        // Reset modal title and z-index
                        const modal = document.getElementById('denomModal');
                        const modalHeader = modal.querySelector('.modal-header h2');
                        modalHeader.textContent = 'கட்டண பதிவு';
                        modal.style.zIndex = '9999';
                    }, 1500);
                } else {
                    modalMessage.textContent = data.message;
                    modalMessage.style.color = 'red';
                    btn.disabled = false;
                    btn.textContent = 'உறுதி செய்து சேமி';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                modalMessage.textContent = 'பிழை ஏற்பட்டது.';
                modalMessage.style.color = 'red';
                btn.disabled = false;
                btn.textContent = 'உறுதி செய்து சேமி';
            });
    }

    // Deleted History Handler
    const btnDeletedHistory = document.getElementById('btnDeletedHistory');
    if (btnDeletedHistory) {
        btnDeletedHistory.addEventListener('click', function () {
            loadDeletedHistory();
        });
    }

    function loadDeletedHistory() {
        const modal = document.getElementById('deletedHistoryModal');
        const container = document.getElementById('deletedHistoryContainer');

        modal.style.display = 'flex';
        container.innerHTML = '<p style="text-align: center; padding: 20px;">ஏற்றுகிறது...</p>';

        fetch(`api/get_deleted_collections.php?function_id=${functionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderDeletedHistory(data.data);
                } else {
                    container.innerHTML = `<p style="text-align: center; padding: 20px; color: red;">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = '<p style="text-align: center; padding: 20px; color: red;">பிழை ஏற்பட்டது.</p>';
            });
    }

    function renderDeletedHistory(records) {
        const container = document.getElementById('deletedHistoryContainer');

        if (!records || records.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 40px; color: #666;">🗑️ நீக்கப்பட்ட பதிவுகள் எதுவும் இல்லை.</p>';
            return;
        }

        let html = `
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #ffebee; border-bottom: 2px solid #ef9a9a;">
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333; width: 80px;">Bill No</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Date</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Place</th>
                        <th style="padding: 10px; text-align: left; font-weight: 600; color: #333;">Name</th>
                        <th style="padding: 10px; text-align: right; font-weight: 600; color: #333; width: 100px;">Amount</th>
                        <th style="padding: 10px; text-align: center; font-weight: 600; color: #333;">Action</th>
                </thead>
                <tbody>
        `;

        records.forEach((record, index) => {
            const fullName = [record.initial_name, record.name1, record.name2].filter(Boolean).join(' ');
            const deletedDate = new Date(record.deleted_at).toLocaleString('en-IN');
            const amount = parseFloat(record.total_amount || 0).toFixed(2);
            const billNo = escapeHtml(record.customer_number || '-');

            html += `
                <tr style="border-bottom: 1px solid #ffcdd2; background: #fff;">
                    <td style="padding: 8px; border-bottom: 1px solid #e0e0e0; text-align: center; font-weight: 500;">${billNo}</td>
                    <td style="padding: 8px; border: 1px solid #ffcdd2;">${deletedDate}</td>
                    <td style="padding: 8px; border: 1px solid #ffcdd2;">${escapeHtml(record.location || '-')}</td>
                    <td style="padding: 8px; border: 1px solid #ffcdd2;">${escapeHtml(fullName)}</td>
                    <td style="padding: 8px; border: 1px solid #ffcdd2; font-weight: bold; text-align: right;">₹${amount}</td>
                    <td style="padding: 8px; border: 1px solid #ffcdd2; text-align: center;">
                        <button onclick="permanentDelete(${record.history_id})" style="background: #d32f2f; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; font-size: 12px;">🗑️ Permanently Delete</button>
                    </td>
                </tr>
            `;
        });

        html += `</tbody></table>`;
        container.innerHTML = html;
    }

    window.permanentDelete = function (historyId) {
        if (confirm('இந்த பதிவை நிரந்தரமாக நீக்க விரும்புகிறீர்களா? இதை திரும்பப் பெற முடியாது!')) {
            fetch('api/permanent_delete_collection.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `history_id=${historyId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('பதிவு நிரந்தரமாக நீக்கப்பட்டது.');
                        loadDeletedHistory();
                    } else {
                        alert('நீக்குவதில் தோல்வி: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('பிழை ஏற்பட்டது');
                });
        }
    };
});




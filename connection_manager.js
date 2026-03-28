/**
 * Connection Manager & Auto-Sync
 * Handles online/offline detection and automatic synchronization
 * Supports manual mode toggle for slow internet situations
 */

const ConnectionManager = {
    isOnline: false,
    isManualMode: false,
    manualOnline: false,
    checkInterval: null,
    syncInterval: null,
    lastSyncTime: 0,
    minSyncInterval: 60000,
    isInitialized: false,
    retryCount: 0,
    maxRetries: 3,

    init: function () {
        console.log('[ConnectionManager] Initializing...');

        // Load saved manual mode preference
        this.loadPreferences();

        // Listen for browser online/offline events
        window.addEventListener('online', () => {
            if (!this.isManualMode) {
                console.log('[ConnectionManager] Browser reports ONLINE');
                this.retryCount = 0; // Reset retry count
                this.checkConnection();
            }
        });

        window.addEventListener('offline', () => {
            if (!this.isManualMode) {
                console.log('[ConnectionManager] Browser reports OFFLINE');
                this.handleConnectionChange(false);
            }
        });

        // Periodic connection check (every 30 seconds) - only if not in manual mode
        this.checkInterval = setInterval(() => {
            if (!this.isManualMode) {
                this.checkConnection();
            }
        }, 30000);

        // Auto-sync every 2 minutes if online
        this.syncInterval = setInterval(() => {
            if (this.isOnline && !this.isManualMode) {
                const now = Date.now();
                if (now - this.lastSyncTime >= this.minSyncInterval) {
                    this.syncData(true);
                }
            }
        }, 120000);

        // Make status indicator clickable
        this.setupClickHandler();

        // Initial check or apply manual mode
        if (this.isManualMode) {
            this.isOnline = this.manualOnline;
            this.updateUI();
            this.isInitialized = true;
        } else {
            this.checkConnection().then(() => {
                this.isInitialized = true;
            });
        }
    },

    loadPreferences: function () {
        try {
            const saved = localStorage.getItem('connectionMode');
            if (saved) {
                const prefs = JSON.parse(saved);
                this.isManualMode = prefs.isManualMode || false;
                this.manualOnline = prefs.manualOnline || false;
                console.log('[ConnectionManager] Loaded preferences:', prefs);
            }
        } catch (e) {
            console.warn('[ConnectionManager] Could not load preferences:', e);
        }
    },

    savePreferences: function () {
        try {
            localStorage.setItem('connectionMode', JSON.stringify({
                isManualMode: this.isManualMode,
                manualOnline: this.manualOnline
            }));
            console.log('[ConnectionManager] Saved preferences');
        } catch (e) {
            console.warn('[ConnectionManager] Could not save preferences:', e);
        }
    },

    setupClickHandler: function () {
        const indicator = document.getElementById('connectionStatus');
        if (!indicator) {
            console.warn('[ConnectionManager] Status indicator not found');
            return;
        }

        indicator.style.cursor = 'pointer';
        indicator.addEventListener('click', (e) => {
            e.stopPropagation();
            this.showModeSelector();
        });
    },

    showModeSelector: function () {
        // Remove existing modal if any
        const existing = document.getElementById('connectionModeModal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'connectionModeModal';
        modal.className = 'modal-overlay';
        modal.style.cssText = 'display: flex; z-index: 10000;';

        const currentModeText = this.isManualMode
            ? (this.manualOnline ? 'Manual: ONLINE' : 'Manual: OFFLINE')
            : 'Automatic';

        const autoSelected = !this.isManualMode;
        const onlineSelected = this.isManualMode && this.manualOnline;
        const offlineSelected = this.isManualMode && !this.manualOnline;

        modal.innerHTML = `
            <div class="modal-box" style="max-width: 450px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1976d2, #42a5f5); color: white;">
                    <h2 style="margin: 0; font-size: 18px;">🌐 Connection Mode</h2>
                    <span class="modal-close" style="color: white; cursor: pointer; font-size: 24px; line-height: 1;">&times;</span>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                        Current: <strong style="color: #1976d2;">${currentModeText}</strong>
                    </p>
                    <p style="margin: 0 0 20px 0; font-size: 12px; color: #888; line-height: 1.4;">
                        💡 If internet is slow, use "Force Offline" for faster response with local cached data.
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button class="mode-btn mode-auto" data-mode="auto" style="padding: 14px 20px; border: 2px solid ${autoSelected ? '#4caf50' : '#ccc'}; background: ${autoSelected ? '#4caf50' : '#fff'}; color: ${autoSelected ? '#fff' : '#666'}; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.2s; text-align: left; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">🔄</span>
                            <div style="flex: 1;">
                                <div>Automatic (Recommended)</div>
                                <div style="font-size: 11px; font-weight: normal; opacity: 0.8;">System detects connectivity</div>
                            </div>
                            ${autoSelected ? '<span style="font-size: 18px;">✓</span>' : ''}
                        </button>
                        <button class="mode-btn mode-online" data-mode="online" style="padding: 14px 20px; border: 2px solid ${onlineSelected ? '#2196f3' : '#ccc'}; background: ${onlineSelected ? '#2196f3' : '#fff'}; color: ${onlineSelected ? '#fff' : '#666'}; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.2s; text-align: left; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">☁️</span>
                            <div style="flex: 1;">
                                <div>Force Online</div>
                                <div style="font-size: 11px; font-weight: normal; opacity: 0.8;">Always use cloud database</div>
                            </div>
                            ${onlineSelected ? '<span style="font-size: 18px;">✓</span>' : ''}
                        </button>
                        <button class="mode-btn mode-offline" data-mode="offline" style="padding: 14px 20px; border: 2px solid ${offlineSelected ? '#ff9800' : '#ccc'}; background: ${offlineSelected ? '#ff9800' : '#fff'}; color: ${offlineSelected ? '#fff' : '#666'}; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; transition: all 0.2s; text-align: left; display: flex; align-items: center; gap: 10px;">
                            <span style="font-size: 20px;">💾</span>
                            <div style="flex: 1;">
                                <div>Force Offline (Faster)</div>
                                <div style="font-size: 11px; font-weight: normal; opacity: 0.8;">Use local data only</div>
                            </div>
                            ${offlineSelected ? '<span style="font-size: 18px;">✓</span>' : ''}
                        </button>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #eee; padding: 15px; text-align: right;">
                    <button class="btn-close-modal" style="padding: 8px 20px; background: #9e9e9e; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">Close</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Add hover effects
        const buttons = modal.querySelectorAll('.mode-btn');
        buttons.forEach(btn => {
            btn.addEventListener('mouseenter', function () {
                if (!this.style.background.includes('rgb')) {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                }
            });
            btn.addEventListener('mouseleave', function () {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });

        // Add event listeners for mode buttons
        modal.querySelectorAll('[data-mode]').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.getAttribute('data-mode');
                this.setMode(mode);
                this.showToast(`Switched to ${mode.toUpperCase()} mode`);
                modal.remove();
            });
        });

        // Close button
        modal.querySelector('.btn-close-modal').addEventListener('click', () => {
            modal.remove();
        });

        // Close on X button
        modal.querySelector('.modal-close').addEventListener('click', () => {
            modal.remove();
        });

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });

        // ESC key to close
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    },

    setMode: function (mode) {
        const previousMode = this.isManualMode ? (this.manualOnline ? 'online' : 'offline') : 'auto';

        switch (mode) {
            case 'auto':
                this.isManualMode = false;
                console.log('[ConnectionManager] Switched to AUTOMATIC mode');
                this.retryCount = 0;
                this.checkConnection();
                break;
            case 'online':
                this.isManualMode = true;
                this.manualOnline = true;
                this.isOnline = true;
                console.log('[ConnectionManager] Switched to FORCE ONLINE mode');
                this.updateUI();
                break;
            case 'offline':
                this.isManualMode = true;
                this.manualOnline = false;
                this.isOnline = false;
                console.log('[ConnectionManager] Switched to FORCE OFFLINE mode');
                this.updateUI();
                break;
        }

        if (previousMode !== mode) {
            this.savePreferences();
        }
    },

    showToast: function (message, duration = 2000) {
        // Remove existing toast
        const existing = document.getElementById('connectionToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'connectionToast';
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
            font-size: 14px;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        `;
        toast.textContent = message;

        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    handleConnectionChange: function (online) {
        if (this.isManualMode) {
            return;
        }

        if (this.isOnline !== online) {
            this.isOnline = online;
            this.updateUI();

            if (online) {
                console.log('[ConnectionManager] Now ONLINE. Auto-syncing...');
                this.retryCount = 0;
                this.syncData(true);
            } else {
                console.log('[ConnectionManager] Now OFFLINE. Using local data.');
            }
        }
    },

    checkConnection: async function () {
        if (this.isManualMode) return;

        try {
            const timestamp = Date.now();
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            const response = await fetch(`api/test_connection.php?t=${timestamp}`, {
                method: 'GET',
                cache: 'no-cache',
                signal: controller.signal,
                headers: { 'Cache-Control': 'no-cache' }
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();

            if (data.success && data.remote_connected) {
                this.handleConnectionChange(true);
                this.retryCount = 0;
            } else {
                this.handleConnectionChange(false);
            }
        } catch (e) {
            console.log('[ConnectionManager] Connection check failed:', e.message);

            // Implement retry logic with exponential backoff
            if (this.retryCount < this.maxRetries) {
                this.retryCount++;
                const retryDelay = Math.min(1000 * Math.pow(2, this.retryCount), 10000);
                console.log(`[ConnectionManager] Retrying in ${retryDelay}ms (attempt ${this.retryCount}/${this.maxRetries})`);

                setTimeout(() => {
                    if (!this.isManualMode) {
                        this.checkConnection();
                    }
                }, retryDelay);
            } else {
                this.handleConnectionChange(false);
            }
        }
    },

    updateUI: function () {
        const indicator = document.getElementById('connectionStatus');
        if (!indicator) return;

        const icon = indicator.querySelector('.status-icon');
        const text = indicator.querySelector('.status-text');

        if (!icon || !text) return;

        const modePrefix = this.isManualMode ? '🔒 ' : '';

        if (this.isOnline) {
            indicator.className = 'status-indicator status-online';
            indicator.title = this.isManualMode
                ? 'Manual Mode: ONLINE (Click to change)'
                : 'Connected to cloud server (Click to change)';
            icon.textContent = '●';
            text.textContent = modePrefix + 'ONLINE';
        } else {
            indicator.className = 'status-indicator status-offline';
            indicator.title = this.isManualMode
                ? 'Manual Mode: OFFLINE (Click to change)'
                : 'Using local data only (Click to change)';
            icon.textContent = '●';
            text.textContent = modePrefix + 'OFFLINE';
        }
    },

    setSyncing: function (isSyncing) {
        if (this.isManualMode && !this.manualOnline) return;

        const indicator = document.getElementById('connectionStatus');
        if (!indicator) return;

        const icon = indicator.querySelector('.status-icon');
        const text = indicator.querySelector('.status-text');

        if (!icon || !text) return;

        if (isSyncing) {
            indicator.className = 'status-indicator status-syncing';
            indicator.title = 'Synchronizing data with cloud...';
            icon.textContent = '↻';
            text.textContent = 'SYNCING...';
        } else {
            this.updateUI();
        }
    },

    syncData: function (silent = false) {
        if (!this.isOnline) {
            console.log('[ConnectionManager] Cannot sync - offline');
            return;
        }

        this.setSyncing(true);
        console.log('[ConnectionManager] Starting data sync...');
        this.lastSyncTime = Date.now();

        fetch('api/sync_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Sync HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('[ConnectionManager] Sync completed:', data);
                if (!data.success) {
                    console.warn('[ConnectionManager] Sync reported failure:', data.message);
                    if (!silent) {
                        this.showToast('Sync failed: ' + data.message, 3000);
                    }
                }
            })
            .catch(error => {
                console.error('[ConnectionManager] Sync error:', error);
                if (!silent) {
                    this.showToast('Sync error: ' + error.message, 3000);
                }
            })
            .finally(() => {
                this.setSyncing(false);
            });
    },

    // Public API for other scripts
    waitForInit: function (callback) {
        if (this.isInitialized) {
            callback();
        } else {
            const checkInit = setInterval(() => {
                if (this.isInitialized) {
                    clearInterval(checkInit);
                    callback();
                }
            }, 100);

            // Timeout after 5 seconds
            setTimeout(() => {
                clearInterval(checkInit);
                console.warn('[ConnectionManager] Init timeout, proceeding anyway');
                callback();
            }, 5000);
        }
    },

    // Manual triggers
    forceSync: function () {
        this.syncData(false);
    },

    forceCheck: function () {
        this.isManualMode = false;
        this.retryCount = 0;
        this.checkConnection();
    }
};

// Expose globally
window.ConnectionManager = ConnectionManager;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
        ConnectionManager.init();
    });
} else {
    ConnectionManager.init();
}

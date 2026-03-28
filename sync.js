document.addEventListener('DOMContentLoaded', function() {
    const syncBtn = document.getElementById('syncDataBtn');
    
    if (syncBtn) {
        syncBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Change button state
            const originalText = syncBtn.innerHTML;
            syncBtn.innerHTML = '🔄 Syncing...';
            syncBtn.disabled = true;
            syncBtn.style.opacity = '0.7';
            
            fetch('api/sync_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let statsMsg = '';
                        if (data.stats) {
                            statsMsg = `\nUsers: ${data.stats.users}\nFunctions: ${data.stats.functions}\nCollections: ${data.stats.collections}\nExpenses: ${data.stats.expenses}\nDeleted: ${data.stats.deleted || 0}`;
                        }
                        alert(data.message + statsMsg);
                    } else {
                        alert('Sync Failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Sync Error: Could not connect to server or API error.');
                })
                .finally(() => {
                    // Restore button state
                    syncBtn.innerHTML = originalText;
                    syncBtn.disabled = false;
                    syncBtn.style.opacity = '1';
                });
        });
    }
});

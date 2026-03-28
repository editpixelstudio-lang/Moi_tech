<?php
/**
 * Undo Sync Tool
 * Allows resetting sync status or deleting synced data from remote server.
 */

require_once '../includes/session.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please login first.");
}

$message = "";
$localConn = getDBConnection();
$remoteConn = getRemoteDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reset_local') {
        // Reset local sync status only
        $tables = ['expenses', 'collections', 'functions', 'users'];
        foreach ($tables as $table) {
            $localConn->query("UPDATE `$table` SET is_synced = 0");
        }
        $message = "Local sync status reset successfully. All records marked as 'Not Synced'.";
    } 
    elseif ($action === 'delete_remote') {
        if (!$remoteConn) {
            $message = "Error: Could not connect to remote server.";
        } else {
            // Delete from remote and reset local
            // Order: Expenses -> Collections -> Functions -> Users
            
            $stats = ['expenses' => 0, 'collections' => 0, 'functions' => 0, 'users' => 0];
            
            // 1. Expenses
            $res = $localConn->query("SELECT id, remote_id FROM expenses WHERE remote_id IS NOT NULL");
            while ($row = $res->fetch_assoc()) {
                if ($remoteConn->query("DELETE FROM expenses WHERE id = " . $row['remote_id'])) {
                    $localConn->query("UPDATE expenses SET remote_id = NULL, is_synced = 0 WHERE id = " . $row['id']);
                    $stats['expenses']++;
                }
            }

            // 2. Collections
            $res = $localConn->query("SELECT id, remote_id FROM collections WHERE remote_id IS NOT NULL");
            while ($row = $res->fetch_assoc()) {
                if ($remoteConn->query("DELETE FROM collections WHERE id = " . $row['remote_id'])) {
                    $localConn->query("UPDATE collections SET remote_id = NULL, is_synced = 0 WHERE id = " . $row['id']);
                    $stats['collections']++;
                }
            }

            // 3. Functions
            $res = $localConn->query("SELECT id, remote_id FROM functions WHERE remote_id IS NOT NULL");
            while ($row = $res->fetch_assoc()) {
                if ($remoteConn->query("DELETE FROM functions WHERE id = " . $row['remote_id'])) {
                    $localConn->query("UPDATE functions SET remote_id = NULL, is_synced = 0 WHERE id = " . $row['id']);
                    $stats['functions']++;
                }
            }

            // 4. Users
            $res = $localConn->query("SELECT id, remote_id FROM users WHERE remote_id IS NOT NULL");
            while ($row = $res->fetch_assoc()) {
                if ($remoteConn->query("DELETE FROM users WHERE id = " . $row['remote_id'])) {
                    $localConn->query("UPDATE users SET remote_id = NULL, is_synced = 0 WHERE id = " . $row['id']);
                    $stats['users']++;
                }
            }

            $message = "Remote data deleted successfully.<br>";
            $message .= "Deleted: Users: {$stats['users']}, Functions: {$stats['functions']}, Collections: {$stats['collections']}, Expenses: {$stats['expenses']}";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undo Sync</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .card { border: 1px solid #ccc; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .btn { padding: 10px 20px; cursor: pointer; color: white; border: none; border-radius: 4px; font-size: 16px; }
        .btn-warning { background-color: #ff9800; }
        .btn-danger { background-color: #f44336; }
        .message { padding: 15px; background-color: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 4px; margin-bottom: 20px; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>Undo / Reset Sync</h1>
    
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Option 1: Reset Local Sync Status</h2>
        <p>This will mark all local records as "Not Synced".</p>
        <p><strong>Use this if:</strong> You want to force the system to re-check and update data on the server during the next sync.</p>
        <form method="POST" onsubmit="return confirm('Are you sure you want to reset local sync status?');">
            <input type="hidden" name="action" value="reset_local">
            <button type="submit" class="btn btn-warning">Reset Local Status</button>
        </form>
    </div>

    <div class="card">
        <h2>Option 2: Delete Remote Data (Full Undo)</h2>
        <p>This will <strong>DELETE</strong> all data from the remote server that is linked to this local system.</p>
        <p>It will also reset the local status to "Not Synced".</p>
        <p><strong>Use this if:</strong> You synced by mistake and want to remove the data from the server completely.</p>
        <p style="color: red;"><strong>Warning:</strong> This action cannot be undone on the server side.</p>
        
        <?php if ($remoteConn): ?>
            <form method="POST" onsubmit="return confirm('WARNING: This will DELETE data from the remote server. Are you sure?');">
                <input type="hidden" name="action" value="delete_remote">
                <button type="submit" class="btn btn-danger">Delete Remote Data & Reset</button>
            </form>
        <?php else: ?>
            <p style="color: red;">Cannot connect to remote server.</p>
        <?php endif; ?>
    </div>
    
    <a href="../index.php">Back to Dashboard</a>
</body>
</html>

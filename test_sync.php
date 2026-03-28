<?php
/**
 * Sync Test & Diagnostic Tool
 * Tests offline/online synchronization functionality
 */

require_once '../includes/session.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please login first.");
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Diagnostic Tool</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            padding: 20px; 
            max-width: 1200px; 
            margin: 0 auto;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .card { 
            background: white;
            border: 1px solid #e0e0e0; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { 
            padding: 12px; 
            background-color: #d4edda; 
            border: 1px solid #c3e6cb; 
            border-radius: 4px; 
            margin-bottom: 10px;
            color: #155724;
        }
        .error { 
            padding: 12px; 
            background-color: #f8d7da; 
            border: 1px solid #f5c6cb; 
            border-radius: 4px; 
            margin-bottom: 10px;
            color: #721c24;
        }
        .warning { 
            padding: 12px; 
            background-color: #fff3cd; 
            border: 1px solid #ffeeba; 
            border-radius: 4px; 
            margin-bottom: 10px;
            color: #856404;
        }
        .info {
            padding: 12px;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            margin-bottom: 10px;
            color: #0c5460;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px;
            background: white;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border: 1px solid #e0e0e0; 
        }
        th { 
            background-color: #667eea; 
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .btn { 
            padding: 10px 20px; 
            cursor: pointer; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-success { background-color: #28a745; }
        .btn-danger { background-color: #dc3545; }
        .btn-warning { background-color: #ffc107; color: #333; }
        .btn:hover { opacity: 0.9; }
        h2 { 
            margin-top: 0; 
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔄 Sync Diagnostic & Test Tool</h1>
        <p>Check and verify offline/online synchronization status</p>
    </div>

    <?php
    $localConn = getDBConnection();
    $remoteConn = getRemoteDBConnection();
    
    // Test 1: Database Connections
    echo '<div class="card">';
    echo '<h2>📊 Database Connection Status</h2>';
    
    if ($localConn) {
        echo '<div class="success">✅ <strong>Local Database Connected</strong><br>';
        echo 'Host: ' . DB_HOST . ' | Database: ' . DB_NAME . '</div>';
    } else {
        echo '<div class="error">❌ <strong>Local Database Connection Failed</strong></div>';
    }
    
    if ($remoteConn) {
        echo '<div class="success">✅ <strong>Remote Database Connected</strong><br>';
        echo 'Host: ' . REMOTE_DB_HOST . ' | Database: ' . REMOTE_DB_NAME . '</div>';
    } else {
        echo '<div class="error">❌ <strong>Remote Database Connection Failed</strong><br>';
        echo 'Cannot sync to remote server. Check internet connection and remote server status.</div>';
    }
    echo '</div>';
    
    // Test 2: Table Structure Check
    echo '<div class="card">';
    echo '<h2>🔧 Table Structure Verification</h2>';
    
    $tables = ['users', 'functions', 'collections', 'expenses'];
    $requiredColumns = ['uuid', 'is_synced', 'remote_id'];
    
    echo '<table>';
    echo '<tr><th>Table</th><th>UUID</th><th>is_synced</th><th>remote_id</th><th>Status</th></tr>';
    
    foreach ($tables as $table) {
        echo '<tr>';
        echo '<td><strong>' . $table . '</strong></td>';
        
        $allColumnsExist = true;
        foreach ($requiredColumns as $col) {
            $res = $localConn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
            $exists = $res->num_rows > 0;
            echo '<td>' . ($exists ? '✅' : '❌') . '</td>';
            if (!$exists) $allColumnsExist = false;
        }
        
        if ($allColumnsExist) {
            echo '<td><span class="badge badge-success">Ready</span></td>';
        } else {
            echo '<td><span class="badge badge-danger">Missing Columns</span></td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<div class="info" style="margin-top: 15px;">💡 If any columns are missing, run sync_data.php once to automatically create them.</div>';
    echo '</div>';
    
    // Test 3: Sync Statistics
    echo '<div class="card">';
    echo '<h2>📈 Synchronization Statistics</h2>';
    
    echo '<div class="stat-grid">';
    
    foreach ($tables as $table) {
        $totalQuery = $localConn->query("SELECT COUNT(*) as total FROM `$table`");
        $total = $totalQuery->fetch_assoc()['total'];
        
        $syncedQuery = $localConn->query("SELECT COUNT(*) as synced FROM `$table` WHERE is_synced = 1");
        $synced = $syncedQuery ? $syncedQuery->fetch_assoc()['synced'] : 0;
        
        $pending = $total - $synced;
        $percentage = $total > 0 ? round(($synced / $total) * 100) : 0;
        
        echo '<div class="stat-box">';
        echo '<div class="stat-label">' . ucfirst($table) . '</div>';
        echo '<div class="stat-value">' . $pending . '</div>';
        echo '<div class="stat-label">Pending Sync (' . $percentage . '% synced)</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Check deleted records
    $deletedQuery = $localConn->query("SELECT COUNT(*) as count FROM deleted_records");
    if ($deletedQuery) {
        $deletedCount = $deletedQuery->fetch_assoc()['count'];
        if ($deletedCount > 0) {
            echo '<div class="warning">⚠️ <strong>' . $deletedCount . ' deleted records</strong> pending sync to remote server.</div>';
        }
    }
    
    echo '</div>';
    
    // Test 4: UUID Coverage
    echo '<div class="card">';
    echo '<h2>🔑 UUID Coverage Check</h2>';
    
    echo '<table>';
    echo '<tr><th>Table</th><th>Total Records</th><th>With UUID</th><th>Missing UUID</th><th>Coverage</th></tr>';
    
    foreach ($tables as $table) {
        $totalQuery = $localConn->query("SELECT COUNT(*) as total FROM `$table`");
        $total = $totalQuery->fetch_assoc()['total'];
        
        $withUuidQuery = $localConn->query("SELECT COUNT(*) as count FROM `$table` WHERE uuid IS NOT NULL AND uuid != ''");
        $withUuid = $withUuidQuery ? $withUuidQuery->fetch_assoc()['count'] : 0;
        
        $missing = $total - $withUuid;
        $coverage = $total > 0 ? round(($withUuid / $total) * 100) : 100;
        
        echo '<tr>';
        echo '<td><strong>' . $table . '</strong></td>';
        echo '<td>' . $total . '</td>';
        echo '<td>' . $withUuid . '</td>';
        echo '<td>' . $missing . '</td>';
        
        if ($coverage >= 100) {
            echo '<td><span class="badge badge-success">' . $coverage . '%</span></td>';
        } elseif ($coverage >= 50) {
            echo '<td><span class="badge badge-warning">' . $coverage . '%</span></td>';
        } else {
            echo '<td><span class="badge badge-danger">' . $coverage . '%</span></td>';
        }
        
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<div class="info" style="margin-top: 15px;">💡 UUIDs are automatically generated during sync for records that don\'t have them.</div>';
    echo '</div>';
    
    // Test 5: Recent Sync Activity
    if ($remoteConn) {
        echo '<div class="card">';
        echo '<h2>🔄 Recent Sync Activity</h2>';
        
        // Get recently synced records
        $recentQuery = $localConn->query("
            SELECT 'collections' as table_type, id, computer_number, total_amount as value, collection_date as date, is_synced, remote_id
            FROM collections 
            WHERE is_synced = 1 
            ORDER BY id DESC 
            LIMIT 5
        ");
        
        if ($recentQuery && $recentQuery->num_rows > 0) {
            echo '<table>';
            echo '<tr><th>Type</th><th>Local ID</th><th>Remote ID</th><th>Computer</th><th>Value</th><th>Date</th><th>Status</th></tr>';
            
            while ($row = $recentQuery->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . ucfirst($row['table_type']) . '</td>';
                echo '<td>#' . $row['id'] . '</td>';
                echo '<td>' . ($row['remote_id'] ? '#' . $row['remote_id'] : 'N/A') . '</td>';
                echo '<td>' . $row['computer_number'] . '</td>';
                echo '<td>₹' . number_format($row['value'], 2) . '</td>';
                echo '<td>' . date('Y-m-d', strtotime($row['date'])) . '</td>';
                echo '<td><span class="badge badge-success">Synced</span></td>';
                echo '</tr>';
            }
            
            echo '</table>';
        } else {
            echo '<div class="info">No recently synced records found.</div>';
        }
        
        echo '</div>';
    }
    
    // Action Buttons
    echo '<div class="card">';
    echo '<h2>⚡ Quick Actions</h2>';
    echo '<a href="sync_data.php" class="btn btn-primary">🔄 Run Full Sync Now</a>';
    echo '<a href="undo_sync.php" class="btn btn-warning">⚙️ Sync Management</a>';
    echo '<a href="../index.php" class="btn btn-success">🏠 Back to Dashboard</a>';
    echo '</div>';
    
    // Recommendations
    echo '<div class="card">';
    echo '<h2>💡 Recommendations</h2>';
    
    $recommendations = [];
    
    // Check if remote connection is available
    if (!$remoteConn) {
        $recommendations[] = ['priority' => 'high', 'message' => 'Remote database connection is not available. Check internet connection and remote server status.'];
    }
    
    // Check for pending records
    $totalPending = 0;
    foreach ($tables as $table) {
        $pendingQuery = $localConn->query("SELECT COUNT(*) as count FROM `$table` WHERE is_synced = 0");
        if ($pendingQuery) {
            $totalPending += $pendingQuery->fetch_assoc()['count'];
        }
    }
    
    if ($totalPending > 50) {
        $recommendations[] = ['priority' => 'medium', 'message' => 'You have ' . $totalPending . ' records pending sync. Consider running a sync operation soon.'];
    } elseif ($totalPending > 0) {
        $recommendations[] = ['priority' => 'low', 'message' => 'You have ' . $totalPending . ' records pending sync.'];
    } else {
        $recommendations[] = ['priority' => 'success', 'message' => 'All records are synced! Your data is up to date.'];
    }
    
    // Check UUID coverage
    foreach ($tables as $table) {
        $totalQuery = $localConn->query("SELECT COUNT(*) as total FROM `$table`");
        $total = $totalQuery->fetch_assoc()['total'];
        
        $withoutUuidQuery = $localConn->query("SELECT COUNT(*) as count FROM `$table` WHERE uuid IS NULL OR uuid = ''");
        $withoutUuid = $withoutUuidQuery ? $withoutUuidQuery->fetch_assoc()['count'] : 0;
        
        if ($withoutUuid > 0 && $total > 0) {
            $percentage = round(($withoutUuid / $total) * 100);
            $recommendations[] = ['priority' => 'medium', 'message' => $percentage . '% of ' . $table . ' records are missing UUIDs. They will be auto-generated during sync.'];
        }
    }
    
    foreach ($recommendations as $rec) {
        $class = $rec['priority'] === 'high' ? 'error' : ($rec['priority'] === 'medium' ? 'warning' : ($rec['priority'] === 'low' ? 'info' : 'success'));
        echo '<div class="' . $class . '">' . $rec['message'] . '</div>';
    }
    
    echo '</div>';
    
    closeDBConnection($localConn);
    if ($remoteConn) {
        closeDBConnection($remoteConn);
    }
    ?>
    
    <div style="text-align: center; padding: 20px; color: #666;">
        <p>Last diagnostic run: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>

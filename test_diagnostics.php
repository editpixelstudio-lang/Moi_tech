<?php
/**
 * Connection Diagnostic Tool
 * Tests online/offline functionality
 */

require_once '../config/database.php';
require_once '../includes/session.php';

header('Content-Type: text/html; charset=utf-8');

if (!isLoggedIn()) {
    die("Please login first");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Connection Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1976d2;
            border-bottom: 2px solid #1976d2;
            padding-bottom: 10px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #2196f3;
        }
        .success {
            color: #4caf50;
            font-weight: bold;
        }
        .error {
            color: #f44336;
            font-weight: bold;
        }
        .info {
            color: #666;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #e3f2fd;
        }
        .btn {
            background: #1976d2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌐 Connection Diagnostics</h1>
        
        <div class="test-section">
            <h3>1. Database Connections</h3>
            <?php
            // Test local connection
            try {
                $localConn = getDBConnection();
                echo "<p class='success'>✓ Local database connected</p>";
                
                $result = $localConn->query("SELECT COUNT(*) as count FROM collections");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p class='info'>Local collections: {$row['count']}</p>";
                }
                closeDBConnection($localConn);
            } catch (Exception $e) {
                echo "<p class='error'>✗ Local connection failed: " . $e->getMessage() . "</p>";
            }
            
            // Test remote connection
            try {
                $remoteConn = getRemoteDBConnection();
                if ($remoteConn) {
                    echo "<p class='success'>✓ Remote database connected</p>";
                    
                    $result = $remoteConn->query("SELECT COUNT(*) as count FROM collections");
                    if ($result) {
                        $row = $result->fetch_assoc();
                        echo "<p class='info'>Remote collections: {$row['count']}</p>";
                    }
                    closeDBConnection($remoteConn);
                } else {
                    echo "<p class='error'>✗ Remote connection failed (returned null)</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>✗ Remote connection failed: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h3>2. Cache Table Status</h3>
            <?php
            try {
                $localConn = getDBConnection();
                
                // Check if cache table exists
                $result = $localConn->query("SHOW TABLES LIKE 'suggestion_cache'");
                if ($result->num_rows > 0) {
                    echo "<p class='success'>✓ Suggestion cache table exists</p>";
                    
                    $count = $localConn->query("SELECT COUNT(*) as count FROM suggestion_cache");
                    $row = $count->fetch_assoc();
                    echo "<p class='info'>Cached entries: {$row['count']}</p>";
                    
                    // Show structure
                    $structure = $localConn->query("DESCRIBE suggestion_cache");
                    echo "<table><tr><th>Field</th><th>Type</th></tr>";
                    while ($field = $structure->fetch_assoc()) {
                        echo "<tr><td>{$field['Field']}</td><td>{$field['Type']}</td></tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p class='error'>✗ Suggestion cache table not found</p>";
                }
                
                closeDBConnection($localConn);
            } catch (Exception $e) {
                echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h3>3. API Tests</h3>
            <button class="btn" onclick="testConnection()">Test Connection API</button>
            <button class="btn" onclick="testSearch()">Test Search API</button>
            <div id="apiResults" style="margin-top: 15px;"></div>
        </div>
        
        <div class="test-section">
            <h3>4. Database Configuration</h3>
            <?php
            echo "<p class='info'>Local DB: " . DB_HOST . "/" . DB_NAME . "</p>";
            echo "<p class='info'>Remote DB: " . REMOTE_DB_HOST . "/" . REMOTE_DB_NAME . "</p>";
            ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <button class="btn" onclick="window.location.reload()">Refresh Tests</button>
            <button class="btn" onclick="window.location.href='../collection_entry.php?function_id=1'">Back to Collection Entry</button>
        </div>
    </div>
    
    <script>
        function testConnection() {
            const resultsDiv = document.getElementById('apiResults');
            resultsDiv.innerHTML = '<p>Testing connection API...</p>';
            
            fetch('../api/test_connection.php?t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    let html = '<h4>Connection API Response:</h4>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    resultsDiv.innerHTML = html;
                })
                .catch(e => {
                    resultsDiv.innerHTML = '<p class="error">Error: ' + e.message + '</p>';
                });
        }
        
        function testSearch() {
            const resultsDiv = document.getElementById('apiResults');
            resultsDiv.innerHTML = '<p>Testing search API (online mode)...</p>';
            
            fetch('../api/search_collections.php?field=location&q=test&is_online=true')
                .then(r => r.json())
                .then(data => {
                    let html = '<h4>Search API Response (Online):</h4>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    
                    // Also test offline
                    return fetch('../api/search_collections.php?field=location&q=test&is_online=false')
                        .then(r => r.json())
                        .then(offlineData => {
                            html += '<h4>Search API Response (Offline):</h4>';
                            html += '<pre>' + JSON.stringify(offlineData, null, 2) + '</pre>';
                            resultsDiv.innerHTML = html;
                        });
                })
                .catch(e => {
                    resultsDiv.innerHTML = '<p class="error">Error: ' + e.message + '</p>';
                });
        }
    </script>
</body>
</html>

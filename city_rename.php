<?php
/**
 * City Rename Page - UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if function_id is provided
if (!isset($_GET['function_id']) || empty($_GET['function_id'])) {
    redirect('index.php');
}

$functionId = intval($_GET['function_id']);
$conn = getDBConnection();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cities'])) {
    $oldNames = $_POST['old_names'] ?? [];
    $newNames = $_POST['new_names'] ?? [];
    $updatedCount = 0;

    for ($i = 0; $i < count($oldNames); $i++) {
        $oldName = $oldNames[$i];
        $newName = trim($newNames[$i]);

        if (!empty($newName) && $newName !== $oldName) {
            $stmt = $conn->prepare("UPDATE collections SET location = ? WHERE location = ? AND function_id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $newName, $oldName, $functionId);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $updatedCount++;
                }
                $stmt->close();
            }
        }
    }
    
    if ($updatedCount > 0) {
        $message = "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px; border-radius: 4px; text-align: center;'>$updatedCount records updated successfully.</div>";
    }
}

// Fetch cities and counts
$stmt = $conn->prepare("SELECT location, COUNT(id) as rel_count FROM collections WHERE function_id = ? GROUP BY location ORDER BY location ASC");
$stmt->bind_param("i", $functionId);
$stmt->execute();
$result = $stmt->get_result();

$cities = [];
$totalCities = 0;
$totalPersons = 0;

while ($row = $result->fetch_assoc()) {
    // Sometimes location could be empty, though handled in save. We should include it to fix empty names if needed.
    $cities[] = $row;
    $totalCities++;
    $totalPersons += $row['rel_count'];
}
$stmt->close();
closeDBConnection($conn);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Rename</title>
    <link href="https://fonts.googleapis.com/css2?family=Kavivanar&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .header-bar {
            background-color: #7b4397; /* Purple */
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
        }

        .header-bar button {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            margin-right: 15px;
            cursor: pointer;
        }

        .page-title-box {
            background-color: white;
            border: 1px solid #000;
            margin: 0;
            padding: 15px;
            text-align: center;
            font-size: 22px;
            font-weight: bold;
        }

        .search-container {
            padding: 15px;
            background: white;
            border-bottom: 1px solid #ddd;
        }

        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        #searchInput {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
        }

        .clear-search {
            position: absolute;
            right: 15px;
            font-size: 18px;
            color: #888;
            cursor: pointer;
            border: none;
            background: none;
        }

        .table-container {
            background: white;
            height: calc(100vh - 280px);
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            color: #333;
            background: white;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            font-size: 16px;
        }

        .col-city {
            width: 45%;
        }

        .col-rel {
            width: 10%;
            text-align: center;
        }

        .col-rename {
            width: 45%;
        }

        .rename-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
        }

        .footer-stats {
            background: white;
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 20px;
            font-weight: bold;
            color: #555;
            border-top: 1px solid #eee;
        }

        .footer-actions {
            display: flex;
            padding: 10px;
            background: white;
            gap: 10px;
        }

        .btn-update {
            flex: 1;
            background-color: #d81b60; /* Pink/Rose color */
            color: white;
            border: none;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-clear, .btn-exit {
            flex: 1;
            background-color: white;
            color: #333;
            border: 1px solid #ccc;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .app-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background: white;
        }

        /* Responsive styling for typical mobile screen look */
        @media (min-width: 600px) {
            .app-container {
                max-width: 600px;
                margin: 0 auto;
                border-left: 1px solid #ccc;
                border-right: 1px solid #ccc;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }
        
    </style>
</head>
<body>

<div class="app-container">
    <div class="header-bar">
        <button type="button" onclick="window.close();">&#8592;</button>
        CITY RENAME
    </div>

    <div class="page-title-box">
        Correct Village Names
    </div>

    <?php echo $message; ?>

    <div class="search-container">
        <div class="search-input-wrapper">
            <input type="text" id="searchInput" placeholder="Type To Search City">
            <button type="button" class="clear-search" id="clearSearchBtn">&#x2715;</button>
        </div>
    </div>

    <form method="POST" id="cityRenameForm" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
        <input type="hidden" name="update_cities" value="1">
        
        <div class="table-container">
            <table id="cityTable">
                <thead>
                    <tr>
                        <th class="col-city">City</th>
                        <th class="col-rel">Rel</th>
                        <th class="col-rename">Rename</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cities as $city): ?>
                    <tr class="city-row">
                        <td class="col-city city-name" lang="ta"><?php echo htmlspecialchars($city['location']); ?></td>
                        <td class="col-rel" style="text-align: center;"><?php echo $city['rel_count']; ?></td>
                        <td class="col-rename">
                            <input type="hidden" name="old_names[]" value="<?php echo htmlspecialchars($city['location']); ?>">
                            <input type="text" name="new_names[]" class="rename-input" value="<?php echo htmlspecialchars($city['location']); ?>" lang="ta">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="footer-stats">
            <div>Total City <span id="totalCityCount"><?php echo $totalCities; ?></span></div>
            <div>Total Persons <span id="totalPersonsCount"><?php echo $totalPersons; ?></span></div>
        </div>

        <div class="footer-actions">
            <button type="submit" class="btn-update">UPDATE</button>
            <button type="button" class="btn-clear" id="btnClear">Clear</button>
            <button type="button" class="btn-exit" onclick="window.close();">Exit</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const cityRows = document.querySelectorAll('.city-row');
    const btnClear = document.getElementById('btnClear');

    // Search functionality
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        let visibleCities = 0;
        let visiblePersons = 0;

        cityRows.forEach(row => {
            const cityName = row.querySelector('.city-name').textContent.toLowerCase();
            const relCount = parseInt(row.querySelector('.col-rel').textContent);
            
            if (cityName.includes(query)) {
                row.style.display = '';
                visibleCities++;
                visiblePersons += relCount;
            } else {
                row.style.display = 'none';
            }
        });

        // Uncomment these if we want search to update counts, but usually it maintains total
        // document.getElementById('totalCityCount').textContent = visibleCities;
        // document.getElementById('totalPersonsCount').textContent = visiblePersons;
    });

    // Clear search
    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    });

    // Clear changes (reset inputs to old names)
    btnClear.addEventListener('click', () => {
        cityRows.forEach(row => {
            const oldName = row.querySelector('input[name="old_names[]"]').value;
            row.querySelector('input[name="new_names[]"]').value = oldName;
        });
    });
});
</script>

</body>
</html>

<?php
/**
 * Printable Collection Report
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';

$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;
if ($functionId <= 0) {
    redirect('index.php');
}
// $computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

// if (empty($computerNumber)) {
//     echo '<script>alert("கணினி எண் அமைக்கப்படவில்லை. தயவு செய்து மீண்டும் முயற்சிக்கவும்."); window.location.href="collection_entry.php?function_id=' . $functionId . '";</script>';
//     exit();
// }

$conn = getDBConnection();

function indian_number_format($num) {
    $num = (string) round($num);
    $len = strlen($num);
    if ($len <= 3) return $num;
    
    $last3 = substr($num, -3);
    $rest = substr($num, 0, -3);
    $rest = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $rest);
    
    return $rest . "," . $last3;
}

// Fetch function details
$stmt = $conn->prepare("SELECT id, function_name, function_date, place, function_details, created_at, uuid, remote_id FROM functions WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $functionId);
$stmt->execute();
$functionResult = $stmt->get_result();

if ($functionResult->num_rows === 0) {
    $stmt->close();
    closeDBConnection($conn);
    redirect('index.php');
}

$function = $functionResult->fetch_assoc();
$stmt->close();

$funcUuid = !empty($function['uuid']) ? $function['uuid'] : '';
$funcRemoteId = !empty($function['remote_id']) ? $function['remote_id'] : 0;

// Fetch collections for the specific function id only
$collectionsStmt = $conn->prepare("
    SELECT c.id, c.location, c.initial_name, c.name1, c.name2, c.initial2, c.occupation, c.occupation2, 
           c.relationship_priority, c.village_going_to, c.phone, c.customer_number, c.description, 
           c.total_amount, c.collection_date, c.computer_number, u.full_name AS logged_in_username 
    FROM collections c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.function_id = ?
    ORDER BY CASE WHEN c.relationship_priority IS NOT NULL THEN 0 ELSE 1 END, c.relationship_priority ASC, CASE WHEN c.location IS NULL OR c.location = '' THEN 1 ELSE 0 END, c.location ASC, c.id ASC
");
$collectionsStmt->bind_param('i', $functionId);
$collectionsStmt->execute();
$collectionsResult = $collectionsStmt->get_result();

$collectionsByLocation = [];
$priorityCollections = [];
$totalEntries = 0;
$totalAmount = 0.0;
$earliestDate = null;
$latestDate = null;

while ($row = $collectionsResult->fetch_assoc()) {
    $totalEntries++;
    $amount = isset($row['total_amount']) ? (float)$row['total_amount'] : 0.0;
    $totalAmount += $amount;

    // Check for priority 1 (Maternal Uncle) and 2 (Aunt-Uncle)
    if (!empty($row['relationship_priority']) && ($row['relationship_priority'] == 1 || $row['relationship_priority'] == 2)) {
        $priorityCollections[] = $row;
    } else {
        $locationKey = trim((string)($row['location'] ?? ''));
        if ($locationKey === '') {
            $locationKey = 'குறிப்பிடப்படாத ஊர்';
        }

        if (!isset($collectionsByLocation[$locationKey])) {
            $collectionsByLocation[$locationKey] = [
                'items' => [],
                'total' => 0.0,
            ];
        }

        $collectionsByLocation[$locationKey]['items'][] = $row;
        $collectionsByLocation[$locationKey]['total'] += $amount;
    }

    if (!empty($row['collection_date'])) {
        $currentDate = $row['collection_date'];
        if ($earliestDate === null || $currentDate < $earliestDate) {
            $earliestDate = $currentDate;
        }
        if ($latestDate === null || $currentDate > $latestDate) {
            $latestDate = $currentDate;
        }
    }
}

$collectionsStmt->close();
closeDBConnection($conn);

// Sort locations alphabetically for the index
if (!empty($collectionsByLocation)) {
    uksort($collectionsByLocation, static function ($a, $b) {
        return strcasecmp($a, $b);
    });
}

$uniqueVillages = count($collectionsByLocation);
$averageTicket = $totalEntries > 0 ? $totalAmount / $totalEntries : 0;

$dateRangeLabel = '—';
if ($earliestDate && $latestDate) {
    $dateRangeLabel = date('d M Y', strtotime($earliestDate)) . ' - ' . date('d M Y', strtotime($latestDate));
} elseif ($earliestDate) {
    $dateRangeLabel = date('d M Y', strtotime($earliestDate));
}

$functionDateLabel = !empty($function['function_date']) ? date('d M Y', strtotime($function['function_date'])) : '—';
$printedOn = date('d M Y \a\t h:i A');

// Prepare index data
$locationIndex = [];
$anchorCounter = 1;
foreach ($collectionsByLocation as $locationName => $payload) {
    $locationIndex[] = [
        'anchor' => 'location-' . $anchorCounter,
        'name' => $locationName,
        'count' => count($payload['items']),
        'total' => $payload['total'],
    ];
    $anchorCounter++;
}
?>
<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>அறிக்கை — <?php echo htmlspecialchars($function['function_name']); ?></title>
    <script src="https://unpkg.com/pagedjs/dist/paged.polyfill.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Catamaran:wght@400;500;600;700&family=Noto+Sans+Tamil:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        class PageTotalHandler extends Paged.Handler {
            constructor(chunker, polisher, caller) {
                super(chunker, polisher, caller);
            }

            afterPageLayout(pageElement, page, breakToken) {
                // Skip pages without data tables (cover, index, summary pages) for Total calculation
                let amounts = pageElement.querySelectorAll('.data-sections .col-amount strong, .priority-section .col-amount strong');
                
                if (amounts.length === 0) return;

                let total = 0;
                amounts.forEach(el => {
                    let text = el.innerText.replace(/,/g, '').replace(/₹/g, '').trim();
                    let val = parseFloat(text);
                    if (!isNaN(val)) total += val;
                });

                if (total === 0) return;

                // Currency formatter
                const formatter = new Intl.NumberFormat('en-IN', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });

                // Find the margin-bottom-center area of the page
                let marginBottomCenter = pageElement.querySelector('.pagedjs_margin-bottom-center .pagedjs_margin-content');
                
                if (marginBottomCenter) {
                    // Append to existing page number area
                    let totalSpan = document.createElement('span');
                    totalSpan.style.marginLeft = '20px';
                    totalSpan.style.fontWeight = 'bold';
                    totalSpan.style.background = '#f0f0f0';
                    totalSpan.style.padding = '2px 8px';
                    totalSpan.style.borderRadius = '3px';
                    totalSpan.style.border = '1px solid #999';
                    totalSpan.innerHTML = ' | பக்க மொத்தம்: ₹' + formatter.format(total);
                    marginBottomCenter.appendChild(totalSpan);
                }
            }

            afterRendered(pages) {
                // Serial numbers are handled by PHP directly.
            }
        }
        Paged.registerHandlers(PageTotalHandler);

        // Handler to replicate table headers on paginated tables
        class RepeatTableHeaderHandler extends Paged.Handler {
            constructor(chunker, polisher, caller) {
                super(chunker, polisher, caller);
            }
            afterPageLayout(pageElement, page, breakToken) {
                let tables = pageElement.querySelectorAll('table[data-split-from]');
                tables.forEach(table => {
                    let ref = table.getAttribute('data-ref');
                    if (ref) {
                        // Find the original table from the source document fragment
                        let originalTable = this.chunker.source.querySelector('table[data-ref="' + ref + '"]');
                        // Fallback to the main document if chunker source doesn't have it
                        if (!originalTable) {
                            originalTable = document.querySelector('table[data-ref="' + ref + '"]');
                        }
                        
                        if (originalTable) {
                            let thead = originalTable.querySelector('thead');
                            if (thead && !table.querySelector('thead')) {
                                let clonedThead = thead.cloneNode(true);
                                table.insertBefore(clonedThead, table.firstChild);
                            }
                        }
                    }
                });
            }
        }
        Paged.registerHandlers(RepeatTableHeaderHandler);
    </script>
    <style>
        :root {
            --font-main: 'Inter', sans-serif;
            --color-text: #111;
            --color-dim: #555;
            --border-color: #ccc;
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --secondary: #8b5cf6;
            --accent: #10b981;
            --warning: #f59e0b;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-3: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-4: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --gradient-5: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        @page {
            size: A4;
            margin: 15mm 15mm 25mm 40mm; /* Increased left margin for book binding */
            @bottom-center {
                content: "பக்கம் " counter(page);
                font-family: 'Inter', sans-serif;
                font-size: 9pt;
            }
        }

        @page cover {
            margin: 0;
            size: A4;
            @bottom-center {
                content: none;
            }
        }

        @media print {
            .no-print, #ui-toolbar { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
        }

        #ui-toolbar {
            display: block !important;
        }

        body {
            font-family: var(--font-main);
            font-size: 10pt;
            color: var(--color-text);
            line-height: 1.3;
            margin: 0;
        }

        /* Toolbar */
        .toolbar {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 9999;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .toolbar button {
            background: #000;
            color: #fff;
            border: none;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 4px;
        }
        .toolbar a {
            margin-left: 10px;
            text-decoration: none;
            color: #000;
            font-size: 14px;
        }

        /* Report Container */
        .report-container {
            max-width: 100%;
        }

        /* First Page with Background */
        .first-page {
            page: cover;
            position: relative;
            width: 100%;
            height: 297mm;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            break-after: page;
            overflow: hidden;
        }
        .first-page .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }

        /* Hidden Header for First Page */
        .header-section {
            display: none;
        }

        /* Function Details Box - Positioned in White Box */
        .details-box {
            position: relative;
            z-index: 1;
            bottom: -280px;
            width: 60%;
            border: 2px solid #000;
            padding: 30px;
            background: white;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .details-box h2 {
            margin: 0 0 10px 0;
            font-size: 20pt;
            font-weight: 800;
            background: linear-gradient(135deg, #d97706 0%, #dc2626 50%, #7c2d12 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-transform: uppercase;
            line-height: 1.3;
            letter-spacing: 1px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .details-box p {
            margin: 5px 0 15px 0;
            white-space: pre-line;
            font-size: 12pt;
            color: #333;
            line-height: 1.5;
        }
        .details-box .function-meta {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #000;
            font-weight: 600;
            font-size: 12pt;
            color: #000;
        }

        /* Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 0;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-bottom: 3px solid;
        }
        .summary-item:nth-child(1) {
            border-bottom-color: #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }
        .summary-item:nth-child(2) {
            border-bottom-color: #10b981;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        .summary-item:nth-child(3) {
            border-bottom-color: #8b5cf6;
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
        }
        .summary-item:nth-child(4) {
            border-bottom-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        .summary-item:nth-child(5) {
            border-bottom-color: #ec4899;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }
        .summary-item h3 {
            margin: 0 0 5px 0;
            font-size: 16pt;
            font-weight: 700;
        }
        .summary-item:nth-child(1) h3 { color: #1e40af; }
        .summary-item:nth-child(2) h3 { color: #047857; }
        .summary-item:nth-child(3) h3 { color: #6d28d9; }
        .summary-item:nth-child(4) h3 { color: #d97706; }
        .summary-item:nth-child(5) h3 { color: #be185d; }
        .summary-item span {
            font-size: 8pt;
            color: var(--color-dim);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* Index Table */
        .index-section {
            margin-bottom: 30px;
            margin-top: 0;
            padding-top: 20px;
        }
        .index-heading {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
            border-bottom: none;
            display: inline-block;
            background: var(--gradient-3);
            color: white;
            padding: 8px 20px;
            border-radius: 6px;
            break-after: avoid;
        }
        .index-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            border: 2px solid #000;
            margin-bottom: 20px;
        }
        .index-table th {
            text-align: center;
            background: #fff;
            color: #000;
            border: 1px solid #000;
            padding: 6px;
            font-weight: bold;
            border-bottom: 2px solid #000;
        }
        .index-table tbody tr {
            background: #fff;
        }
        .index-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
            text-align: center;
        }
        .index-table td:first-child {
            text-align: left; /* Keep names left aligned */
        }
        .index-table td.page-col {
            font-weight: bold;
            color: #000;
        }
        /* Paged.js magic for page numbers */
        .page-ref::after {
            content: target-counter(attr(href), page);
        }

        /* Data Tables */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            font-size: 10pt;
            margin-bottom: 20px;
            -fs-table-paginate: paginate;
        }
        .main-table th {
            border: 1px solid #000;
            padding: 6px;
            background: #fff;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #000;
        }
        .main-table th.col-sl {
            text-align: left;
            padding-left: 15px;
        }
        .main-table th.col-name {
            text-align: left;
            padding-left: 10px;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: middle;
            text-align: center;
        }

        /* Serial numbers via data attribute - immune to Paged.js DOM restructuring */

        .col-sl { width: 60px; text-align: left !important; padding-left: 15px !important; font-weight: bold; }
        .col-name {
            font-weight: 600;
            font-family: 'Noto Sans Tamil', 'Arial', sans-serif;
            width: 40%;
            text-align: left;
            padding-left: 10px !important;
        }
        .col-amount { width: 15%; text-align: center; font-weight: bold; font-size: 11pt; padding-left: 0; }
        .col-manual { width: 15%; text-align: center; }

        /* Location Header Row */
        .location-row td {
            background-color: #fff;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 11pt;
            padding: 8px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        /* Total Rows */
        .total-row td {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            padding: 8px;
            background-color: #fff;
            border-top: 1px solid #000;
        }
        .grand-total-row td {
            text-align: right;
            font-weight: bold;
            font-size: 12pt;
            padding: 10px;
            background-color: #f0f0f0;
            border-top: 2px solid #000;
        }

        /* Compact adjustments */
        .compact-text { font-size: 8.5pt; }

        .page-break-before {
            display: block;
            break-before: page;
            page-break-before: always;
        }

        .page-break-after {
            display: block;
            break-after: page;
            page-break-after: always;
        }

        .priority-section .main-table,
        .priority-section .main-table th,
        .priority-section .main-table td,
        .priority-section .location-row td {
            font-family: 'Catamaran', 'Inter', sans-serif;
            letter-spacing: 0.2px;
        }

        .priority-section .col-name {
            font-family: 'Noto Sans Tamil', 'Arial', sans-serif;
        }

        /* Force table headers to repeat on new pages */
        @media print {
            .main-table thead { display: table-header-group; }
            .main-table tfoot { display: table-footer-group; }
            .main-table tr { break-inside: avoid; page-break-inside: avoid; }
        }
        
        /* Ensure Paged.js respects this structure */
        .main-table {
            break-inside: auto;
        }
        .main-table thead {
            display: table-header-group;
        }
        .main-table tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

    <div id="ui-toolbar" class="toolbar" data-html2canvas-ignore="true">
        <button onclick="window.print()">அச்சிடு / PDF சேமி</button>
        <button id="downloadPdfBtn" style="margin-left: 10px; background: #dc2626;">PDF பதிவிறக்கம்</button>
        <a href="collection_entry.php?function_id=<?php echo $functionId; ?>">பதிவுக்கு திரும்ப</a>
    </div>

    <div class="report-container">
        
        <!-- Page 1: Decorative Background with Function Details -->
        <div class="first-page">
            <img src="assets/images/collection-bg.jpg" alt="Background" class="bg-image">
            <div class="details-box">
                <h2><?php echo htmlspecialchars($function['function_name']); ?></h2>
                <?php if (!empty($function['function_details'])): ?>
                <p><?php echo htmlspecialchars($function['function_details']); ?></p>
                <?php endif; ?>
                <div class="function-meta">
                    <?php echo $functionDateLabel; ?> | <?php echo htmlspecialchars($function['place']); ?>
                </div>
            </div>
        </div>

        <?php if ($totalEntries > 0): ?>
        <?php $serialNo = 1; ?>
        <?php $indexClasses = 'index-section page-break-after'; ?>
        <div class="<?php echo $indexClasses; ?>">
            <table class="index-table">
                <thead>
                    <tr>
                        <th>ஊர்கள்</th>
                        <th>வருகைகள்</th>
                        <th>தொகைகள்</th>
                        <th class="page-col">பக்கம் எண்</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locationIndex as $loc): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($loc['name']); ?></td>
                        <td><?php echo number_format($loc['count']); ?></td>
                        <td>₹<?php echo indian_number_format($loc['total']); ?></td>
                        <td class="page-col"><a href="#<?php echo $loc['anchor']; ?>" class="page-ref" style="text-decoration:none; color:inherit;"></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="serial-counter-wrapper">
        <?php if (!empty($priorityCollections)): ?>
        <!-- Priority Section -->
        <div class="priority-section page-break-after">
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="col-sl">வ.எண்</th>
                        <th class="col-name">பெயர் / விவரம்</th>
                        <th class="col-amount">வந்த மொய்</th>
                        <th class="col-manual">செய்த தொகை</th>
                        <th class="col-manual">இருப்பு</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentPrio = 0;
                    foreach ($priorityCollections as $entry): 
                        $prio = $entry['relationship_priority'];
                        if ($prio != $currentPrio) {
                            $currentPrio = $prio;
                            $prioTitle = ($prio == 1) ? 'தாய்மாமன்' : 'அத்தை - மாமா';
                    ?>
                        <tr class="location-row">
                            <td colspan="5"><?php echo $prioTitle; ?></td>
                        </tr>
                    <?php } ?>
                        <tr class="data-row">
                            <td class="col-sl"><?php echo $serialNo++; ?></td>
                            <td class="col-name">
                                <?php 
                                    // Build formatted name with occupation
                                    $output = '';
                                    
                                    // First person: Initial.Name - (Occupation)
                                    $initial1 = $entry['initial_name'] ?? '';
                                    $name1 = $entry['name1'] ?? '';
                                    $occupation1 = $entry['occupation'] ?? '';
                                    
                                    if (!empty($name1)) {
                                        $output .= trim($initial1 . ' ' . $name1);
                                        if (!empty($occupation1)) {
                                            $output .= ' - (' . $occupation1 . ')';
                                        }
                                    }
                                    
                                    // Second person: Initial.Name (Occupation)
                                    $initial2 = $entry['initial2'] ?? '';
                                    $name2 = $entry['name2'] ?? '';
                                    $occupation2 = $entry['occupation2'] ?? '';
                                    
                                    if (!empty($name2)) {
                                        if (!empty($output)) $output .= ' ';
                                        $output .= trim($initial2 . ' ' . $name2);
                                        if (!empty($occupation2)) {
                                            $output .= ' (' . $occupation2 . ')';
                                        }
                                    }
                                    
                                    // Add description with arrow
                                    if (!empty($entry['description'])) {
                                        if (!empty($output)) $output .= ' ';
                                        $output .= '→ ' . $entry['description'];
                                    }
                                    
                                    echo htmlspecialchars($output);
                                    
                                ?>
                            </td>
                            <td class="col-amount"><strong><?php echo indian_number_format((float)$entry['total_amount']); ?></strong></td>
                            <td class="col-manual"></td>
                            <td class="col-manual"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Data Sections -->
        <div class="data-sections">
            <table class="main-table">
                <thead>
                    <tr>
                        <th class="col-sl">வ.எண்</th>
                        <th class="col-name">பெயர் / விவரம்</th>
                        <th class="col-amount">வந்த மொய்</th>
                        <th class="col-manual">செய்த தொகை</th>
                        <th class="col-manual">இருப்பு</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sectionIndex = 0; ?>
                    <?php foreach ($collectionsByLocation as $locationName => $payload): ?>
                        <?php $sectionIndex++; $anchorId = 'location-' . $sectionIndex; ?>
                        
                        <!-- Location Header -->
                        <tr id="<?php echo $anchorId; ?>" class="location-row">
                            <td colspan="5"><?php echo htmlspecialchars($locationName); ?></td>
                        </tr>
                        
                        <!-- Items -->
                        <?php foreach ($payload['items'] as $entry): ?>
                            <tr class="data-row">
                                <td class="col-sl"><?php echo $serialNo++; ?></td>
                                <td class="col-name">
                                    <?php 
                                        // Build formatted name with occupation
                                        $output = '';
                                        
                                        // First person: Initial.Name - (Occupation)
                                        $initial1 = $entry['initial_name'] ?? '';
                                        $name1 = $entry['name1'] ?? '';
                                        $occupation1 = $entry['occupation'] ?? '';
                                        
                                        if (!empty($name1)) {
                                            $output .= trim($initial1 . ' ' . $name1);
                                            if (!empty($occupation1)) {
                                                $output .= ' - (' . $occupation1 . ')';
                                            }
                                        }
                                        
                                        // Second person: Initial.Name (Occupation)
                                        $initial2 = $entry['initial2'] ?? '';
                                        $name2 = $entry['name2'] ?? '';
                                        $occupation2 = $entry['occupation2'] ?? '';
                                        
                                        if (!empty($name2)) {
                                            if (!empty($output)) $output .= ' ';
                                            $output .= trim($initial2 . ' ' . $name2);
                                            if (!empty($occupation2)) {
                                                $output .= ' (' . $occupation2 . ')';
                                            }
                                        }
                                        
                                        // Add description with arrow
                                        if (!empty($entry['description'])) {
                                            if (!empty($output)) $output .= ' ';
                                            $output .= '→ ' . $entry['description'];
                                        }
                                        
                                        echo htmlspecialchars($output);
                                    ?>
                                </td>
                                <td class="col-amount"><strong><?php echo indian_number_format((float)$entry['total_amount']); ?></strong></td>
                                <td class="col-manual"></td>
                                <td class="col-manual"></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    <!-- Grand Total -->
                    <tr class="grand-total-row">
                        <td colspan="5">
                            முழு மொத்த தொகை : ₹<?php echo indian_number_format($totalAmount); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        </div><!-- end .serial-counter-wrapper -->
        <!-- Summary at End -->
        <div style="break-before: page; padding-top: 50px;">
            <div class="summary-grid">
                <div class="summary-item">
                    <h3><?php echo number_format($totalEntries); ?></h3>
                    <span>மொத்த வரவு எண்கள்</span>
                </div>
                <div class="summary-item">
                    <h3>₹<?php echo indian_number_format($totalAmount); ?></h3>
                    <span>மொத்த தொகை</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <script>
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            alert('சிறந்த PDF தரத்திற்கு, "Save as PDF" (PDF ஆக சேமிக்கவும்) என்பதை தேர்ந்தெடுத்து Print கொடுக்கவும்.');
            window.print();
        });

        // Auto-download if pdf=1 param is present
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('pdf') && urlParams.get('pdf') === '1') {
                // Wait for Paged.js to finish rendering
                setTimeout(function() {
                    const btn = document.getElementById('downloadPdfBtn');
                    if (btn) {
                        console.log('Auto-downloading PDF...');
                        btn.click();
                    } else {
                        console.error('Download button not found');
                    }
                }, 2000);
            }
        });
    </script>
</body>
</html>

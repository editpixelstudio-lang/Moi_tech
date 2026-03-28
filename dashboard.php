<?php
/**
 * Dashboard - Home Page
 * UZRS MOI Collection System
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - UZRS MOI Collection</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/mobile.css">
</head>
<body class="dashboard-body">
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>UZRS மொய் வசூல்</h2>
            </div>
            <div class="nav-user">
                <span>வணக்கம், <?php echo htmlspecialchars($user['name']); ?>!</span>
                <?php if (isset($user['role']) && $user['role'] === 'super_admin'): ?>
                <a href="user_management.php" class="btn-dashboard">👥 Users</a>
                <a href="function_management.php" class="btn-dashboard">🎉 Functions</a>
                <?php endif; ?>
                <button id="syncDataBtn" class="btn-dashboard" style="background-color: #28a745; color: white; border: none; cursor: pointer;">🔄 Sync Data</button>
                <a href="mobile_dashboard.php" class="btn-dashboard">மொபைல் பார்வை</a>
                <a href="index.php" class="btn-dashboard">விசேஷங்கள்</a>
                <a href="api/logout.php" class="btn-logout">வெளியேறு</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-content">
            <h1>விசேஷங்கள் நிர்வாகம்</h1>
            <p class="subtitle">பல்வேறு விசேஷங்களுக்கான மொய் வசூலை நிர்வகிக்கவும்</p>

            <div class="event-types">
                <div class="event-card">
                    <div class="event-icon">💒</div>
                    <h3>திருமணம்</h3>
                    <p>திருமண விசேஷ வசூல்</p>
                    <button class="btn btn-primary">விசேஷத்தை உருவாக்கு</button>
                </div>

                <div class="event-card">
                    <div class="event-icon">🎉</div>
                    <h3>காதணி விழா</h3>
                    <p>காதணி விழா வசூல்</p>
                    <button class="btn btn-primary">விசேஷத்தை உருவாக்கு</button>
                </div>

                <div class="event-card">
                    <div class="event-icon">🏠</div>
                    <h3>கிரகப்பிரவேசம்</h3>
                    <p>புதுமனை புகுவிழா வசூல்</p>
                    <button class="btn btn-primary">விசேஷத்தை உருவாக்கு</button>
                </div>

                <div class="event-card">
                    <div class="event-icon">🎊</div>
                    <h3>மற்ற விசேஷங்கள்</h3>
                    <p>மற்ற விசேஷ வசூல்</p>
                    <button class="btn btn-primary">விசேஷத்தை உருவாக்கு</button>
                </div>
            </div>

            <div class="recent-events">
                <h2>சமீபத்திய விசேஷங்கள்</h2>
                <?php
                require_once 'config/database.php';
                $conn = getDBConnection();
                $userId = $user['id'];
                
                $stmt = $conn->prepare("SELECT function_name, function_date, place, function_details, created_at FROM functions WHERE user_id = ? ORDER BY function_date DESC, created_at DESC LIMIT 10");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="functions-grid">';
                    while ($row = $result->fetch_assoc()) {
                        $formattedDate = date('d-m-Y', strtotime($row['function_date']));
                        echo '<div class="function-card">';
                        echo '<h3>' . htmlspecialchars($row['function_name']) . '</h3>';
                        echo '<div class="function-details">';
                        echo '<p><strong>📅 தேதி:</strong> ' . $formattedDate . '</p>';
                        echo '<p><strong>📍 இடம்:</strong> ' . htmlspecialchars($row['place']) . '</p>';
                        if (!empty($row['function_details'])) {
                            echo '<div class="details-box"><strong>விவரம்:</strong><p>' . nl2br(htmlspecialchars($row['function_details'])) . '</p></div>';
                        }
                        echo '<p class="created-date">உருவாக்கப்பட்டது: ' . date('d-m-Y', strtotime($row['created_at'])) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p class="empty-state">இதுவரை எந்த விசேஷமும் உருவாக்கப்படவில்லை. <a href="index.php" style="color: var(--primary-color); text-decoration: underline;">விசேஷங்கள் நிர்வாகம்</a> பக்கத்திற்கு சென்று புதிய விசேஷத்தை உருவாக்கவும்!</p>';
                }
                
                $stmt->close();
                closeDBConnection($conn);
                ?>
            </div>
        </div>
    </div>
    <script src="js/sync.js"></script>
</body>
</html>

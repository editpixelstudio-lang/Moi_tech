<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>உள்நுழைய - UZRS மொய் வசூல்</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>UZRS மொய் வசூல்</h1>
                <h2>உங்கள் கணக்கில் உள்நுழையவும்</h2>
            </div>

            <form id="loginForm" class="auth-form">
                <div class="form-group">
                    <label for="phone">கைபேசி எண்</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="password">கடவுச்சொல்</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div id="message" class="message"></div>

                <button type="submit" class="btn btn-primary">உள்நுழைய</button>
            </form>

            <div class="auth-footer">
                <p>கணக்கு இல்லையா? <a href="signup.php">இங்கே பதிவு செய்யவும்</a></p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/login.js"></script>
</body>
</html>

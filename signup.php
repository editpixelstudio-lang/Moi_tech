<!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>பதிவு - UZRS மொய் வசூல்</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/mobile.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>UZRS மொய் வசூல்</h1>
                <h2>கணக்கை உருவாக்கவும்</h2>
            </div>

            <form id="signupForm" class="auth-form">
                <div class="form-group">
                    <label for="full_name">முழு பெயர்</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="phone">கைபேசி எண்</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="password">கடவுச்சொல்</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">கடவுச்சொல்லை உறுதிப்படுத்தவும்</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div id="message" class="message"></div>

                <button type="submit" class="btn btn-primary">பதிவு செய்</button>
            </form>

            <div class="auth-footer">
                <p>ஏற்கனவே கணக்கு உள்ளதா? <a href="login.php">இங்கே உள்நுழையவும்</a></p>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script src="js/signup.js"></script>
</body>
</html>

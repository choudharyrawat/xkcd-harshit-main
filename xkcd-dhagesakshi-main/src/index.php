<?php
session_start();
require_once 'functions.php';

$message = '';
$show_verification_form = false;

// Handle email submission to send verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $code = generateVerificationCode();
        $_SESSION['verification_code'] = $code;
        $_SESSION['email'] = $email;

        sendVerificationEmail($email, $code);
        
        $message = "A verification code has been sent to your email. Please enter it below.";
        $show_verification_form = true;
    } else {
        $message = "Invalid email format.";
    }
}

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $code = trim($_POST['verification_code']);
    $email_to_verify = $_SESSION['email'] ?? null;

    if ($email_to_verify && verifyCode($email_to_verify, $code)) {
        registerEmail($email_to_verify);
        $message = "Success! You have been subscribed to daily XKCD comics.";
        $show_verification_form = false; 
    } else {
        $message = "Invalid verification code. Please try again.";
        $show_verification_form = true; // Keep the form visible on failure
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe to XKCD Comics</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        form { display: flex; flex-direction: column; gap: 10px; }
        input { padding: 8px; }
        button { padding: 10px; cursor: pointer; }
        .message { margin-bottom: 20px; padding: 10px; border-radius: 5px; }
        .success { background-color: #d4edda; color: #155724; }
        .error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>Subscribe to Daily XKCD Comics</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'Success') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="index.php" method="post" id="email-form">
        <label for="email">Enter your email to subscribe:</label>
        <input type="email" name="email" required>
        <button type="submit" id="submit-email">Submit</button>
    </form>

    <hr style="margin: 20px 0;">

    <form action="index.php" method="post" id="verification-form">
        <label for="verification_code">Enter 6-digit verification code:</label>
        <input type="text" name="verification_code" maxlength="6" required pattern="\d{6}">
        <button type="submit" id="submit-verification">Verify</button>
    </form>

</body>
</html>
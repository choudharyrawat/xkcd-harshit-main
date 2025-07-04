<?php
session_start();
require_once 'functions.php';

$message = '';

// Handle email submission to send unsubscribe confirmation code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unsubscribe_email'])) {
    $email = filter_var(trim($_POST['unsubscribe_email']), FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $file = __DIR__ . '/registered_emails.txt';
        $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
        
        if (in_array($email, $emails)) {
            $code = generateVerificationCode();
            $_SESSION['verification_code'] = $code;
            $_SESSION['email'] = $email;
            
            sendUnsubscribeConfirmationEmail($email, $code);
            $message = "A confirmation code has been sent to your email. Please enter it below to unsubscribe.";
        } else {
            $message = "This email is not subscribed.";
        }
    } else {
        $message = "Invalid email format.";
    }
}

// Handle verification code submission to finalize unsubscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    $code = trim($_POST['verification_code']);
    $email_to_verify = $_SESSION['email'] ?? null;

    if ($email_to_verify && verifyCode($email_to_verify, $code)) {
        unsubscribeEmail($email_to_verify);
        $message = "You have been successfully unsubscribed.";
    } else {
        $message = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe from XKCD Comics</title>
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
    <h1>Unsubscribe from Daily XKCD Comics</h1>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="unsubscribe.php" method="post" id="unsubscribe-email-form">
        <label for="unsubscribe_email">Enter your email to unsubscribe:</label>
        <input type="email" name="unsubscribe_email" required>
        <button type="submit" id="submit-unsubscribe">Unsubscribe</button>
    </form>

    <hr style="margin: 20px 0;">

    <form action="unsubscribe.php" method="post" id="unsubscribe-verification-form">
        <label for="verification_code">Enter 6-digit confirmation code:</label>
        <input type="text" name="verification_code" maxlength="6" required pattern="\d{6}">
        <button type="submit" id="submit-verification">Verify</button>
    </form>
</body>
</html>
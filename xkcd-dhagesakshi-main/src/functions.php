<?php

/**
 * Generates a 6-digit numeric verification code.
 *
 * @return int The generated 6-digit code.
 */
function generateVerificationCode() {
    return rand(100000, 999999);
}

/**
 * Registers a verified email by saving it to the database file.
 * It ensures that no duplicate emails are added.
 *
 * @param string $email The email address to register.
 */
function registerEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $email = trim($email);
    // Ensure the file exists and is readable before proceeding
    $emails = file_exists($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    // Add email only if it's not already in the list
    if (!in_array($email, $emails)) {
        file_put_contents($file, $email . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Unsubscribes an email by removing it from the database file.
 *
 * @param string $email The email address to unsubscribe.
 */
function unsubscribeEmail($email) {
    $file = __DIR__ . '/registered_emails.txt';
    $email_to_remove = trim($email);

    if (!file_exists($file)) {
        return; // File doesn't exist, nothing to do.
    }

    $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    // Filter out the email to be removed
    $remaining_emails = array_filter($emails, function($e) use ($email_to_remove) {
        return trim($e) !== $email_to_remove;
    });

    // Write the updated list back to the file
    file_put_contents($file, implode(PHP_EOL, $remaining_emails) . PHP_EOL, LOCK_EX);
}

/**
 * Sends a verification email to the user.
 *
 * @param string $email The recipient's email address.
 * @param int|string $code The verification code to send.
 */
function sendVerificationEmail($email, $code) {
    $subject = 'Your Verification Code';
    $body = '<p>Your verification code is: <strong>' . $code . '</strong></p>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=iso-8859-1',
        'From: XKCD Comics <no-reply@example.com>'
    ];

    mail($email, $subject, $body, implode("\r\n", $headers));
}

/**
 * Sends an unsubscribe confirmation email.
 *
 * @param string $email The recipient's email address.
 * @param int|string $code The confirmation code.
 */
function sendUnsubscribeConfirmationEmail($email, $code) {
    $subject = 'Confirm Un-subscription';
    $body = '<p>To confirm un-subscription, use this code: <strong>' . $code . '</strong></p>';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=iso-8859-1',
        'From: XKCD Comics <no-reply@example.com>'
    ];
    mail($email, $subject, $body, implode("\r\n", $headers));
}

/**
 * Verifies if the provided code matches the one stored in the session.
 *
 * @param string $email The email associated with the code.
 * @param int|string $code The code provided by the user.
 * @return bool True if the code is valid, false otherwise.
 */
function verifyCode($email, $code) {
    if (isset($_SESSION['verification_code']) && isset($_SESSION['email'])) {
        if ($_SESSION['email'] === $email && (string)$_SESSION['verification_code'] === (string)$code) {
            // Clear the session variables after successful verification
            unset($_SESSION['verification_code']);
            unset($_SESSION['email']);
            return true;
        }
    }
    return false;
}

/**
 * Fetches the latest XKCD comic ID, then gets a random comic and formats it as HTML.
 *
 * @return string|false The HTML-formatted comic data, or false on failure.
 */
function fetchAndFormatXKCDData() {
    // First, get the latest comic to find the max comic ID
    $latest_comic_url = 'https://xkcd.com/info.0.json';
    $json_latest = @file_get_contents($latest_comic_url);
    if ($json_latest === false) {
        return false; // Failed to fetch latest comic data
    }
    $latest_data = json_decode($json_latest);
    $max_comic_id = $latest_data->num;

    // Get a random comic ID
    $random_comic_id = rand(1, $max_comic_id);
    $random_comic_url = "https://xkcd.com/{$random_comic_id}/info.0.json";

    // Fetch the random comic data
    $json_comic = @file_get_contents($random_comic_url);
    if ($json_comic === false) {
        return false; // Failed to fetch random comic data
    }

    $data = json_decode($json_comic);

    // Get the unsubscribe link. This assumes the script is in a web-accessible directory.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $unsubscribe_url = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']) . '/unsubscribe.php';
    
    // Format as HTML
    $html = <<<HTML
    <h2>XKCD Comic</h2>
    <h3>{$data->safe_title}</h3>
    <img src="{$data->img}" alt="{$data->alt}">
    <p><a href="{$unsubscribe_url}" id="unsubscribe-button">Unsubscribe</a></p>
HTML;

    return $html;
}


/**
 * Sends the latest XKCD comic to all registered subscribers.
 */
function sendXKCDUpdatesToSubscribers() {
    $file = __DIR__ . '/registered_emails.txt';
    if (!file_exists($file)) {
        return; // No subscribers yet
    }

    $subscribers = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($subscribers)) {
        return; // Subscriber list is empty
    }

    $comic_html = fetchAndFormatXKCDData();
    if ($comic_html === false) {
        return; // Could not fetch comic data
    }

    $subject = 'Your XKCD Comic';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=iso-8859-1',
        'From: XKCD Comics <no-reply@example.com>'
    ];

    foreach ($subscribers as $email) {
        mail(trim($email), $subject, $comic_html, implode("\r\n", $headers));
    }
}
?>
<?php
/**
 * save_message.php
 * Handles contact form submissions:
 *   - Validates input
 *   - Saves the message into messages.json (JSON file, no database)
 *   - Optionally sends a copy by email (if PHP mail() is available/supported)
 *
 * JSON storage MUST work even if email sending fails or is unsupported.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ---- Config --------------------------------------------------------------
$JSON_FILE    = __DIR__ . '/messages.json';
$NOTIFY_EMAIL = 'batoulanfif@gmail.com'; // copy of each message
$MAX_MESSAGES = 5000;                     // safety cap to avoid huge files

// ---- Read & validate input ----------------------------------------------
$name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($name === '') {
    echo json_encode(['ok' => false, 'error' => 'name']);
    exit;
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'email']);
    exit;
}
if ($message === '') {
    echo json_encode(['ok' => false, 'error' => 'message']);
    exit;
}

// Enforce sensible length limits
$name    = mb_substr($name, 0, 100);
$email   = mb_substr($email, 0, 254);
$message = mb_substr($message, 0, 5000);

// ---- Load existing messages ---------------------------------------------
$messages = [];
if (is_file($JSON_FILE)) {
    $raw = @file_get_contents($JSON_FILE);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $messages = $decoded;
        }
    }
}

// ---- Build new message record -------------------------------------------
$newMessage = [
    'id'      => uniqid('msg_', true),
    'name'    => $name,
    'email'   => $email,
    'message' => $message,
    'date'    => date('Y-m-d'),
    'time'    => date('H:i:s'),
    'datetime'=> date('Y-m-d H:i:s'),
];

array_unshift($messages, $newMessage); // newest first inside the file

// ---- Safety cap ----------------------------------------------------------
if (count($messages) > $MAX_MESSAGES) {
    $messages = array_slice($messages, 0, $MAX_MESSAGES);
}

// ---- Save JSON file ------------------------------------------------------
$written = false;
$json    = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Try to write; fall back to a temp file + rename for atomicity on most hosts.
$tmp = $JSON_FILE . '.tmp.' . uniqid();
if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
    if (@rename($tmp, $JSON_FILE)) {
        $written = true;
        @chmod($JSON_FILE, 0644);
    }
}
// Fallback: direct write if rename failed
if (!$written) {
    if (@file_put_contents($JSON_FILE, $json, LOCK_EX) !== false) {
        $written = true;
        @chmod($JSON_FILE, 0644);
    }
}

if (!$written) {
    echo json_encode(['ok' => false, 'error' => 'storage']);
    exit;
}

// ---- Send email copy (optional - never blocks/breaks storage) -----------
// The website works fine even if mail() is disabled by the host.
$mailSent = false;
if (function_exists('mail')) {
    $subject = 'Nouveau message via le site - ' . $name;
    $body    = "Vous avez recu un nouveau message via le formulaire de contact :\n\n"
             . "Nom     : " . $name . "\n"
             . "Email   : " . $email . "\n"
             . "Date    : " . date('Y-m-d H:i:s') . "\n\n"
             . "Message :\n" . $message . "\n";
    $headers = "From: no-reply@" . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost') . "\r\n"
             . "Reply-To: " . $email . "\r\n"
             . "Content-Type: text/plain; charset=utf-8\r\n";

    // @ so a failed mail() call never triggers a warning / breaks the flow.
    $mailSent = @mail($NOTIFY_EMAIL, $subject, $body, $headers);
}

// ---- Respond -------------------------------------------------------------
echo json_encode([
    'ok'      => true,
    'mail'    => $mailSent, // informative only
    'message' => 'Votre message a bien ete enregistre.'
]);

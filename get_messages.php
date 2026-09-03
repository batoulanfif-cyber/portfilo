<?php
/**
 * get_messages.php
 * Returns all messages from messages.json, newest first.
 * Only accessible to logged-in admins (PHP session).
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/session_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$JSON_FILE = __DIR__ . '/messages.json';
$messages  = [];

if (is_file($JSON_FILE)) {
    $raw = @file_get_contents($JSON_FILE);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $messages = $decoded;
        }
    }
}

echo json_encode(['ok' => true, 'messages' => $messages]);

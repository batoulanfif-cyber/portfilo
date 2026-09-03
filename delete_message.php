<?php
/**
 * delete_message.php
 * Deletes a single message from messages.json by its id.
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

$id    = isset($_POST['id']) ? trim($_POST['id']) : '';
$JSON_FILE = __DIR__ . '/messages.json';

if ($id === '' || !is_file($JSON_FILE)) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$raw = @file_get_contents($JSON_FILE);
if ($raw === false) {
    echo json_encode(['ok' => false, 'error' => 'read_failed']);
    exit;
}

$messages = json_decode($raw, true);
if (!is_array($messages)) {
    $messages = [];
}

$found = false;
foreach ($messages as $k => $m) {
    if ((isset($m['id']) && $m['id'] === $id) || (string)$k === (string)$id) {
        unset($messages[$k]);
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$messages = array_values($messages); // re-index
$json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$tmp = $JSON_FILE . '.tmp.' . uniqid();
$written = false;
if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
    if (@rename($tmp, $JSON_FILE)) {
        $written = true;
        @chmod($JSON_FILE, 0644);
    }
}
if (!$written) {
    if (@file_put_contents($JSON_FILE, $json, LOCK_EX) !== false) {
        $written = true;
    }
}

if (!$written) {
    echo json_encode(['ok' => false, 'error' => 'write_failed']);
    exit;
}

echo json_encode(['ok' => true]);

<?php
/**
 * delete_project.php
 * Deletes a single project from projects.json by its id.
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

$input   = json_decode(file_get_contents('php://input'), true);
$id      = isset($input['id']) ? trim($input['id']) : '';
$JSON_FILE = __DIR__ . '/projects.json';

if ($id === '' || !is_file($JSON_FILE)) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$raw = @file_get_contents($JSON_FILE);
if ($raw === false) {
    echo json_encode(['ok' => false, 'error' => 'read_failed']);
    exit;
}

$projects = json_decode($raw, true);
if (!is_array($projects)) {
    $projects = [];
}

$found = false;
foreach ($projects as $k => $p) {
    if (isset($p['id']) && $p['id'] === $id) {
        unset($projects[$k]);
        $found = true;
        break;
    }
}

if (!$found && empty($projects)) {
    /* This id might be one of the built-in defaults, which only exist
     * virtually (from default_projects.php) until they're persisted.
     * Seed them in so deleting one works and the other two survive. */
    $projects = require __DIR__ . '/default_projects.php';
    foreach ($projects as $k => $p) {
        if (isset($p['id']) && $p['id'] === $id) {
            unset($projects[$k]);
            $found = true;
            break;
        }
    }
}

if (!$found) {
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$projects = array_values($projects);
$json = json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

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

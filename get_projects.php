<?php
/**
 * get_projects.php
 * Public endpoint – returns all portfolio projects.
 * No authentication required (visitors must see the projects).
 * If projects.json is empty/missing the built-in defaults are returned.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$JSON_FILE = __DIR__ . '/projects.json';
$projects  = [];

if (is_file($JSON_FILE)) {
    $raw = @file_get_contents($JSON_FILE);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $projects = $decoded;
        }
    }
}

/* Return the built-in defaults when no projects exist yet. */
if (empty($projects)) {
    $projects = require __DIR__ . '/default_projects.php';
}

echo json_encode(['ok' => true, 'projects' => $projects]);

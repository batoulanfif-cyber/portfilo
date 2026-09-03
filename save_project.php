<?php
/**
 * save_project.php
 * Creates or updates a project in projects.json.
 * Only accessible to logged-in admins (PHP session).
 *
 * Expected JSON body:
 *   { title, link?, desc?, tech?[], image? (base64), icon?, placeholderClass?, id? }
 *
 * If "id" is present → update existing project
 * If "id" is absent  → create new project
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/session_config.php';

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

/* Server-side mirror of the client's MAX_IMG_SIZE (script.js). Keeps the
 * whole JSON payload comfortably under the post_max_size most hosts allow,
 * and stops an oversized image from being silently accepted or truncated. */
$MAX_IMAGE_CHARS = 1300000; // ~1.3M chars of base64 text

$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);

if (!is_array($input)) {
    if ($rawInput === '' || $rawInput === false) {
        /* An empty body here almost always means the request was larger
         * than this server's post_max_size and PHP discarded it before
         * the script even ran - not a JSON formatting problem. */
        echo json_encode(['ok' => false, 'error' => 'empty_request']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'invalid_input']);
    }
    exit;
}

$title = isset($input['title']) ? trim($input['title']) : '';
if ($title === '') {
    echo json_encode(['ok' => false, 'error' => 'title_required']);
    exit;
}

$link              = isset($input['link'])              ? trim($input['link'])              : '';
$desc              = isset($input['desc'])              ? trim($input['desc'])              : '';
$tech              = isset($input['tech']) && is_array($input['tech']) ? $input['tech'] : [];
$image             = isset($input['image'])             ? $input['image']                   : null;
$icon              = isset($input['icon'])              ? $input['icon']                    : 'fas fa-code';
$placeholderClass  = isset($input['placeholderClass'])  ? $input['placeholderClass']        : 'placeholder-tech';
$id                = isset($input['id'])                ? trim($input['id'])                : '';

if ($image !== null && strlen((string)$image) > $MAX_IMAGE_CHARS) {
    echo json_encode(['ok' => false, 'error' => 'image_too_large']);
    exit;
}

/* ---- Load current projects --------------------------------------------- */
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

/* ---- Create or Update -------------------------------------------------- */
if ($id !== '') {
    /* Update. A project shown to the admin can be one of the built-in
     * defaults from default_projects.php, which - by design - only ever
     * live in projects.json once the admin actually touches one of them.
     * If we don't find the id yet and the file is still empty, seed the
     * real defaults in first so the edit can apply and its two untouched
     * siblings are carried over instead of disappearing. */
    $applyUpdate = function (&$list, $targetId) use ($title, $link, $desc, $tech, $icon, $placeholderClass, $image) {
        foreach ($list as $k => $p) {
            if (isset($p['id']) && $p['id'] === $targetId) {
                $list[$k]['title']            = $title;
                $list[$k]['link']             = $link;
                $list[$k]['desc']             = $desc;
                $list[$k]['tech']             = $tech;
                $list[$k]['icon']             = $icon;
                $list[$k]['placeholderClass'] = $placeholderClass;
                if ($image !== null) {
                    $list[$k]['image'] = $image;
                }
                return true;
            }
        }
        return false;
    };

    $found = $applyUpdate($projects, $id);

    if (!$found && empty($projects)) {
        $projects = require __DIR__ . '/default_projects.php';
        $found = $applyUpdate($projects, $id);
    }

    if (!$found) {
        /* Unknown id (shouldn't normally happen from the admin UI) -
         * persist it as a new project under the id the client already
         * had, rather than failing the save outright. */
        $projects[] = [
            'id'              => $id,
            'title'           => $title,
            'desc'            => $desc,
            'link'            => $link,
            'tech'            => $tech,
            'image'           => $image,
            'icon'            => $icon,
            'placeholderClass'=> $placeholderClass
        ];
    }
} else {
    /* Create */
    $projects[] = [
        'id'              => 'p-' . time() . '-' . bin2hex(random_bytes(4)),
        'title'           => $title,
        'desc'            => $desc,
        'link'            => $link,
        'tech'            => $tech,
        'image'           => $image,
        'icon'            => $icon,
        'placeholderClass'=> $placeholderClass
    ];
}

/* ---- Write to disk ----------------------------------------------------- */
$json  = json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$tmp   = $JSON_FILE . '.tmp.' . uniqid();
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
        @chmod($JSON_FILE, 0644);
    }
}

if (!$written) {
    echo json_encode(['ok' => false, 'error' => 'storage']);
    exit;
}

echo json_encode(['ok' => true]);

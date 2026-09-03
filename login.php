<?php
/**
 * login.php
 * Server-side admin authentication using PHP sessions (no database).
 *
 * CREDENTIALS:
 *   username: ferdaous
 *   password: the one stored as ADMIN_PASS_HASH below.
 *
 * The password is stored ONLY as a one-way bcrypt hash; it can never be
 * read back. To change it, regenerate a hash and replace ADMIN_PASS_HASH:
 *
 *   php -r "echo password_hash('NEW_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
 *
 * NOTE: login.php must itself stay private from direct public access.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/session_config.php';

$ADMIN_USER = 'ferdaous';
$ADMIN_PASS_HASH = '$2y$10$/QO8DBN/.S3rXfPOy/foXu4gS2q6szTtUIv0Ffz0VXDapBDPb1TJ6';

$user = isset($_POST['username']) ? trim($_POST['username']) : '';
$pass = isset($_POST['password']) ? (string)$_POST['password'] : '';

if ($user === $ADMIN_USER && password_verify($pass, $ADMIN_PASS_HASH)) {
    // Recover from the rare bcrypt rehash / salt_rounds bump.
    if (password_needs_rehash($ADMIN_PASS_HASH, PASSWORD_DEFAULT)) {
        // (No-op here; rehash persistence would require a writable config.)
    }
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_username']   = $user;
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'credentials']);
}

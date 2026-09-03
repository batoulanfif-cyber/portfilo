<?php
/**
 * session_config.php
 * Shared session bootstrap used by every admin-protected endpoint
 * (login.php, logout.php, save_project.php, delete_project.php,
 * get_messages.php, delete_message.php).
 *
 * WHY THIS FILE EXISTS:
 * On many shared/free PHP hosts, the default session save path (a
 * shared system tmp folder) is not reliably writable/readable by this
 * particular hosting account. When that happens, session_start()
 * still returns successfully (no visible error), but PHP is unable to
 * persist $_SESSION between requests - so every request after login
 * looks "unauthorized" again, even though the login itself succeeded.
 * From the outside this looks exactly like "the admin panel doesn't
 * work": you can log in, but saving/deleting projects or loading
 * messages silently fails right after.
 *
 * The fix is to point PHP to a private, writable folder inside this
 * project instead of relying on the host's shared default, and to
 * create that folder automatically if it doesn't exist yet.
 */

if (session_status() === PHP_SESSION_NONE) {

    $sessionDir = __DIR__ . '/data/sessions';

    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0700, true);
    }

    if (is_dir($sessionDir) && is_writable($sessionDir)) {
        session_save_path($sessionDir);
    }
    // If the custom folder isn't writable either (extremely locked-down
    // host), PHP will silently fall back to its own configured default.
    // Either way this never throws a fatal error.

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

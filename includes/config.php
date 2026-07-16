<?php
/**
 * GeoAttend – Global configuration
 * Change these values to match your local WAMP setup.
 */

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');                       // default WAMP: empty
define('DB_NAME', 'attendance_system');

// --- App ---
define('APP_NAME', 'GeoAttend');
define('APP_URL',  'http://localhost/GeoAttend');
define('SESSION_LIFETIME', 60 * 60 * 4);     // 4 hours

// --- Session bootstrap (single source of truth) ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// --- Error reporting (dev only; disable on production) ---
error_reporting(E_ALL);
ini_set('display_errors', '1');

// --- Timezone ---
date_default_timezone_set('Asia/Kolkata');

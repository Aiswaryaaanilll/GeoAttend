<?php
/**
 * GeoAttend – Authentication & role guards.
 *
 * Session shape after login:
 *   $_SESSION['user'] = [
 *     'id'    => int,
 *     'role'  => 'student' | 'teacher',
 *     'name'  => string,
 *     'email' => string,
 *   ];
 */
require_once __DIR__ . '/config.php';

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return isset($_SESSION['user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        flash_set('warning', 'Please log in to continue.');
        redirect(APP_URL . '/login.php');
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? null) !== $role) {
        http_response_code(403);
        include __DIR__ . '/../unauthorized.php';
        exit;
    }
}

function login_user(array $user, string $role): void {
    // Prevent session fixation.
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int) ($user[$role . '_id']),
        'role'  => $role,
        'name'  => $user['name'],
        'email' => $user['email'],
    ];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

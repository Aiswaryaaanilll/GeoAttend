<?php
/**
 * GeoAttend – Handle login form POST.
 * - Validates CSRF & inputs
 * - Uses prepared statements
 * - Verifies password_hash() via password_verify()
 * - Regenerates session id on success (via login_user)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/login.php');
}

// CSRF
if (!csrf_verify($_POST['csrf'] ?? null)) {
    flash_set('danger', 'Your session expired. Please try again.');
    redirect(APP_URL . '/login.php');
}

$role     = $_POST['role']     ?? '';
$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';
$remember = !empty($_POST['remember']);

// Basic validation
if (!in_array($role, ['student', 'teacher'], true) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($password) < 6) {
    flash_set('danger', 'Please enter valid credentials.');
    redirect(APP_URL . '/login.php');
}

// Table + id column per role
$table = $role === 'teacher' ? 'teachers' : 'students';
$idCol = $role . '_id';

$sql = "SELECT {$idCol}, name, email, password FROM {$table} WHERE email = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    flash_set('danger', 'Login is temporarily unavailable.');
    redirect(APP_URL . '/login.php');
}
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    // Same message for both cases → prevents user enumeration.
    flash_set('danger', 'Invalid email or password.');
    redirect(APP_URL . '/login.php');
}

// Remember-me: store non-sensitive preferences only (never the password).
if ($remember) {
    $exp = time() + 60 * 60 * 24 * 30; // 30 days
    setcookie('geoattend_role',  $role,  $exp, '/', '', false, true);
    setcookie('geoattend_email', $email, $exp, '/', '', false, true);
} else {
    setcookie('geoattend_role',  '', time() - 3600, '/');
    setcookie('geoattend_email', '', time() - 3600, '/');
}

login_user($user, $role);
flash_set('success', 'Welcome back, ' . $user['name'] . '!');
redirect(APP_URL . '/' . $role . '/dashboard.php');

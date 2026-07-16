<?php
/**
 * GeoAttend – Reusable helper functions.
 */
require_once __DIR__ . '/config.php';

/** Escape output for safe HTML rendering. */
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect helper. */
function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

/** Flash message helpers (one-shot messages across a redirect). */
function flash_set(string $type, string $message): void {
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}
function flash_all(): array {
    $out = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $out;
}

/** Basic CSRF token utilities. */
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}
function csrf_verify(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
}

/** Haversine distance between two lat/lng points in meters. */
function haversine_m(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371000.0; // Earth radius (m)
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    return 2 * $R * asin(sqrt($a));
}

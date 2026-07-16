<?php
/**
 * Endpoint: mark attendance for a live session.
 * Method: POST (JSON)  Body: { session_id, latitude, longitude, accuracy? }
 * Returns JSON: { ok: bool, message: string, distance_m?: number }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in() || ($_SESSION['user']['role'] ?? '') !== 'student') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Please log in as a student.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed.']);
    exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true) ?: [];
$sessionId = isset($in['session_id']) ? (int) $in['session_id'] : 0;
$lat = isset($in['latitude'])  ? (float) $in['latitude']  : null;
$lng = isset($in['longitude']) ? (float) $in['longitude'] : null;

if (!$sessionId || $lat === null || $lng === null) {
    echo json_encode(['ok' => false, 'message' => 'Missing session or location data.']);
    exit;
}

$studentId = (int) $_SESSION['user']['id'];

// Load session + classroom
$stmt = $mysqli->prepare("
    SELECT s.session_id, s.status, s.start_time, s.end_time,
           c.latitude, c.longitude, c.radius_m
      FROM attendance_sessions s
      JOIN classrooms c ON c.classroom_id = s.classroom_id
     WHERE s.session_id = ?
");
$stmt->bind_param('i', $sessionId);
$stmt->execute();
$sess = $stmt->get_result()->fetch_assoc();

if (!$sess) {
    echo json_encode(['ok' => false, 'message' => 'Session not found.']);
    exit;
}
if ($sess['status'] !== 'active') {
    echo json_encode(['ok' => false, 'message' => 'This session is closed.']);
    exit;
}
$now = time();
if ($now < strtotime($sess['start_time']) || $now > strtotime($sess['end_time'])) {
    echo json_encode(['ok' => false, 'message' => 'This session is not currently running.']);
    exit;
}

// Duplicate prevention
$dup = $mysqli->prepare(
    "SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?"
);
$dup->bind_param('ii', $sessionId, $studentId);
$dup->execute();
if ($dup->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'message' => 'You already marked attendance for this session.']);
    exit;
}

// Geofence check
$distance = haversine_m(
    (float) $sess['latitude'], (float) $sess['longitude'], $lat, $lng
);
$radius = (int) $sess['radius_m'];
if ($distance > $radius) {
    echo json_encode([
        'ok' => false,
        'message' => sprintf('You are %d m from the classroom (allowed: %d m).',
                              (int) $distance, $radius),
        'distance_m' => (int) $distance,
    ]);
    exit;
}

// Insert
$ins = $mysqli->prepare("
    INSERT INTO attendance (session_id, student_id, marked_at, latitude, longitude, status)
    VALUES (?, ?, NOW(), ?, ?, 'present')
");
$ins->bind_param('iidd', $sessionId, $studentId, $lat, $lng);
if (!$ins->execute()) {
    echo json_encode(['ok' => false, 'message' => 'Could not save attendance. Try again.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'message' => 'Attendance marked successfully.',
    'distance_m' => (int) $distance,
]);

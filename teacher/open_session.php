<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
    flash_set('error', 'Invalid session token. Please try again.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

$user        = current_user();
$teacher_id  = (int)$user['id'];
$subject_id  = (int)($_POST['subject_id']   ?? 0);
$classroom_id= (int)($_POST['classroom_id'] ?? 0);
$radius_m    = (int)($_POST['radius_m']     ?? 30);
$duration    = (int)($_POST['duration_min'] ?? 15);

if ($subject_id <= 0 || $classroom_id <= 0) {
    flash_set('error', 'Please select a subject and classroom.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

if ($duration < 1 || $duration > 180) {
    flash_set('error', 'Duration must be between 1 and 180 minutes.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

// Ownership: verify that this subject belongs to the logged-in teacher
$stmt = $mysqli->prepare(
    "SELECT subject_id
     FROM subjects
     WHERE subject_id = ? AND teacher_id = ?
     LIMIT 1"
);

$stmt->bind_param('ii', $subject_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    flash_set('danger', 'You are not assigned to this subject.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

$stmt->close();

// Classroom must be free (no active session there)
$stmt = $mysqli->prepare(
    "SELECT session_id FROM attendance_sessions
     WHERE classroom_id = ? AND status = 'active' 
     AND NOW() BETWEEN start_time AND end_time
     LIMIT 1"
);
$stmt->bind_param('i', $classroom_id);
$stmt->execute();
if ($stmt->get_result()->fetch_row()) {
    $stmt->close();
    flash_set('error', 'That classroom already has an active session.');
    redirect(APP_URL . '/teacher/dashboard.php');
}
$stmt->close();

$start = date('Y-m-d H:i:s');
$end   = date('Y-m-d H:i:s', time() + $duration * 60);

$stmt = $mysqli->prepare(
    "INSERT INTO attendance_sessions
    (teacher_id, subject_id, classroom_id, start_time, end_time, status)
    VALUES (?, ?, ?, ?, ?, 'active')"
);

$stmt->bind_param(
    "iiiss",
    $teacher_id,
    $subject_id,
    $classroom_id,
    $start,
    $end
);
if ($stmt->execute()) {
    flash_set('success', 'Attendance session opened.');
} else {
    flash_set('error', 'Could not open session: ' . e($stmt->error));
}
$stmt->close();

redirect(APP_URL . '/teacher/dashboard.php');

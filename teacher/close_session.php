<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/teacher/dashboard.php');
}

if (!csrf_verify($_POST['csrf'] ?? '')) {
    flash_set('error', 'Invalid session token. Please try again.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

$user       = current_user();
$teacher_id = (int)$user['id'];
$session_id = (int)($_POST['session_id'] ?? 0);

if ($session_id <= 0) {
    flash_set('error', 'Missing session id.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

// Verify session belongs to this teacher and is active
$stmt = $mysqli->prepare("
    SELECT session_id, status
    FROM attendance_sessions
    WHERE session_id = ? AND teacher_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $session_id, $teacher_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    flash_set('error', 'Session not found or not yours.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

if ($row['status'] !== 'active') {
    flash_set('error', 'Session is already closed.');
    redirect(APP_URL . '/teacher/dashboard.php');
}

// Close the session
$now = date('Y-m-d H:i:s');

$stmt = $mysqli->prepare("
    UPDATE attendance_sessions
    SET status = 'closed',
        end_time = ?
    WHERE session_id = ?
      AND teacher_id = ?
      AND status = 'active'
");
$stmt->bind_param('sii', $now, $session_id, $teacher_id);

if ($stmt->execute() && $stmt->affected_rows === 1) {

    $stmt->close();

    // Automatically mark all remaining students as absent
    $absent = $mysqli->prepare("
        INSERT INTO attendance
            (session_id, student_id, marked_at, status)
        SELECT
            ?, s.student_id, NOW(), 'absent'
        FROM students s
        WHERE NOT EXISTS (
            SELECT 1
            FROM attendance a
            WHERE a.session_id = ?
              AND a.student_id = s.student_id
        )
    ");

    $absent->bind_param('ii', $session_id, $session_id);
    $absent->execute();
    $absent->close();

    flash_set('success', 'Session closed successfully. Absent students have been recorded.');

} else {

    $stmt->close();

    flash_set('error', 'Could not close session.');
}

redirect(APP_URL . '/teacher/dashboard.php');
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');
$sid = (int) $_SESSION['user']['id'];

$rows = [];
$stmt = $mysqli->prepare("
    SELECT a.marked_at, a.status, a.latitude, a.longitude,
           sub.subject_code, sub.subject_name,
           c.room_name, c.building,
           t.name AS teacher_name
      FROM attendance a
      JOIN attendance_sessions s ON s.session_id  = a.session_id
      JOIN subjects   sub ON sub.subject_id   = s.subject_id
      JOIN classrooms c   ON c.classroom_id   = s.classroom_id
      JOIN teachers   t   ON t.teacher_id     = s.teacher_id
     WHERE a.student_id = ?
  ORDER BY a.marked_at DESC
");
$stmt->bind_param('i', $sid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Attendance';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>My attendance</h1>
    <p>All classes you've marked attendance for.</p>
  </div>
</div>

<?php if (empty($rows)): ?>
  <div class="empty">
    <h3>No attendance records yet</h3>
    <p>Once you mark attendance in a live session, it will show up here.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Date &amp; time</th>
          <th>Subject</th>
          <th>Teacher</th>
          <th>Classroom</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= e(date('d M Y · h:i A', strtotime($r['marked_at']))) ?></td>
            <td>
              <div style="font-weight:600"><?= e($r['subject_name']) ?></div>
              <div style="font-size:12px;color:var(--text-muted)"><?= e($r['subject_code']) ?></div>
            </td>
            <td><?= e($r['teacher_name']) ?></td>
            <td><?= e($r['room_name']) ?><?= $r['building'] ? ', ' . e($r['building']) : '' ?></td>
            <td><span class="pill <?= $r['status'] === 'present' ? 'present' : 'absent' ?>"><?= e($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

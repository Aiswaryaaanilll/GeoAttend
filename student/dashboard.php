<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');
$student = current_user();
$sid = (int) $student['id'];

// ---- Stats ----------------------------------------------------------------
$stats = ['total' => 0, 'present' => 0, 'absent' => 0, 'percent' => 0];

$q = $mysqli->prepare(
    "SELECT COUNT(*) AS total,
            SUM(status='present') AS present,
            SUM(status='absent')  AS absent
       FROM attendance WHERE student_id = ?"
);
$q->bind_param('i', $sid);
$q->execute();
$row = $q->get_result()->fetch_assoc();
$stats['total']   = (int) $row['total'];
$stats['present'] = (int) $row['present'];
$stats['absent']  = (int) $row['absent'];
$stats['percent'] = $stats['total'] ? round(($stats['present'] / $stats['total']) * 100) : 0;

// Live sessions: active + within window + not yet marked by this student.
$sessions = $mysqli->prepare("
    SELECT s.session_id, s.start_time, s.end_time,
           sub.subject_code, sub.subject_name,
           c.room_name, c.building, c.latitude, c.longitude, c.radius_m,
           t.name AS teacher_name,
           a.attendance_id
      FROM attendance_sessions s
      JOIN subjects   sub ON sub.subject_id   = s.subject_id
      JOIN classrooms c   ON c.classroom_id   = s.classroom_id
      JOIN teachers   t   ON t.teacher_id     = s.teacher_id
 LEFT JOIN attendance a   ON a.session_id = s.session_id AND a.student_id = ?
     WHERE s.status = 'active'
       AND NOW() BETWEEN s.start_time AND s.end_time
  ORDER BY s.start_time DESC
");
$sessions->bind_param('i', $sid);
$sessions->execute();
$activeSessions = $sessions->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Student Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Hi, <?= e(explode(' ', $student['name'])[0]) ?> 👋</h1>
    <p>Here's your attendance snapshot and live class sessions.</p>
  </div>
</div>

<section class="stats-grid">
  <div class="stat">
    <div class="label">Classes attended</div>
    <div class="value"><?= (int) $stats['present'] ?></div>
    <div class="trend">out of <?= (int) $stats['total'] ?> total</div>
  </div>
  <div class="stat good">
    <div class="label">Attendance %</div>
    <div class="value"><?= (int) $stats['percent'] ?>%</div>
    <div class="trend">Cumulative</div>
  </div>
  <div class="stat bad">
    <div class="label">Missed</div>
    <div class="value"><?= (int) $stats['absent'] ?></div>
    <div class="trend">Marked absent</div>
  </div>
  <div class="stat">
    <div class="label">Live now</div>
    <div class="value"><?= count($activeSessions) ?></div>
    <div class="trend">Active class sessions</div>
  </div>
</section>

<div class="section-title">
  <h2>Live sessions</h2>
  <span class="muted">Mark attendance from inside the classroom.</span>
</div>

<?php if (empty($activeSessions)): ?>
  <div class="empty">
    <h3>No live sessions right now</h3>
    <p>When your teacher starts a class, it will appear here.</p>
  </div>
<?php else: ?>
  <div class="session-grid">
    <?php foreach ($activeSessions as $s): ?>
      <article class="session-card"
               data-session="<?= (int) $s['session_id'] ?>"
               data-lat="<?= e((string) $s['latitude']) ?>"
               data-lng="<?= e((string) $s['longitude']) ?>"
               data-radius="<?= (int) $s['radius_m'] ?>">
        <div class="top">
          <div>
            <div class="code"><?= e($s['subject_code']) ?></div>
            <h3><?= e($s['subject_name']) ?></h3>
            <div class="meta">
              <span><?= e($s['room_name']) ?><?= $s['building'] ? ', ' . e($s['building']) : '' ?></span>
              <span><?= e($s['teacher_name']) ?></span>
              <span>Ends <?= e(date('h:i A', strtotime($s['end_time']))) ?></span>
            </div>
          </div>
          <span class="pill live">Live</span>
        </div>

        <div class="actions">
          <?php if ($s['attendance_id']): ?>
            <span class="marked">✓ Attendance marked</span>
          <?php else: ?>
            <button class="btn btn-primary mark-btn" type="button">Mark attendance</button>
            <span class="geo-status" data-role="status"></span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="<?= e(APP_URL) ?>/assets/js/mark_attendance.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * GeoAttend – Teacher Dashboard
 * Shows today's sessions, totals, and the "Open new session" form.
 * Follows the same architecture as student/dashboard.php.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$teacher = current_user();
$tid     = (int) $teacher['id'];
$csrf    = csrf_token();

// ---- Stats ----------------------------------------------------------------
$stats = [
    'total_students'   => 0,
    'sessions_today'   => 0,
    'active_sessions'  => 0,
    'attendance_today' => 0,
];

// Total students in the system (all teachers see the same roster).
if ($r = $mysqli->query("SELECT COUNT(*) AS c FROM students")) {
    $stats['total_students'] = (int) $r->fetch_assoc()['c'];
}

// Sessions THIS teacher started today.
$q = $mysqli->prepare(
    "SELECT COUNT(*) AS c
       FROM attendance_sessions
      WHERE teacher_id = ? AND DATE(start_time) = CURDATE()"
);
$q->bind_param('i', $tid);
$q->execute();
$stats['sessions_today'] = (int) $q->get_result()->fetch_assoc()['c'];
$q->close();

// Active sessions owned by this teacher.
$q = $mysqli->prepare(
    "SELECT COUNT(*) AS c
       FROM attendance_sessions
      WHERE teacher_id = ? AND status = 'active'
        AND NOW() BETWEEN start_time AND end_time"
);
$q->bind_param('i', $tid);
$q->execute();
$stats['active_sessions'] = (int) $q->get_result()->fetch_assoc()['c'];
$q->close();

// Attendance marks recorded today across this teacher's sessions.
$q = $mysqli->prepare(
    "SELECT COUNT(*) AS c
       FROM attendance a
       JOIN attendance_sessions s ON s.session_id = a.session_id
      WHERE s.teacher_id = ? AND DATE(a.marked_at) = CURDATE()"
);
$q->bind_param('i', $tid);
$q->execute();
$stats['attendance_today'] = (int) $q->get_result()->fetch_assoc()['c'];
$q->close();

// ---- Subjects & classrooms for the "Open session" form -------------------
$subjects = [];
$q = $mysqli->prepare(
    "SELECT subject_id, subject_code, subject_name
       FROM subjects WHERE teacher_id = ? ORDER BY subject_code"
);
$q->bind_param('i', $tid);
$q->execute();
$subjects = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$classrooms = [];
if ($r = $mysqli->query(
    "SELECT classroom_id, room_name, building FROM classrooms ORDER BY room_name"
)) {
    $classrooms = $r->fetch_all(MYSQLI_ASSOC);
}

// Which classrooms already have an active session (any teacher)?
$busyRooms = [];
if ($r = $mysqli->query(
    "SELECT classroom_id FROM attendance_sessions
      WHERE status = 'active' AND NOW() BETWEEN start_time AND end_time"
)) {
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $row) {
        $busyRooms[(int) $row['classroom_id']] = true;
    }
}

// ---- Today's sessions (active + closed) for this teacher -----------------
$q = $mysqli->prepare("
    SELECT s.session_id, s.start_time, s.end_time, s.status,
           sub.subject_code, sub.subject_name,
           c.room_name, c.building,
           (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.session_id) AS marked
      FROM attendance_sessions s
      JOIN subjects   sub ON sub.subject_id   = s.subject_id
      JOIN classrooms c   ON c.classroom_id   = s.classroom_id
     WHERE s.teacher_id = ?
       AND DATE(s.start_time) = CURDATE()
  ORDER BY s.start_time DESC
");
$q->bind_param('i', $tid);
$q->execute();
$todaysSessions = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$pageTitle = 'Teacher Dashboard';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Welcome, <?= e(explode(' ', $teacher['name'])[0]) ?> 👋</h1>
    <p>Manage attendance sessions and see today's activity at a glance.</p>
  </div>
</div>

<section class="stats-grid">
  <div class="stat">
    <div class="label">Total students</div>
    <div class="value"><?= (int) $stats['total_students'] ?></div>
    <div class="trend">Enrolled in the system</div>
  </div>
  <div class="stat good">
    <div class="label">Active sessions</div>
    <div class="value"><?= (int) $stats['active_sessions'] ?></div>
    <div class="trend">Yours, running now</div>
  </div>
  <div class="stat">
    <div class="label">Sessions today</div>
    <div class="value"><?= (int) $stats['sessions_today'] ?></div>
    <div class="trend">Opened by you</div>
  </div>
  <div class="stat">
    <div class="label">Marks today</div>
    <div class="value"><?= (int) $stats['attendance_today'] ?></div>
    <div class="trend">Across your sessions</div>
  </div>
</section>

<div class="section-title">
  <h2>Open a new attendance session</h2>
  <span class="muted">One active session per classroom at a time.</span>
</div>

<?php if (empty($subjects)): ?>
  <div class="empty">
    <h3>No subjects assigned</h3>
    <p>Ask an admin to link subjects to your teacher account.</p>
  </div>
<?php else: ?>
  <form class="card session-form" method="post"
        action="<?= e(APP_URL) ?>/teacher/open_session.php">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />

    <div class="form-row">
      <label for="subject_id">Subject</label>
      <select id="subject_id" name="subject_id" required>
        <?php foreach ($subjects as $s): ?>
          <option value="<?= (int) $s['subject_id'] ?>">
            <?= e($s['subject_code']) ?> — <?= e($s['subject_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <label for="classroom_id">Classroom</label>
      <select id="classroom_id" name="classroom_id" required>
        <?php foreach ($classrooms as $c):
          $busy = isset($busyRooms[(int) $c['classroom_id']]); ?>
          <option value="<?= (int) $c['classroom_id'] ?>" <?= $busy ? 'disabled' : '' ?>>
            <?= e($c['room_name']) ?><?= $c['building'] ? ' — ' . e($c['building']) : '' ?>
            <?= $busy ? ' (busy)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <label for="duration">Duration</label>
      <select id="duration" name="duration_min" required>
        <option value="15">15 minutes</option>
        <option value="30" selected>30 minutes</option>
        <option value="45">45 minutes</option>
        <option value="60">60 minutes</option>
        <option value="90">90 minutes</option>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Open attendance</button>
    </div>
  </form>
<?php endif; ?>

<div class="section-title">
  <h2>Today's sessions</h2>
  <span class="muted">Open sessions can be closed manually.</span>
</div>

<?php if (empty($todaysSessions)): ?>
  <div class="empty">
    <h3>Nothing yet today</h3>
    <p>Open a session above to start taking attendance.</p>
  </div>
<?php else: ?>
  <div class="session-grid">
    <?php foreach ($todaysSessions as $s):
      $isActive = $s['status'] === 'active'
                  && strtotime($s['end_time']) >= time(); ?>
      <article class="session-card">
        <div class="top">
          <div>
            <div class="code"><?= e($s['subject_code']) ?></div>
            <h3><?= e($s['subject_name']) ?></h3>
            <div class="meta">
              <span><?= e($s['room_name']) ?><?= $s['building'] ? ', ' . e($s['building']) : '' ?></span>
              <span><?= e(date('h:i A', strtotime($s['start_time']))) ?>
                – <?= e(date('h:i A', strtotime($s['end_time']))) ?></span>
              <span><?= (int) $s['marked'] ?> marked</span>
            </div>
          </div>
          <span class="pill <?= $isActive ? 'live' : '' ?>">
            <?= $isActive ? 'Live' : 'Closed' ?>
          </span>
        </div>

        <?php if ($isActive): ?>
          <div class="actions">
           
          </div>
          <?php if ($isActive): ?>
    <div class="actions">
        <form class="ga-close-form"
              method="post"
              action="<?= e(APP_URL) ?>/teacher/close_session.php">

            <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />

            <input type="hidden"
                   name="session_id"
                   value="<?= (int)$s['session_id'] ?>">

            <button type="submit" class="btn btn-ghost">
                Close now
            </button>

        </form>
    </div>
<?php endif; ?>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script src="<?= e(APP_URL) ?>/assets/js/teacher_session.js" defer></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

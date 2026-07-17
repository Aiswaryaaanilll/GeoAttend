<?php
/**
 * GeoAttend – Session details
 * Lists every student vs. this session: present/absent, marked time, and
 * that student's overall attendance %.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$teacher = current_user();
$tid     = (int) $teacher['id'];
$csrf    = csrf_token();

$session_id = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
if ($session_id <= 0) {
    flash_set('error', 'Missing session id.');
    redirect(APP_URL . '/teacher/sessions.php');
}

// Ownership + session info
$q = $mysqli->prepare("
    SELECT s.session_id, s.start_time, s.end_time, s.status,
           sub.subject_id, sub.subject_code, sub.subject_name,
           c.room_name, c.building
      FROM attendance_sessions s
      JOIN subjects   sub ON sub.subject_id   = s.subject_id
      JOIN classrooms c   ON c.classroom_id   = s.classroom_id
     WHERE s.session_id = ? AND s.teacher_id = ?
     LIMIT 1
");
$q->bind_param('ii', $session_id, $tid);
$q->execute();
$session = $q->get_result()->fetch_assoc();
$q->close();

if (!$session) {
    flash_set('error', 'Session not found or not yours.');
    redirect(APP_URL . '/teacher/sessions.php');
}
$isActive = $session['status'] === 'active' && strtotime($session['end_time']) >= time();

// Total sessions the teacher has run for THIS subject (denominator for %).
$totalSubjectSessions = 0;
$q = $mysqli->prepare(
    "SELECT COUNT(*) AS c FROM attendance_sessions
      WHERE teacher_id = ? AND subject_id = ?"
);
$q->bind_param('ii', $tid, $session['subject_id']);
$q->execute();
$totalSubjectSessions = (int) $q->get_result()->fetch_assoc()['c'];
$q->close();

// All students, LEFT JOIN this session's attendance,
// plus a computed % = present marks in this subject / total sessions of this subject.
$q = $mysqli->prepare("
    SELECT st.student_id, st.name, st.roll_no, st.branch, st.semester,
           a.marked_at, a.status,
           (SELECT COUNT(*)
              FROM attendance ax
              JOIN attendance_sessions sx ON sx.session_id = ax.session_id
             WHERE ax.student_id = st.student_id
               AND sx.teacher_id = ?
               AND sx.subject_id = ?
               AND ax.status = 'present') AS present_in_subject
      FROM students st
      LEFT JOIN attendance a
             ON a.session_id = ? AND a.student_id = st.student_id
  ORDER BY st.roll_no ASC
");
$q->bind_param('iii', $tid, $session['subject_id'], $session_id);
$q->execute();
$students = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$presentNow = 0;
foreach ($students as $s) {
    if ($s['status'] === 'present') { $presentNow++; }
}
$absentNow = max(0, count($students) - $presentNow);

$pageTitle = 'Session #' . $session_id;
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1><?= e($session['subject_name']) ?>
      <span class="pill <?= $isActive ? 'live' : '' ?>">
        <?= $isActive ? 'Live' : e(ucfirst($session['status'])) ?>
      </span>
    </h1>
    <p>
      <?= e($session['subject_code']) ?> ·
      <?= e($session['room_name']) ?><?= $session['building'] ? ', ' . e($session['building']) : '' ?> ·
      <?= e(date('d M Y · h:i A', strtotime($session['start_time']))) ?>
      – <?= e(date('h:i A', strtotime($session['end_time']))) ?>
    </p>
  </div>

  <?php if ($isActive): ?>
    <form method="post" action="<?= e(APP_URL) ?>/teacher/close_session.php">
      <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="session_id" value="<?= (int) $session_id ?>">
      <button type="submit" class="btn btn-ghost">Close now</button>
    </form>
  <?php endif; ?>
</div>

<section class="stats-grid">
  <div class="stat good">
    <div class="label">Present</div>
    <div class="value"><?= (int) $presentNow ?></div>
  </div>
  <div class="stat">
    <div class="label">Absent</div>
    <div class="value"><?= (int) $absentNow ?></div>
  </div>
  <div class="stat">
    <div class="label">Roster</div>
    <div class="value"><?= count($students) ?></div>
  </div>
  <div class="stat">
    <div class="label">Attendance %</div>
    <div class="value"><?= count($students) > 0 ? round($presentNow / count($students) * 100, 1) : 0 ?>%</div>
  </div>
</section>

<div class="section-title">
  <h2>Students</h2>
  <span class="muted">Attendance % is for this subject across all your sessions.</span>
</div>

<?php if (empty($students)): ?>
  <div class="empty"><h3>No students in the system</h3></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Roll no</th>
          <th>Name</th>
          <th>Marked at</th>
          <th>Status</th>
          <th>Attendance %</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($students as $st):
        $pct = $totalSubjectSessions > 0
             ? round(((int) $st['present_in_subject']) / $totalSubjectSessions * 100, 1)
             : 0.0;
        $isPresent = $st['status'] === 'present';
      ?>
        <tr>
          <td><?= e($st['roll_no']) ?></td>
          <td>
            <div style="font-weight:600"><?= e($st['name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted)">
              <?= e($st['branch'] ?? '') ?><?= $st['semester'] ? ' · Sem ' . (int) $st['semester'] : '' ?>
            </div>
          </td>
          <td><?= $st['marked_at'] ? e(date('h:i A', strtotime($st['marked_at']))) : '—' ?></td>
          <td>
            <span class="pill <?= $isPresent ? 'present' : 'absent' ?>">
              <?= $isPresent ? 'present' : 'absent' ?>
            </span>
          </td>
          <td><?= e((string) $pct) ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

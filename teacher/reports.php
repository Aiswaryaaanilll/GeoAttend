<?php
/**
 * GeoAttend – Teacher Reports
 * Attendance report scoped to the logged-in teacher.
 * Filters: subject, from-date, to-date, session.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$teacher = current_user();
$tid     = (int) $teacher['id'];

// ---- Filters -------------------------------------------------------------
$fSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
$fSession = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
$fFrom    = trim($_GET['from'] ?? '');
$fTo      = trim($_GET['to']   ?? '');

// Validate date strings (YYYY-MM-DD); ignore anything else.
$validDate = fn($d) => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
if (!$validDate($fFrom)) { $fFrom = ''; }
if (!$validDate($fTo))   { $fTo   = ''; }

// ---- Filter option lists (subjects owned by this teacher) ---------------
$subjects = [];
$q = $mysqli->prepare(
    "SELECT subject_id, subject_code, subject_name
       FROM subjects WHERE teacher_id = ? ORDER BY subject_code"
);
$q->bind_param('i', $tid);
$q->execute();
$subjects = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// Sessions owned by teacher (optionally narrowed by chosen subject).
$sessionOpts = [];
$sql = "SELECT s.session_id, s.start_time, sub.subject_code
          FROM attendance_sessions s
          JOIN subjects sub ON sub.subject_id = s.subject_id
         WHERE s.teacher_id = ?";
$types  = 'i';
$params = [$tid];
if ($fSubject > 0) { $sql .= " AND s.subject_id = ?"; $types .= 'i'; $params[] = $fSubject; }
$sql .= " ORDER BY s.start_time DESC LIMIT 200";
$q = $mysqli->prepare($sql);
$q->bind_param($types, ...$params);
$q->execute();
$sessionOpts = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// ---- Main query: attendance rows (present marks) ------------------------
$sql = "
    SELECT a.attendance_id, a.marked_at, a.status,
           st.student_id, st.name AS student_name, st.roll_no,
           sub.subject_code, sub.subject_name,
           c.room_name, c.building,
           s.session_id, s.start_time, s.end_time
      FROM attendance a
      JOIN attendance_sessions s ON s.session_id  = a.session_id
      JOIN subjects   sub        ON sub.subject_id = s.subject_id
      JOIN classrooms c          ON c.classroom_id = s.classroom_id
      JOIN students   st         ON st.student_id  = a.student_id
     WHERE s.teacher_id = ?
";
$types  = 'i';
$params = [$tid];

if ($fSubject > 0) { $sql .= " AND s.subject_id = ?"; $types .= 'i'; $params[] = $fSubject; }
if ($fSession > 0) { $sql .= " AND s.session_id = ?"; $types .= 'i'; $params[] = $fSession; }
if ($fFrom !== '') { $sql .= " AND DATE(a.marked_at) >= ?"; $types .= 's'; $params[] = $fFrom; }
if ($fTo   !== '') { $sql .= " AND DATE(a.marked_at) <= ?"; $types .= 's'; $params[] = $fTo; }

$sql .= " ORDER BY a.marked_at DESC";

$q = $mysqli->prepare($sql);
$q->bind_param($types, ...$params);
$q->execute();
$rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// ---- Totals --------------------------------------------------------------
$totalStudents = 0;
if ($r = $mysqli->query("SELECT COUNT(*) AS c FROM students")) {
    $totalStudents = (int) $r->fetch_assoc()['c'];
}
$presentCount = 0;
$distinctStudents = [];
foreach ($rows as $r) {
    if ($r['status'] === 'present') {
        $presentCount++;
        $distinctStudents[(int) $r['student_id']] = true;
    }
}
$distinctPresent = count($distinctStudents);
$absentCount = max(0, $totalStudents - $distinctPresent);
$pct = $totalStudents > 0 ? round(($distinctPresent / $totalStudents) * 100, 1) : 0.0;

$pageTitle = 'Reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Attendance reports</h1>
    <p>Filter attendance across your subjects, sessions and dates.</p>
  </div>
</div>

<form class="card session-form" method="get" action="<?= e(APP_URL) ?>/teacher/reports.php">
  <div class="form-row">
    <label for="subject_id">Subject</label>
    <select id="subject_id" name="subject_id">
      <option value="0">All subjects</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= (int) $s['subject_id'] ?>" <?= $fSubject === (int) $s['subject_id'] ? 'selected' : '' ?>>
          <?= e($s['subject_code']) ?> — <?= e($s['subject_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-row">
    <label for="session_id">Session</label>
    <select id="session_id" name="session_id">
      <option value="0">All sessions</option>
      <?php foreach ($sessionOpts as $so): ?>
        <option value="<?= (int) $so['session_id'] ?>" <?= $fSession === (int) $so['session_id'] ? 'selected' : '' ?>>
          #<?= (int) $so['session_id'] ?> · <?= e($so['subject_code']) ?> · <?= e(date('d M · h:i A', strtotime($so['start_time']))) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-row">
    <label for="from">From</label>
    <input type="date" id="from" name="from" value="<?= e($fFrom) ?>">
  </div>

  <div class="form-row">
    <label for="to">To</label>
    <input type="date" id="to" name="to" value="<?= e($fTo) ?>">
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-ghost" href="<?= e(APP_URL) ?>/teacher/reports.php">Reset</a>
  </div>
</form>

<section class="stats-grid">
  <div class="stat">
    <div class="label">Total students</div>
    <div class="value"><?= (int) $totalStudents ?></div>
    <div class="trend">Roster in system</div>
  </div>
  <div class="stat good">
    <div class="label">Present (distinct)</div>
    <div class="value"><?= (int) $distinctPresent ?></div>
    <div class="trend"><?= (int) $presentCount ?> total marks</div>
  </div>
  <div class="stat">
    <div class="label">Absent</div>
    <div class="value"><?= (int) $absentCount ?></div>
    <div class="trend">Never marked in filter</div>
  </div>
  <div class="stat">
    <div class="label">Attendance %</div>
    <div class="value"><?= e((string) $pct) ?>%</div>
    <div class="trend">Distinct present / total</div>
  </div>
</section>

<div class="section-title">
  <h2>Attendance records</h2>
  <span class="muted"><?= count($rows) ?> row<?= count($rows) === 1 ? '' : 's' ?></span>
</div>

<?php if (empty($rows)): ?>
  <div class="empty">
    <h3>No records match</h3>
    <p>Try widening the date range or clearing filters.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Marked at</th>
          <th>Student</th>
          <th>Roll no</th>
          <th>Subject</th>
          <th>Classroom</th>
          <th>Session</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= e(date('d M Y · h:i A', strtotime($r['marked_at']))) ?></td>
          <td><?= e($r['student_name']) ?></td>
          <td><?= e($r['roll_no']) ?></td>
          <td>
            <div style="font-weight:600"><?= e($r['subject_name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted)"><?= e($r['subject_code']) ?></div>
          </td>
          <td><?= e($r['room_name']) ?><?= $r['building'] ? ', ' . e($r['building']) : '' ?></td>
          <td>
            <a href="<?= e(APP_URL) ?>/teacher/session_details.php?session_id=<?= (int) $r['session_id'] ?>">
              #<?= (int) $r['session_id'] ?>
            </a>
          </td>
          <td><span class="pill <?= $r['status'] === 'present' ? 'present' : 'absent' ?>"><?= e($r['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

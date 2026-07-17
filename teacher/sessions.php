<?php
/**
 * GeoAttend – Teacher Sessions
 * All sessions created by the logged-in teacher (any date).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('teacher');
$teacher = current_user();
$tid     = (int) $teacher['id'];
$csrf    = csrf_token();

// Filters
$fStatus  = $_GET['status'] ?? 'all';
if (!in_array($fStatus, ['all', 'active', 'closed'], true)) { $fStatus = 'all'; }
$fSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;

// Subject options
$subjects = [];
$q = $mysqli->prepare(
    "SELECT subject_id, subject_code, subject_name
       FROM subjects WHERE teacher_id = ? ORDER BY subject_code"
);
$q->bind_param('i', $tid);
$q->execute();
$subjects = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

// Sessions
$sql = "
    SELECT s.session_id, s.start_time, s.end_time, s.status,
           sub.subject_code, sub.subject_name,
           c.room_name, c.building,
           (SELECT COUNT(*) FROM attendance a WHERE a.session_id = s.session_id) AS marked
      FROM attendance_sessions s
      JOIN subjects   sub ON sub.subject_id   = s.subject_id
      JOIN classrooms c   ON c.classroom_id   = s.classroom_id
     WHERE s.teacher_id = ?
";
$types  = 'i';
$params = [$tid];
if ($fStatus !== 'all') { $sql .= " AND s.status = ?";     $types .= 's'; $params[] = $fStatus; }
if ($fSubject > 0)      { $sql .= " AND s.subject_id = ?"; $types .= 'i'; $params[] = $fSubject; }
$sql .= " ORDER BY s.start_time DESC";

$q = $mysqli->prepare($sql);
$q->bind_param($types, ...$params);
$q->execute();
$rows = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$pageTitle = 'My Sessions';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>My sessions</h1>
    <p>Every attendance session you've created.</p>
  </div>
</div>

<form class="card session-form" method="get" action="<?= e(APP_URL) ?>/teacher/sessions.php">
  <div class="form-row">
    <label for="status">Status</label>
    <select id="status" name="status">
      <option value="all"    <?= $fStatus === 'all'    ? 'selected' : '' ?>>All</option>
      <option value="active" <?= $fStatus === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="closed" <?= $fStatus === 'closed' ? 'selected' : '' ?>>Closed</option>
    </select>
  </div>
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
  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Apply</button>
    <a class="btn btn-ghost" href="<?= e(APP_URL) ?>/teacher/sessions.php">Reset</a>
  </div>
</form>

<?php if (empty($rows)): ?>
  <div class="empty">
    <h3>No sessions yet</h3>
    <p>Open a session from the dashboard to see it here.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Session</th>
          <th>Subject</th>
          <th>Classroom</th>
          <th>Start</th>
          <th>End</th>
          <th>Status</th>
          <th>Present</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r):
        $isActive = $r['status'] === 'active' && strtotime($r['end_time']) >= time(); ?>
        <tr>
          <td>#<?= (int) $r['session_id'] ?></td>
          <td>
            <div style="font-weight:600"><?= e($r['subject_name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted)"><?= e($r['subject_code']) ?></div>
          </td>
          <td><?= e($r['room_name']) ?><?= $r['building'] ? ', ' . e($r['building']) : '' ?></td>
          <td><?= e(date('d M Y · h:i A', strtotime($r['start_time']))) ?></td>
          <td><?= e(date('d M Y · h:i A', strtotime($r['end_time']))) ?></td>
          <td><span class="pill <?= $isActive ? 'live' : '' ?>"><?= $isActive ? 'Live' : e(ucfirst($r['status'])) ?></span></td>
          <td><?= (int) $r['marked'] ?></td>
          <td>
            <a class="btn btn-ghost" href="<?= e(APP_URL) ?>/teacher/session_details.php?session_id=<?= (int) $r['session_id'] ?>">
              View attendance
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>

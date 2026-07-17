<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

require_role('student');

$user = current_user();
$student_id = (int)$user['id'];

/* -----------------------------
   Fetch student profile
------------------------------ */

$stmt = $mysqli->prepare("
    SELECT
        student_id,
        name,
        roll_no,
        branch,
        semester,
        email
    FROM students
    WHERE student_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$profile = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$profile) {
    flash_set('error', 'Profile not found.');
    header('Location: ' . APP_URL . '/logout.php');
    exit;
}

/* -----------------------------
   Attendance Statistics
   (Same logic as Dashboard)
------------------------------ */

$stmt = $mysqli->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status='present') AS present,
        SUM(status='absent') AS absent
    FROM attendance
    WHERE student_id = ?
");

$stmt->bind_param("i", $student_id);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();

$stmt->close();

$total = (int)($stats['total'] ?? 0);
$attended = (int)($stats['present'] ?? 0);
$missed = (int)($stats['absent'] ?? 0);

$percentage = $total > 0
    ? round(($attended / $total) * 100, 1)
    : 0;

$pageTitle = "My Profile";

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <h1>My Profile</h1>

    <a class="btn btn-ghost" href="<?= APP_URL ?>/student/dashboard.php">
        ← Back to Dashboard
    </a>
</div>

<div class="card">

    <div class="stats-grid">

        <div class="stat">
            <div class="value"><?= e($profile['name']) ?></div>
            <div class="label">Name</div>
        </div>

        <div class="stat">
            <div class="value"><?= e($profile['roll_no']) ?></div>
            <div class="label">Roll Number</div>
        </div>

        <div class="stat">
            <div class="value"><?= e($profile['branch']) ?></div>
            <div class="label">Branch</div>
        </div>

        <div class="stat">
            <div class="value"><?= e($profile['semester']) ?></div>
            <div class="label">Semester</div>
        </div>

        <div class="stat">
            <div class="value"><?= e($profile['email']) ?></div>
            <div class="label">Email</div>
        </div>

    </div>

</div>

<div class="section-title">
    <h2>Attendance Summary</h2>
</div>

<div class="stats-grid">

    <div class="stat">
        <div class="value"><?= $total ?></div>
        <div class="label">Total Records</div>
    </div>

    <div class="stat">
        <div class="value"><?= $attended ?></div>
        <div class="label">Present</div>
    </div>

    <div class="stat">
        <div class="value"><?= $missed ?></div>
        <div class="label">Absent</div>
    </div>

    <div class="stat">
        <div class="value"><?= $percentage ?>%</div>
        <div class="label">Attendance</div>

        <br>

        <?php if ($percentage >= 75): ?>
            <span class="pill live">Good Standing</span>
        <?php else: ?>
            <span class="pill">Below 75%</span>
        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
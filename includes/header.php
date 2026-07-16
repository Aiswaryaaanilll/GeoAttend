<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
$u = current_user();
$role = $u['role'] ?? '';
$flashes = flash_all();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= e($pageTitle ?? APP_NAME) ?> – <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css" />
  <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/dashboard.css" />
</head>
<body>
<header class="app-header">
  <div class="app-header-inner">
    <a href="<?= e(APP_URL) ?>/<?= e($role) ?>/dashboard.php" class="brand">
      <span class="brand-dot"></span><?= e(APP_NAME) ?>
    </a>
    <nav class="app-nav">
      <?php if ($role === 'student'): ?>
        <a href="<?= e(APP_URL) ?>/student/dashboard.php">Dashboard</a>
        <a href="<?= e(APP_URL) ?>/student/history.php">My Attendance</a>
      <?php endif; ?>
    </nav>
    <div class="app-user">
      <div class="who">
        <div class="name"><?= e($u['name'] ?? '') ?></div>
        <div class="role"><?= e(ucfirst($role)) ?></div>
      </div>
      <a class="btn btn-ghost" href="<?= e(APP_URL) ?>/logout.php">Log out</a>
    </div>
  </div>
</header>

<?php if (!empty($flashes)): ?>
<div class="flash-stack" role="status" aria-live="polite">
  <?php foreach ($flashes as $f): ?>
    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<main class="app-main">

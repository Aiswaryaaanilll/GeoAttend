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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?> - <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/dashboard.css">
</head>

<body>

<header class="app-header">
    <div class="app-header-inner">

        <!-- Logo -->
        <a href="<?= e(APP_URL) ?>/<?= e($role) ?>/dashboard.php" class="brand">
            <span class="brand-dot"></span>
            <?= e(APP_NAME) ?>
        </a>

        <!-- Navigation -->
        <nav class="app-nav">

            <?php if ($role === 'student'): ?>

                <a href="<?= e(APP_URL) ?>/student/dashboard.php">Dashboard</a>
                <a href="<?= e(APP_URL) ?>/student/history.php">History</a>

                <?php if (file_exists(__DIR__ . '/../student/profile.php')): ?>
                    <a href="<?= e(APP_URL) ?>/student/profile.php">Profile</a>
                <?php endif; ?>

            <?php elseif ($role === 'teacher'): ?>

                <a href="<?= e(APP_URL) ?>/teacher/dashboard.php">Dashboard</a>

                <?php if (file_exists(__DIR__ . '/../teacher/sessions.php')): ?>
                    <a href="<?= e(APP_URL) ?>/teacher/sessions.php">Sessions</a>
                <?php endif; ?>

                <?php if (file_exists(__DIR__ . '/../teacher/reports.php')): ?>
                    <a href="<?= e(APP_URL) ?>/teacher/reports.php">Reports</a>
                <?php endif; ?>

            <?php endif; ?>

        </nav>

        <!-- Logged in user -->
        <?php if (!empty($u)): ?>

            <div class="app-user">

                <div class="who">
                    <div class="name">
                        <?= e($u['name']) ?>
                    </div>

                    <div class="role">
                        <?= e(ucfirst($role)) ?>
                    </div>
                </div>

                <a class="btn btn-ghost"
                   href="<?= e(APP_URL) ?>/logout.php">
                    Logout
                </a>

            </div>

        <?php endif; ?>

    </div>
</header>

<?php if (!empty($flashes)): ?>

<div class="flash-stack" role="status" aria-live="polite">

    <?php foreach ($flashes as $flash): ?>

        <div class="flash <?= e($flash['type']) ?>">
            <?= e($flash['message']) ?>
        </div>

    <?php endforeach; ?>

</div>

<?php endif; ?>

<main class="app-main"></main>
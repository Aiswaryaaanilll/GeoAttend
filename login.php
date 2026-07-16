<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, bounce to the appropriate dashboard.
if (is_logged_in()) {
    $role = $_SESSION['user']['role'];
    redirect(APP_URL . '/' . $role . '/dashboard.php');
}

$flashes    = flash_all();
$prefRole   = $_COOKIE['geoattend_role']  ?? 'student';
$prefEmail  = $_COOKIE['geoattend_email'] ?? '';
$csrf       = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sign in – <?= e(APP_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="assets/css/login.css" />
</head>
<body>
  <!-- Flash toasts -->
  <?php if (!empty($flashes)): ?>
    <div class="flash-stack" role="status" aria-live="polite">
      <?php foreach ($flashes as $f): ?>
        <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <main class="auth-shell">
    <!-- Left: brand/hero -->
    <aside class="auth-hero" aria-hidden="true">
      <div class="brand">
        <span class="brand-dot"></span>
        <span><?= e(APP_NAME) ?></span>
      </div>

      <div class="hero-copy">
        <h1>Smart classroom attendance, secured by location.</h1>
        <p>Teachers open a session, students inside the classroom mark
           attendance in one tap. No proxies. No paperwork.</p>
      </div>

      <div class="hero-badges">
        <span class="badge">Geofenced</span>
        <span class="badge">Real-time reports</span>
        <span class="badge">Made for campuses</span>
      </div>
    </aside>

    <!-- Right: form -->
    <section class="auth-panel">
      <div class="auth-card" role="dialog" aria-labelledby="loginTitle">
        <h2 id="loginTitle">Welcome back</h2>
        <p class="sub">Sign in to continue to your dashboard.</p>

        <!-- Role tabs -->
        <div class="role-tabs" role="tablist" aria-label="Login role">
          <button type="button" class="role-tab" role="tab"
                  data-role="student"
                  aria-selected="<?= $prefRole === 'student' ? 'true' : 'false' ?>">
            Student
          </button>
          <button type="button" class="role-tab" role="tab"
                  data-role="teacher"
                  aria-selected="<?= $prefRole === 'teacher' ? 'true' : 'false' ?>">
            Teacher
          </button>
        </div>

        <form id="loginForm" method="POST" action="authenticate.php" novalidate>
          <input type="hidden" name="csrf" value="<?= e($csrf) ?>" />
          <input type="hidden" name="role" id="role" value="<?= e($prefRole) ?>" />

          <div class="field" id="emailField">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" autocomplete="email"
                   required value="<?= e($prefEmail) ?>"
                   placeholder="you@college.edu" />
            <span class="err">Please enter a valid email.</span>
          </div>

          <div class="field" id="passwordField">
            <label for="password">Password</label>
            <div class="pw-wrap">
              <input type="password" id="password" name="password"
                     autocomplete="current-password" required minlength="6"
                     placeholder="Your password" />
              <button type="button" class="pw-toggle" id="pwToggle"
                      aria-label="Show password">Show</button>
            </div>
            <span class="err">Password must be at least 6 characters.</span>
          </div>

          <div class="row-between">
            <label class="remember">
              <input type="checkbox" name="remember" value="1"
                     <?= $prefEmail ? 'checked' : '' ?> />
              Remember me
            </label>
            <a href="#" onclick="alert('Contact your administrator to reset your password.');return false;">
              Forgot password?
            </a>
          </div>

          <button type="submit" id="submitBtn" class="btn btn-primary btn-block">
            <span class="spinner" aria-hidden="true"></span>
            <span>Sign in</span>
          </button>
        </form>

        <p class="auth-foot">
          Protected by session-based auth · <?= e(APP_NAME) ?> © <?= date('Y') ?>
        </p>
      </div>
    </section>
  </main>

  <script src="assets/js/login.js"></script>
</body>
</html>

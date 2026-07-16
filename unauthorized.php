<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Access denied – <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(APP_URL) ?>/assets/css/style.css" />
</head>
<body>
  <main style="min-height:100vh;display:grid;place-items:center;padding:2rem;">
    <div class="card" style="max-width:460px;padding:2rem;text-align:center;">
      <h1 style="margin:0 0 .5rem;font-size:22px;">Access denied</h1>
      <p style="color:var(--text-muted);margin:0 0 1.25rem;">
        You don't have permission to view this page.
      </p>
      <a class="btn btn-primary" href="<?= htmlspecialchars(APP_URL) ?>/login.php">
        Back to sign in
      </a>
    </div>
  </main>
</body>
</html>

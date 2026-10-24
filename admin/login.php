<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
header('Cache-Control: no-store, private');

if (is_admin_authenticated()) {
    header('Location: /admin');
    exit;
}

$error = '';
$email = '';
$setupError = false;
$adminReady = false;
try {
    $adminReady = (int) database_connection()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
} catch (Throwable $exception) {
    $setupError = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $lockUntil = (int) ($_SESSION['admin_lock_until'] ?? 0);

    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'This page expired. Refresh it and try again.';
    } elseif ($setupError) {
        $error = 'Admin setup is incomplete. Follow the setup steps in README.md first.';
    } elseif ($lockUntil > time()) {
        $error = 'Too many attempts. Please wait a few minutes, then try again.';
    } else {
        $statement = database_connection()->prepare('SELECT id, email, password_hash FROM admins WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower($email)]);
        $admin = $statement->fetch();
        if ($admin && password_verify($password, (string) $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_email'] = (string) $admin['email'];
            $_SESSION['admin_login_failures'] = 0;
            unset($_SESSION['admin_lock_until']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /admin');
            exit;
        }

        $failures = (int) ($_SESSION['admin_login_failures'] ?? 0) + 1;
        $_SESSION['admin_login_failures'] = $failures;
        if ($failures >= 5) {
            $_SESSION['admin_login_failures'] = 0;
            $_SESSION['admin_lock_until'] = time() + 300;
        }
        $error = 'Email or password didn’t match.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Admin sign in · You Aspirant</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="admin.css">
</head>
<body class="admin-body">
  <main class="login-shell">
    <a class="admin-brand" href="/"><span class="brand-mark" aria-hidden="true"><span>Y</span></span><span><strong>you aspirant</strong><small>Team sign in</small></span></a>
    <section class="login-card" aria-labelledby="login-title">
      <p class="admin-eyebrow">A quiet corner for the team</p>
      <h1 id="login-title">Welcome back.</h1>
      <p class="login-intro">Sign in to review student enquiries.</p>
      <?php if ($setupError): ?>
        <div class="admin-alert" role="alert">Setup needed: configure the database, import <code>database/schema.sql</code>, then create the first admin from the command line. See <code>README.md</code>.</div>
      <?php elseif (!$adminReady): ?>
        <div class="admin-alert" role="alert">No admin account yet. From the project folder, run <code>php create_admin.php</code> in a terminal.</div>
      <?php endif; ?>
      <?php if ($error !== ''): ?><p class="admin-error" role="alert"><?= escape_html($error) ?></p><?php endif; ?>
      <form method="post" action="login">
        <input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>">
        <label for="email">Admin email</label>
        <input id="email" name="email" type="email" value="<?= escape_html($email) ?>" autocomplete="username" required>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button button-dark" type="submit">Sign in <span aria-hidden="true">↗</span></button>
      </form>
      <a class="login-back" href="/">← Back to the website</a>
    </section>
    <p class="login-foot">Student details are only available to an authenticated team member.</p>
  </main>
</body>
</html>


<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Cache-Control: no-store, private');
if (is_user_authenticated()) { header('Location: my-account'); exit; }
$error = '';
$mode = is_string($_POST['mode'] ?? null) ? $_POST['mode'] : 'login';
$loginLockedUntil = (int) ($_SESSION['student_lock_until'] ?? 0);
$name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
$email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    try {
        if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
            $error = 'This page expired. Refresh it and try again.';
        } elseif ($mode === 'login' && $loginLockedUntil > time()) {
            $error = 'Too many attempts. Please wait a few minutes and try again.';
        } elseif ($mode === 'register') {
            if ($name === '' || text_length($name) > 120 || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254 || strlen($password) < 12) {
                $error = 'Enter your name and a valid email, then choose a password with at least 12 characters.';
            } else {
                $statement = database_connection()->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :hash)');
                $statement->execute(['name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['user_id'] = (int) database_connection()->lastInsertId();
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                header('Location: my-account'); exit;
            }
        } elseif ($mode === 'login') {
            $statement = database_connection()->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if ($user && password_verify($password, (string) $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_email'] = (string) $user['email'];
                $_SESSION['user_name'] = (string) $user['name'];
                $_SESSION['student_login_failures'] = 0;
                unset($_SESSION['student_lock_until']);
                header('Location: my-account'); exit;
            }
            $failures = (int) ($_SESSION['student_login_failures'] ?? 0) + 1;
            if ($failures >= 5) {
                $_SESSION['student_login_failures'] = 0;
                $_SESSION['student_lock_until'] = time() + 300;
                $error = 'Too many attempts. Please wait a few minutes and try again.';
            } else {
                $_SESSION['student_login_failures'] = $failures;
                $error = 'Email or password did not match.';
            }
        } else {
            $error = 'Choose sign in or create account.';
        }
    } catch (PDOException $exception) {
        $error = $mode === 'register' ? 'An account with that email may already exist.' : 'We could not reach the account database. Try again shortly.';
    } catch (Throwable $exception) {
        $error = 'Account setup is incomplete. Check the database and try again.';
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow"><title>Student account · You Aspirant</title><link rel="stylesheet" href="styles.css"><link rel="stylesheet" href="features.css"></head><body class="feature-page"><header class="feature-header"><a class="brand" href="/"><span class="brand-mark"><span>Y</span></span><span class="brand-copy"><strong>you aspirant</strong><small>Make your next step yours.</small></span></a><a href="/">← Back to website</a></header><main class="feature-shell"><p class="eyebrow eyebrow-muted">Your guidance, in one place</p><h1>Student account</h1><p>Sign in to follow your enquiries, read replies from your expert, and check counselling bookings. New here? Create an account to get started.</p><?php if ($error !== ''): ?><p class="feature-alert" role="alert"><?= escape_html($error) ?></p><?php endif; ?><div class="account-grid"><section class="feature-panel"><h2>Sign in</h2><form class="feature-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="mode" value="login"><label>Email<input type="email" name="email" value="<?= escape_html($email) ?>" autocomplete="email" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="button button-dark" type="submit">Sign in ↗</button></form></section><section class="feature-panel"><h2>Create an account</h2><form class="feature-form" method="post"><input type="hidden" name="csrf_token" value="<?= escape_html(csrf_token()) ?>"><input type="hidden" name="mode" value="register"><label>Your name<input type="text" name="name" maxlength="120" value="<?= escape_html($name) ?>" autocomplete="name" required></label><label>Email<input type="email" name="email" maxlength="254" value="<?= escape_html($email) ?>" autocomplete="email" required></label><label>Create password<input type="password" name="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters.</small></label><button class="button button-dark" type="submit">Create account ↗</button></form></section></div><p class="feature-help">You can also <a href="book">request a counselling slot</a> or <a href="quiz">take the career quiz</a> without an account.</p></main></body></html>

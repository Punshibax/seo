<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

try {
    $pdo = database_connection();
    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($adminCount > 0) {
        fwrite(STDERR, "An admin account already exists.\n");
        exit(1);
    }

    $email = strtolower(trim((string) readline('Admin email: ')));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        fwrite(STDERR, "Enter a valid email address.\n");
        exit(1);
    }

    $password = (string) readline('Admin password (12+ characters): ');
    if (strlen($password) < 12) {
        fwrite(STDERR, "Choose a password with at least 12 characters.\n");
        exit(1);
    }
    $confirm = (string) readline('Confirm password: ');
    if (!hash_equals($password, $confirm)) {
        fwrite(STDERR, "The passwords did not match.\n");
        exit(1);
    }

    $statement = $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (:email, :password_hash)');
    $statement->execute([
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Admin account created. Sign in at /admin/login.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Could not create admin. Check database setup and import database/schema.sql.\n");
    exit(1);
}

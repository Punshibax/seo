<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$isJson = str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

function finish_submission(int $status, string $message, bool $isJson): void
{
    if ($isJson) {
        send_json($status, ['message' => $message]);
    }

    $_SESSION['form_flash'] = $message;
    header('Location: /#contact', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    finish_submission(405, 'Please send the enquiry using the form.', $isJson);
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    finish_submission(403, 'This form expired. Refresh the page and try again.', $isJson);
}

$honeypot = is_string($_POST['website'] ?? null) ? trim($_POST['website']) : '';
if ($honeypot !== '') {
    finish_submission(200, 'Thanks for sharing. We’ll be in touch about your enquiry.', $isJson);
}

$name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';$email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
$interest = is_string($_POST['interest'] ?? null) ? trim($_POST['interest']) : '';
$message = is_string($_POST['message'] ?? null) ? trim($_POST['message']) : '';
$allowedInterests = [
    'Career guidance',
    'Skills and training',
    'Interview practice',
    'A little help figuring that out',
];

if ($name === '' || text_length($name) > 120) {
    finish_submission(422, 'Please enter your name (up to 120 characters).', $isJson);
}
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
    finish_submission(422, 'Please enter a valid email address.', $isJson);
}
if (!in_array($interest, $allowedInterests, true)) {
    finish_submission(422, 'Please choose what you would like help with.', $isJson);
}
if (text_length($message) > 3000) {
    finish_submission(422, 'Please keep your note under 3,000 characters.', $isJson);
}

$lastSubmission = (int) ($_SESSION['last_lead_submission'] ?? 0);
if ($lastSubmission > 0 && time() - $lastSubmission < 20) {
    finish_submission(429, 'Please wait a moment before sending another enquiry.', $isJson);
}

try {
    $database = database_connection();
    $expertMatch = $database->prepare('SELECT id FROM experts WHERE active = 1 AND LOWER(specialty) LIKE :specialty ORDER BY id LIMIT 1');
    $expertMatch->execute([':specialty' => '%' . strtolower($interest) . '%']);
    $expertId = $expertMatch->fetchColumn();
    $statement = $database->prepare(
        'INSERT INTO leads (user_id, expert_id, name, email, interest, message) VALUES (:user_id, :expert_id, :name, :email, :interest, :message)'
    );
    $statement->execute([
        ':user_id' => current_user_id(),
        ':expert_id' => $expertId === false ? null : (int) $expertId,
        ':name' => $name,
        ':email' => $email,
        ':interest' => $interest,
        ':message' => $message === '' ? null : $message,
    ]);
    $_SESSION['last_lead_submission'] = time();
    finish_submission(201, 'Thanks for reaching out. We’ve received your enquiry.', $isJson);
} catch (Throwable $exception) {
    error_log('You Aspirant enquiry could not be saved: ' . $exception->getMessage());
    finish_submission(503, 'We couldn’t save your enquiry just now. Please try again in a little while.', $isJson);
}

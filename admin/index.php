<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
header('Cache-Control: no-store, private');

$notice = is_string($_SESSION['admin_notice'] ?? null) ? $_SESSION['admin_notice'] : '';
unset($_SESSION['admin_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $_SESSION['admin_notice'] = 'This page expired. Refresh it and try again.';
    } else {
        $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false || !in_array($action, ['contacted', 'new', 'delete'], true)) {
            $_SESSION['admin_notice'] = 'That action wasn’t valid. Refresh the list and try again.';
        } else {
            try {
                $database = database_connection();
                if ($action === 'delete') {
                    $database->beginTransaction();
                    $messageDelete = $database->prepare('DELETE FROM lead_messages WHERE lead_id = :id');
                    $messageDelete->execute([':id' => $id]);
                    $statement = $database->prepare('DELETE FROM leads WHERE id = :id');
                    $statement->execute([':id' => $id]);
                    $database->commit();
                    $_SESSION['admin_notice'] = $statement->rowCount() ? 'Enquiry deleted.' : 'That enquiry was already removed.';
                } else {
                    $statement = $database->prepare('UPDATE leads SET status = :status WHERE id = :id');
                    $statement->execute([':status' => $action, ':id' => $id]);
                    $_SESSION['admin_notice'] = 'Enquiry status updated.';
                }
            } catch (Throwable $exception) {
                error_log('You Aspirant admin action failed: ' . $exception->getMessage());
                $_SESSION['admin_notice'] = 'The database action failed. Check the database connection and try again.';
            }
        }
    }
    header('Location: /admin');
    exit;
}

$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$query = function_exists('mb_substr') ? mb_substr($query, 0, 100, 'UTF-8') : substr($query, 0, 100);
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
if (!in_array($statusFilter, ['', 'new', 'contacted'], true)) {
    $statusFilter = '';
}
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$leads = [];
$total = 0;
$stats = ['total' => 0, 'new' => 0, 'contacted' => 0];
$databaseError = false;

try {
    $database = database_connection();
    $stats = $database->query(
        "SELECT COUNT(*) AS total, SUM(status = 'new') AS new_count, SUM(status = 'contacted') AS contacted_count FROM leads"
    )->fetch();
    $stats = [
        'total' => (int) ($stats['total'] ?? 0),
        'new' => (int) ($stats['new_count'] ?? 0),
        'contacted' => (int) ($stats['contacted_count'] ?? 0),
    ];

    $conditions = [];
    $parameters = [];
    if ($query !== '') {
        $conditions[] = '(l.name LIKE :name_term OR l.email LIKE :email_term OR l.interest LIKE :interest_term)';
        $term = '%' . $query . '%';
        $parameters[':name_term'] = $term;
        $parameters[':email_term'] = $term;
        $parameters[':interest_term'] = $term;
    }
    if ($statusFilter !== '') {
        $conditions[] = 'l.status = :status';
        $parameters[':status'] = $statusFilter;
    }
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    $countStatement = $database->prepare('SELECT COUNT(*) FROM leads l' . $where);
    $countStatement->execute($parameters);
    $total = (int) $countStatement->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $listStatement = $database->prepare(
        "SELECT l.id, l.name, l.email, l.interest, l.message, l.status, l.created_at, e.name AS expert_name, (SELECT COUNT(*) FROM lead_messages m WHERE m.lead_id=l.id AND m.sender_type='student' AND m.is_read=0) AS unread_replies FROM leads l LEFT JOIN experts e ON e.id=l.expert_id" . $where . ' ORDER BY l.created_at DESC, l.id DESC LIMIT :limit OFFSET :offset'
    );
    foreach ($parameters as $key => $value) {
        $listStatement->bindValue($key, $value, PDO::PARAM_STR);
    }
    $listStatement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $listStatement->bindValue(':offset', $offset, PDO::PARAM_INT);
    $listStatement->execute();
    $leads = $listStatement->fetchAll();
} catch (Throwable $exception) {
    error_log('You Aspirant admin list failed: ' . $exception->getMessage());
    $databaseError = true;
    $pages = 1;
}

$token = escape_html(csrf_token());
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>Student enquiries · You Aspirant admin</title>
  <link rel="stylesheet" href="../styles.css">
  <link rel="stylesheet" href="admin.css">
  <link rel="stylesheet" href="../features.css">
</head>
<body class="admin-body dashboard-body">
  <header class="dashboard-header">
    <a class="admin-brand" href="/"><span class="brand-mark" aria-hidden="true"><span>Y</span></span><span><strong>you aspirant</strong><small>Enquiry desk</small></span></a>
    <form method="post" action="logout"><input type="hidden" name="csrf_token" value="<?= $token ?>"><button class="logout-button" type="submit">Sign out <span aria-hidden="true">↗</span></button></form>
  </header>
  <?php require __DIR__ . '/_nav.php'; ?>
  <main class="dashboard-main">
    <div class="dashboard-title"><div><p class="admin-eyebrow">A little clarity for the team</p><h1>Student enquiries</h1><p class="dashboard-subtitle">A private place to read and follow up on new conversations.</p></div><span class="signed-in">Signed in as <?= escape_html($_SESSION['admin_email']) ?></span></div>
    <?php if ($notice !== ''): ?><p class="admin-notice" role="status"><?= escape_html($notice) ?></p><?php endif; ?>
    <?php if ($databaseError): ?>
      <div class="admin-alert" role="alert">The lead list isn’t available yet. Check <code>config.php</code>, make sure the database is running, and import <code>database/schema.sql</code>.</div>
    <?php else: ?>
      <section class="stat-grid" aria-label="Enquiry totals">
        <article class="stat-card"><span>All enquiries</span><strong><?= number_format($stats['total']) ?></strong></article>
        <article class="stat-card stat-new"><span>Need a reply</span><strong><?= number_format($stats['new']) ?></strong></article>
        <article class="stat-card stat-contacted"><span>Contacted</span><strong><?= number_format($stats['contacted']) ?></strong></article>
      </section>
      <section class="leads-panel" aria-labelledby="leads-title">
        <div class="leads-panel-heading"><div><h2 id="leads-title">The inbox</h2><p><?= number_format($total) ?> <?= $total === 1 ? 'enquiry' : 'enquiries' ?> in this view</p></div>
          <form class="lead-filters" method="get" action="/admin">
            <label class="sr-only" for="search">Search name, email, or interest</label><input id="search" name="q" type="search" value="<?= escape_html($query) ?>" placeholder="Search the inbox">
            <label class="sr-only" for="status">Filter by status</label><select id="status" name="status"><option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All statuses</option><option value="new" <?= $statusFilter === 'new' ? 'selected' : '' ?>>Needs a reply</option><option value="contacted" <?= $statusFilter === 'contacted' ? 'selected' : '' ?>>Contacted</option></select>
            <button type="submit" class="filter-button">Filter</button>
          </form>
        </div>
        <?php if (!$leads): ?>
          <div class="empty-inbox"><span aria-hidden="true">✳</span><h3><?= $query !== '' || $statusFilter !== '' ? 'No enquiries match those filters.' : 'No enquiries yet.' ?></h3><p><?= $query !== '' || $statusFilter !== '' ? 'Try a different search or show all statuses.' : 'New student enquiries will appear here.' ?></p></div>
        <?php else: ?>
          <div class="table-scroll"><table class="leads-table"><thead><tr><th>Student</th><th>Looking for</th><th>Note</th><th>Received</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead><tbody>
          <?php foreach ($leads as $lead): ?>
            <tr>
              <td class="student-cell"><strong><?= escape_html($lead['name']) ?></strong><a href="mailto:<?= escape_html($lead['email']) ?>"><?= escape_html($lead['email']) ?></a></td>
              <td><?= escape_html($lead['interest']) ?><?php if ($lead['expert_name']): ?><small class="table-subline">Expert: <?= escape_html($lead['expert_name']) ?></small><?php else: ?><small class="table-subline">Expert unassigned</small><?php endif; ?></td>
              <td class="note-cell"><?= trim((string) $lead['message']) !== '' ? nl2br(escape_html($lead['message'])) : '<span class="muted">No note</span>' ?></td>
              <td class="date-cell"><?= escape_html(date('M j, Y', strtotime((string) $lead['created_at']))) ?><small><?= escape_html(date('g:i a', strtotime((string) $lead['created_at']))) ?></small></td>
              <td><span class="status-pill status-<?= escape_html($lead['status']) ?>"><?= escape_html($lead['status'] === 'new' ? 'Needs a reply' : 'Contacted') ?></span></td>
              <td class="actions-cell">
                <a class="row-action" href="lead?id=<?= (int) $lead['id'] ?>">Open conversation<?= (int)$lead['unread_replies'] > 0 ? ' · ' . (int)$lead['unread_replies'] . ' new' : '' ?></a>
                <form method="post" action="/admin"><input type="hidden" name="csrf_token" value="<?= $token ?>"><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>"><input type="hidden" name="action" value="<?= $lead['status'] === 'new' ? 'contacted' : 'new' ?>"><button class="row-action" type="submit"><?= $lead['status'] === 'new' ? 'Mark contacted' : 'Reopen' ?></button></form>
                <form method="post" action="/admin" data-delete-form><input type="hidden" name="csrf_token" value="<?= $token ?>"><input type="hidden" name="id" value="<?= (int) $lead['id'] ?>"><input type="hidden" name="action" value="delete"><button class="row-action row-delete" type="submit">Delete</button></form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody></table></div>
          <?php if ($pages > 1): ?><nav class="pagination" aria-label="Enquiry pages">
            <span>Page <?= $page ?> of <?= $pages ?></span><div>
              <?php if ($page > 1): ?><a href="?<?= http_build_query(['q' => $query, 'status' => $statusFilter, 'page' => $page - 1]) ?>">← Newer</a><?php endif; ?>
              <?php if ($page < $pages): ?><a href="?<?= http_build_query(['q' => $query, 'status' => $statusFilter, 'page' => $page + 1]) ?>">Older →</a><?php endif; ?>
            </div>
          </nav><?php endif; ?>
        <?php endif; ?>
      </section>
    <?php endif; ?>
    <p class="dashboard-back"><a href="/">← View the website</a></p>
  </main>
  <script>
    document.querySelectorAll('[data-delete-form]').forEach((form) => {
      form.addEventListener('submit', (event) => {
        if (!window.confirm('Delete this enquiry? This cannot be undone.')) event.preventDefault();
      });
    });
  </script>
</body>
</html>


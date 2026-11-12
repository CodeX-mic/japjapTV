<?php
/**
 * Visitor message inbox.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$validStatuses = ['new', 'read', 'replied', 'archived'];
$returnStatus = post_string('return_status');
$returnQ = mb_substr(post_string('return_q'), 0, 100);
$returnParams = [];
if (in_array($returnStatus, $validStatuses, true)) {
    $returnParams['status'] = $returnStatus;
}
if ($returnQ !== '') {
    $returnParams['q'] = $returnQ;
}
$returnUrl = 'admin/messages.php' . ($returnParams !== [] ? '?' . http_build_query($returnParams) : '');

if (is_post()) {
    admin_verify_csrf($returnUrl);
    $action = post_string('action');
    $messageId = post_int('message_id');

    $stmt = db()->prepare('SELECT id, name, subject, status FROM contact_messages WHERE id = :id');
    $stmt->execute(['id' => $messageId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('error', 'That message could not be found.');
        redirect($returnUrl);
    }

    if ($action === 'set_status') {
        $newStatus = post_string('status');
        if (!in_array($newStatus, $validStatuses, true)) {
            flash('error', 'Choose a valid message status.');
        } else {
            $update = db()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id');
            $update->execute(['status' => $newStatus, 'id' => $messageId]);
            $meta = admin_message_status_meta($newStatus);
            flash('success', 'Message “' . $target['subject'] . '” marked ' . mb_strtolower($meta[0]) . '.');
        }
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(['id' => $messageId]);
        flash('success', 'Message “' . $target['subject'] . '” deleted.');
    } else {
        flash('error', 'Unknown message action.');
    }

    redirect($returnUrl);
}

$q = mb_substr(get_string('q'), 0, 100);
$statusFilter = get_string('status');
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE :q_name OR email LIKE :q_email OR subject LIKE :q_subject OR message LIKE :q_message)';
    $like = '%' . $q . '%';
    $params += [
        'q_name'    => $like,
        'q_email'   => $like,
        'q_subject' => $like,
        'q_message' => $like,
    ];
}
if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params['status'] = $statusFilter;
}
$whereSql = $where !== [] ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare('SELECT COUNT(*) FROM contact_messages' . $whereSql);
$countStmt->execute($params);
$pagination = admin_pagination((int) $countStmt->fetchColumn(), 25);

$stmt = db()->prepare("SELECT id, name, email, subject, message, status, created_at
    FROM contact_messages
    {$whereSql}
    ORDER BY (status = 'new') DESC, created_at DESC, id DESC
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$messages = $stmt->fetchAll();

$counts = ['new' => 0, 'read' => 0, 'replied' => 0, 'archived' => 0];
foreach (db()->query('SELECT status, COUNT(*) AS total FROM contact_messages GROUP BY status')->fetchAll() as $countRow) {
    if (isset($counts[$countRow['status']])) {
        $counts[$countRow['status']] = (int) $countRow['total'];
    }
}
$allCount = array_sum($counts);

$filtersActive = $q !== '' || $statusFilter !== '';
$qSuffix = $q !== '' ? '&q=' . rawurlencode($q) : '';

$adminTitle = 'Messages';
$adminSubtitle = 'Review correction reports and privacy questions from visitors. Replies open in your email client.';
$adminActive = 'messages';

require __DIR__ . '/includes/header.php';
?>
<div class="admin-toolbar">
    <nav class="admin-pagination" aria-label="Message filters" style="margin:0;">
        <a class="<?= $statusFilter === '' ? 'is-current' : '' ?>" href="<?= e(admin_url('messages.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''))) ?>">All (<?= $allCount ?>)</a>
        <?php foreach ($validStatuses as $status): ?>
            <?php $meta = admin_message_status_meta($status); ?>
            <a class="<?= $statusFilter === $status ? 'is-current' : '' ?>" href="<?= e(admin_url('messages.php?status=' . $status . $qSuffix)) ?>"><?= e($meta[0]) ?> (<?= (int) $counts[$status] ?>)</a>
        <?php endforeach; ?>
    </nav>

    <form class="admin-toolbar-group" action="<?= e(admin_url('messages.php')) ?>" method="get">
        <?php if ($statusFilter !== ''): ?>
            <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <?php endif; ?>
        <div class="admin-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="messageSearch">Search messages</label>
            <input id="messageSearch" type="search" name="q" value="<?= e($q) ?>" placeholder="Search sender, email, subject, or body">
        </div>
        <button class="btn btn-light btn-sm" type="submit">Search</button>
        <?php if ($filtersActive): ?><a class="btn btn-ghost btn-sm" href="<?= e(admin_url('messages.php')) ?>">Clear</a><?php endif; ?>
    </form>
</div>

<?php if ($messages === []): ?>
    <div class="admin-card admin-empty">
        <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
        <?= $filtersActive ? 'No messages match those filters.' : 'The inbox is empty.' ?>
        <?php if ($filtersActive): ?><br><a href="<?= e(admin_url('messages.php')) ?>" style="color:#a7d4ff;">Show all messages</a>.<?php endif; ?>
    </div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table" style="min-width:900px;">
            <thead>
                <tr>
                    <th>Sender</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Received</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                    <?php $meta = admin_message_status_meta((string) $message['status']); ?>
                    <tr class="admin-message-row<?= $message['status'] === 'new' ? ' is-unread' : '' ?>">
                        <td>
                            <span class="is-primary"><?= e($message['name']) ?></span><br>
                            <small class="is-muted"><?= e($message['email']) ?></small>
                        </td>
                        <td style="max-width:360px;">
                            <a class="is-primary" href="<?= e(admin_url('message.php?id=' . (int) $message['id'])) ?>"><?= e($message['subject']) ?></a><br>
                            <small class="is-muted"><?= e(truncate_text((string) $message['message'], 100)) ?></small>
                        </td>
                        <td><span class="<?= e($meta[1]) ?>"><i class="fa-solid <?= e($meta[2]) ?>" aria-hidden="true"></i> <?= e($meta[0]) ?></span></td>
                        <td class="is-nowrap is-muted">
                            <?= e(time_ago((string) $message['created_at'])) ?><br>
                            <small><?= e(pht_datetime((string) $message['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="admin-icon-btn" href="<?= e(admin_url('message.php?id=' . (int) $message['id'])) ?>" title="Open message"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                                <form action="<?= e(admin_url('messages.php')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <input type="hidden" name="status" value="<?= $message['status'] === 'new' ? 'read' : 'new' ?>">
                                    <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                                    <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                    <button class="admin-icon-btn" type="submit" title="<?= $message['status'] === 'new' ? 'Mark read' : 'Mark unread' ?>"><i class="fa-solid <?= $message['status'] === 'new' ? 'fa-envelope-open' : 'fa-envelope' ?>" aria-hidden="true"></i></button>
                                </form>
                                <?php if ($message['status'] !== 'archived'): ?>
                                    <form action="<?= e(admin_url('messages.php')) ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="set_status">
                                        <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                        <input type="hidden" name="status" value="archived">
                                        <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                                        <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                        <button class="admin-icon-btn" type="submit" title="Archive message"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?= e(admin_url('messages.php')) ?>" method="post" data-confirm="Delete the message from <?= e($message['name']) ?>? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= (int) $message['id'] ?>">
                                    <input type="hidden" name="return_status" value="<?= e($statusFilter) ?>">
                                    <input type="hidden" name="return_q" value="<?= e($q) ?>">
                                    <button class="admin-icon-btn is-danger" type="submit" title="Delete message"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['pages'] > 1): ?>
        <nav class="admin-pagination" aria-label="Message pages">
            <?php if ($pagination['page'] > 1): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] - 1)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><?php endif; ?>
            <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['pages'], $pagination['page'] + 2); $i++): ?>
                <?php if ($i === $pagination['page']): ?><span class="is-current"><?= $i ?></span><?php else: ?><a href="<?= e(admin_page_url($pagination, $i)) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] + 1)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-head"><h2>Privacy and retention</h2></div>
    <p class="admin-help" style="margin-top:0;">Messages contain a visitor’s name, email address, message, and a one-way hash of their IP address. Use them only to respond to the report. Delete messages you no longer need, and follow the retention guidance on the public <a href="<?= e(url('privacy.php')) ?>" target="_blank" rel="noopener" style="color:#a7d4ff;">privacy page</a>.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * Read and manage a single visitor message.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$messageId = ctype_digit(get_string('id')) ? (int) get_string('id') : 0;
if ($messageId < 1) {
    flash('error', 'Choose a message to open.');
    redirect('admin/messages.php');
}

$validStatuses = ['new', 'read', 'replied', 'archived'];

if (is_post()) {
    admin_verify_csrf('admin/message.php?id=' . $messageId);
    $action = post_string('action');

    $stmt = db()->prepare('SELECT id, subject FROM contact_messages WHERE id = :id');
    $stmt->execute(['id' => $messageId]);
    $target = $stmt->fetch();
    if (!$target) {
        flash('error', 'That message could not be found.');
        redirect('admin/messages.php');
    }

    if ($action === 'delete') {
        db()->prepare('DELETE FROM contact_messages WHERE id = :id')->execute(['id' => $messageId]);
        flash('success', 'Message “' . $target['subject'] . '” deleted.');
        redirect('admin/messages.php');
    }

    if ($action === 'set_status') {
        $newStatus = post_string('status');
        if (!in_array($newStatus, $validStatuses, true)) {
            flash('error', 'Choose a valid message status.');
        } else {
            db()->prepare('UPDATE contact_messages SET status = :status WHERE id = :id')
                ->execute(['status' => $newStatus, 'id' => $messageId]);
            $meta = admin_message_status_meta($newStatus);
            flash('success', 'Message marked ' . mb_strtolower($meta[0]) . '.');
        }
        redirect('admin/message.php?id=' . $messageId);
    }

    flash('error', 'Unknown message action.');
    redirect('admin/message.php?id=' . $messageId);
}

$stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = :id');
$stmt->execute(['id' => $messageId]);
$message = $stmt->fetch();

if (!$message) {
    flash('error', 'That message could not be found.');
    redirect('admin/messages.php');
}

// Opening an unread message marks it read. Status changes and deletion remain
// explicit POST actions protected by CSRF.
if ($message['status'] === 'new') {
    db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id")->execute(['id' => $messageId]);
    $message['status'] = 'read';
}

$statusMeta = admin_message_status_meta((string) $message['status']);
$replySubject = trim(preg_replace('/[\r\n]+/', ' ', (string) $message['subject']) ?? '');
$replyHref = 'mailto:' . rawurlencode((string) $message['email'])
    . '?subject=' . rawurlencode('Re: ' . $replySubject)
    . '&body=' . rawurlencode("Hello " . $message['name'] . ",\n\n");

$adminTitle = 'Message';
$adminSubtitle = 'Received ' . pht_datetime((string) $message['created_at']);
$adminActive = 'messages';

require __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumbs" style="margin-bottom:16px;" aria-label="Breadcrumb">
    <a href="<?= e(admin_url('messages.php')) ?>">Messages</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span aria-current="page"><?= e(truncate_text((string) $message['subject'], 60)) ?></span>
</nav>

<div class="admin-grid-3">
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
                        <h2 style="margin:0;"><?= e($message['subject']) ?></h2>
                        <span class="<?= e($statusMeta[1]) ?>"><i class="fa-solid <?= e($statusMeta[2]) ?>" aria-hidden="true"></i> <?= e($statusMeta[0]) ?></span>
                    </div>
                    <p>From <?= e($message['name']) ?> · <?= e(pht_datetime((string) $message['created_at'])) ?> PHT</p>
                </div>
                <a class="admin-card-link" href="<?= e(admin_url('messages.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inbox</a>
            </div>

            <div class="admin-message-body"><?= e($message['message']) ?></div>

            <div class="admin-actions-bar" style="margin:16px 0 0;">
                <a class="btn btn-primary btn-sm" href="<?= e($replyHref) ?>"><i class="fa-solid fa-reply" aria-hidden="true"></i> Reply by email</a>

                <?php if ($message['status'] !== 'replied'): ?>
                    <form action="<?= e(admin_url('message.php?id=' . $messageId)) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="status" value="replied">
                        <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i> Mark replied</button>
                    </form>
                <?php endif; ?>

                <?php if ($message['status'] === 'archived'): ?>
                    <form action="<?= e(admin_url('message.php?id=' . $messageId)) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="status" value="read">
                        <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Restore to inbox</button>
                    </form>
                <?php else: ?>
                    <form action="<?= e(admin_url('message.php?id=' . $messageId)) ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="set_status">
                        <input type="hidden" name="status" value="archived">
                        <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-box-archive" aria-hidden="true"></i> Archive</button>
                    </form>
                <?php endif; ?>

                <form action="<?= e(admin_url('message.php?id=' . $messageId)) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="set_status">
                    <input type="hidden" name="status" value="new">
                    <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Mark unread</button>
                </form>

                <form action="<?= e(admin_url('message.php?id=' . $messageId)) ?>" method="post" data-confirm="Delete this message permanently? This cannot be undone.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <button class="btn btn-ghost btn-sm" type="submit" style="color:#ff9aa4;"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
                </form>
            </div>
        </div>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card-head"><h2>Sender</h2></div>
            <dl class="admin-meta-list">
                <div><dt>Name</dt><dd><?= e($message['name']) ?></dd></div>
                <div><dt>Email</dt><dd><a href="<?= e($replyHref) ?>" style="color:#a7d4ff;"><?= e($message['email']) ?></a></dd></div>
                <div><dt>Received</dt><dd><?= e(pht_datetime((string) $message['created_at'])) ?></dd></div>
                <div><dt>Status</dt><dd><?= e($statusMeta[0]) ?></dd></div>
            </dl>
            <p class="admin-help">Replies are composed in your own email client. This page does not send email automatically.</p>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>Request details</h2></div>
            <dl class="admin-meta-list">
                <div><dt>Message ID</dt><dd><?= $messageId ?></dd></div>
                <div><dt>IP fingerprint</dt><dd title="A one-way hash, never the raw address"><?= e(mb_substr((string) ($message['ip_hash'] ?? '—'), 0, 16)) ?>…</dd></div>
                <div><dt>User agent</dt><dd style="max-width:190px;"><?= e(truncate_text((string) ($message['user_agent'] ?? 'Not recorded'), 90)) ?></dd></div>
            </dl>
            <p class="admin-help">Do not forward a visitor’s email address or message without their permission. Delete the message when it is no longer needed.</p>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>Add a correction</h2></div>
            <p class="admin-help" style="margin-top:0;">If the report identifies a broken source, open the related channel and run a source check. Never paste a replacement stream URL unless it is official, public, and authorized for embedding.</p>
            <a class="btn btn-ghost btn-sm btn-block" href="<?= e(admin_url('channels.php')) ?>"><i class="fa-solid fa-tv" aria-hidden="true"></i> Find the channel</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

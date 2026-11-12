<?php
/**
 * Admin channel list.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_post() && post_string('action') === 'delete') {
    admin_verify_csrf('admin/channels.php');
    $deleteId = post_int('channel_id');
    $stmt = db()->prepare('SELECT id, channel_name, logo FROM channels WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $deleteId]);
    $target = $stmt->fetch();

    if (!$target) {
        flash('error', 'That channel no longer exists.');
    } else {
        db()->prepare('DELETE FROM channels WHERE id = :id')->execute(['id' => $deleteId]);
        admin_remove_uploaded_logo((string) ($target['logo'] ?? ''));
        flash('success', '“' . $target['channel_name'] . '” and its sources were deleted.');
    }
    redirect('admin/channels.php');
}

$q = mb_substr(get_string('q'), 0, 100);
$status = get_string('status');
if (!in_array($status, ['available', 'external_only', 'unavailable', 'coming_soon'], true)) {
    $status = '';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(c.channel_name LIKE :q OR c.slug LIKE :q2)';
    $params['q'] = '%' . $q . '%';
    $params['q2'] = '%' . $q . '%';
}
if ($status !== '') {
    $where[] = 'c.availability_status = :status';
    $params['status'] = $status;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare('SELECT COUNT(*) FROM channels c' . $whereSql);
$countStmt->execute($params);
$pagination = admin_pagination((int) $countStmt->fetchColumn(), 25);

$sql = channel_base_select() . $whereSql . " ORDER BY c.display_order ASC, c.channel_name ASC LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$channels = $stmt->fetchAll();

$adminTitle = 'Channels';
$adminSubtitle = (int) $pagination['total'] . ' channels in the catalogue. Use sources to control what can actually play.';
$adminActive = 'channels';

require __DIR__ . '/includes/header.php';
?>
<div class="admin-toolbar">
    <form class="admin-toolbar-group" action="<?= e(admin_url('channels.php')) ?>" method="get">
        <div class="admin-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="channelSearch">Search channels</label>
            <input id="channelSearch" type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name or slug">
        </div>
        <label class="sr-only" for="channelStatus">Status</label>
        <select id="channelStatus" name="status" style="height:38px;padding:0 10px;border:1px solid var(--border);border-radius:9px;background:rgba(255,255,255,.03);color:var(--text);font-size:11px;">
            <option value="">Any status</option>
            <option value="available"<?= $status === 'available' ? ' selected' : '' ?>>Live now</option>
            <option value="external_only"<?= $status === 'external_only' ? ' selected' : '' ?>>Official site</option>
            <option value="unavailable"<?= $status === 'unavailable' ? ' selected' : '' ?>>Unavailable</option>
            <option value="coming_soon"<?= $status === 'coming_soon' ? ' selected' : '' ?>>Coming soon</option>
        </select>
        <button class="btn btn-light btn-sm" type="submit">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?><a class="btn btn-ghost btn-sm" href="<?= e(admin_url('channels.php')) ?>">Clear</a><?php endif; ?>
    </form>
    <div class="admin-toolbar-group">
        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('channel-edit.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add channel</a>
    </div>
</div>

<?php if ($channels === []): ?>
    <div class="admin-card admin-empty"><i class="fa-solid fa-tv" aria-hidden="true"></i> No channels match. <a href="<?= e(admin_url('channel-edit.php')) ?>" style="color:#a7d4ff;">Add a channel</a>.</div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Country</th>
                    <th>Category</th>
                    <th>Access</th>
                    <th>Status</th>
                    <th>Sources</th>
                    <th>Views</th>
                    <th>Order</th>
                    <th>Updated</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($channels as $channel): ?>
                    <?php $statusMeta = availability_meta((string) $channel['availability_status']); ?>
                    <tr>
                        <td>
                            <div class="admin-channel-cell">
                                <img src="<?= e(channel_logo_url($channel)) ?>" width="30" height="30" alt="">
                                <span>
                                    <strong><?= e($channel['channel_name']) ?></strong>
                                    <small><?= e($channel['slug']) ?><?= (int) $channel['is_featured'] === 1 ? ' · featured' : '' ?></small>
                                </span>
                            </div>
                        </td>
                        <td><?= e($channel['flag_emoji'] ?? '') ?> <?= e($channel['country_name']) ?></td>
                        <td><?= e($channel['category_name']) ?></td>
                        <td><?= e(access_label((string) $channel['access_type'])) ?></td>
                        <td><span class="status-pill <?= e($statusMeta['class']) ?>"><?= e($statusMeta['short']) ?></span></td>
                        <td class="is-nowrap"><?= (int) $channel['active_source_count'] ?> active</td>
                        <td class="is-nowrap is-muted"><?= e(format_views((int) $channel['view_count'])) ?></td>
                        <td class="is-nowrap is-muted"><?= (int) $channel['display_order'] ?></td>
                        <td class="is-nowrap is-muted"><?= e(time_ago((string) $channel['updated_at'])) ?></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="admin-icon-btn" href="<?= e(admin_url('channel-edit.php?id=' . (int) $channel['id'])) ?>" title="Edit channel"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <a class="admin-icon-btn" href="<?= e(admin_url('sources.php?channel=' . (int) $channel['id'])) ?>" title="Manage sources"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></a>
                                <a class="admin-icon-btn" href="<?= e(url('watch.php?id=' . rawurlencode((string) $channel['slug']))) ?>" target="_blank" rel="noopener" title="View public page"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                                <form action="<?= e(admin_url('channels.php')) ?>" method="post" data-confirm="Delete “<?= e($channel['channel_name']) ?>” and all of its sources? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="channel_id" value="<?= (int) $channel['id'] ?>">
                                    <button class="admin-icon-btn is-danger" type="submit" title="Delete channel"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['pages'] > 1): ?>
        <nav class="admin-pagination" aria-label="Channel pages">
            <?php if ($pagination['page'] > 1): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] - 1)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><?php endif; ?>
            <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['pages'], $pagination['page'] + 2); $i++): ?>
                <?php if ($i === $pagination['page']): ?><span class="is-current"><?= $i ?></span><?php else: ?><a href="<?= e(admin_page_url($pagination, $i)) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] + 1)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

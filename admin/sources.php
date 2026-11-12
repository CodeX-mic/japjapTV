<?php
/**
 * Admin source list and verification actions.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$admin = current_admin();

if (is_post()) {
    admin_verify_csrf('admin/sources.php');
    $action = post_string('action');

    if ($action === 'check') {
        $source = get_source_by_id(post_int('source_id'));
        if ($source === null) {
            flash('error', 'That source could not be found.');
        } else {
            $result = check_source_record($source, true, (int) $admin['id']);
            $meta = admin_source_status_meta((string) $result['status']);
            flash($result['status'] === 'live' ? 'success' : 'warning', $source['channel_name'] . ': ' . $meta[0] . ' — ' . $result['message']);
        }
        redirect('admin/sources.php' . (post_int('channel') > 0 ? '?channel=' . post_int('channel') : ''));
    }

    if ($action === 'check_all') {
        set_time_limit(0);
        ignore_user_abort(true);
        $results = check_all_sources((int) $admin['id']);
        $live = count(array_filter($results, static fn (array $r): bool => $r['status'] === 'live'));
        $failed = count(array_filter($results, static fn (array $r): bool => in_array($r['status'], ['offline', 'error'], true)));
        flash($failed > 0 ? 'warning' : 'success', sprintf(
            'Checked %d sources: %d live, %d failed. Uncertain results left channel status unchanged.',
            count($results),
            $live,
            $failed
        ));
        redirect('admin/sources.php');
    }

    if ($action === 'delete') {
        $source = get_source_by_id(post_int('source_id'));
        if ($source === null) {
            flash('error', 'That source could not be found.');
        } else {
            db()->prepare('DELETE FROM channel_sources WHERE id = :id')->execute(['id' => $source['id']]);
            admin_sync_channel_sources((int) $source['channel_id']);
            flash('success', 'Source “' . $source['label'] . '” deleted.');
        }
        redirect('admin/sources.php' . (post_int('channel') > 0 ? '?channel=' . post_int('channel') : ''));
    }
}

$q = mb_substr(get_string('q'), 0, 100);
$channelFilter = ctype_digit(get_string('channel')) ? (int) get_string('channel') : 0;
$statusFilter = get_string('status');
$validStatuses = ['live', 'reachable', 'offline', 'error', 'unverified', 'unknown'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(sx.label LIKE :q OR sx.source_url LIKE :q2 OR c.channel_name LIKE :q3)';
    $like = '%' . $q . '%';
    $params += ['q' => $like, 'q2' => $like, 'q3' => $like];
}
if ($channelFilter > 0) {
    $where[] = 'sx.channel_id = :channel';
    $params['channel'] = $channelFilter;
}
if ($statusFilter !== '') {
    $where[] = 'sx.last_check_status = :status';
    $params['status'] = $statusFilter;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = db()->prepare('SELECT COUNT(*) FROM channel_sources sx
    INNER JOIN channels c ON c.id = sx.channel_id' . $whereSql);
$countStmt->execute($params);
$pagination = admin_pagination((int) $countStmt->fetchColumn(), 30);

$stmt = db()->prepare("SELECT sx.*, c.channel_name, c.slug AS channel_slug, c.availability_status
    FROM channel_sources sx
    INNER JOIN channels c ON c.id = sx.channel_id
    {$whereSql}
    ORDER BY c.channel_name ASC, sx.priority ASC, sx.id ASC
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$sources = $stmt->fetchAll();

$channelContext = $channelFilter > 0 ? get_channel_by_id($channelFilter) : null;
$sourceTypes = ['youtube_live' => 'YouTube live', 'youtube_video' => 'YouTube video', 'hls' => 'HLS', 'iframe' => 'Iframe', 'external' => 'External'];

$adminTitle = 'Sources';
$adminSubtitle = 'Every playable stream must be official, active, and authorized. Checks never promote a channel on HTTP 200 alone.';
$adminActive = 'sources';

require __DIR__ . '/includes/header.php';
?>
<?php if ($channelContext !== null): ?>
    <div class="admin-alert">
        <i class="fa-solid fa-tv" aria-hidden="true"></i>
        <div>
            <strong>Filtered to <?= e($channelContext['channel_name']) ?></strong>
            <p>Manage this channel’s official sources, or <a href="<?= e(admin_url('sources.php')) ?>">show all sources</a>.</p>
        </div>
    </div>
<?php endif; ?>

<div class="admin-toolbar">
    <form class="admin-toolbar-group" action="<?= e(admin_url('sources.php')) ?>" method="get">
        <div class="admin-search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <label class="sr-only" for="sourceSearch">Search sources</label>
            <input id="sourceSearch" type="search" name="q" value="<?= e($q) ?>" placeholder="Search label, URL, or channel">
        </div>
        <label class="sr-only" for="sourceStatus">Status</label>
        <select id="sourceStatus" name="status" style="height:38px;padding:0 10px;border:1px solid var(--border);border-radius:9px;background:rgba(255,255,255,.03);color:var(--text);font-size:11px;">
            <option value="">Any status</option>
            <?php foreach ($validStatuses as $value): ?>
                <option value="<?= e($value) ?>"<?= $statusFilter === $value ? ' selected' : '' ?>><?= e(admin_source_status_meta($value)[0]) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($channelFilter > 0): ?><input type="hidden" name="channel" value="<?= $channelFilter ?>"><?php endif; ?>
        <button class="btn btn-light btn-sm" type="submit">Filter</button>
        <?php if ($q !== '' || $statusFilter !== '' || $channelFilter > 0): ?><a class="btn btn-ghost btn-sm" href="<?= e(admin_url('sources.php')) ?>">Clear</a><?php endif; ?>
    </form>
    <div class="admin-toolbar-group">
        <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('source-edit.php' . ($channelFilter > 0 ? '?channel=' . $channelFilter : ''))) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add source</a>
        <form action="<?= e(admin_url('sources.php')) ?>" method="post" data-confirm="Check every active source now? This makes network requests and can take a minute.">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check_all">
            <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Check all sources</button>
        </form>
    </div>
</div>

<div class="policy-note" style="margin-bottom:18px;">
    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
    <span>“Live” means YouTube confirmed an embeddable live video. “Unverified” iframe and external sources never promote a channel automatically. A channel is demoted to “Official site” only after three definitive failures on every active source.</span>
</div>

<?php if ($sources === []): ?>
    <div class="admin-card admin-empty">
        <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i>
        No sources match.
        <a href="<?= e(admin_url('source-edit.php' . ($channelFilter > 0 ? '?channel=' . $channelFilter : ''))) ?>" style="color:#a7d4ff;">Add an official source</a>.
    </div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table" style="min-width:1050px;">
            <thead>
                <tr>
                    <th>Channel</th>
                    <th>Source</th>
                    <th>Type</th>
                    <th>State</th>
                    <th>Last check</th>
                    <th>Priority</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sources as $source): ?>
                    <?php $meta = admin_source_status_meta((string) $source['last_check_status']); ?>
                    <tr>
                        <td>
                            <span class="is-primary"><?= e($source['channel_name']) ?></span><br>
                            <small class="is-muted"><?= e($source['channel_slug']) ?></small>
                        </td>
                        <td style="max-width:280px;">
                            <span class="is-primary"><?= e($source['label']) ?></span><br>
                            <small class="is-muted" style="overflow-wrap:anywhere;"><?= e(truncate_text((string) $source['source_url'], 80)) ?></small>
                        </td>
                        <td class="is-nowrap"><?= e($sourceTypes[$source['source_type']] ?? $source['source_type']) ?></td>
                        <td>
                            <span class="<?= e($meta[1]) ?>"><?= e($meta[0]) ?></span>
                            <br>
                            <small class="is-muted">
                                <?= (int) $source['is_active'] === 1 ? 'Active' : 'Inactive' ?> ·
                                <?= (int) $source['is_authorized'] === 1 ? 'Authorized' : 'Not authorized' ?>
                            </small>
                        </td>
                        <td style="max-width:230px;">
                            <span class="is-muted"><?= e($source['last_checked'] ? time_ago((string) $source['last_checked']) : 'Never') ?></span>
                            <?php if (trim((string) ($source['last_check_message'] ?? '')) !== ''): ?>
                                <br><small class="is-muted"><?= e(truncate_text((string) $source['last_check_message'], 80)) ?></small>
                            <?php endif; ?>
                            <?php if ((int) $source['consecutive_failures'] > 0): ?>
                                <br><small style="color:#ff9aa4;"><?= (int) $source['consecutive_failures'] ?> consecutive failure<?= (int) $source['consecutive_failures'] === 1 ? '' : 's' ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="is-nowrap"><?= (int) $source['priority'] ?></td>
                        <td>
                            <div class="admin-row-actions">
                                <form action="<?= e(admin_url('sources.php')) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="check">
                                    <input type="hidden" name="source_id" value="<?= (int) $source['id'] ?>">
                                    <input type="hidden" name="channel" value="<?= $channelFilter ?>">
                                    <button class="admin-icon-btn" type="submit" title="Check this source now"><i class="fa-solid fa-rotate" aria-hidden="true"></i></button>
                                </form>
                                <a class="admin-icon-btn" href="<?= e(admin_url('source-edit.php?id=' . (int) $source['id'])) ?>" title="Edit source"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form action="<?= e(admin_url('sources.php')) ?>" method="post" data-confirm="Delete this source? The channel may be demoted to “Official site”.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="source_id" value="<?= (int) $source['id'] ?>">
                                    <input type="hidden" name="channel" value="<?= $channelFilter ?>">
                                    <button class="admin-icon-btn is-danger" type="submit" title="Delete source"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['pages'] > 1): ?>
        <nav class="admin-pagination" aria-label="Source pages">
            <?php if ($pagination['page'] > 1): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] - 1)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><?php endif; ?>
            <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['pages'], $pagination['page'] + 2); $i++): ?>
                <?php if ($i === $pagination['page']): ?><span class="is-current"><?= $i ?></span><?php else: ?><a href="<?= e(admin_page_url($pagination, $i)) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] + 1)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

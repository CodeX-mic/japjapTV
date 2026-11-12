<?php
/**
 * Admin dashboard.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$stats = get_catalog_stats();
$unread = admin_message_count();
$featuredCount = (int) db()->query('SELECT COUNT(*) FROM featured_channels')->fetchColumn();
$nbaCount = (int) db()->query('SELECT COUNT(*) FROM nba_games')->fetchColumn();
$sourceProblems = (int) db()->query("SELECT COUNT(*) FROM channel_sources WHERE last_check_status IN ('offline','error')")->fetchColumn();
$lastCheck = db()->query('SELECT MAX(last_checked) FROM channel_sources')->fetchColumn();

$recentChannels = db()->query("SELECT c.id, c.channel_name, c.slug, c.logo, c.availability_status, c.access_type, c.updated_at,
        co.country_name, co.flag_emoji, cat.category_name
    FROM channels c
    INNER JOIN countries co ON co.id = c.country_id
    INNER JOIN categories cat ON cat.id = c.category_id
    ORDER BY c.updated_at DESC, c.id DESC LIMIT 7")->fetchAll();

$recentMessages = db()->query('SELECT id, name, email, subject, status, created_at
    FROM contact_messages ORDER BY created_at DESC LIMIT 5')->fetchAll();

$recentChecks = db()->query("SELECT sc.status, sc.checked_at, sc.message, sx.label, c.channel_name, c.slug
    FROM source_checks sc
    INNER JOIN channel_sources sx ON sx.id = sc.source_id
    INNER JOIN channels c ON c.id = sx.channel_id
    ORDER BY sc.checked_at DESC, sc.id DESC LIMIT 8")->fetchAll();

$adminTitle = 'Dashboard';
$adminSubtitle = 'A quick read on the catalogue and what needs attention.';
$adminActive = 'dashboard';

require __DIR__ . '/includes/header.php';
?>
<div class="admin-stats">
    <div class="admin-stat" style="--stat-color:#39d98a;">
        <span class="admin-stat-icon"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i></span>
        <span class="admin-stat-value"><?= (int) ($stats['available'] ?? 0) ?></span>
        <span class="admin-stat-label">Verified live channels</span>
    </div>
    <div class="admin-stat" style="--stat-color:#5eb8ff;">
        <span class="admin-stat-icon"><i class="fa-solid fa-tv" aria-hidden="true"></i></span>
        <span class="admin-stat-value"><?= (int) ($stats['total'] ?? 0) ?></span>
        <span class="admin-stat-label">Channels in catalogue</span>
    </div>
    <div class="admin-stat" style="--stat-color:#f5b942;">
        <span class="admin-stat-icon"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></span>
        <span class="admin-stat-value"><?= (int) ($stats['total_sources'] ?? 0) ?></span>
        <span class="admin-stat-label">Configured sources</span>
    </div>
    <div class="admin-stat" style="--stat-color:#ff5d6c;">
        <span class="admin-stat-icon"><i class="fa-solid fa-envelope" aria-hidden="true"></i></span>
        <span class="admin-stat-value"><?= $unread ?></span>
        <span class="admin-stat-label">New messages</span>
    </div>
</div>

<div class="admin-actions-bar">
    <a class="btn btn-primary btn-sm" href="<?= e(admin_url('channel-edit.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add channel</a>
    <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('sources.php')) ?>"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> Manage sources</a>
    <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('nba-games.php')) ?>"><i class="fa-solid fa-basketball" aria-hidden="true"></i> NBA schedule</a>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> View site</a>
</div>

<div class="admin-grid-3">
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Recently updated channels</h2>
                    <p>The latest edits across the catalogue.</p>
                </div>
                <a class="admin-card-link" href="<?= e(admin_url('channels.php')) ?>">All channels <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>

            <?php if ($recentChannels === []): ?>
                <div class="admin-empty"><i class="fa-solid fa-tv" aria-hidden="true"></i> No channels yet. Add the first one.</div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table" style="min-width:560px;">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentChannels as $recent): ?>
                                <?php $statusMeta = availability_meta((string) $recent['availability_status']); ?>
                                <tr>
                                    <td>
                                        <div class="admin-channel-cell">
                                            <img src="<?= e(channel_logo_url($recent)) ?>" width="30" height="30" alt="">
                                            <span>
                                                <strong><?= e($recent['channel_name']) ?></strong>
                                                <small><?= e($recent['flag_emoji']) ?> <?= e($recent['country_name']) ?> · <?= e($recent['category_name']) ?></small>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="status-pill <?= e($statusMeta['class']) ?>"><?= e($statusMeta['short']) ?></span></td>
                                    <td class="is-nowrap is-muted"><?= e(time_ago((string) $recent['updated_at'])) ?></td>
                                    <td>
                                        <div class="admin-row-actions">
                                            <a class="admin-icon-btn" href="<?= e(admin_url('channel-edit.php?id=' . (int) $recent['id'])) ?>" title="Edit <?= e($recent['channel_name']) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                            <a class="admin-icon-btn" href="<?= e(url('watch.php?id=' . rawurlencode((string) $recent['slug']))) ?>" target="_blank" rel="noopener" title="View public page"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Recent source checks</h2>
                    <p><?= $lastCheck ? 'Last activity ' . e(time_ago((string) $lastCheck)) : 'The source checker has not run yet.' ?></p>
                </div>
                <a class="admin-card-link" href="<?= e(admin_url('sources.php')) ?>">Sources <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>

            <?php if ($recentChecks === []): ?>
                <div class="admin-empty"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i> No checks logged yet. Use “Check all sources” on the sources page.</div>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table" style="min-width:620px;">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Result</th>
                                <th>Message</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentChecks as $check): ?>
                                <?php $checkMeta = admin_source_status_meta((string) $check['status']); ?>
                                <tr>
                                    <td class="is-primary"><?= e($check['channel_name']) ?><br><small class="is-muted"><?= e($check['label']) ?></small></td>
                                    <td><span class="<?= e($checkMeta[1]) ?>"><?= e($checkMeta[0]) ?></span></td>
                                    <td style="max-width:260px;"><?= e(truncate_text((string) $check['message'], 90)) ?></td>
                                    <td class="is-nowrap is-muted"><?= e(time_ago((string) $check['checked_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <h2>Needs attention</h2>
            </div>
            <dl class="admin-meta-list">
                <div><dt>Failed sources</dt><dd><?= $sourceProblems ?></dd></div>
                <div><dt>Featured picks</dt><dd><?= $featuredCount ?></dd></div>
                <div><dt>Saved NBA games</dt><dd><?= $nbaCount ?></dd></div>
                <div><dt>Unread messages</dt><dd><?= $unread ?></dd></div>
                <div><dt>Last source check</dt><dd><?= e($lastCheck ? time_ago((string) $lastCheck) : 'Never') ?></dd></div>
            </dl>
            <?php if ($sourceProblems > 0): ?>
                <div class="admin-alert is-warning" style="margin:14px 0 0;padding:10px 12px;">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    <div>
                        <strong><?= $sourceProblems ?> source<?= $sourceProblems === 1 ? '' : 's' ?> reporting a failure.</strong>
                        <p><a href="<?= e(admin_url('sources.php?status=offline')) ?>">Review failed sources</a> and re-check before changing a channel’s status.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Latest messages</h2>
                </div>
                <a class="admin-card-link" href="<?= e(admin_url('messages.php')) ?>">Inbox <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <?php if ($recentMessages === []): ?>
                <div class="admin-empty"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i> No messages yet.</div>
            <?php else: ?>
                <?php foreach ($recentMessages as $message): ?>
                    <a class="admin-source" href="<?= e(admin_url('message.php?id=' . (int) $message['id'])) ?>" style="display:block;">
                        <div class="admin-source-head">
                            <h3 style="font-size:0.82rem;"><?= e($message['subject']) ?></h3>
                            <?php if ($message['status'] === 'new'): ?><span class="badge badge-live">New</span><?php endif; ?>
                        </div>
                        <div class="admin-source-meta">
                            <span><?= e($message['name']) ?></span>
                            <span><?= e(time_ago((string) $message['created_at'])) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <h2>Quick links</h2>
            </div>
            <div class="side-cta" style="display:grid;gap:6px;">
                <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('channel-edit.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> New channel</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('source-edit.php')) ?>"><i class="fa-solid fa-link" aria-hidden="true"></i> New source</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('featured.php')) ?>"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured picks</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('categories.php')) ?>"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> Categories</a>
                <a class="btn btn-ghost btn-sm" href="<?= e(admin_url('account.php')) ?>"><i class="fa-solid fa-key" aria-hidden="true"></i> Change password</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

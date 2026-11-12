<?php
/**
 * Manage homepage featured channels.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    admin_verify_csrf('admin/featured.php');
    $action = post_string('action');

    if ($action === 'add') {
        $channelId = post_int('channel_id');
        $stmt = db()->prepare('SELECT id, channel_name FROM channels WHERE id = :id');
        $stmt->execute(['id' => $channelId]);
        $channel = $stmt->fetch();

        if (!$channel) {
            flash('error', 'Choose a valid channel to feature.');
        } else {
            $next = (int) db()->query('SELECT COALESCE(MAX(display_order), 0) + 1 FROM featured_channels')->fetchColumn();
            $stmt = db()->prepare('INSERT INTO featured_channels (channel_id, display_order, created_at)
                VALUES (:channel_id, :display_order, :created_at)
                ON DUPLICATE KEY UPDATE display_order = VALUES(display_order)');
            $stmt->execute(['channel_id' => $channelId, 'display_order' => $next, 'created_at' => utc_now()]);
            db()->prepare('UPDATE channels SET is_featured = 1 WHERE id = :id')->execute(['id' => $channelId]);
            flash('success', '“' . $channel['channel_name'] . '” added to featured picks.');
        }
        redirect('admin/featured.php');
    }

    if ($action === 'remove') {
        $channelId = post_int('channel_id');
        $stmt = db()->prepare('SELECT c.channel_name FROM featured_channels f
            INNER JOIN channels c ON c.id = f.channel_id WHERE f.channel_id = :id');
        $stmt->execute(['id' => $channelId]);
        $name = $stmt->fetchColumn();
        db()->prepare('DELETE FROM featured_channels WHERE channel_id = :id')->execute(['id' => $channelId]);
        db()->prepare('UPDATE channels SET is_featured = 0 WHERE id = :id')->execute(['id' => $channelId]);
        flash('success', $name ? '“' . $name . '” removed from featured picks.' : 'Featured channel removed.');
        redirect('admin/featured.php');
    }

    if ($action === 'save_order') {
        $orders = $_POST['orders'] ?? [];
        $updated = 0;
        if (is_array($orders)) {
            $stmt = db()->prepare('UPDATE featured_channels SET display_order = :display_order WHERE channel_id = :channel_id');
            foreach ($orders as $channelId => $order) {
                if (!ctype_digit((string) $channelId) || !is_scalar($order)) {
                    continue;
                }
                $stmt->execute([
                    'display_order' => max(0, min(65535, (int) $order)),
                    'channel_id'    => (int) $channelId,
                ]);
                $updated++;
            }
        }
        flash('success', 'Featured order saved for ' . $updated . ' channel' . ($updated === 1 ? '' : 's') . '.');
        redirect('admin/featured.php');
    }
}

$featured = db()->query("SELECT f.channel_id, f.display_order AS featured_order, f.created_at AS featured_at,
        c.channel_name, c.slug, c.logo, c.availability_status, c.is_featured,
        co.country_name, co.flag_emoji, cat.category_name
    FROM featured_channels f
    INNER JOIN channels c ON c.id = f.channel_id
    INNER JOIN countries co ON co.id = c.country_id
    INNER JOIN categories cat ON cat.id = c.category_id
    ORDER BY f.display_order ASC, f.id ASC")->fetchAll();

$featuredIds = array_map(static fn (array $row): int => (int) $row['channel_id'], $featured);
$availableChannels = db()->query("SELECT c.id, c.channel_name, c.slug, co.country_name
    FROM channels c INNER JOIN countries co ON co.id = c.country_id
    ORDER BY c.channel_name ASC")->fetchAll();
$availableChannels = array_values(array_filter($availableChannels, static fn (array $row): bool => !in_array((int) $row['id'], $featuredIds, true)));

$adminTitle = 'Featured picks';
$adminSubtitle = 'Control the homepage strip. A featured channel is also marked with a star in the public catalogue.';
$adminActive = 'featured';

require __DIR__ . '/includes/header.php';
?>
<div class="admin-grid">
    <div class="admin-card">
        <div class="admin-card-head">
            <div>
                <h2>Current featured channels</h2>
                <p><?= count($featured) ?> of the homepage’s curated picks.</p>
            </div>
            <a class="admin-card-link" href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">View homepage <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
        </div>

        <?php if ($featured === []): ?>
            <div class="admin-empty"><i class="fa-solid fa-star" aria-hidden="true"></i> No featured channels yet. Add one from the panel beside this list.</div>
        <?php else: ?>
            <form action="<?= e(admin_url('featured.php')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save_order">
                <div class="admin-table-wrap">
                    <table class="admin-table" style="min-width:680px;">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th>Status</th>
                                <th style="width:110px;">Order</th>
                                <th><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($featured as $row): ?>
                                <?php $statusMeta = availability_meta((string) $row['availability_status']); ?>
                                <tr>
                                    <td>
                                        <div class="admin-channel-cell">
                                            <img src="<?= e(channel_logo_url($row)) ?>" width="30" height="30" alt="">
                                            <span>
                                                <strong><?= e($row['channel_name']) ?></strong>
                                                <small><?= e($row['flag_emoji']) ?> <?= e($row['country_name']) ?> · <?= e($row['category_name']) ?></small>
                                            </span>
                                        </div>
                                    </td>
                                    <td><span class="status-pill <?= e($statusMeta['class']) ?>"><?= e($statusMeta['short']) ?></span></td>
                                    <td>
                                        <label class="sr-only" for="order<?= (int) $row['channel_id'] ?>">Order for <?= e($row['channel_name']) ?></label>
                                        <input id="order<?= (int) $row['channel_id'] ?>" type="number" name="orders[<?= (int) $row['channel_id'] ?>]" value="<?= (int) $row['featured_order'] ?>" min="0" max="65535" style="width:76px;height:34px;padding:0 8px;border:1px solid var(--border);border-radius:7px;background:rgba(255,255,255,.03);color:var(--text);font-size:11px;">
                                    </td>
                                    <td>
                                        <div class="admin-row-actions">
                                            <a class="admin-icon-btn" href="<?= e(admin_url('channel-edit.php?id=' . (int) $row['channel_id'])) ?>" title="Edit channel"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save order</button>
                </div>
            </form>

            <hr class="admin-divider">
            <div class="admin-row-actions" style="flex-wrap:wrap;">
                <?php foreach ($featured as $row): ?>
                    <form action="<?= e(admin_url('featured.php')) ?>" method="post" data-confirm="Remove “<?= e($row['channel_name']) ?>” from featured picks?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="channel_id" value="<?= (int) $row['channel_id'] ?>">
                        <button class="btn btn-ghost btn-sm" type="submit"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Remove <?= e($row['channel_name']) ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card-head"><h2>Add a featured channel</h2></div>
            <?php if ($availableChannels === []): ?>
                <p class="admin-help" style="margin-top:0;">Every channel is already featured.</p>
            <?php else: ?>
                <form action="<?= e(admin_url('featured.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="field" style="margin-bottom:12px;">
                        <label for="featuredChannel">Channel</label>
                        <select id="featuredChannel" name="channel_id" required>
                            <option value="">Choose a channel…</option>
                            <?php foreach ($availableChannels as $option): ?>
                                <option value="<?= (int) $option['id'] ?>"><?= e($option['channel_name']) ?> — <?= e($option['country_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-star" aria-hidden="true"></i> Add to featured</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>How it appears</h2></div>
            <p class="admin-help" style="margin-top:0;">The homepage window shows the first featured channels, ordered by the number in this table. Featured channels also receive a star in the public catalogue. Removing a channel here clears its featured flag.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

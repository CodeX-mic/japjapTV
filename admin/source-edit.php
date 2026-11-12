<?php
/**
 * Create or edit an official channel source.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$admin = current_admin();
$sourceId = ctype_digit(get_string('id')) ? (int) get_string('id') : 0;
$existing = $sourceId > 0 ? get_source_by_id($sourceId) : null;

if ($sourceId > 0 && $existing === null) {
    flash('error', 'That source could not be found.');
    redirect('admin/sources.php');
}

$channelOptions = db()->query('SELECT id, channel_name, slug FROM channels ORDER BY channel_name ASC')->fetchAll();
$presetChannel = ctype_digit(get_string('channel')) ? (int) get_string('channel') : 0;

$form = [
    'channel_id'    => (int) ($existing['channel_id'] ?? $presetChannel),
    'source_type'   => (string) ($existing['source_type'] ?? 'youtube_live'),
    'label'         => (string) ($existing['label'] ?? ''),
    'raw'           => (string) ($existing['provider_ref'] ?? '') !== '' ? (string) $existing['provider_ref'] : (string) ($existing['source_url'] ?? ''),
    'priority'      => (int) ($existing['priority'] ?? 1),
    'is_active'     => (int) ($existing['is_active'] ?? 1),
    'is_authorized' => (int) ($existing['is_authorized'] ?? 0),
    'check_now'     => 1,
];
$errors = [];
$checkResult = null;

if (is_post()) {
    admin_verify_csrf('admin/source-edit.php' . ($existing ? '?id=' . (int) $existing['id'] : ''));

    $form = [
        'channel_id'    => post_int('channel_id'),
        'source_type'   => post_string('source_type'),
        'label'         => post_string('label'),
        'raw'           => post_string('source_url'),
        'priority'      => max(1, min(255, post_int('priority', 1))),
        'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        'is_authorized' => isset($_POST['is_authorized']) ? 1 : 0,
        'check_now'     => isset($_POST['check_now']) ? 1 : 0,
    ];

    $stmt = db()->prepare('SELECT id, channel_name FROM channels WHERE id = :id');
    $stmt->execute(['id' => $form['channel_id']]);
    $channelRow = $stmt->fetch();
    if (!$channelRow) {
        $errors[] = 'Choose a valid channel.';
    }
    if (!in_array($form['source_type'], ['youtube_live', 'youtube_video', 'hls', 'iframe', 'external'], true)) {
        $errors[] = 'Choose a valid source type.';
    }
    if (mb_strlen($form['label']) < 3 || mb_strlen($form['label']) > 120) {
        $errors[] = 'Source label must be 3–120 characters.';
    }

    $normalized = normalize_source_input((string) $form['source_type'], (string) $form['raw'], (string) $form['label']);
    if ($normalized['error'] !== null) {
        $errors[] = $normalized['error'];
    }

    if ($errors === []) {
        $now = utc_now();
        $values = [
            'channel_id'    => $form['channel_id'],
            'source_type'   => $form['source_type'],
            'label'         => $form['label'],
            'source_url'    => $normalized['source_url'],
            'embed_url'     => $normalized['embed_url'],
            'provider_ref'  => $normalized['provider_ref'],
            'priority'      => $form['priority'],
            'is_active'     => $form['is_active'],
            'is_authorized' => $form['is_authorized'],
            'updated_at'    => $now,
        ];

        if ($existing !== null) {
            $values['id'] = $existing['id'];
            $stmt = db()->prepare('UPDATE channel_sources SET
                    channel_id = :channel_id,
                    source_type = :source_type,
                    label = :label,
                    source_url = :source_url,
                    embed_url = :embed_url,
                    provider_ref = :provider_ref,
                    priority = :priority,
                    is_active = :is_active,
                    is_authorized = :is_authorized,
                    updated_at = :updated_at
                WHERE id = :id');
            $stmt->execute($values);
            $sourceId = (int) $existing['id'];
            flash('success', 'Source updated.');
        } else {
            $values['created_at'] = $now;
            $stmt = db()->prepare('INSERT INTO channel_sources
                (channel_id, source_type, label, source_url, embed_url, provider_ref,
                 priority, is_active, is_authorized, last_check_status, created_at, updated_at)
                VALUES
                (:channel_id, :source_type, :label, :source_url, :embed_url, :provider_ref,
                 :priority, :is_active, :is_authorized, "unverified", :created_at, :updated_at)');
            $stmt->execute($values);
            $sourceId = (int) db()->lastInsertId();
            flash('success', 'Source added.');
        }

        if ($existing !== null && (int) $existing['channel_id'] !== (int) $form['channel_id']) {
            admin_sync_channel_sources((int) $existing['channel_id']);
        }

        if ($form['check_now'] === 1 && $form['is_active'] === 1 && $form['is_authorized'] === 1) {
            $fresh = get_source_by_id($sourceId);
            if ($fresh !== null) {
                set_time_limit(0);
                $checkResult = check_source_record($fresh, true, (int) $admin['id']);
                $meta = admin_source_status_meta((string) $checkResult['status']);
                flash($checkResult['status'] === 'live' ? 'success' : 'warning', 'Verification: ' . $meta[0] . ' — ' . $checkResult['message']);
            }
        } else {
            admin_sync_channel_sources((int) $form['channel_id']);
        }

        redirect('admin/source-edit.php?id=' . $sourceId . '&saved=1');
    }

    http_response_code(422);
}

$history = [];
if ($existing !== null) {
    $stmt = db()->prepare('SELECT * FROM source_checks WHERE source_id = :id ORDER BY checked_at DESC, id DESC LIMIT 10');
    $stmt->execute(['id' => $existing['id']]);
    $history = $stmt->fetchAll();
}

$adminTitle = $existing !== null ? 'Edit source' : 'Add source';
$adminSubtitle = $existing !== null ? $existing['channel_name'] : 'Attach an official, authorized player to a channel.';
$adminActive = 'sources';

require __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumbs" style="margin-bottom:16px;" aria-label="Breadcrumb">
    <a href="<?= e(admin_url('sources.php')) ?>">Sources</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span aria-current="page"><?= $existing !== null ? 'Edit' : 'Add' ?></span>
</nav>

<?php if (get_string('saved') === '1'): ?>
    <div class="admin-alert" style="border-color:rgba(57,217,138,.25);background:rgba(16,52,36,.35);">
        <i class="fa-solid fa-circle-check" aria-hidden="true" style="color:var(--success-soft);"></i>
        <div>
            <strong>Source saved.</strong>
            <p><?= $existing !== null ? 'Review the channel status and verification history below.' : 'The source is available from the channel’s watch page if active and authorized.' ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <strong>The source could not be saved:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($channelOptions === []): ?>
    <div class="admin-card admin-empty"><i class="fa-solid fa-tv" aria-hidden="true"></i> Add a channel before creating a source.</div>
<?php else: ?>
    <form action="<?= e(admin_url('source-edit.php' . ($existing !== null ? '?id=' . (int) $existing['id'] : ''))) ?>" method="post">
        <?= csrf_field() ?>
        <div class="admin-grid-3">
            <div>
                <div class="admin-card">
                    <div class="admin-card-head">
                        <div>
                            <h2>Source details</h2>
                            <p>Only official broadcaster players and authorized public manifests belong here.</p>
                        </div>
                    </div>

                    <div class="admin-form-grid">
                        <div class="field">
                            <label for="sourceChannel">Channel *</label>
                            <select id="sourceChannel" name="channel_id" required>
                                <option value="">Choose a channel…</option>
                                <?php foreach ($channelOptions as $option): ?>
                                    <option value="<?= (int) $option['id'] ?>"<?= (int) $form['channel_id'] === (int) $option['id'] ? ' selected' : '' ?>><?= e($option['channel_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="sourceType">Source type *</label>
                            <select id="sourceType" name="source_type" required>
                                <option value="youtube_live"<?= $form['source_type'] === 'youtube_live' ? ' selected' : '' ?>>YouTube live channel</option>
                                <option value="youtube_video"<?= $form['source_type'] === 'youtube_video' ? ' selected' : '' ?>>YouTube fixed video</option>
                                <option value="hls"<?= $form['source_type'] === 'hls' ? ' selected' : '' ?>>HLS manifest (HTTPS)</option>
                                <option value="iframe"<?= $form['source_type'] === 'iframe' ? ' selected' : '' ?>>Approved iframe URL</option>
                                <option value="external"<?= $form['source_type'] === 'external' ? ' selected' : '' ?>>External official link</option>
                            </select>
                        </div>
                        <div class="field field-full">
                            <label for="sourceLabel">Label *</label>
                            <input id="sourceLabel" type="text" name="label" value="<?= e($form['label']) ?>" maxlength="120" required placeholder="Official GMA Network YouTube livestream">
                            <span class="hint">Shown in the admin console and public source selector.</span>
                        </div>
                        <div class="field field-full">
                            <label for="sourceUrl">Source URL or identifier *</label>
                            <input id="sourceUrl" type="text" name="source_url" value="<?= e($form['raw']) ?>" maxlength="1000" required>
                            <span class="hint">
                                YouTube live: channel ID (UC…), /channel/UC… URL, or @handle · YouTube video: watch URL or 11-character ID · HLS: HTTPS .m3u8 · Iframe: only the approved allowlist · External: official viewing page.
                            </span>
                        </div>
                        <div class="field">
                            <label for="sourcePriority">Priority</label>
                            <input id="sourcePriority" type="number" name="priority" value="<?= (int) $form['priority'] ?>" min="1" max="255" step="1">
                            <span class="hint">Lower numbers are tried first.</span>
                        </div>
                    </div>

                    <hr class="admin-divider">

                    <label class="admin-check" style="margin-bottom:11px;">
                        <input type="checkbox" name="is_active" value="1"<?= (int) $form['is_active'] === 1 ? ' checked' : '' ?>>
                        <span><strong>Active</strong><br>Include this source when choosing a channel’s playable player.</span>
                    </label>
                    <label class="admin-check" style="margin-bottom:11px;">
                        <input type="checkbox" name="is_authorized" value="1"<?= (int) $form['is_authorized'] === 1 ? ' checked' : '' ?>>
                        <span><strong>Authorized</strong><br>I have confirmed this is a broadcaster-controlled source that permits embedding or linking. Unauthorized IPTV and scraped feeds are prohibited.</span>
                    </label>
                    <label class="admin-check">
                        <input type="checkbox" name="check_now" value="1"<?= (int) $form['check_now'] === 1 ? ' checked' : '' ?>>
                        <span><strong>Verify after saving</strong><br>Run the server-side check now if the source is active and authorized. This can take a few seconds.</span>
                    </label>
                </div>

                <?php if ($existing !== null): ?>
                    <div class="admin-card">
                        <div class="admin-card-head">
                            <div>
                                <h2>Verification history</h2>
                                <p>Most recent checks for this source.</p>
                            </div>
                            <form action="<?= e(admin_url('sources.php')) ?>" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="check">
                                <input type="hidden" name="source_id" value="<?= (int) $existing['id'] ?>">
                                <input type="hidden" name="channel" value="<?= (int) $existing['channel_id'] ?>">
                                <button class="btn btn-light btn-sm" type="submit"><i class="fa-solid fa-rotate" aria-hidden="true"></i> Check now</button>
                            </form>
                        </div>
                        <?php if ($history === []): ?>
                            <p class="admin-help" style="margin-top:0;">No checks logged yet.</p>
                        <?php else: ?>
                            <div class="admin-table-wrap">
                                <table class="admin-table" style="min-width:560px;">
                                    <thead><tr><th>Result</th><th>Message</th><th>HTTP</th><th>When</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($history as $row): ?>
                                            <?php $meta = admin_source_status_meta((string) $row['status']); ?>
                                            <tr>
                                                <td><span class="<?= e($meta[1]) ?>"><?= e($meta[0]) ?></span></td>
                                                <td><?= e(truncate_text((string) $row['message'], 100)) ?></td>
                                                <td class="is-muted"><?= $row['http_code'] !== null ? (int) $row['http_code'] : '—' ?></td>
                                                <td class="is-nowrap is-muted"><?= e(time_ago((string) $row['checked_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="admin-card">
                    <div class="admin-card-head"><h2>What gets checked</h2></div>
                    <dl class="admin-meta-list">
                        <div><dt>YouTube live</dt><dd>Live + embeddable</dd></div>
                        <div><dt>YouTube video</dt><dd>Public via oEmbed</dd></div>
                        <div><dt>HLS</dt><dd>Manifest + segments</dd></div>
                        <div><dt>Iframe</dt><dd>Manual only</dd></div>
                        <div><dt>External</dt><dd>Not embedded</dd></div>
                    </dl>
                    <p class="admin-help">A failed check is not enough to demote a channel. The source must fail definitively three times, and every active source must be down.</p>
                </div>

                <div class="admin-card">
                    <div class="admin-form-grid" style="grid-template-columns:minmax(0,1fr);">
                        <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $existing !== null ? 'Save source' : 'Add source' ?></button>
                        <a class="btn btn-ghost btn-block" href="<?= e(admin_url('sources.php')) ?>">Back to sources</a>
                        <?php if ($existing !== null): ?>
                            <a class="btn btn-ghost btn-block" href="<?= e(admin_url('channel-edit.php?id=' . (int) $existing['channel_id'])) ?>">Edit channel</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="admin-card">
                    <div class="admin-card-head"><h2>Source policy</h2></div>
                    <p class="admin-help" style="margin-top:0;">Never add pirate IPTV, restreams, scraped player URLs, DRM circumvention, or paid feeds without authorization. When embedding is not permitted, use the broadcaster’s official destination on the channel instead of a source row.</p>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

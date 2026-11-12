<?php
/**
 * Create or edit a channel, including logo upload and featured state.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$channelId = ctype_digit(get_string('id')) ? (int) get_string('id') : 0;
$existing = $channelId > 0 ? get_channel_by_id($channelId) : null;

if ($channelId > 0 && $existing === null) {
    flash('error', 'That channel could not be found.');
    redirect('admin/channels.php');
}

$countryOptions = get_country_options();
$categoryOptions = get_category_tiles();
$firstCountryId = (int) ($countryOptions[0]['id'] ?? 0);
$firstCategoryId = (int) ($categoryOptions[0]['id'] ?? 0);

$form = [
    'channel_name'        => (string) ($existing['channel_name'] ?? ''),
    'slug'                => (string) ($existing['slug'] ?? ''),
    'description'         => (string) ($existing['description'] ?? ''),
    'country_id'          => (int) ($existing['country_id'] ?? $firstCountryId),
    'category_id'         => (int) ($existing['category_id'] ?? $firstCategoryId),
    'official_url'        => (string) ($existing['official_url'] ?? ''),
    'access_type'         => (string) ($existing['access_type'] ?? 'free'),
    'availability_status' => (string) ($existing['availability_status'] ?? 'external_only'),
    'region'              => (string) ($existing['region'] ?? 'international'),
    'language'            => (string) ($existing['language'] ?? 'English'),
    'accent_color'        => (string) ($existing['accent_color'] ?? '#ff3b47'),
    'notice'              => (string) ($existing['notice'] ?? ''),
    'is_featured'         => (int) ($existing['is_featured'] ?? 0),
    'display_order'       => (int) ($existing['display_order'] ?? 0),
    'logo'                => (string) ($existing['logo'] ?? ''),
];
$errors = [];
$logoPath = (string) ($existing['logo'] ?? '');
$saved = get_string('saved') === '1';

if (is_post()) {
    admin_verify_csrf('admin/channel-edit.php' . ($existing ? '?id=' . (int) $existing['id'] : ''));

    $form = [
        'channel_name'        => post_string('channel_name'),
        'slug'                => post_string('slug'),
        'description'         => post_string('description'),
        'country_id'          => post_int('country_id'),
        'category_id'         => post_int('category_id'),
        'official_url'        => post_string('official_url'),
        'access_type'         => post_string('access_type', 'unknown'),
        'availability_status' => post_string('availability_status', 'external_only'),
        'region'              => post_string('region', 'international'),
        'language'            => post_string('language', 'English'),
        'accent_color'        => post_string('accent_color', '#ff3b47'),
        'notice'              => post_string('notice'),
        'is_featured'         => isset($_POST['is_featured']) ? 1 : 0,
        'display_order'       => post_int('display_order'),
        'logo'                => (string) ($existing['logo'] ?? ''),
    ];

    [$data, $errors] = admin_validate_channel($form, $existing);
    $removeLogo = isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1';

    if ($errors === []) {
        $upload = admin_handle_logo_upload($_FILES['logo'] ?? null, (string) $data['slug']);
        if ($upload['error'] !== null) {
            $errors[] = $upload['error'];
        } elseif ($upload['path'] !== null) {
            $logoPath = $upload['path'];
            admin_remove_uploaded_logo((string) ($existing['logo'] ?? ''));
        } elseif ($removeLogo) {
            $logoPath = null;
            admin_remove_uploaded_logo((string) ($existing['logo'] ?? ''));
        }
    }

    if ($errors === []) {
        $now = utc_now();

        if ($existing !== null) {
            $stmt = db()->prepare('UPDATE channels SET
                    channel_name = :channel_name,
                    slug = :slug,
                    logo = :logo,
                    description = :description,
                    country_id = :country_id,
                    category_id = :category_id,
                    official_url = :official_url,
                    access_type = :access_type,
                    availability_status = :availability_status,
                    region = :region,
                    language = :language,
                    accent_color = :accent_color,
                    notice = :notice,
                    is_featured = :is_featured,
                    display_order = :display_order,
                    updated_at = :updated_at
                WHERE id = :id');
            $stmt->execute([
                'channel_name'        => $data['channel_name'],
                'slug'                => $data['slug'],
                'logo'                => $logoPath !== '' ? $logoPath : null,
                'description'         => $data['description'],
                'country_id'          => $data['country_id'],
                'category_id'         => $data['category_id'],
                'official_url'        => $data['official_url'],
                'access_type'         => $data['access_type'],
                'availability_status' => $data['availability_status'],
                'region'              => $data['region'],
                'language'            => $data['language'],
                'accent_color'        => $data['accent_color'],
                'notice'              => $data['notice'],
                'is_featured'         => $data['is_featured'],
                'display_order'       => $data['display_order'],
                'updated_at'          => $now,
                'id'                  => $existing['id'],
            ]);
            $channelId = (int) $existing['id'];
            flash('success', 'Channel updated.');
        } else {
            $stmt = db()->prepare('INSERT INTO channels
                (channel_name, slug, logo, description, country_id, category_id, official_url,
                 access_type, availability_status, region, language, accent_color, notice,
                 is_featured, display_order, view_count, created_at, updated_at)
                VALUES
                (:channel_name, :slug, :logo, :description, :country_id, :category_id, :official_url,
                 :access_type, :availability_status, :region, :language, :accent_color, :notice,
                 :is_featured, :display_order, 0, :created_at, :updated_at)');
            $stmt->execute([
                'channel_name'        => $data['channel_name'],
                'slug'                => $data['slug'],
                'logo'                => $logoPath !== '' ? $logoPath : null,
                'description'         => $data['description'],
                'country_id'          => $data['country_id'],
                'category_id'         => $data['category_id'],
                'official_url'        => $data['official_url'],
                'access_type'         => $data['access_type'],
                'availability_status' => $data['availability_status'],
                'region'              => $data['region'],
                'language'            => $data['language'],
                'accent_color'        => $data['accent_color'],
                'notice'              => $data['notice'],
                'is_featured'         => $data['is_featured'],
                'display_order'       => $data['display_order'],
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
            $channelId = (int) db()->lastInsertId();
            flash('success', 'Channel created. Add an official source next.');
        }

        if ($data['is_featured'] === 1) {
            $featured = db()->prepare('INSERT INTO featured_channels (channel_id, display_order)
                VALUES (:channel_id, :display_order)
                ON DUPLICATE KEY UPDATE display_order = VALUES(display_order)');
            $featured->execute(['channel_id' => $channelId, 'display_order' => $data['display_order']]);
        } else {
            db()->prepare('DELETE FROM featured_channels WHERE channel_id = :id')->execute(['id' => $channelId]);
        }

        redirect('admin/channel-edit.php?id=' . $channelId . '&saved=1');
    }

    http_response_code(422);
}

$previewChannel = [
    'slug' => $form['slug'] !== '' ? slugify($form['slug']) : slugify($form['channel_name']),
    'logo' => $logoPath,
];
$previewLogo = channel_logo_url($previewChannel);
$sourceSummary = $existing !== null ? get_channel_sources((int) $existing['id']) : [];

$adminTitle = $existing !== null ? 'Edit channel' : 'Add channel';
$adminSubtitle = $existing !== null ? $existing['channel_name'] : 'Create a listing, then attach an official source.';
$adminActive = 'channels';

require __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumbs" style="margin-bottom:16px;" aria-label="Breadcrumb">
    <a href="<?= e(admin_url('channels.php')) ?>">Channels</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span aria-current="page"><?= $existing !== null ? 'Edit' : 'Add' ?></span>
</nav>

<?php if ($saved): ?>
    <div class="admin-alert" style="border-color:rgba(57,217,138,.25);background:rgba(16,52,36,.35);">
        <i class="fa-solid fa-circle-check" aria-hidden="true" style="color:var(--success-soft);"></i>
        <div><strong>Channel saved.</strong><p>Review the public page and attach or re-check an official source if needed.</p></div>
    </div>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <strong>The channel could not be saved:</strong>
        <ul>
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form action="<?= e(admin_url('channel-edit.php' . ($existing !== null ? '?id=' . (int) $existing['id'] : ''))) ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="admin-grid-3">
        <div>
            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <h2>Channel details</h2>
                        <p>The name, copy, and links shown on the public page.</p>
                    </div>
                </div>

                <div class="admin-form-grid">
                    <div class="field">
                        <label for="channelName">Channel name *</label>
                        <input id="channelName" type="text" name="channel_name" value="<?= e($form['channel_name']) ?>" maxlength="120" required>
                    </div>
                    <div class="field">
                        <label for="channelSlug">Slug</label>
                        <input id="channelSlug" type="text" name="slug" value="<?= e($form['slug']) ?>" maxlength="140" placeholder="auto-generated from name">
                        <span class="hint">Used in the watch URL. Leave blank to generate automatically.</span>
                    </div>
                    <div class="field field-full">
                        <label for="channelDescription">Description *</label>
                        <textarea id="channelDescription" name="description" rows="5" maxlength="4000" required><?= e($form['description']) ?></textarea>
                        <span class="hint">Be factual: network, programming, and whether access is free or paid.</span>
                    </div>
                    <div class="field field-full">
                        <label for="officialUrl">Official destination URL *</label>
                        <input id="officialUrl" type="url" name="official_url" value="<?= e($form['official_url']) ?>" maxlength="1000" required placeholder="https://www.example.com/watch-live">
                        <span class="hint">The broadcaster’s own website or official streaming page. Never an unauthorized third-party feed.</span>
                    </div>
                    <div class="field field-full">
                        <label for="channelNotice">Notice (optional)</label>
                        <input id="channelNotice" type="text" name="notice" value="<?= e($form['notice']) ?>" maxlength="255" placeholder="e.g. Ceased broadcasting in September 2024">
                        <span class="hint">Shown as a caution on the watch page. Keep it short and factual.</span>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-head">
                    <div>
                        <h2>Classification</h2>
                        <p>Where the channel appears and how access is described.</p>
                    </div>
                </div>

                <div class="admin-form-grid">
                    <div class="field">
                        <label for="channelCountry">Country *</label>
                        <select id="channelCountry" name="country_id" required>
                            <?php foreach ($countryOptions as $country): ?>
                                <option value="<?= (int) $country['id'] ?>"<?= (int) $form['country_id'] === (int) $country['id'] ? ' selected' : '' ?>><?= e($country['flag_emoji']) ?> <?= e($country['country_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="channelCategory">Category *</label>
                        <select id="channelCategory" name="category_id" required>
                            <?php foreach ($categoryOptions as $category): ?>
                                <option value="<?= (int) $category['id'] ?>"<?= (int) $form['category_id'] === (int) $category['id'] ? ' selected' : '' ?>><?= e($category['category_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="channelRegion">Region *</label>
                        <select id="channelRegion" name="region" required>
                            <option value="ph"<?= $form['region'] === 'ph' ? ' selected' : '' ?>>Philippines</option>
                            <option value="international"<?= $form['region'] === 'international' ? ' selected' : '' ?>>International</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="channelLanguage">Language *</label>
                        <input id="channelLanguage" type="text" name="language" value="<?= e($form['language']) ?>" maxlength="80" required>
                    </div>
                    <div class="field">
                        <label for="channelAccess">Access type *</label>
                        <select id="channelAccess" name="access_type" required>
                            <option value="free"<?= $form['access_type'] === 'free' ? ' selected' : '' ?>>Free</option>
                            <option value="subscription"<?= $form['access_type'] === 'subscription' ? ' selected' : '' ?>>Subscription</option>
                            <option value="unknown"<?= $form['access_type'] === 'unknown' ? ' selected' : '' ?>>Check access</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="channelAvailability">Availability *</label>
                        <?php $statusOptions = ['available' => 'Live now (source verified)', 'external_only' => 'Official site / external', 'unavailable' => 'Unavailable', 'coming_soon' => 'Coming soon']; ?>
                        <select id="channelAvailability" name="availability_status" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $form['availability_status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">“Live now” requires an active, authorized source. The checker updates this automatically after verification.</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="admin-card-head">
                    <h2>Logo</h2>
                </div>
                <div class="admin-logo-row">
                    <img class="admin-logo-preview" src="<?= e($previewLogo) ?>" width="70" height="70" alt="Logo preview">
                    <div>
                        <span class="admin-help">Generate a fallback from the slug, or upload a logo you are allowed to use.</span>
                    </div>
                </div>
                <div class="field">
                    <label for="channelLogo">Upload logo</label>
                    <input id="channelLogo" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                    <span class="hint">PNG, JPEG, or WebP. Max 2 MB, up to 4000×4000. SVG is not accepted.</span>
                </div>
                <?php if ($logoPath !== ''): ?>
                    <label class="admin-check" style="margin-top:12px;">
                        <input type="checkbox" name="remove_logo" value="1">
                        <span>Remove the uploaded logo and use the generated fallback.</span>
                    </label>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <div class="admin-card-head">
                    <h2>Presentation</h2>
                </div>
                <div class="field" style="margin-bottom:13px;">
                    <label for="accentColor">Accent colour</label>
                    <input id="accentColor" type="text" name="accent_color" value="<?= e($form['accent_color']) ?>" maxlength="7" pattern="#[0-9a-fA-F]{6}" required>
                    <span class="hint">Six-digit hex, e.g. #e50914.</span>
                </div>
                <div class="field" style="margin-bottom:13px;">
                    <label for="displayOrder">Display order</label>
                    <input id="displayOrder" type="number" name="display_order" value="<?= (int) $form['display_order'] ?>" min="0" max="65535" step="1">
                    <span class="hint">Lower numbers appear first.</span>
                </div>
                <label class="admin-check">
                    <input type="checkbox" name="is_featured" value="1"<?= (int) $form['is_featured'] === 1 ? ' checked' : '' ?>>
                    <span><strong>Feature this channel</strong><br>Adds it to the homepage featured strip. You can reorder featured picks separately.</span>
                </label>
            </div>

            <?php if ($existing !== null): ?>
                <div class="admin-card">
                    <div class="admin-card-head">
                        <h2>Sources</h2>
                        <a class="admin-card-link" href="<?= e(admin_url('sources.php?channel=' . (int) $existing['id'])) ?>">Manage <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <?php if ($sourceSummary === []): ?>
                        <p class="admin-help" style="margin-top:0;">No sources yet. Add one before marking this channel “Live now”.</p>
                    <?php else: ?>
                        <?php foreach ($sourceSummary as $src): ?>
                            <?php $meta = admin_source_status_meta((string) $src['last_check_status']); ?>
                            <div class="admin-source">
                                <div class="admin-source-head">
                                    <h3 style="font-size:0.8rem;"><?= e($src['label']) ?></h3>
                                    <span class="<?= e($meta[1]) ?>"><?= e($meta[0]) ?></span>
                                </div>
                                <div class="admin-source-meta">
                                    <span><?= e(source_type_label((string) $src['source_type'])) ?></span>
                                    <span><?= (int) $src['is_active'] === 1 ? 'Active' : 'Inactive' ?></span>
                                    <span><?= (int) $src['is_authorized'] === 1 ? 'Authorized' : 'Not authorized' ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="admin-form-grid" style="grid-template-columns:minmax(0,1fr);">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $existing !== null ? 'Save changes' : 'Create channel' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(admin_url('channels.php')) ?>">Cancel</a>
                    <?php if ($existing !== null): ?>
                        <a class="btn btn-ghost btn-block" href="<?= e(url('watch.php?id=' . rawurlencode((string) $existing['slug']))) ?>" target="_blank" rel="noopener">Preview public page <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>

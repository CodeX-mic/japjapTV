<?php
/**
 * Manage countries.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$editing = null;
$errors = [];
$form = [
    'country_name'  => '',
    'country_code'  => '',
    'flag_emoji'    => '',
    'region'        => 'International',
    'display_order' => 0,
];

if (is_post()) {
    admin_verify_csrf('admin/countries.php');
    $action = post_string('action');

    if ($action === 'delete') {
        $deleteId = post_int('country_id');
        $stmt = db()->prepare('SELECT c.country_name, c.country_code,
                (SELECT COUNT(*) FROM channels ch WHERE ch.country_id = c.id) AS channel_count
            FROM countries c WHERE c.id = :id');
        $stmt->execute(['id' => $deleteId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'That country no longer exists.');
        } elseif ((int) $target['channel_count'] > 0) {
            flash('error', '“' . $target['country_name'] . '” still has ' . (int) $target['channel_count'] . ' channel(s). Move or delete those channels first.');
        } else {
            db()->prepare('DELETE FROM countries WHERE id = :id')->execute(['id' => $deleteId]);
            flash('success', 'Country “' . $target['country_name'] . '” deleted.');
        }
        redirect('admin/countries.php');
    }

    if ($action === 'save') {
        $editId = post_int('country_id');
        $editing = $editId > 0 ? ['id' => $editId] : null;
        if ($editId > 0) {
            $stmt = db()->prepare('SELECT * FROM countries WHERE id = :id');
            $stmt->execute(['id' => $editId]);
            $editing = $stmt->fetch() ?: null;
            if ($editing === null) {
                flash('error', 'That country no longer exists.');
                redirect('admin/countries.php');
            }
        }

        $form = [
            'country_name'  => post_string('country_name'),
            'country_code'  => post_string('country_code'),
            'flag_emoji'    => post_string('flag_emoji'),
            'region'        => post_string('region', 'International'),
            'display_order' => post_int('display_order'),
        ];

        [$data, $errors] = admin_validate_country($form, $editing);

        if ($errors === []) {
            if ($editing !== null) {
                $stmt = db()->prepare('UPDATE countries SET country_name = :name, country_code = :code,
                    flag_emoji = :flag, region = :region, display_order = :display_order WHERE id = :id');
                $stmt->execute([
                    'name'          => $data['country_name'],
                    'code'          => $data['country_code'],
                    'flag'          => $data['flag_emoji'],
                    'region'        => $data['region'],
                    'display_order' => $data['display_order'],
                    'id'            => $editing['id'],
                ]);
                flash('success', 'Country updated.');
            } else {
                $stmt = db()->prepare('INSERT INTO countries
                    (country_name, country_code, flag_emoji, region, display_order, created_at)
                    VALUES (:name, :code, :flag, :region, :display_order, :created_at)');
                $stmt->execute([
                    'name'          => $data['country_name'],
                    'code'          => $data['country_code'],
                    'flag'          => $data['flag_emoji'],
                    'region'        => $data['region'],
                    'display_order' => $data['display_order'],
                    'created_at'    => utc_now(),
                ]);
                flash('success', 'Country created.');
            }
            redirect('admin/countries.php');
        }
        http_response_code(422);
    }
}

if (!is_post() && ctype_digit(get_string('edit'))) {
    $stmt = db()->prepare('SELECT * FROM countries WHERE id = :id');
    $stmt->execute(['id' => (int) get_string('edit')]);
    $editing = $stmt->fetch() ?: null;
    if ($editing !== null) {
        $form = [
            'country_name'  => $editing['country_name'],
            'country_code'  => $editing['country_code'],
            'flag_emoji'    => (string) ($editing['flag_emoji'] ?? ''),
            'region'        => $editing['region'],
            'display_order' => $editing['display_order'],
        ];
    }
}

$countries = db()->query('SELECT co.*, COUNT(c.id) AS channel_count,
        SUM(CASE WHEN c.availability_status = "available" THEN 1 ELSE 0 END) AS live_count
    FROM countries co LEFT JOIN channels c ON c.country_id = co.id
    GROUP BY co.id
    ORDER BY co.display_order ASC, co.country_name ASC')->fetchAll();

$adminTitle = 'Countries';
$adminSubtitle = 'Country labels and flags used across filters, cards, and country hub pages.';
$adminActive = 'countries';

require __DIR__ . '/includes/header.php';
?>
<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <strong>The country could not be saved:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div class="admin-card">
        <div class="admin-card-head">
            <div><h2>All countries</h2><p><?= count($countries) ?> countries represented.</p></div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table" style="min-width:650px;">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Code</th>
                        <th>Region</th>
                        <th>Channels</th>
                        <th>Live</th>
                        <th>Order</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($countries as $country): ?>
                        <tr>
                            <td class="is-primary"><span aria-hidden="true"><?= e($country['flag_emoji'] ?? '') ?></span> <?= e($country['country_name']) ?></td>
                            <td class="is-muted"><?= e($country['country_code']) ?></td>
                            <td><?= e($country['region']) ?></td>
                            <td><?= (int) $country['channel_count'] ?></td>
                            <td><?= (int) $country['live_count'] ?></td>
                            <td class="is-muted"><?= (int) $country['display_order'] ?></td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-icon-btn" href="<?= e(admin_url('countries.php?edit=' . (int) $country['id'])) ?>" title="Edit country"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form action="<?= e(admin_url('countries.php')) ?>" method="post" data-confirm="Delete the country “<?= e($country['country_name']) ?>”? This only works if it has no channels.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="country_id" value="<?= (int) $country['id'] ?>">
                                        <button class="admin-icon-btn is-danger" type="submit" title="Delete country"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2><?= $editing !== null ? 'Edit country' : 'Add country' ?></h2>
                    <?php if ($editing !== null): ?><p>Editing “<?= e($editing['country_name']) ?>”.</p><?php endif; ?>
                </div>
                <?php if ($editing !== null): ?><a class="admin-card-link" href="<?= e(admin_url('countries.php')) ?>">Cancel</a><?php endif; ?>
            </div>

            <form action="<?= e(admin_url('countries.php')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="country_id" value="<?= (int) ($editing['id'] ?? 0) ?>">

                <div class="field" style="margin-bottom:12px;">
                    <label for="countryName">Name *</label>
                    <input id="countryName" type="text" name="country_name" value="<?= e($form['country_name']) ?>" maxlength="100" required>
                </div>
                <div class="admin-form-grid" style="grid-template-columns:0.7fr 1.3fr;margin-bottom:12px;">
                    <div class="field">
                        <label for="countryCode">ISO code *</label>
                        <input id="countryCode" type="text" name="country_code" value="<?= e($form['country_code']) ?>" maxlength="2" required placeholder="PH" style="text-transform:uppercase;">
                    </div>
                    <div class="field">
                        <label for="countryFlag">Flag emoji</label>
                        <input id="countryFlag" type="text" name="flag_emoji" value="<?= e($form['flag_emoji']) ?>" maxlength="16" placeholder="🇵🇭">
                    </div>
                </div>
                <div class="field" style="margin-bottom:12px;">
                    <label for="countryRegion">Region *</label>
                    <input id="countryRegion" type="text" name="region" value="<?= e($form['region']) ?>" maxlength="60" required placeholder="Southeast Asia">
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label for="countryOrder">Display order</label>
                    <input id="countryOrder" type="number" name="display_order" value="<?= (int) $form['display_order'] ?>" min="0" max="65535">
                </div>

                <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $editing !== null ? 'Save country' : 'Create country' ?></button>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>Flag tip</h2></div>
            <p class="admin-help" style="margin-top:0;">Paste the Unicode flag emoji directly. On Windows, press <span class="admin-kbd">Win</span> + <span class="admin-kbd">.</span> to open the emoji picker. The flag is decorative; the country code drives filtering.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

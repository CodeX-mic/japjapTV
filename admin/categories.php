<?php
/**
 * Manage channel categories.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$editing = null;
$errors = [];
$form = [
    'category_name' => '',
    'category_slug' => '',
    'description'   => '',
    'icon'          => 'fa-tv',
    'color'         => '#ff3b47',
    'display_order' => 0,
];

if (is_post()) {
    admin_verify_csrf('admin/categories.php');
    $action = post_string('action');

    if ($action === 'delete') {
        $deleteId = post_int('category_id');
        $stmt = db()->prepare('SELECT c.category_name,
                (SELECT COUNT(*) FROM channels ch WHERE ch.category_id = c.id) AS channel_count
            FROM categories c WHERE c.id = :id');
        $stmt->execute(['id' => $deleteId]);
        $target = $stmt->fetch();

        if (!$target) {
            flash('error', 'That category no longer exists.');
        } elseif ((int) $target['channel_count'] > 0) {
            flash('error', '“' . $target['category_name'] . '” still has ' . (int) $target['channel_count'] . ' channel(s). Move or delete those channels first.');
        } else {
            db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $deleteId]);
            flash('success', 'Category “' . $target['category_name'] . '” deleted.');
        }
        redirect('admin/categories.php');
    }

    if ($action === 'save') {
        $editId = post_int('category_id');
        $editing = $editId > 0 ? ['id' => $editId] : null;
        if ($editId > 0) {
            $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id');
            $stmt->execute(['id' => $editId]);
            $editing = $stmt->fetch() ?: null;
            if ($editing === null) {
                flash('error', 'That category no longer exists.');
                redirect('admin/categories.php');
            }
        }

        $form = [
            'category_name' => post_string('category_name'),
            'category_slug' => post_string('category_slug'),
            'description'   => post_string('description'),
            'icon'          => post_string('icon', 'fa-tv'),
            'color'         => post_string('color', '#ff3b47'),
            'display_order' => post_int('display_order'),
        ];

        [$data, $errors] = admin_validate_category($form, $editing);

        if ($errors === []) {
            if ($editing !== null) {
                $stmt = db()->prepare('UPDATE categories SET category_name = :name, category_slug = :slug,
                    description = :description, icon = :icon, color = :color, display_order = :display_order
                    WHERE id = :id');
                $stmt->execute([
                    'name'          => $data['category_name'],
                    'slug'          => $data['category_slug'],
                    'description'   => $data['description'],
                    'icon'          => $data['icon'],
                    'color'         => $data['color'],
                    'display_order' => $data['display_order'],
                    'id'            => $editing['id'],
                ]);
                flash('success', 'Category updated.');
            } else {
                $stmt = db()->prepare('INSERT INTO categories
                    (category_name, category_slug, description, icon, color, display_order, created_at)
                    VALUES (:name, :slug, :description, :icon, :color, :display_order, :created_at)');
                $stmt->execute([
                    'name'          => $data['category_name'],
                    'slug'          => $data['category_slug'],
                    'description'   => $data['description'],
                    'icon'          => $data['icon'],
                    'color'         => $data['color'],
                    'display_order' => $data['display_order'],
                    'created_at'    => utc_now(),
                ]);
                flash('success', 'Category created.');
            }
            redirect('admin/categories.php');
        }
        http_response_code(422);
    }
}

if (!is_post() && ctype_digit(get_string('edit'))) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id');
    $stmt->execute(['id' => (int) get_string('edit')]);
    $editing = $stmt->fetch() ?: null;
    if ($editing !== null) {
        $form = [
            'category_name' => $editing['category_name'],
            'category_slug' => $editing['category_slug'],
            'description'   => (string) ($editing['description'] ?? ''),
            'icon'          => $editing['icon'],
            'color'         => $editing['color'],
            'display_order' => $editing['display_order'],
        ];
    }
}

$categories = db()->query("SELECT cat.*,
        COUNT(c.id) AS channel_count,
        SUM(CASE WHEN c.availability_status = 'available' THEN 1 ELSE 0 END) AS live_count
    FROM categories cat
    LEFT JOIN channels c ON c.category_id = cat.id
    GROUP BY cat.id
    ORDER BY cat.display_order ASC, cat.category_name ASC")->fetchAll();

$adminTitle = 'Categories';
$adminSubtitle = 'Organize the catalogue and control the tiles shown on the homepage and filters.';
$adminActive = 'categories';

require __DIR__ . '/includes/header.php';
?>
<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <strong>The category could not be saved:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div class="admin-card">
        <div class="admin-card-head">
            <div>
                <h2>All categories</h2>
                <p><?= count($categories) ?> categories.</p>
            </div>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table" style="min-width:720px;">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Channels</th>
                        <th>Live</th>
                        <th>Order</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <div class="admin-channel-cell">
                                    <span class="admin-avatar" style="background:<?= e($category['color']) ?>22;color:<?= e($category['color']) ?>;border:1px solid <?= e($category['color']) ?>33;"><i class="fa-solid <?= e($category['icon']) ?>" aria-hidden="true"></i></span>
                                    <span>
                                        <strong><?= e($category['category_name']) ?></strong>
                                        <small><?= e(truncate_text((string) ($category['description'] ?? ''), 48)) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="is-muted"><?= e($category['category_slug']) ?></td>
                            <td><?= (int) $category['channel_count'] ?></td>
                            <td><?= (int) $category['live_count'] ?></td>
                            <td class="is-muted"><?= (int) $category['display_order'] ?></td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="admin-icon-btn" href="<?= e(admin_url('categories.php?edit=' . (int) $category['id'])) ?>" title="Edit category"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                    <form action="<?= e(admin_url('categories.php')) ?>" method="post" data-confirm="Delete the category “<?= e($category['category_name']) ?>”? This only works if it has no channels.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>">
                                        <button class="admin-icon-btn is-danger" type="submit" title="Delete category"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
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
                    <h2><?= $editing !== null ? 'Edit category' : 'Add category' ?></h2>
                    <?php if ($editing !== null): ?><p>Editing “<?= e($editing['category_name']) ?>”.</p><?php endif; ?>
                </div>
                <?php if ($editing !== null): ?><a class="admin-card-link" href="<?= e(admin_url('categories.php')) ?>">Cancel</a><?php endif; ?>
            </div>

            <form action="<?= e(admin_url('categories.php')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="category_id" value="<?= (int) ($editing['id'] ?? 0) ?>">

                <div class="field" style="margin-bottom:12px;">
                    <label for="categoryName">Name *</label>
                    <input id="categoryName" type="text" name="category_name" value="<?= e($form['category_name']) ?>" maxlength="80" required>
                </div>
                <div class="field" style="margin-bottom:12px;">
                    <label for="categorySlug">Slug *</label>
                    <input id="categorySlug" type="text" name="category_slug" value="<?= e($form['category_slug']) ?>" maxlength="80" required placeholder="news">
                </div>
                <div class="field" style="margin-bottom:12px;">
                    <label for="categoryDescription">Description</label>
                    <input id="categoryDescription" type="text" name="description" value="<?= e($form['description']) ?>" maxlength="255">
                </div>
                <div class="admin-form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));margin-bottom:12px;">
                    <div class="field">
                        <label for="categoryIcon">Font Awesome icon *</label>
                        <input id="categoryIcon" type="text" name="icon" value="<?= e($form['icon']) ?>" maxlength="60" required placeholder="fa-tv">
                    </div>
                    <div class="field">
                        <label for="categoryColor">Colour *</label>
                        <input id="categoryColor" type="text" name="color" value="<?= e($form['color']) ?>" maxlength="7" pattern="#[0-9a-fA-F]{6}" required>
                    </div>
                </div>
                <div class="field" style="margin-bottom:14px;">
                    <label for="categoryOrder">Display order</label>
                    <input id="categoryOrder" type="number" name="display_order" value="<?= (int) $form['display_order'] ?>" min="0" max="65535">
                </div>

                <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $editing !== null ? 'Save category' : 'Create category' ?></button>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>Icon examples</h2></div>
            <p class="admin-help" style="margin-top:0;">Use any free Font Awesome 6 solid class, for example <code>fa-newspaper</code>, <code>fa-film</code>, <code>fa-music</code>, <code>fa-earth-americas</code>, or <code>fa-graduation-cap</code>. The icon appears on the homepage tiles and filters.</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

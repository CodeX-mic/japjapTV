<?php
/**
 * Shared channel filter bar.
 *
 * Expects:
 *   $filterAction (string, e.g. 'channels.php')
 *   $filters      (normalized filter array)
 *   optional $showRegion (bool), $hideCountry (bool)
 */

declare(strict_types=1);

$filterAction = $filterAction ?? 'channels.php';
$showRegion = $showRegion ?? true;
$hideCountry = $hideCountry ?? false;
$categoryOptions = $categoryOptions ?? get_category_tiles();
$countryOptions = $countryOptions ?? get_country_options();

$categoryNames = [];
foreach ($categoryOptions as $option) {
    $categoryNames[(string) $option['category_slug']] = (string) $option['category_name'];
}
$countryNames = [];
foreach ($countryOptions as $option) {
    $countryNames[(string) $option['country_code']] = (string) $option['country_name'];
}

$chips = [];
if ($filters['q'] !== '') {
    $chips['q'] = ['label' => '“' . $filters['q'] . '”', 'icon' => 'fa-magnifying-glass'];
}
if ($filters['category'] !== '' && isset($categoryNames[$filters['category']])) {
    $chips['category'] = ['label' => $categoryNames[$filters['category']], 'icon' => 'fa-layer-group'];
}
if ($filters['country'] !== '' && isset($countryNames[$filters['country']])) {
    $chips['country'] = ['label' => $countryNames[$filters['country']], 'icon' => 'fa-globe'];
}
if ($filters['region'] !== '') {
    $chips['region'] = ['label' => $filters['region'] === 'ph' ? 'Philippines' : 'International', 'icon' => 'fa-location-dot'];
}
if ($filters['access'] !== '') {
    $chips['access'] = ['label' => access_label($filters['access']), 'icon' => 'fa-key'];
}
if ($filters['status'] !== '') {
    $chips['status'] = ['label' => availability_meta($filters['status'])['label'], 'icon' => 'fa-tower-broadcast'];
}
?><form class="filter-panel" action="<?= e(url($filterAction)) ?>" method="get" role="search" aria-label="Filter channels">
    <div class="filter-panel__search">
        <label for="filterQ">Search</label>
        <div class="input-with-icon">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input id="filterQ" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Channel, country, or category" maxlength="100" autocomplete="off">
        </div>
    </div>

    <div class="filter-panel__field">
        <label for="filterCategory">Category</label>
        <select id="filterCategory" name="category">
            <option value="">All categories</option>
            <?php foreach ($categoryOptions as $option): ?>
                <?php if ((int) $option['channel_count'] === 0) { continue; } ?>
                <option value="<?= e($option['category_slug']) ?>"<?= $filters['category'] === $option['category_slug'] ? ' selected' : '' ?>><?= e($option['category_name']) ?> (<?= (int) $option['channel_count'] ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php if (!$hideCountry): ?>
        <div class="filter-panel__field">
            <label for="filterCountry">Country</label>
            <select id="filterCountry" name="country">
                <option value="">All countries</option>
                <?php foreach ($countryOptions as $option): ?>
                    <option value="<?= e($option['country_code']) ?>"<?= $filters['country'] === $option['country_code'] ? ' selected' : '' ?>><?= e($option['flag_emoji']) ?> <?= e($option['country_name']) ?> (<?= (int) $option['channel_count'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if ($showRegion): ?>
        <div class="filter-panel__field">
            <label for="filterRegion">Region</label>
            <select id="filterRegion" name="region">
                <option value="">All regions</option>
                <option value="ph"<?= $filters['region'] === 'ph' ? ' selected' : '' ?>>Philippines</option>
                <option value="international"<?= $filters['region'] === 'international' ? ' selected' : '' ?>>International</option>
            </select>
        </div>
    <?php endif; ?>

    <div class="filter-panel__field">
        <label for="filterAccess">Access</label>
        <select id="filterAccess" name="access">
            <option value="">Any access</option>
            <option value="free"<?= $filters['access'] === 'free' ? ' selected' : '' ?>>Free</option>
            <option value="subscription"<?= $filters['access'] === 'subscription' ? ' selected' : '' ?>>Subscription</option>
            <option value="unknown"<?= $filters['access'] === 'unknown' ? ' selected' : '' ?>>Check access</option>
        </select>
    </div>

    <div class="filter-panel__field">
        <label for="filterStatus">Status</label>
        <select id="filterStatus" name="status">
            <option value="">Any status</option>
            <option value="available"<?= $filters['status'] === 'available' ? ' selected' : '' ?>>Live now</option>
            <option value="external_only"<?= $filters['status'] === 'external_only' ? ' selected' : '' ?>>Official site</option>
            <option value="unavailable"<?= $filters['status'] === 'unavailable' ? ' selected' : '' ?>>Unavailable</option>
            <option value="coming_soon"<?= $filters['status'] === 'coming_soon' ? ' selected' : '' ?>>Coming soon</option>
        </select>
    </div>

    <div class="filter-panel__field">
        <label for="filterSort">Sort</label>
        <select id="filterSort" name="sort">
            <?php foreach (selection_options() as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= $filters['sort'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="filter-panel__actions">
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Apply</button>
        <a class="btn btn-ghost" href="<?= e(url($filterAction)) ?>">Reset</a>
    </div>
</form>

<?php if ($chips !== []): ?>
    <div class="active-filters" aria-label="Active filters">
        <span class="active-filters__label">Active:</span>
        <?php foreach ($chips as $key => $chip): ?>
            <a class="filter-chip" href="<?= e(query_url([$key => null, 'page' => null])) ?>">
                <i class="fa-solid <?= e($chip['icon']) ?>" aria-hidden="true"></i>
                <?= e($chip['label']) ?>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span class="sr-only">Remove filter</span>
            </a>
        <?php endforeach; ?>
        <a class="clear-filters" href="<?= e(url($filterAction)) ?>">Clear all</a>
    </div>
<?php endif; ?>

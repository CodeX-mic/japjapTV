<?php
/**
 * Channel directory with search, country, category, access, status and region filters.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = normalize_channel_filters();
$page = max(1, (int) (ctype_digit(get_string('page')) ? get_string('page') : 1));
$perPage = 24;
$total = count_channels($filters);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$channels = search_channels($filters, $perPage, $offset);
$categories = get_category_tiles();
$countries = get_country_options();
$stats = get_catalog_stats();

$pageTitle = 'All channels';
$pageDescription = 'Browse every Philippine and international channel in the japjapTV catalogue. Filter by country, category, free or subscription access, live status, and region.';
$bodyClass = 'page-channels';
$activeNav = 'channels.php';
$canonical = APP_ORIGIN . url('channels.php');
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $total);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Channels</span>
        </nav>
        <p class="eyebrow">Channel directory</p>
        <h1>Every channel, clearly labelled.</h1>
        <p class="lead">Filter the full catalogue by country, category, access type, and availability. “Live now” means an official player was verified as live; “Official site” means we send you to the broadcaster instead of embedding an unauthorized stream.</p>
        <div class="page-meta-row">
            <span class="meta-chip"><i class="fa-solid fa-tv" aria-hidden="true"></i> <?= (int) ($stats['total'] ?? 0) ?> channels</span>
            <span class="meta-chip"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i> <?= (int) ($stats['available'] ?? 0) ?> live now</span>
            <span class="meta-chip"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?= count($countries) ?> countries</span>
            <span class="meta-chip"><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?= count($categories) ?> categories</span>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <?php require __DIR__ . '/includes/channel-filters.php'; ?>

        <div class="toolbar">
            <p class="result-count" aria-live="polite">
                <?php if ($total > 0): ?>
                    Showing <strong><?= $showingFrom ?>–<?= $showingTo ?></strong> of <strong><?= $total ?></strong> channels
                <?php else: ?>
                    No channels match these filters
                <?php endif; ?>
            </p>
            <span class="result-count">Sorted by <strong><?= e(sort_label($filters['sort'])) ?></strong></span>
        </div>

        <?php if ($channels === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></span>
                <h2>No channels found</h2>
                <p>Try a broader search, another country, or clear a filter. The catalogue includes channels whose official stream is not embeddable too.</p>
                <a class="btn btn-primary" href="<?= e(url('channels.php')) ?>">Clear filters</a>
            </div>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($channels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <?php require __DIR__ . '/includes/pagination.php'; ?>
        <?php endif; ?>

        <div class="policy-note" style="margin-top: 30px;">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span><strong>How to read the labels:</strong> “Live now” means a verified, embeddable official stream. “Official site” means the broadcaster’s own destination, often because embedding is not authorized or the feed is behind a subscription. “Unavailable” is kept for transparency, not as a working player.</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

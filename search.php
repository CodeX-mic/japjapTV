<?php
/**
 * Search: full-catalogue search with the shared filter panel.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = normalize_channel_filters();
$hasQuery = $filters['q'] !== ''
    || $filters['category'] !== ''
    || $filters['country'] !== ''
    || $filters['access'] !== ''
    || $filters['status'] !== ''
    || $filters['region'] !== '';

$page = max(1, (int) (ctype_digit(get_string('page')) ? get_string('page') : 1));
$perPage = 24;
$total = $hasQuery ? count_channels($filters) : 0;
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$channels = $hasQuery ? search_channels($filters, $perPage, $offset) : [];
$categories = get_category_tiles();
$countries = get_country_options();
$popular = $hasQuery ? [] : get_popular_channels(8);

$pageTitle = $filters['q'] !== '' ? 'Search: ' . $filters['q'] : 'Search channels';
$pageDescription = 'Search the japjapTV catalogue by channel name, country, category, access type, availability, or region.';
$bodyClass = 'page-search';
$activeNav = '';
$robots = $filters['q'] !== '' || $hasQuery ? 'noindex, follow' : 'index, follow';
$canonical = APP_ORIGIN . url('search.php') . ($filters['q'] !== '' ? '?q=' . rawurlencode($filters['q']) : '');
$showingFrom = $total > 0 ? $offset + 1 : 0;
$showingTo = min($offset + $perPage, $total);

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Search</span>
        </nav>
        <p class="eyebrow">Find something to watch</p>
        <h1><?= $filters['q'] !== '' ? 'Results for “' . e($filters['q']) . '”' : 'Search the catalogue.' ?></h1>
        <p class="lead">Search by channel name, country, or category. Narrow the results by access, live status, and region — every entry is clearly labelled as an embedded official stream or an official destination.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <?php require __DIR__ . '/includes/channel-filters.php'; ?>

        <?php if (!$hasQuery): ?>
            <div class="search-suggestions">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Start somewhere</p>
                        <h2>Browse by category</h2>
                    </div>
                    <a class="section-link" href="<?= e(url('channels.php')) ?>">Open full directory <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="category-grid">
                    <?php foreach ($categories as $category): ?>
                        <?php if ((int) $category['channel_count'] === 0) { continue; } ?>
                        <a class="category-tile" style="--cat-color: <?= e($category['color']) ?>" href="<?= e(url('search.php?category=' . rawurlencode((string) $category['category_slug']))) ?>">
                            <span class="category-icon"><i class="fa-solid <?= e($category['icon']) ?>" aria-hidden="true"></i></span>
                            <span>
                                <h3><?= e($category['category_name']) ?></h3>
                                <p class="category-count"><span class="live-dot" aria-hidden="true"></span> <?= (int) $category['live_count'] ?> live · <?= (int) $category['channel_count'] ?> channels</p>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-head" style="margin-top:34px;">
                <div>
                    <p class="eyebrow">Popular</p>
                    <h2>Most visited channels</h2>
                </div>
            </div>
            <div class="channel-grid">
                <?php foreach ($popular as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="toolbar">
                <p class="result-count" aria-live="polite">
                    <?php if ($total > 0): ?>
                        Showing <strong><?= $showingFrom ?>–<?= $showingTo ?></strong> of <strong><?= $total ?></strong> matching channel<?= $total === 1 ? '' : 's' ?>
                    <?php else: ?>
                        No channels match this search
                    <?php endif; ?>
                </p>
                <span class="result-count">Sorted by <strong><?= e(sort_label($filters['sort'])) ?></strong></span>
            </div>

            <?php if ($channels === []): ?>
                <div class="empty-state">
                    <span class="empty-state-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                    <h2>Nothing matched</h2>
                    <p>Try a shorter name, check the spelling, or remove one of the filters. Not every channel has an embeddable stream, but all catalogue entries remain searchable.</p>
                    <a class="btn btn-primary" href="<?= e(url('search.php')) ?>">Start a new search</a>
                </div>
            <?php else: ?>
                <div class="channel-grid">
                    <?php foreach ($channels as $channel): ?>
                        <?php require __DIR__ . '/includes/channel-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php require __DIR__ . '/includes/pagination.php'; ?>
            <?php endif; ?>
        <?php endif; ?>

        <div class="policy-note" style="margin-top:30px;">
            <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
            <span><strong>Search tips:</strong> try “GMA”, “BBC”, “news”, “basketball”, “Philippines”, or “music”. Use the status filter to show only channels with a verified live embed.</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

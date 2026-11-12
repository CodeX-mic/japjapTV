<?php
/**
 * Philippines hub: Filipino channels, verified livestreams, and official links.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q'        => '',
    'category' => '',
    'country'  => 'PH',
    'access'   => '',
    'status'   => '',
    'region'   => 'ph',
    'sort'     => 'recommended',
];
$channels = search_channels($filters, 96);
$liveChannels = [];
$officialChannels = [];
$offlineChannels = [];

foreach ($channels as $entry) {
    if ((string) $entry['availability_status'] === 'available') {
        $liveChannels[] = $entry;
    } elseif ((string) $entry['availability_status'] === 'unavailable') {
        $offlineChannels[] = $entry;
    } else {
        $officialChannels[] = $entry;
    }
}

$categoryCounts = db()->query("SELECT cat.category_name, cat.category_slug, cat.icon, cat.color, COUNT(c.id) AS channel_count,
        SUM(CASE WHEN c.availability_status = 'available' THEN 1 ELSE 0 END) AS live_count
    FROM categories cat
    INNER JOIN channels c ON c.category_id = cat.id
    INNER JOIN countries co ON co.id = c.country_id
    WHERE co.country_code = 'PH'
    GROUP BY cat.id
    ORDER BY cat.display_order ASC")->fetchAll();

$phStats = db()->query("SELECT COUNT(*) AS total,
        SUM(c.availability_status = 'available') AS live,
        SUM(c.access_type = 'free') AS free
    FROM channels c INNER JOIN countries co ON co.id = c.country_id
    WHERE co.country_code = 'PH'")->fetch();

$pageTitle = 'Philippine TV channels';
$pageDescription = 'Watch Philippine TV channels through official broadcaster livestreams where embedding is permitted, or open the network’s official destination. GMA, TV5, PTV, ABS-CBN, GTV, and more.';
$bodyClass = 'page-philippines';
$activeNav = 'philippines.php';
$canonical = APP_ORIGIN . url('philippines.php');

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Philippines</span>
        </nav>
        <p class="eyebrow">Philippine television</p>
        <h1>Malapit sa bahay.</h1>
        <p class="lead">Free-to-air networks, news channels, and national broadcasters from the Philippines. We embed an official livestream only where the broadcaster permits it; otherwise we take you straight to their official site.</p>
        <div class="page-meta-row">
            <span class="meta-chip"><i class="fa-solid fa-tv" aria-hidden="true"></i> <?= (int) ($phStats['total'] ?? 0) ?> PH channels</span>
            <span class="meta-chip"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i> <?= (int) ($phStats['live'] ?? 0) ?> live embeds</span>
            <span class="meta-chip"><i class="fa-solid fa-unlock" aria-hidden="true"></i> <?= (int) ($phStats['free'] ?? 0) ?> free to watch</span>
            <span class="meta-chip"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Official sources only</span>
        </div>
        <div class="page-hero-actions">
            <a class="btn btn-primary" href="<?= e(url('channels.php?region=ph&status=available')) ?>"><i class="fa-solid fa-play" aria-hidden="true"></i> Watch live now</a>
            <a class="btn btn-ghost" href="<?= e(url('channels.php?region=ph')) ?>">Filter all PH channels</a>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">On air in the Philippines</p>
                <h2>Verified Philippine livestreams</h2>
                <p>These networks resolved to a live, embeddable official player at the last source check. Availability can change without notice.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php?region=ph&status=available')) ?>">See all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($liveChannels === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></span>
                <h2>No live embeds right now</h2>
                <p>Philippine channels are still listed with their official destinations. Re-run the source checker or try again later.</p>
                <a class="btn btn-primary" href="<?= e(url('channels.php?region=ph')) ?>">Browse PH channels</a>
            </div>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($liveChannels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Official destinations</p>
                <h2>Watch on the network’s own site</h2>
                <p>Pay-TV, news, and entertainment channels that do not offer an authorized embed. These open the broadcaster’s official destination in a new tab.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php?region=ph&status=external_only')) ?>">All official links <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($officialChannels === []): ?>
            <p class="text-soft">No external-only Philippine channels are listed.</p>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($officialChannels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Browse by genre</p>
                <h2>What’s on</h2>
            </div>
        </div>
        <div class="category-grid">
            <?php foreach ($categoryCounts as $category): ?>
                <a class="category-tile" style="--cat-color: <?= e($category['color']) ?>" href="<?= e(url('channels.php?region=ph&category=' . rawurlencode((string) $category['category_slug']))) ?>">
                    <span class="category-icon"><i class="fa-solid <?= e($category['icon']) ?>" aria-hidden="true"></i></span>
                    <span>
                        <h3><?= e($category['category_name']) ?></h3>
                        <p class="category-count"><span class="live-dot" aria-hidden="true"></span> <?= (int) $category['live_count'] ?> live · <?= (int) $category['channel_count'] ?> channels</p>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($offlineChannels !== []): ?>
    <section class="section-sm">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">For reference</p>
                    <h2>Channels no longer broadcasting</h2>
                </div>
            </div>
            <div class="policy-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>We keep defunct or unavailable channels in the catalogue for transparency. They are clearly labelled and never present a working player. <?php foreach ($offlineChannels as $offline): ?><a href="<?= e(url('watch.php?id=' . rawurlencode((string) $offline['slug']))) ?>"><?= e($offline['channel_name']) ?></a> · <?php endforeach; ?></span>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

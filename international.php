<?php
/**
 * International hub: global newsrooms, public broadcasters, and official destinations.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q'        => '',
    'category' => '',
    'country'  => '',
    'access'   => '',
    'status'   => '',
    'region'   => 'international',
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

$countries = db()->query("SELECT co.country_name, co.country_code, co.flag_emoji, COUNT(c.id) AS channel_count,
        SUM(CASE WHEN c.availability_status = 'available' THEN 1 ELSE 0 END) AS live_count
    FROM countries co
    INNER JOIN channels c ON c.country_id = co.id
    WHERE co.country_code <> 'PH'
    GROUP BY co.id
    HAVING channel_count > 0
    ORDER BY co.display_order ASC, co.country_name ASC")->fetchAll();

$intlStats = db()->query("SELECT COUNT(*) AS total,
        SUM(c.availability_status = 'available') AS live,
        SUM(c.access_type = 'free') AS free
    FROM channels c INNER JOIN countries co ON co.id = c.country_id
    WHERE co.country_code <> 'PH'")->fetch();

$pageTitle = 'International TV channels';
$pageDescription = 'Watch verified international livestreams from BBC, Al Jazeera, DW, France 24, NHK, Bloomberg, NASA, and more. Official broadcaster sources only.';
$bodyClass = 'page-international';
$activeNav = 'international.php';
$canonical = APP_ORIGIN . url('international.php');

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">International</span>
        </nav>
        <p class="eyebrow">The world, live</p>
        <h1>Newsrooms and worlds beyond.</h1>
        <p class="lead">Public broadcasters, global newsrooms, music channels, and factual programming from outside the Philippines. Verified official streams run inside the player where embedding is permitted.</p>
        <div class="page-meta-row">
            <span class="meta-chip"><i class="fa-solid fa-globe" aria-hidden="true"></i> <?= (int) ($intlStats['total'] ?? 0) ?> international channels</span>
            <span class="meta-chip"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i> <?= (int) ($intlStats['live'] ?? 0) ?> live embeds</span>
            <span class="meta-chip"><i class="fa-solid fa-earth-americas" aria-hidden="true"></i> <?= count($countries) ?> countries</span>
            <span class="meta-chip"><i class="fa-solid fa-unlock" aria-hidden="true"></i> <?= (int) ($intlStats['free'] ?? 0) ?> free to watch</span>
        </div>
        <div class="page-hero-actions">
            <a class="btn btn-primary" href="<?= e(url('channels.php?region=international&status=available')) ?>"><i class="fa-solid fa-play" aria-hidden="true"></i> Watch live now</a>
            <a class="btn btn-ghost" href="<?= e(url('channels.php?region=international')) ?>">Filter international channels</a>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Verified livestreams</p>
                <h2>Live from official broadcasters</h2>
                <p>These channels resolved to a live, embeddable official player at the last verification. Schedules and embedding permissions can change at any time.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php?region=international&status=available')) ?>">See all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($liveChannels === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></span>
                <h2>No live embeds right now</h2>
                <p>International channels are still listed with official destinations. Re-run the source checker or try again later.</p>
                <a class="btn btn-primary" href="<?= e(url('channels.php?region=international')) ?>">Browse international channels</a>
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
                <p class="eyebrow">By country</p>
                <h2>Cross the dial</h2>
                <p>Every country currently represented in the catalogue.</p>
            </div>
        </div>
        <div class="country-chip-grid">
            <?php foreach ($countries as $country): ?>
                <a class="country-chip" href="<?= e(url('channels.php?country=' . rawurlencode((string) $country['country_code']))) ?>">
                    <span class="country-flag" aria-hidden="true"><?= e($country['flag_emoji']) ?></span>
                    <span>
                        <strong><?= e($country['country_name']) ?></strong>
                        <small><?= (int) $country['live_count'] ?> live · <?= (int) $country['channel_count'] ?> listed</small>
                    </span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Official destinations</p>
                <h2>Subscription and regional services</h2>
                <p>Premium and territory-restricted channels open the provider’s official destination. A subscription, TV package, or regional account may be required — those rules are set by the provider, not by japjapTV.</p>
            </div>
        </div>

        <?php if ($officialChannels === []): ?>
            <p class="text-soft">No external-only international channels are listed.</p>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($officialChannels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($offlineChannels !== []): ?>
    <section class="section-sm">
        <div class="container">
            <div class="policy-note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>
                    Reference entries kept for transparency:
                    <?php foreach ($offlineChannels as $offline): ?><a href="<?= e(url('watch.php?id=' . rawurlencode((string) $offline['slug']))) ?>"><?= e($offline['channel_name']) ?></a> · <?php endforeach; ?>
                </span>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

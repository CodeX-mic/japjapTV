<?php
/**
 * Homepage — editorial landing for the channel catalogue.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$stats = get_catalog_stats();
$categories = get_category_tiles();
$featured = get_featured_channels(8);
$liveNow = get_live_now(8);

$phFilters = [
    'q' => '', 'category' => '', 'country' => '', 'access' => '', 'status' => 'available', 'region' => 'ph', 'sort' => 'recommended',
];
$intlFilters = [
    'q' => '', 'category' => '', 'country' => '', 'access' => '', 'status' => 'available', 'region' => 'international', 'sort' => 'recommended',
];
$phChannels = search_channels($phFilters, 4);
$intlChannels = search_channels($intlFilters, 4);

$heroChannels = [];
foreach (array_merge($featured, $liveNow) as $heroChannel) {
    $heroChannels[(int) $heroChannel['id']] = $heroChannel;
    if (count($heroChannels) >= 4) {
        break;
    }
}
$heroChannels = array_values($heroChannels);
$heroPrimary = $heroChannels[0] ?? null;
$heroSecondary = array_slice($heroChannels, 1, 3);
$latestCheck = db()->query('SELECT MAX(last_checked) FROM channel_sources')->fetchColumn();
$totalAvailable = (int) ($stats['available'] ?? 0);
$totalChannels = (int) ($stats['total'] ?? 0);

$pageTitle = 'Live TV from official broadcasters';
$fullTitle = 'japjapTV — Your channels. One honest hub.';
$pageDescription = 'Browse Philippine and international livestreams from official broadcasters. Play authorized embedded streams, or open the broadcaster’s own destination when embedding is not available.';
$bodyClass = 'page-home';
$activeNav = 'index.php';
$canonical = APP_ORIGIN . url('index.php');

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Curated live TV · PH &amp; international</p>
            <h1 class="hero-title"><span class="line">Your channels.</span><span class="line accent">One honest hub.</span></h1>
            <p class="hero-subtitle">Real livestreams from official broadcasters — not scraped IPTV. Play what the broadcaster allows us to embed, or go straight to their official destination when it doesn’t.</p>

            <form class="hero-search" action="<?= e(url('search.php')) ?>" method="get" role="search">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <label class="sr-only" for="heroSearch">Search channels</label>
                <input id="heroSearch" type="search" name="q" placeholder="Search GMA, BBC, NBA, news, movies…" maxlength="100" autocomplete="off">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>

            <div class="hero-quick" aria-label="Quick filters">
                <span class="hero-quick-label">Jump to</span>
                <a class="quick-chip" href="<?= e(url('channels.php?status=available')) ?>"><span class="live-dot" aria-hidden="true"></span> Live now</a>
                <a class="quick-chip" href="<?= e(url('philippines.php')) ?>">Philippines</a>
                <a class="quick-chip" href="<?= e(url('channels.php?category=news')) ?>">News</a>
                <a class="quick-chip" href="<?= e(url('sports.php')) ?>">Sports</a>
                <a class="quick-chip" href="<?= e(url('nba.php')) ?>">NBA</a>
            </div>

            <div class="hero-stats">
                <div class="hero-stat"><strong><?= $totalAvailable ?></strong><span>verified livestreams</span></div>
                <div class="hero-stat"><strong><?= $totalChannels ?></strong><span>curated channels</span></div>
                <div class="hero-stat"><strong><?= count($categories) ?></strong><span>categories to explore</span></div>
            </div>
        </div>

        <div class="hero-stage" aria-label="Featured live channels">
            <div class="hero-window">
                <div class="window-bar">
                    <span class="window-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="window-title">japjapTV · live hub</span>
                    <i class="fa-solid fa-tower-broadcast window-signal" aria-hidden="true"></i>
                </div>

                <?php if ($heroPrimary !== null): ?>
                    <?php $heroStatus = availability_meta((string) $heroPrimary['availability_status']); ?>
                    <a class="hero-feature" href="<?= e(url('watch.php?id=' . rawurlencode((string) $heroPrimary['slug']))) ?>">
                        <img class="hero-feature-logo" src="<?= e(channel_logo_url($heroPrimary)) ?>" width="76" height="76" alt="<?= e($heroPrimary['channel_name']) ?> placeholder logo">
                        <span class="hero-feature-copy">
                            <span class="status-pill <?= e($heroStatus['class']) ?>">
                                <?php if ($heroStatus['class'] === 'is-live'): ?><span class="live-dot" aria-hidden="true"></span><?php endif; ?>
                                <?= e($heroStatus['label']) ?>
                            </span>
                            <h2><?= e($heroPrimary['channel_name']) ?></h2>
                            <p><span aria-hidden="true"><?= e($heroPrimary['flag_emoji'] ?? '') ?></span> <?= e($heroPrimary['country_name']) ?> · <?= e($heroPrimary['category_name']) ?></p>
                        </span>
                        <span class="hero-play" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                    </a>
                <?php endif; ?>

                <div class="hero-mini-list">
                    <?php foreach ($heroSecondary as $mini): ?>
                        <?php $miniStatus = availability_meta((string) $mini['availability_status']); ?>
                        <a class="hero-mini" href="<?= e(url('watch.php?id=' . rawurlencode((string) $mini['slug']))) ?>">
                            <img src="<?= e(channel_logo_url($mini)) ?>" width="36" height="36" alt="" loading="lazy">
                            <span class="hero-mini-copy">
                                <span class="hero-mini-name"><?= e($mini['channel_name']) ?></span>
                                <span class="hero-mini-meta"><?= e($mini['country_name']) ?> · <?= e($miniStatus['short']) ?></span>
                            </span>
                            <i class="fa-solid fa-play" aria-hidden="true"></i>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="hero-window-footer">
                    <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Official players only</span>
                    <span><span class="live-dot" aria-hidden="true"></span> <?= $totalAvailable ?> live now</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">On air</p>
                <h2>Live right now</h2>
                <p>These channels resolved to a live, embeddable official player at the last verification. Availability can change — the player will tell you if it does.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php?status=available')) ?>">All live channels <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($liveNow === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i></span>
                <h2>No live channels are listed yet</h2>
                <p>The catalogue is being prepared. You can still browse every channel and its official destination.</p>
                <a class="btn btn-primary" href="<?= e(url('channels.php')) ?>">Browse the catalogue</a>
            </div>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($liveNow as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Browse by mood</p>
                <h2>Find your corner of the dial</h2>
                <p>News, drama, sport, music, kids, documentaries — organized the way you actually choose what to watch.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php')) ?>">All channels <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <?php if ((int) $category['channel_count'] === 0) { continue; } ?>
                <a class="category-tile" style="--cat-color: <?= e($category['color']) ?>" href="<?= e(url('channels.php?category=' . rawurlencode((string) $category['category_slug']))) ?>">
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

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Philippines</p>
                <h2>Malapit sa bahay</h2>
                <p>Free-to-air favourites and nationwide networks with verified official livestreams, plus honest official links for the channels that don’t allow embedding.</p>
            </div>
            <a class="section-link" href="<?= e(url('philippines.php')) ?>">Explore Philippine TV <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="channel-grid">
            <?php foreach ($phChannels as $channel): ?>
                <?php require __DIR__ . '/includes/channel-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="feature-banner nba-banner">
            <div class="feature-banner-copy">
                <p class="eyebrow">NBA · schedule &amp; official viewing</p>
                <h2>Follow the games. Watch them the right way.</h2>
                <p>Live scoreboard dates from ESPN, Philippine and Eastern tip-off times, and a clear guide to NBA League Pass, NBA TV Philippines, and other official providers. No game streams are hosted here.</p>
                <div class="page-hero-actions">
                    <a class="btn btn-primary" href="<?= e(url('nba.php')) ?>"><i class="fa-solid fa-basketball" aria-hidden="true"></i> View NBA schedule</a>
                    <a class="btn btn-ghost" href="<?= e(url('sports.php')) ?>">Sports destinations</a>
                </div>
            </div>
            <div class="basketball-art" aria-hidden="true"><i class="fa-solid fa-basketball"></i></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">International</p>
                <h2>Newsrooms and worlds beyond</h2>
                <p>Verified international livestreams from public broadcasters and global newsrooms, ready to play inside japjapTV.</p>
            </div>
            <a class="section-link" href="<?= e(url('international.php')) ?>">Explore international <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="channel-grid">
            <?php foreach ($intlChannels as $channel): ?>
                <?php require __DIR__ . '/includes/channel-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">How japjapTV works</p>
                <h2>No tricks. Just a clean path to the stream.</h2>
            </div>
        </div>
        <div class="method-grid">
            <article class="method-card">
                <span class="method-step">01</span>
                <h3>Pick a channel</h3>
                <p>Browse by country, category, access, or live status. Every channel page says exactly what kind of source it has.</p>
            </article>
            <article class="method-card">
                <span class="method-step">02</span>
                <h3>Play, if the broadcaster allows it</h3>
                <p>When an official public player permits embedding, it runs right here with play, volume, fullscreen, loading, and error states.</p>
            </article>
            <article class="method-card">
                <span class="method-step">03</span>
                <h3>Otherwise, go official</h3>
                <p>Pay-TV channels, geo-restricted feeds, and feeds without embedding get a direct link to the broadcaster’s own destination — never a substitute stream.</p>
            </article>
        </div>
        <div class="policy-note" style="margin-top: 16px;">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span><strong>Our rule is simple:</strong> no unauthorized IPTV, no scraping paywalls, and no pretending a channel is free when it isn’t. Source status was last refreshed <?= e(pht_datetime($latestCheck ?: null, 'M j, Y')) ?>. <a href="<?= e(url('about.php')) ?>" style="color: #a7d4ff;">Read the source policy</a>.</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

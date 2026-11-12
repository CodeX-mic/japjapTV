<?php
/**
 * Sports hub: sports channels plus official league and streaming destinations.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$filters = [
    'q'        => '',
    'category' => 'sports',
    'country'  => '',
    'access'   => '',
    'status'   => '',
    'region'   => '',
    'sort'     => 'recommended',
];
$channels = search_channels($filters, 48);
$destinations = sports_destinations();
$upcomingGames = [];
$scoreboard = nba_fetch_scoreboard(today_et());
if (!empty($scoreboard['ok'])) {
    foreach (array_slice($scoreboard['games'], 0, 4) as $game) {
        $upcomingGames[] = $game;
    }
}

$pageTitle = 'Sports channels and official destinations';
$pageDescription = 'Browse sports channels and official league destinations for the PBA, NBA, FIBA, ONE Championship, the Olympics, and Philippine sports streaming services.';
$bodyClass = 'page-sports';
$activeNav = 'sports.php';
$canonical = APP_ORIGIN . url('sports.php');

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Sports</span>
        </nav>
        <p class="eyebrow">Sports · official viewing guide</p>
        <h1>Follow the game. Skip the sketchy feed.</h1>
        <p class="lead">Sports rights are territorial and usually paid. This page lists sports channels in the catalogue and the official league or provider destinations where you can actually watch — with subscriptions and restrictions stated plainly.</p>
        <div class="page-meta-row">
            <span class="meta-chip"><i class="fa-solid fa-trophy" aria-hidden="true"></i> <?= count($channels) ?> sports channels</span>
            <span class="meta-chip"><i class="fa-solid fa-basketball" aria-hidden="true"></i> NBA schedule &amp; viewing guide</span>
            <span class="meta-chip"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> No game restreams</span>
        </div>
        <div class="page-hero-actions">
            <a class="btn btn-primary" href="<?= e(url('nba.php')) ?>"><i class="fa-solid fa-basketball" aria-hidden="true"></i> NBA scores &amp; schedule</a>
            <a class="btn btn-ghost" href="#official-destinations">Official destinations</a>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Sports channels</p>
                <h2>In the catalogue</h2>
                <p>Most premium sports channels do not allow third-party embedding. Their cards link to the broadcaster or provider’s official destination.</p>
            </div>
            <a class="section-link" href="<?= e(url('channels.php?category=sports')) ?>">Open channel filter <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <?php if ($channels === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-trophy" aria-hidden="true"></i></span>
                <h2>No sports channels listed</h2>
                <p>The catalogue may still be loading. Try the full channel directory.</p>
                <a class="btn btn-primary" href="<?= e(url('channels.php')) ?>">Browse channels</a>
            </div>
        <?php else: ?>
            <div class="channel-grid">
                <?php foreach ($channels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="nba-disclaimer" style="margin-top:22px;">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <span>Cards labelled “Official” or “Subscription” do not play inside japjapTV. They open the provider’s own website or app. A cable, streaming, or league subscription may be required, and blackout rules can apply.</span>
        </div>
    </div>
</section>

<?php if ($upcomingGames !== []): ?>
    <section class="section-sm">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">NBA · today</p>
                    <h2>On the scoreboard</h2>
                    <p>Tip-off times shown in Philippine time and Eastern time. This is schedule information, not a game stream.</p>
                </div>
                <a class="section-link" href="<?= e(url('nba.php')) ?>">Full NBA page <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="games-grid">
                <?php foreach ($upcomingGames as $game): ?>
                    <?php require __DIR__ . '/includes/nba-game-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section" id="official-destinations">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Watch legitimately</p>
                <h2>Official sports destinations</h2>
                <p>These are the league and provider websites themselves. Availability, pricing, and regional rules are controlled by each organisation.</p>
            </div>
        </div>

        <div class="sports-destination-grid">
            <?php foreach ($destinations as $destination): ?>
                <article class="sports-card">
                    <span class="sports-icon"><i class="fa-solid <?= e($destination['icon']) ?>" aria-hidden="true"></i></span>
                    <div>
                        <span class="sports-label"><?= e($destination['label']) ?></span>
                        <h3><?= e($destination['name']) ?></h3>
                        <p><?= e($destination['description']) ?></p>
                        <?php $destinationUrl = safe_external_url((string) $destination['url']); ?>
                        <?php if ($destinationUrl !== null): ?>
                            <a class="viewing-link" href="<?= e($destinationUrl) ?>" target="_blank" rel="noopener noreferrer">Open official site <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="policy-note" style="margin-top:20px;">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span><strong>No sports restreams:</strong> japjapTV does not host, proxy, or link to unauthorized live game feeds. If a game is not available on an official free platform in your territory, the official paid provider is the legitimate option.</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * NBA schedule and official viewing guide.
 *
 * Schedule details come from ESPN's public scoreboard API, with a local
 * database fallback. No NBA game streams are hosted or embedded here.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$date = nba_valid_date(get_string('date')) ?? today_et();
$scoreboard = nba_fetch_scoreboard($date);
$usingFallback = false;
$games = [];

if (!empty($scoreboard['ok'])) {
    $games = $scoreboard['games'];
} else {
    $games = nba_games_from_db($date);
    $usingFallback = true;
}

$todayEt = today_et();
$prevDate = nba_shift_date($date, -1);
$nextDate = nba_shift_date($date, 1);
$dateLabel = nba_date_label($date);
$isToday = $date === $todayEt;
$viewingOptions = nba_viewing_options();

$nbaChannels = db()->prepare(channel_base_select() . "
    WHERE c.channel_name LIKE :nba
    ORDER BY c.is_featured DESC, c.display_order ASC
    LIMIT 6");
$nbaChannels->execute(['nba' => '%NBA%']);
$nbaChannels = $nbaChannels->fetchAll();

$pageTitle = 'NBA schedule and official viewing guide';
$pageDescription = 'Today’s NBA scoreboard and tip-off times in Philippine and Eastern time, plus the official ways to watch NBA games through League Pass, NBA TV Philippines, and Cignal.';
$bodyClass = 'page-nba';
$activeNav = 'nba.php';
$canonical = APP_ORIGIN . url('nba.php') . ($date !== $todayEt ? '?date=' . rawurlencode($date) : '');

require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <section class="nba-hero">
        <div class="nba-hero-copy">
            <p class="eyebrow">NBA · schedule &amp; official viewing</p>
            <h1>Every tip-off. Clearly explained.</h1>
            <p>Real scoreboard data for the selected date, with tip-off times in Philippine time and Eastern time. japjapTV does not host or restream NBA games — the viewing guide below points to the league and its official providers.</p>
            <div class="nba-disclaimer">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span><strong>No free full-game stream is promised here.</strong> NBA League Pass and NBA TV Philippines require a paid subscription. NBA’s YouTube channel offers free highlights, not live full games. Blackout and territory rules apply.</span>
            </div>
            <div class="page-hero-actions" style="margin-top:18px;">
                <a class="btn btn-primary" href="<?= e(url('sports.php')) ?>"><i class="fa-solid fa-trophy" aria-hidden="true"></i> Sports destinations</a>
                <a class="btn btn-ghost" href="https://www.nba.com/schedule" target="_blank" rel="noopener noreferrer">Official NBA schedule <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </div>
        </div>
    </section>
</div>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Scoreboard</p>
                <h2><?= $isToday ? 'Today’s games' : 'Games on ' . e($dateLabel) ?></h2>
                <p>All dates follow the NBA’s Eastern Time game day. Philippine time can fall on the following day for late tip-offs.</p>
            </div>
        </div>

        <div class="schedule-toolbar">
            <div class="schedule-date-nav">
                <a class="btn btn-ghost btn-sm" href="<?= e(url('nba.php?date=' . rawurlencode($prevDate))) ?>" aria-label="Previous day"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
                <span class="schedule-date-label"><?= e($dateLabel) ?></span>
                <a class="btn btn-ghost btn-sm" href="<?= e(url('nba.php?date=' . rawurlencode($nextDate))) ?>" aria-label="Next day"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
                <?php if (!$isToday): ?>
                    <a class="btn btn-ghost btn-sm" href="<?= e(url('nba.php')) ?>">Today</a>
                <?php endif; ?>
            </div>

            <form class="schedule-date-form" action="<?= e(url('nba.php')) ?>" method="get">
                <label class="sr-only" for="nbaDate">Choose a date</label>
                <input id="nbaDate" type="date" name="date" value="<?= e($date) ?>" required>
                <button class="btn btn-light btn-sm" type="submit">Go</button>
            </form>

            <span class="schedule-attribution">
                <?php if ($usingFallback): ?>
                    Public feed unavailable · showing saved schedule ·
                <?php else: ?>
                    Schedule data from the
                <?php endif; ?>
                <a href="https://www.espn.com/nba/scoreboard" target="_blank" rel="noopener noreferrer">ESPN public scoreboard</a>
                · times may change
            </span>
        </div>

        <?php if ($usingFallback): ?>
            <div class="form-errors" role="status">
                <strong>The live scoreboard feed could not be reached.</strong>
                <?php if ($games !== []): ?>Showing the saved schedule for this date.<?php else: ?>No saved games exist for this date either. Please check NBA.com for the latest schedule.<?php endif; ?>
                The source error was: <?= e($scoreboard['error'] ?: 'unknown error') ?>.
            </div>
        <?php endif; ?>

        <?php if ($games === []): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-basketball" aria-hidden="true"></i></span>
                <h2>No games scheduled on this date</h2>
                <p>The NBA calendar has quiet days, and the public feed can lag during the offseason. Use the arrows or date picker to check another day, and confirm with NBA.com.</p>
                <div class="page-hero-actions" style="justify-content:center;">
                    <a class="btn btn-primary" href="<?= e(url('nba.php?date=' . rawurlencode($prevDate))) ?>">Previous day</a>
                    <a class="btn btn-ghost" href="<?= e(url('nba.php?date=' . rawurlencode($nextDate))) ?>">Next day</a>
                </div>
            </div>
        <?php else: ?>
            <div class="games-grid">
                <?php foreach ($games as $game): ?>
                    <?php require __DIR__ . '/includes/nba-game-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow">Watch officially</p>
                <h2>Where NBA games are actually available</h2>
                <p>These are the legitimate options. Each opens the provider’s own service; none of them play inside japjapTV.</p>
            </div>
        </div>

        <div class="viewing-grid">
            <?php foreach ($viewingOptions as $option): ?>
                <?php $optionUrl = safe_external_url((string) $option['url']); ?>
                <article class="viewing-card">
                    <span class="viewing-icon"><i class="fa-solid <?= e($option['icon']) ?>" aria-hidden="true"></i></span>
                    <div class="viewing-body">
                        <div class="viewing-title-row">
                            <h3><?= e($option['name']) ?></h3>
                            <span class="viewing-access is-<?= e($option['access']) ?>"><?= e($option['label']) ?></span>
                        </div>
                        <p><?= e($option['description']) ?></p>
                        <?php if ($optionUrl !== null): ?>
                            <a class="viewing-link" href="<?= e($optionUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e($option['note']) ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($nbaChannels !== []): ?>
    <section class="section-sm">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">NBA channels</p>
                    <h2>In the japjapTV catalogue</h2>
                    <p>These are subscription or external destinations, not working game feeds. Their channel pages explain the official access route.</p>
                </div>
                <a class="section-link" href="<?= e(url('channels.php?q=NBA')) ?>">Search NBA <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
            <div class="channel-grid">
                <?php foreach ($nbaChannels as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="section-sm">
    <div class="container">
        <div class="policy-note">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <span><strong>Attribution and accuracy:</strong> Schedule and score data are read from ESPN’s public scoreboard API and cached briefly. japjapTV is not affiliated with the NBA, ESPN, Cignal, or any team. Confirm final tip-off times, broadcasts, and blackout rules with <a href="https://www.nba.com/" target="_blank" rel="noopener noreferrer">NBA.com</a>.</span>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

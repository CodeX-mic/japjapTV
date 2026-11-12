<?php
/**
 * About japjapTV, source policy, and verification method.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$stats = get_catalog_stats();
$lastCheck = db()->query('SELECT MAX(last_checked) FROM channel_sources')->fetchColumn();

$pageTitle = 'About japjapTV';
$pageDescription = 'Why japjapTV exists, how official livestreams are verified, what the channel labels mean, and the source policy behind the catalogue.';
$bodyClass = 'page-about';
$activeNav = 'about.php';
$canonical = APP_ORIGIN . url('about.php');

require __DIR__ . '/includes/header.php';
?>
<section class="about-hero container">
    <div>
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">About</span>
        </nav>
        <p class="eyebrow">About the project</p>
        <h1>A calmer, more honest way to find live TV.</h1>
        <p class="lead">japjapTV is a curated directory of Philippine and international channels. It does not operate a pirate IPTV service. It gathers official broadcaster players where embedding is allowed, and sends you to the broadcaster’s own destination everywhere else.</p>
        <div class="page-hero-actions">
            <a class="btn btn-primary" href="<?= e(url('channels.php')) ?>">Browse channels</a>
            <a class="btn btn-ghost" href="<?= e(url('contact.php')) ?>">Report a correction</a>
        </div>
    </div>
    <aside class="about-panel">
        <h2>The catalogue today</h2>
        <dl class="info-list">
            <div class="info-row"><dt>Channels listed</dt><dd><?= (int) ($stats['total'] ?? 0) ?></dd></div>
            <div class="info-row"><dt>Verified live embeds</dt><dd><?= (int) ($stats['available'] ?? 0) ?></dd></div>
            <div class="info-row"><dt>Official destinations</dt><dd><?= (int) ($stats['external_only'] ?? 0) ?></dd></div>
            <div class="info-row"><dt>Reference only</dt><dd><?= (int) ($stats['unavailable'] ?? 0) ?></dd></div>
            <div class="info-row"><dt>Active sources</dt><dd><?= (int) ($stats['total_sources'] ?? 0) ?></dd></div>
            <div class="info-row"><dt>Last source check</dt><dd><?= e($lastCheck ? pht_date((string) $lastCheck, 'M j, Y') : 'Not yet run') ?></dd></div>
        </dl>
    </aside>
</section>

<section class="section-sm">
    <div class="container content-layout">
        <div>
            <div class="content-card prose">
                <h2>Why this exists</h2>
                <p>Finding a channel online usually means wading through fake players, aggressive redirects, and links that promise a free feed but deliver malware, stolen restreams, or nothing at all. japjapTV takes a narrower path: list the channel, state its access honestly, and connect you to a source the broadcaster actually operates.</p>
                <p>That means the catalogue contains two very different kinds of entries. A <strong>live embed</strong> is an official public player that the broadcaster permits other sites to embed. An <strong>official destination</strong> is a link to the broadcaster’s own website or streaming service — useful, legitimate, and clearly not a player on this site.</p>

                <h2>What “official” means here</h2>
                <p>Every playable source is tied to a broadcaster-controlled channel or an official public player. We do not scrape password-protected players, bypass geographic restrictions, defeat DRM, or accept user-submitted IPTV playlists. A channel is never marked “live” just because a web page returned HTTP 200.</p>

                <h3>How a source is verified</h3>
                <ul>
                    <li><strong>YouTube live sources</strong> are checked server-side through YouTube’s public player data. The channel must currently be live and the player must allow embedding.</li>
                    <li><strong>HLS sources</strong> are checked by fetching the manifest and confirming it parses as a real stream — not by trusting an HTML page.</li>
                    <li><strong>Iframe sources</strong> must be on a short audited allowlist and are labelled as requiring manual verification.</li>
                    <li><strong>External destinations</strong> are not touted as live players. They open the provider’s own site, where that provider controls access.</li>
                </ul>
                <p>When a check is uncertain — a timeout, a blocked request, a temporary error — the channel status is left alone rather than guessed. A channel is only demoted after repeated, definitive failures across every active source.</p>

                <h2>How to read the labels</h2>
                <p><span class="status-pill is-live"><span class="live-dot" aria-hidden="true"></span> Live now</span> means an official player was verified as live and embeddable. <span class="status-pill is-external"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Official site</span> means the card opens the broadcaster or provider directly. <span class="status-pill is-offline"><i class="fa-solid fa-circle-slash" aria-hidden="true"></i> Unavailable</span> is a reference entry for a channel that has ceased broadcasting or has no current official stream.</p>

                <h2>What we do not do</h2>
                <ul>
                    <li>We do not host, proxy, transcode, or restream television signals.</li>
                    <li>We do not provide unauthorized IPTV playlists, M3U feeds, or pay-TV bypasses.</li>
                    <li>We do not promise that a subscription channel is free. Pay-TV and league services are labelled as paid.</li>
                    <li>We do not embed a feed where the broadcaster has disabled embedding, even if a direct URL exists.</li>
                    <li>We do not sell viewer data. There are no visitor accounts and no ad-tech trackers.</li>
                </ul>

                <h2>Attribution</h2>
                <p>Channel names, logos, and programming belong to their respective owners. The logo marks in this catalogue are original text placeholders, not official broadcaster artwork. NBA schedule data is read from ESPN’s public scoreboard API for reference; the NBA, ESPN, and team names belong to their owners. japjapTV is not affiliated with or endorsed by any broadcaster, league, or streaming service.</p>

                <h2>Corrections</h2>
                <p>Streams move, channels rebrand, and sites disappear. If a source is broken, a logo is wrong, or a channel is mislabelled, <a href="<?= e(url('contact.php')) ?>">send a correction</a>. Include the channel name, the page URL, and what you saw. Reports are reviewed by the site administrator.</p>
            </div>
        </div>

        <aside>
            <div class="content-card">
                <h2>Our principles</h2>
                <div class="principle-list">
                    <div class="principle"><i class="fa-solid fa-check" aria-hidden="true"></i><span><strong>Official first.</strong> If we cannot verify an official source, we link to the broadcaster instead of inventing one.</span></div>
                    <div class="principle"><i class="fa-solid fa-check" aria-hidden="true"></i><span><strong>Say what it is.</strong> Free, subscription, external, or unavailable — every channel says which.</span></div>
                    <div class="principle"><i class="fa-solid fa-check" aria-hidden="true"></i><span><strong>No dark patterns.</strong> No fake play buttons, no forced redirects, no “free” labels on paid services.</span></div>
                    <div class="principle"><i class="fa-solid fa-check" aria-hidden="true"></i><span><strong>Respect the broadcaster.</strong> Embedding permissions and regional rules are not circumvented.</span></div>
                </div>
            </div>

            <div class="content-card">
                <h2>What happens when you press play</h2>
                <ul class="timeline">
                    <li><strong>The page checks the source type</strong><span>A YouTube channel source is resolved server-side to the current public live video.</span></li>
                    <li><strong>The official player loads</strong><span>YouTube’s own embed handles video delivery, advertising, and regional rules.</span></li>
                    <li><strong>If it fails, you get a fallback</strong><span>The player shows an error panel with a retry button and the official destination.</span></li>
                    <li><strong>No hidden substitute</strong><span>japjapTV never swaps in an unofficial stream when the official one is unavailable.</span></li>
                </ul>
            </div>

            <div class="content-card">
                <h2>Technical notes</h2>
                <p class="text-soft">Built with PHP 8 and MySQL, served through Apache on XAMPP. The player uses YouTube’s official embed and hls.js where an authorized HLS manifest exists. The app uses PDO prepared statements, CSRF protection, secure sessions, and a small, auditable dependency footprint.</p>
                <p><a class="viewing-link" href="<?= e(url('privacy.php')) ?>">Privacy policy <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
            </div>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

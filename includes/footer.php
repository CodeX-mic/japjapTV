<?php
/**
 * Public footer and script loading.
 */

declare(strict_types=1);

$currentYear = date('Y');
?></main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="japjapTV home">
                <img src="<?= e(url('assets/images/logo-mark.svg')) ?>" width="38" height="38" alt="">
                <span class="brand-word">japjap<span>TV</span></span>
            </a>
            <p>One calm place to find Philippine and international channels. We link to official broadcaster streams and official destinations — never unauthorized restreams.</p>
            <div class="footer-badges">
                <span class="mini-badge"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Official sources</span>
                <span class="mini-badge"><i class="fa-solid fa-code" aria-hidden="true"></i> No IPTV feeds</span>
            </div>
        </div>

        <nav class="footer-column" aria-label="Browse">
            <h2>Browse</h2>
            <a href="<?= e(url('channels.php')) ?>">All channels</a>
            <a href="<?= e(url('philippines.php')) ?>">Philippines</a>
            <a href="<?= e(url('international.php')) ?>">International</a>
            <a href="<?= e(url('sports.php')) ?>">Sports</a>
            <a href="<?= e(url('nba.php')) ?>">NBA schedule</a>
            <a href="<?= e(url('search.php')) ?>">Search</a>
        </nav>

        <nav class="footer-column" aria-label="Information">
            <h2>Information</h2>
            <a href="<?= e(url('about.php')) ?>">About japjapTV</a>
            <a href="<?= e(url('contact.php')) ?>">Contact &amp; corrections</a>
            <a href="<?= e(url('privacy.php')) ?>">Privacy policy</a>
            <a href="<?= e(url('terms.php')) ?>">Terms of use</a>
            <a href="<?= e(url('admin/login.php')) ?>">Admin sign in</a>
        </nav>

        <div class="footer-column footer-note">
            <h2>Source policy</h2>
            <p>Playable channels come from an official broadcaster player. Where embedding is not authorized or not available, we send you to the broadcaster’s own website.</p>
            <p class="footer-note-small">Schedules change. Verify important events with the broadcaster. NBA schedule data is attributed to ESPN’s public scoreboard.</p>
        </div>
    </div>

    <div class="container footer-bottom">
        <p>© <?= e((string) $currentYear) ?> japjapTV. Not affiliated with any broadcaster, league, or streaming service.</p>
        <p class="footer-meta">
            <span>v<?= e(APP_VERSION) ?></span>
            <span aria-hidden="true">·</span>
            <span>Made for the Philippines and the world</span>
        </p>
    </div>
</footer>
</div>

<button class="to-top" type="button" data-back-top aria-label="Back to top">
    <i class="fa-solid fa-arrow-up" aria-hidden="true"></i>
</button>

<script src="<?= e(asset('assets/js/main.js')) ?>" defer></script>
<?php if (!empty($includePlayer)): ?>
    <script src="<?= e(asset('assets/js/player.js')) ?>" defer></script>
<?php endif; ?>
</body>
</html>

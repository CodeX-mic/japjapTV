<?php
/**
 * Public 404 page.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);

$popular = get_popular_channels(4);
$pageTitle = 'Page not found';
$pageDescription = 'The page you requested does not exist. Browse the japjapTV channel directory or search for a broadcaster.';
$bodyClass = 'page-404';
$activeNav = '';
$robots = 'noindex, follow';
$canonical = APP_ORIGIN . url('404.php');

require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="empty-state">
            <span class="empty-state-icon"><i class="fa-solid fa-compass" aria-hidden="true"></i></span>
            <p class="eyebrow">404 · Page not found</p>
            <h1>That page isn’t on this dial.</h1>
            <p>The address may be mistyped, or the page may have moved. Try the channel directory, search for a broadcaster, or head back to the homepage.</p>
            <div class="page-hero-actions" style="justify-content:center;">
                <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Back home</a>
                <a class="btn btn-ghost" href="<?= e(url('channels.php')) ?>">Browse channels</a>
            </div>
        </div>

        <?php if ($popular !== []): ?>
            <div class="section-head" style="margin-top:34px;">
                <div>
                    <p class="eyebrow">Popular right now</p>
                    <h2>Maybe one of these</h2>
                </div>
            </div>
            <div class="channel-grid">
                <?php foreach ($popular as $channel): ?>
                    <?php require __DIR__ . '/includes/channel-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

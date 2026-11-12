<?php
/**
 * Channel watch page: integrated player, source details, and related channels.
 *
 * Accepts either a numeric channel id or a channel slug in ?id=. A numeric
 * ?source= may select another active, authorized source for the same channel.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$identifier = get_string('id');
$channel = null;

if ($identifier !== '') {
    if (ctype_digit($identifier)) {
        $channel = get_channel_by_id((int) $identifier);
    } elseif (preg_match('/^[a-z0-9-]{1,140}$/', $identifier)) {
        $channel = get_channel_by_slug($identifier);
    }
}

if ($channel === null) {
    http_response_code(404);
    $pageTitle = 'Channel not found';
    $pageDescription = 'The channel you requested is not in the japjapTV catalogue.';
    $bodyClass = 'page-404';
    $activeNav = 'channels.php';
    $robots = 'noindex, follow';
    $canonical = APP_ORIGIN . url('404.php');

    require __DIR__ . '/includes/header.php';
    ?>
    <section class="section">
        <div class="container">
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fa-solid fa-satellite-dish" aria-hidden="true"></i></span>
                <p class="eyebrow">404 · Channel not found</p>
                <h1>We couldn’t find that channel</h1>
                <p>The link may be old, or the channel may have been removed from the catalogue. Browse the directory or search for another broadcaster.</p>
                <div class="page-hero-actions" style="justify-content:center;">
                    <a class="btn btn-primary" href="<?= e(url('channels.php')) ?>">Browse all channels</a>
                    <a class="btn btn-ghost" href="<?= e(url('search.php')) ?>">Search channels</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$activeSources = get_active_sources((int) $channel['id']);
$selectedSourceId = ctype_digit(get_string('source')) ? (int) get_string('source') : null;
$source = get_primary_source((int) $channel['id'], $selectedSourceId);
$relatedChannels = get_related_channels($channel, 4);
$previousChannel = get_adjacent_channel($channel, 'prev');
$nextChannel = get_adjacent_channel($channel, 'next');
$status = availability_meta((string) $channel['availability_status']);
$officialUrl = safe_external_url((string) $channel['official_url']);

increment_channel_view((int) $channel['id']);

$watchUrl = APP_ORIGIN . url('watch.php?id=' . rawurlencode((string) $channel['slug']));
$pageTitle = $channel['channel_name'] . ' live stream';
$pageDescription = truncate_text((string) $channel['description'], 155);
$bodyClass = 'page-watch';
$activeNav = '';
$includePlayer = true;
$canonical = $watchUrl;
$metaImage = APP_ORIGIN . channel_logo_url($channel);

require __DIR__ . '/includes/header.php';
?>
<section class="watch-page">
    <div class="container">
        <nav class="breadcrumbs watch-breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <a href="<?= e(url('channels.php')) ?>">Channels</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page"><?= e($channel['channel_name']) ?></span>
        </nav>

        <div class="watch-head">
            <div class="watch-head-logo">
                <img src="<?= e(channel_logo_url($channel)) ?>" width="66" height="66" alt="<?= e($channel['channel_name']) ?> placeholder logo">
            </div>
            <div class="watch-head-copy">
                <span class="status-pill <?= e($status['class']) ?>">
                    <?php if ($status['class'] === 'is-live'): ?><span class="live-dot" aria-hidden="true"></span><?php else: ?><i class="fa-solid <?= e($status['icon']) ?>" aria-hidden="true"></i><?php endif; ?>
                    <?= e($status['label']) ?>
                </span>
                <h1><?= e($channel['channel_name']) ?></h1>
                <p class="watch-meta">
                    <span><span aria-hidden="true"><?= e($channel['flag_emoji'] ?? '') ?></span> <?= e($channel['country_name']) ?></span>
                    <span class="meta-dot" aria-hidden="true">·</span>
                    <a href="<?= e(url('channels.php?category=' . rawurlencode((string) $channel['category_slug']))) ?>"><?= e($channel['category_name']) ?></a>
                    <span class="meta-dot" aria-hidden="true">·</span>
                    <span><?= e(access_label((string) $channel['access_type'])) ?></span>
                    <span class="meta-dot" aria-hidden="true">·</span>
                    <span><?= e($channel['language']) ?></span>
                </p>
            </div>
            <div class="watch-head-actions">
                <button class="btn btn-ghost btn-sm" type="button" data-share data-share-url="<?= e($watchUrl) ?>" data-share-title="<?= e($channel['channel_name'] . ' on japjapTV') ?>">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i> <span data-copy-label>Share</span>
                </button>
                <?php if ($officialUrl !== null): ?>
                    <a class="btn btn-light btn-sm" href="<?= e($officialUrl) ?>" target="_blank" rel="noopener noreferrer">
                        Official site <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="watch-layout">
            <div class="watch-main">
                <?php require __DIR__ . '/includes/player.php'; ?>

                <div class="watch-description">
                    <h2>About <?= e($channel['channel_name']) ?></h2>
                    <p><?= e($channel['description']) ?></p>
                    <?php if (trim((string) ($channel['notice'] ?? '')) !== ''): ?>
                        <p class="notice"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($channel['notice']) ?></p>
                    <?php endif; ?>
                    <h2 style="margin-top:22px;">How this listing works</h2>
                    <p>
                        <?php if ((string) $channel['availability_status'] === 'available' && $source !== null): ?>
                            This channel has an active official source that was verified as a live, embeddable player. japjapTV embeds only public players the broadcaster makes available. If the broadcaster stops the stream or changes embedding permissions, use “Official channel” to watch directly at the source.
                        <?php elseif ((string) $channel['availability_status'] === 'external_only'): ?>
                            No authorized, embeddable livestream is configured for this channel. japjapTV does not substitute unofficial feeds. The official destination button opens the broadcaster’s own website or streaming service, where access rules and subscriptions are set by that provider.
                        <?php else: ?>
                            This channel is listed for reference only. It is unavailable, has ceased broadcasting, or has no current official stream. The catalogue keeps the entry so the official destination and history remain clear.
                        <?php endif; ?>
                    </p>
                </div>

                <nav class="watch-nav" aria-label="Previous and next channels">
                    <?php if ($previousChannel !== null): ?>
                        <a class="watch-nav-link prev" href="<?= e(url('watch.php?id=' . rawurlencode((string) $previousChannel['slug']))) ?>">
                            <span class="watch-nav-icon"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></span>
                            <span class="watch-nav-copy">
                                <span class="watch-nav-label">Previous</span>
                                <span class="watch-nav-name"><?= e($previousChannel['channel_name']) ?></span>
                            </span>
                        </a>
                    <?php else: ?>
                        <span class="watch-nav-link is-empty" aria-hidden="true"></span>
                    <?php endif; ?>
                    <?php if ($nextChannel !== null): ?>
                        <a class="watch-nav-link next" href="<?= e(url('watch.php?id=' . rawurlencode((string) $nextChannel['slug']))) ?>">
                            <span class="watch-nav-copy">
                                <span class="watch-nav-label">Next</span>
                                <span class="watch-nav-name"><?= e($nextChannel['channel_name']) ?></span>
                            </span>
                            <span class="watch-nav-icon"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                        </a>
                    <?php else: ?>
                        <span class="watch-nav-link is-empty" aria-hidden="true"></span>
                    <?php endif; ?>
                </nav>
            </div>

            <aside class="watch-sidebar" aria-label="Channel details">
                <div class="side-card">
                    <h2>Channel details</h2>
                    <dl class="info-list">
                        <div class="info-row">
                            <dt>Status</dt>
                            <dd><span class="status-pill <?= e($status['class']) ?>"><?= e($status['short']) ?></span></dd>
                        </div>
                        <div class="info-row">
                            <dt>Country</dt>
                            <dd><span aria-hidden="true"><?= e($channel['flag_emoji'] ?? '') ?></span> <?= e($channel['country_name']) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Category</dt>
                            <dd><?= e($channel['category_name']) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Region</dt>
                            <dd><?= $channel['region'] === 'ph' ? 'Philippines' : 'International' ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Access</dt>
                            <dd><?= e(access_label((string) $channel['access_type'])) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Language</dt>
                            <dd><?= e($channel['language']) ?></dd>
                        </div>
                        <div class="info-row">
                            <dt>Views</dt>
                            <dd><?= e(format_views((int) $channel['view_count'])) ?></dd>
                        </div>
                        <?php if ($source !== null): ?>
                            <div class="info-row">
                                <dt>Source</dt>
                                <dd><?= e(source_type_label((string) $source['source_type'])) ?></dd>
                            </div>
                            <div class="info-row">
                                <dt>Last checked</dt>
                                <dd><?= e(pht_date((string) ($source['last_checked'] ?? ''), 'M j, Y')) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <?php if (count($activeSources) > 1): ?>
                    <div class="side-card">
                        <h2>Available sources</h2>
                        <div class="side-cta">
                            <?php foreach ($activeSources as $option): ?>
                                <?php $isCurrent = $source !== null && (int) $source['id'] === (int) $option['id']; ?>
                                <a class="btn <?= $isCurrent ? 'btn-light' : 'btn-ghost' ?> btn-sm" href="<?= e(url('watch.php?id=' . rawurlencode((string) $channel['slug']) . '&source=' . (int) $option['id'])) ?>">
                                    <?= e($option['label']) ?><?= $isCurrent ? ' · current' : '' ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="side-card side-cta">
                    <h2>Watch options</h2>
                    <?php if ($officialUrl !== null): ?>
                        <a class="btn btn-primary" href="<?= e($officialUrl) ?>" target="_blank" rel="noopener noreferrer">
                            Open official destination <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="<?= e(url('channels.php?status=available')) ?>"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i> Browse live channels</a>
                    <a class="btn btn-ghost" href="<?= e(url('contact.php?channel=' . rawurlencode((string) $channel['slug']))) ?>"><i class="fa-regular fa-message" aria-hidden="true"></i> Report a problem</a>
                    <p class="side-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> japjapTV links only to official broadcaster sources. It does not proxy, restream, or bypass regional restrictions.</p>
                </div>
            </aside>
        </div>

        <?php if ($relatedChannels !== []): ?>
            <section class="related-section" aria-labelledby="relatedHeading">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Keep watching</p>
                        <h2 id="relatedHeading">More like <?= e($channel['channel_name']) ?></h2>
                    </div>
                    <a class="section-link" href="<?= e(url('channels.php?category=' . rawurlencode((string) $channel['category_slug']))) ?>">More <?= e($channel['category_name']) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="channel-grid">
                    <?php foreach ($relatedChannels as $relatedChannel): ?>
                        <?php $channel = $relatedChannel; ?>
                        <?php require __DIR__ . '/includes/channel-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

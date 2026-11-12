<?php
/**
 * Integrated player panel.
 *
 * Expects $channel and optional $source (primary channel_sources row).
 * YouTube uses the official embed with native controls plus a lightweight
 * custom control bar. HLS is played with hls.js where supported. External-only
 * channels always get an official destination panel — never a fake player.
 */

declare(strict_types=1);

$channel = $channel ?? null;
if (!is_array($channel)) {
    return;
}

$source = $source ?? null;
$availability = (string) ($channel['availability_status'] ?? 'unknown');
$officialUrl = safe_external_url((string) ($channel['official_url'] ?? ''));
$embedUrl = null;
$sourceType = is_array($source) ? (string) ($source['source_type'] ?? '') : '';
$hlsUrl = null;
$isEmbeddable = false;
$isLive = false;
$loadingLabel = 'Connecting to the official stream…';
$fallbackReason = channel_fallback_reason($channel, is_array($source) ? $source : null);

if ($availability === 'available' && is_array($source)) {
    if (in_array($sourceType, ['youtube_live', 'youtube_video', 'iframe'], true)) {
        $embedUrl = source_embed_url($source);
        $isEmbeddable = $embedUrl !== null;
    } elseif ($sourceType === 'hls') {
        $candidate = safe_external_url((string) ($source['source_url'] ?? ''));
        if ($candidate !== null && str_starts_with(strtolower($candidate), 'https://')) {
            $hlsUrl = $candidate;
            $isEmbeddable = true;
        }
    }

    if (is_array($source) && in_array($sourceType, ['external'], true)) {
        $fallbackReason = 'This listing points to the broadcaster’s official destination. No embedded player is configured or authorized for this channel.';
    }
}

if (in_array($availability, ['external_only', 'unavailable', 'coming_soon'], true)) {
    $isEmbeddable = false;
    $hlsUrl = null;
    $embedUrl = null;
}

if ($isEmbeddable && in_array($sourceType, ['youtube_live', 'hls'], true)) {
    $isLive = true;
}

if ($isEmbeddable && $embedUrl !== null && in_array($sourceType, ['youtube_live', 'youtube_video', 'iframe'], true)) {
    $separator = str_contains($embedUrl, '?') ? '&' : '?';
    if ($sourceType === 'iframe') {
        $embedUrl .= $separator . http_build_query(['autoplay' => '0']);
    } else {
        $embedUrl .= $separator . http_build_query([
            'autoplay'    => '0',
            'enablejsapi' => '1',
            'playsinline' => '1',
            'rel'         => '0',
            'modestbranding' => '1',
            'origin'      => APP_ORIGIN,
        ]);
    }
}

$statusMeta = availability_meta($availability);
$playerMode = $hlsUrl !== null ? 'hls' : ($isEmbeddable ? $sourceType : 'offline');
$officialLastChecked = (string) ($source['last_checked'] ?? '');
$officialLastVerified = (string) ($channel['source_last_verified'] ?? ($source['last_verified'] ?? ''));
$notice = trim((string) ($channel['notice'] ?? ''));
?><section class="player-panel" aria-label="<?= e($channel['channel_name']) ?> player">
    <?php if ($isEmbeddable): ?>
        <div class="player" data-player data-source-type="<?= e($playerMode) ?>" data-channel-name="<?= e($channel['channel_name']) ?>" data-official-url="<?= e($officialUrl ?? '') ?>"<?= $hlsUrl !== null ? ' data-hls-src="' . e($hlsUrl) . '"' : '' ?>>
            <div class="player-stage" data-player-stage>
                <?php if ($hlsUrl !== null): ?>
                    <video class="player-video" data-player-video playsinline controls preload="metadata" aria-label="<?= e($channel['channel_name']) ?> livestream"></video>
                <?php else: ?>
                    <iframe
                        class="player-iframe"
                        data-player-iframe
                        src="<?= e($embedUrl ?? '') ?>"
                        title="<?= e($channel['channel_name']) ?> — official livestream player"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share; fullscreen"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"></iframe>
                <?php endif; ?>

                <div class="player-topbar">
                    <?php if ($isLive): ?>
                        <span class="player-live"><span class="live-dot" aria-hidden="true"></span> LIVE</span>
                    <?php else: ?>
                        <span class="player-live is-video"><i class="fa-solid fa-circle-play" aria-hidden="true"></i> <?= $sourceType === 'iframe' ? 'OFFICIAL PLAYER' : 'VIDEO' ?></span>
                    <?php endif; ?>
                    <span class="player-brand"><img src="<?= e(url('assets/images/logo-mark.svg')) ?>" width="18" height="18" alt=""> japjapTV</span>
                </div>

                <div class="player-loading" data-player-loading role="status" hidden>
                    <span class="spinner" aria-hidden="true"></span>
                    <strong><?= e($loadingLabel) ?></strong>
                    <span>The official player may take a moment. If it stays blank, use the official channel link.</span>
                </div>

                <div class="player-fallback" data-player-fallback hidden>
                    <div class="player-fallback-icon"><i class="fa-solid fa-tower-broadcast" aria-hidden="true"></i></div>
                    <h2>The stream did not load</h2>
                    <p>It may be offline, restricted in your region, or refusing embedding. We do not proxy or bypass restrictions.</p>
                    <div class="player-fallback-actions">
                        <button class="btn btn-light btn-sm" type="button" data-player-retry><i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Try again</button>
                        <?php if ($officialUrl !== null): ?>
                            <a class="btn btn-ghost btn-sm" href="<?= e($officialUrl) ?>" target="_blank" rel="noopener noreferrer">Official channel <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="player-controls" data-player-controls>
                <button class="player-control" type="button" data-player-play aria-label="Pause">
                    <i class="fa-solid fa-pause" data-player-play-icon aria-hidden="true"></i>
                </button>
                <div class="player-volume">
                    <button class="player-control" type="button" data-player-mute aria-label="Mute">
                        <i class="fa-solid fa-volume-high" data-player-mute-icon aria-hidden="true"></i>
                    </button>
                    <input class="volume-slider" type="range" min="0" max="100" value="75" step="1" data-player-volume aria-label="Volume">
                </div>
                <span class="player-state" data-player-status><span class="state-dot" aria-hidden="true"></span> <span data-player-status-text>Ready</span></span>
                <span class="player-controls-spacer"></span>
                <?php if ($officialUrl !== null): ?>
                    <a class="player-control-link" href="<?= e($officialUrl) ?>" target="_blank" rel="noopener noreferrer">Official channel <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                <?php endif; ?>
                <button class="player-control" type="button" data-player-fullscreen aria-label="Enter fullscreen">
                    <i class="fa-solid fa-expand" aria-hidden="true"></i>
                </button>
            </div>

            <p class="player-footnote">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                Playback is handled by the broadcaster or its platform. Availability can change without notice.
                <?php if ($source !== null): ?>Source checked <?= e(pht_date($officialLastChecked ?: $officialLastVerified, 'M j, Y')) ?>.<?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="player-offline" style="--accent: <?= e(channel_accent($channel)) ?>">
            <div class="player-offline-art">
                <img src="<?= e(channel_logo_url($channel)) ?>" width="112" height="112" alt="<?= e($channel['channel_name']) ?> placeholder logo">
                <span class="status-pill <?= e($statusMeta['class']) ?>">
                    <?php if ($statusMeta['class'] === 'is-live'): ?><span class="live-dot" aria-hidden="true"></span><?php endif; ?>
                    <?= e($statusMeta['label']) ?>
                </span>
            </div>
            <div class="player-offline-copy">
                <p class="eyebrow"><?= $availability === 'unavailable' ? 'No longer broadcasting' : 'Official destination only' ?></p>
                <h2><?= $availability === 'unavailable' ? 'This channel is unavailable' : 'Watch on the official site' ?></h2>
                <p><?= e($fallbackReason) ?></p>
                <?php if ($notice !== ''): ?>
                    <p class="player-notice"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> <?= e($notice) ?></p>
                <?php endif; ?>
                <div class="player-offline-actions">
                    <?php if ($officialUrl !== null): ?>
                        <a class="btn btn-primary" href="<?= e($officialUrl) ?>" target="_blank" rel="noopener noreferrer">
                            Open official destination <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="<?= e(url('channels.php?status=available')) ?>">Browse live channels</a>
                </div>
                <p class="player-offline-meta">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    japjapTV never substitutes an unofficial stream, proxy, or restream.
                    <?php if ($officialLastVerified !== ''): ?> Last verified <?= e(pht_date($officialLastVerified, 'M j, Y')) ?>.<?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</section>

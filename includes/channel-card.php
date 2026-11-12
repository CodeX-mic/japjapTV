<?php
/**
 * Reusable channel card.
 *
 * Expects $channel (row from channel_base_select()).
 */

declare(strict_types=1);

if (!isset($channel) || !is_array($channel)) {
    return;
}

$watchUrl = url('watch.php?id=' . rawurlencode((string) $channel['slug']));
$status = availability_meta((string) $channel['availability_status']);
$accent = channel_accent($channel);
$access = access_label((string) $channel['access_type']);
$accessIcon = match ($channel['access_type']) {
    'free' => 'fa-unlock',
    'subscription' => 'fa-lock',
    default => 'fa-circle-info',
};
$isUnavailable = (string) $channel['availability_status'] === 'unavailable';
?><article class="channel-card<?= $isUnavailable ? ' is-unavailable' : '' ?>" style="--accent: <?= e($accent) ?>">
    <a class="channel-card__hit" href="<?= e($watchUrl) ?>" aria-label="View <?= e($channel['channel_name']) ?>"></a>
    <div class="channel-card__media">
        <div class="channel-logo">
            <img src="<?= e(channel_logo_url($channel)) ?>" width="84" height="84" alt="<?= e($channel['channel_name']) ?> placeholder logo" loading="lazy" decoding="async">
        </div>
        <span class="status-pill <?= e($status['class']) ?>">
            <?php if ($status['class'] === 'is-live'): ?><span class="live-dot" aria-hidden="true"></span><?php else: ?><i class="fa-solid <?= e($status['icon']) ?>" aria-hidden="true"></i><?php endif; ?>
            <?= e($status['short']) ?>
        </span>
    </div>

    <div class="channel-card__body">
        <div class="channel-card__title-row">
            <h3><?= e($channel['channel_name']) ?></h3>
            <?php if ((int) ($channel['is_featured'] ?? 0) === 1): ?>
                <span class="featured-star" title="Featured channel"><i class="fa-solid fa-star" aria-hidden="true"></i><span class="sr-only">Featured channel</span></span>
            <?php endif; ?>
        </div>
        <p class="channel-card__meta">
            <span><span class="flag" aria-hidden="true"><?= e($channel['flag_emoji'] ?? '') ?></span> <?= e($channel['country_name']) ?></span>
            <span class="meta-dot" aria-hidden="true">·</span>
            <span><?= e($channel['category_name']) ?></span>
        </p>
        <p class="channel-card__desc"><?= e(truncate_text((string) $channel['description'], 92)) ?></p>
    </div>

    <div class="channel-card__footer">
        <span class="access-chip"><i class="fa-solid <?= e($accessIcon) ?>" aria-hidden="true"></i> <?= e($access) ?></span>
        <span class="card-cta"><?= $isUnavailable ? 'Details' : 'Open' ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
    </div>
</article>

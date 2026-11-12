<?php
/**
 * NBA game card. Expects a normalised $game array from includes/nba.php.
 */

declare(strict_types=1);

if (!isset($game) || !is_array($game)) {
    return;
}

$home = $game['home'] ?? [];
$away = $game['away'] ?? [];
$statusClass = (string) ($game['status_class'] ?? 'is-upcoming');
$statusLabel = nba_status_text($game);
$gameUrl = safe_external_url((string) ($game['official_url'] ?? '')) ?? 'https://www.nba.com/games';
$isFinal = $statusClass === 'is-final';
$hasScore = trim((string) ($home['score'] ?? '')) !== '' || trim((string) ($away['score'] ?? '')) !== '';
?><article class="game-card" aria-label="<?= e($away['name'] ?? 'Away') ?> at <?= e($home['name'] ?? 'Home') ?>">
    <div class="game-card-header">
        <span class="game-status <?= e($statusClass) ?>">
            <?php if ($statusClass === 'is-live'): ?><span class="live-dot" aria-hidden="true"></span><?php else: ?><i class="fa-solid <?= $statusClass === 'is-final' ? 'fa-flag-checkered' : ($statusClass === 'is-postponed' ? 'fa-clock' : 'fa-calendar-day') ?>" aria-hidden="true"></i><?php endif; ?>
            <?= e($statusLabel) ?>
        </span>
        <span class="game-time"><?= e($game['pht_time'] ?? 'Time TBA') ?> PHT · <?= e($game['et_time'] ?? 'Time TBA') ?> ET</span>
    </div>

    <div class="game-teams">
        <?php foreach ([$away, $home] as $team): ?>
            <?php $winner = $isFinal && !empty($team['winner']); ?>
            <div class="game-team<?= $winner ? ' is-winner' : '' ?>">
                <span class="team-mark" style="--team-color: <?= e($team['color'] ?? '#5b6472') ?>" aria-hidden="true"><?= e($team['abbr'] ?? '—') ?></span>
                <span class="game-team-copy">
                    <span class="game-team-name"><?= e($team['short'] ?? $team['name'] ?? 'TBA') ?></span>
                    <span class="game-team-record"><?= e($team['record'] !== '' ? $team['record'] : ($team['name'] ?? '')) ?></span>
                </span>
                <span class="game-score"><?= $hasScore ? e($team['score'] !== '' ? (string) $team['score'] : '–') : '–' ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="game-card-footer">
        <span class="game-venue"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= e(($game['venue'] ?? '') !== '' ? $game['venue'] : 'Venue TBA') ?></span>
        <a class="game-link" href="<?= e($gameUrl) ?>" target="_blank" rel="noopener noreferrer">NBA.com <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    </div>
</article>

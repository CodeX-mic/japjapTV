<?php
/**
 * Admin NBA schedule list (local fallback data).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_post() && post_string('action') === 'delete') {
    admin_verify_csrf('admin/nba-games.php');
    $id = post_int('game_id');
    $stmt = db()->prepare('SELECT home_team, away_team FROM nba_games WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $game = $stmt->fetch();

    if (!$game) {
        flash('error', 'That game no longer exists.');
    } else {
        db()->prepare('DELETE FROM nba_games WHERE id = :id')->execute(['id' => $id]);
        flash('success', 'Saved game “' . $game['away_team'] . ' at ' . $game['home_team'] . '” deleted.');
    }
    redirect('admin/nba-games.php');
}

$pagination = admin_pagination((int) db()->query('SELECT COUNT(*) FROM nba_games')->fetchColumn(), 30);
$games = db()->query("SELECT * FROM nba_games ORDER BY game_date DESC, id DESC
    LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}")->fetchAll();

$adminTitle = 'NBA games';
$adminSubtitle = 'Optional local fallback schedule. The public NBA page prefers the live ESPN scoreboard and only uses these rows when the feed is unavailable.';
$adminActive = 'nba';

require __DIR__ . '/includes/header.php';
?>
<div class="policy-note" style="margin-bottom:18px;">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
    <span>The public NBA page reads ESPN’s public scoreboard for the selected date. These saved rows are a fallback, not a substitute for the live feed. japjapTV does not host or embed NBA game streams; the NBA page always points to League Pass, NBA TV Philippines, and official providers.</span>
</div>

<div class="admin-toolbar">
    <div class="admin-toolbar-group">
        <span style="color:var(--muted-2);font-size:10.5px;"><?= (int) $pagination['total'] ?> saved game<?= (int) $pagination['total'] === 1 ? '' : 's' ?>.</span>
    </div>
    <div class="admin-toolbar-group">
        <a class="btn btn-primary btn-sm" href="<?= e(admin_url('nba-game-edit.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add game</a>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('nba.php')) ?>" target="_blank" rel="noopener">Public NBA page <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
    </div>
</div>

<?php if ($games === []): ?>
    <div class="admin-card admin-empty"><i class="fa-solid fa-basketball" aria-hidden="true"></i> No saved games. Add a fallback row if you want a manual schedule during feed outages.</div>
<?php else: ?>
    <div class="admin-table-wrap">
        <table class="admin-table" style="min-width:880px;">
            <thead>
                <tr>
                    <th>Date (PHT)</th>
                    <th>Matchup</th>
                    <th>Score</th>
                    <th>Status</th>
                    <th>Venue</th>
                    <th>Source</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($games as $game): ?>
                    <tr>
                        <td class="is-nowrap"><?= e(pht_datetime((string) $game['game_date'], 'M j, Y g:i A')) ?></td>
                        <td class="is-primary"><?= e($game['away_team']) ?> at <?= e($game['home_team']) ?></td>
                        <td class="is-nowrap">
                            <?= $game['away_score'] !== null ? (int) $game['away_score'] : '—' ?>
                            <span class="is-muted">–</span>
                            <?= $game['home_score'] !== null ? (int) $game['home_score'] : '—' ?>
                        </td>
                        <td><span class="badge <?= $game['game_status'] === 'live' ? 'badge-live' : ($game['game_status'] === 'postponed' ? 'badge-error' : 'badge-muted') ?>"><?= e(ucfirst((string) $game['game_status'])) ?></span></td>
                        <td style="max-width:200px;"><?= e($game['venue'] ?: '—') ?></td>
                        <td class="is-muted"><?= $game['espn_game_id'] ? 'ESPN ' . e($game['espn_game_id']) : 'Manual' ?></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="admin-icon-btn" href="<?= e(admin_url('nba-game-edit.php?id=' . (int) $game['id'])) ?>" title="Edit game"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form action="<?= e(admin_url('nba-games.php')) ?>" method="post" data-confirm="Delete this saved game?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="game_id" value="<?= (int) $game['id'] ?>">
                                    <button class="admin-icon-btn is-danger" type="submit" title="Delete game"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['pages'] > 1): ?>
        <nav class="admin-pagination" aria-label="NBA game pages">
            <?php if ($pagination['page'] > 1): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] - 1)) ?>" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a><?php endif; ?>
            <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['pages'], $pagination['page'] + 2); $i++): ?>
                <?php if ($i === $pagination['page']): ?><span class="is-current"><?= $i ?></span><?php else: ?><a href="<?= e(admin_page_url($pagination, $i)) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?><a href="<?= e(admin_page_url($pagination, $pagination['page'] + 1)) ?>" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * Create or edit a saved NBA game (fallback schedule).
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$gameId = ctype_digit(get_string('id')) ? (int) get_string('id') : 0;
$existing = null;
if ($gameId > 0) {
    $stmt = db()->prepare('SELECT * FROM nba_games WHERE id = :id');
    $stmt->execute(['id' => $gameId]);
    $existing = $stmt->fetch() ?: null;
    if ($existing === null) {
        flash('error', 'That game could not be found.');
        redirect('admin/nba-games.php');
    }
}

$form = [
    'espn_game_id'  => (string) ($existing['espn_game_id'] ?? ''),
    'game_date'     => utc_to_local_input((string) ($existing['game_date'] ?? '')),
    'away_team'     => (string) ($existing['away_team'] ?? ''),
    'home_team'     => (string) ($existing['home_team'] ?? ''),
    'away_abbr'     => (string) ($existing['away_abbr'] ?? ''),
    'home_abbr'     => (string) ($existing['home_abbr'] ?? ''),
    'away_score'    => $existing['away_score'] !== null ? (string) $existing['away_score'] : '',
    'home_score'    => $existing['home_score'] !== null ? (string) $existing['home_score'] : '',
    'game_status'   => (string) ($existing['game_status'] ?? 'scheduled'),
    'status_detail' => (string) ($existing['status_detail'] ?? ''),
    'venue'         => (string) ($existing['venue'] ?? ''),
    'broadcast'     => (string) ($existing['broadcast'] ?? ''),
    'official_url'  => (string) ($existing['official_url'] ?? 'https://www.nba.com/games'),
    'notes'         => (string) ($existing['notes'] ?? ''),
];
$errors = [];

if (is_post()) {
    admin_verify_csrf('admin/nba-game-edit.php' . ($existing !== null ? '?id=' . (int) $existing['id'] : ''));

    $form = [
        'espn_game_id'  => post_string('espn_game_id'),
        'game_date'     => post_string('game_date'),
        'away_team'     => post_string('away_team'),
        'home_team'     => post_string('home_team'),
        'away_abbr'     => post_string('away_abbr'),
        'home_abbr'     => post_string('home_abbr'),
        'away_score'    => post_string('away_score'),
        'home_score'    => post_string('home_score'),
        'game_status'   => post_string('game_status'),
        'status_detail' => post_string('status_detail'),
        'venue'         => post_string('venue'),
        'broadcast'     => post_string('broadcast'),
        'official_url'  => post_string('official_url'),
        'notes'         => post_string('notes'),
    ];

    [$data, $errors] = admin_validate_nba_game($form);

    if ($errors === []) {
        $values = [
            'espn_game_id'  => $data['espn_game_id'],
            'game_date'     => $data['game_date'],
            'home_team'     => $data['home_team'],
            'away_team'     => $data['away_team'],
            'home_abbr'     => $data['home_abbr'],
            'away_abbr'     => $data['away_abbr'],
            'home_score'    => $data['home_score'],
            'away_score'    => $data['away_score'],
            'game_status'   => $data['game_status'],
            'status_detail' => $data['status_detail'],
            'venue'         => $data['venue'],
            'broadcast'     => $data['broadcast'],
            'official_url'  => $data['official_url'],
            'notes'         => $data['notes'],
            'updated_at'    => utc_now(),
        ];

        if ($existing !== null) {
            $values['id'] = $existing['id'];
            $stmt = db()->prepare('UPDATE nba_games SET
                espn_game_id = :espn_game_id, game_date = :game_date, home_team = :home_team, away_team = :away_team,
                home_abbr = :home_abbr, away_abbr = :away_abbr, home_score = :home_score, away_score = :away_score,
                game_status = :game_status, status_detail = :status_detail, venue = :venue, broadcast = :broadcast,
                official_url = :official_url, notes = :notes, updated_at = :updated_at
                WHERE id = :id');
            $stmt->execute($values);
            flash('success', 'Saved game updated.');
        } else {
            $values['created_at'] = utc_now();
            $stmt = db()->prepare('INSERT INTO nba_games
                (espn_game_id, game_date, home_team, away_team, home_abbr, away_abbr, home_score, away_score,
                 game_status, status_detail, venue, broadcast, official_url, notes, created_at, updated_at)
                VALUES
                (:espn_game_id, :game_date, :home_team, :away_team, :home_abbr, :away_abbr, :home_score, :away_score,
                 :game_status, :status_detail, :venue, :broadcast, :official_url, :notes, :created_at, :updated_at)');
            $stmt->execute($values);
            flash('success', 'Saved game added.');
        }

        redirect('admin/nba-games.php');
    }

    http_response_code(422);
}

$adminTitle = $existing !== null ? 'Edit saved game' : 'Add saved game';
$adminSubtitle = 'Times are entered in Philippine time and stored in UTC. Used only as a fallback when the ESPN feed is down.';
$adminActive = 'nba';

require __DIR__ . '/includes/header.php';
?>
<nav class="breadcrumbs" style="margin-bottom:16px;" aria-label="Breadcrumb">
    <a href="<?= e(admin_url('nba-games.php')) ?>">NBA games</a>
    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
    <span aria-current="page"><?= $existing !== null ? 'Edit' : 'Add' ?></span>
</nav>

<?php if ($errors !== []): ?>
    <div class="form-errors" role="alert">
        <strong>The saved game could not be saved:</strong>
        <ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form action="<?= e(admin_url('nba-game-edit.php' . ($existing !== null ? '?id=' . (int) $existing['id'] : ''))) ?>" method="post">
    <?= csrf_field() ?>
    <div class="admin-grid-3">
        <div class="admin-card">
            <div class="admin-card-head">
                <div><h2>Game details</h2><p>All times are Philippine time (UTC+8).</p></div>
            </div>

            <div class="admin-form-grid">
                <div class="field">
                    <label for="gameDate">Tip-off date &amp; time (PHT) *</label>
                    <input id="gameDate" type="datetime-local" name="game_date" value="<?= e($form['game_date']) ?>" required>
                </div>
                <div class="field">
                    <label for="gameStatus">Status *</label>
                    <select id="gameStatus" name="game_status" required>
                        <?php foreach (['scheduled' => 'Scheduled', 'live' => 'Live', 'final' => 'Final', 'postponed' => 'Postponed'] as $value => $label): ?>
                            <option value="<?= e($value) ?>"<?= $form['game_status'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="awayTeam">Away team *</label>
                    <input id="awayTeam" type="text" name="away_team" value="<?= e($form['away_team']) ?>" maxlength="100" required>
                </div>
                <div class="field">
                    <label for="awayAbbr">Away abbreviation</label>
                    <input id="awayAbbr" type="text" name="away_abbr" value="<?= e($form['away_abbr']) ?>" maxlength="10" placeholder="LAL">
                </div>
                <div class="field">
                    <label for="homeTeam">Home team *</label>
                    <input id="homeTeam" type="text" name="home_team" value="<?= e($form['home_team']) ?>" maxlength="100" required>
                </div>
                <div class="field">
                    <label for="homeAbbr">Home abbreviation</label>
                    <input id="homeAbbr" type="text" name="home_abbr" value="<?= e($form['home_abbr']) ?>" maxlength="10" placeholder="BOS">
                </div>
                <div class="field">
                    <label for="awayScore">Away score</label>
                    <input id="awayScore" type="number" name="away_score" value="<?= e($form['away_score']) ?>" min="0" max="9999" step="1">
                </div>
                <div class="field">
                    <label for="homeScore">Home score</label>
                    <input id="homeScore" type="number" name="home_score" value="<?= e($form['home_score']) ?>" min="0" max="9999" step="1">
                </div>
                <div class="field field-full">
                    <label for="statusDetail">Status detail</label>
                    <input id="statusDetail" type="text" name="status_detail" value="<?= e($form['status_detail']) ?>" maxlength="100" placeholder="Final, Q4 2:14, Postponed">
                </div>
                <div class="field">
                    <label for="gameVenue">Venue</label>
                    <input id="gameVenue" type="text" name="venue" value="<?= e($form['venue']) ?>" maxlength="150">
                </div>
                <div class="field">
                    <label for="gameBroadcast">Broadcast</label>
                    <input id="gameBroadcast" type="text" name="broadcast" value="<?= e($form['broadcast']) ?>" maxlength="150" placeholder="NBA TV Philippines">
                </div>
                <div class="field field-full">
                    <label for="gameOfficial">Official URL</label>
                    <input id="gameOfficial" type="url" name="official_url" value="<?= e($form['official_url']) ?>" maxlength="1000">
                    <span class="hint">Link to the official game page or NBA schedule. Never an unauthorized stream.</span>
                </div>
                <div class="field">
                    <label for="espnId">ESPN game ID (optional)</label>
                    <input id="espnId" type="text" name="espn_game_id" value="<?= e($form['espn_game_id']) ?>" maxlength="40">
                </div>
                <div class="field">
                    <label for="gameNotes">Notes</label>
                    <input id="gameNotes" type="text" name="notes" value="<?= e($form['notes']) ?>" maxlength="255">
                </div>
            </div>
        </div>

        <div>
            <div class="admin-card">
                <div class="admin-card-head"><h2>Save</h2></div>
                <div class="admin-form-grid" style="grid-template-columns:minmax(0,1fr);">
                    <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= $existing !== null ? 'Save game' : 'Add game' ?></button>
                    <a class="btn btn-ghost btn-block" href="<?= e(admin_url('nba-games.php')) ?>">Cancel</a>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-head"><h2>Reminder</h2></div>
                <p class="admin-help" style="margin-top:0;">These rows do not create a livestream. The public page always shows the official viewing options and labels paid providers clearly.</p>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>

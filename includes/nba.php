<?php
/**
 * NBA schedule data and official viewing destinations.
 *
 * Schedule details are read from ESPN's public scoreboard feed for reference.
 * japjapTV does not host, restream, or authorize any NBA game. Always confirm
 * times and broadcasts with NBA.com and the official local provider.
 */

declare(strict_types=1);

function nba_valid_date(?string $date): ?string
{
    $date = trim((string) $date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('America/New_York'));
    if (!$parsed instanceof DateTimeImmutable || $parsed->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
}

function nba_cache_path(string $date): string
{
    return CACHE_DIR . DIRECTORY_SEPARATOR . 'nba-' . $date . '.json';
}

/**
 * @return array{ok:bool,date:string,games:array,source:string,error:string,fetched_at:int}
 */
function nba_fetch_scoreboard(string $date, bool $fresh = false): array
{
    $date = nba_valid_date($date) ?? today_et();
    $cacheFile = nba_cache_path($date);

    if (!$fresh && is_file($cacheFile)) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && (int) ($cached['expires'] ?? 0) > time()) {
            unset($cached['expires']);
            return $cached;
        }
    }

    $result = [
        'ok'         => false,
        'date'       => $date,
        'games'      => [],
        'source'     => 'ESPN public scoreboard',
        'error'      => '',
        'fetched_at' => time(),
    ];

    $espnDate = str_replace('-', '', $date);
    $url = 'https://site.api.espn.com/apis/site/v2/sports/basketball/nba/scoreboard?dates=' . rawurlencode($espnDate);
    $response = http_fetch($url, 8, 3_500_000);

    if (!$response['ok']) {
        $result['error'] = 'The public NBA scoreboard feed is temporarily unavailable.';
    } else {
        $payload = json_decode($response['body'], true);
        if (!is_array($payload) || !isset($payload['events']) || !is_array($payload['events'])) {
            $result['error'] = 'The scoreboard response could not be read.';
        } else {
            $games = [];
            foreach ($payload['events'] as $event) {
                $game = nba_parse_event($event);
                if ($game !== null) {
                    $games[] = $game;
                }
            }
            usort($games, static fn (array $a, array $b): int => strcmp($a['date_utc'], $b['date_utc']));
            $result['games'] = $games;
            $result['ok'] = true;
        }
    }

    $cacheData = $result;
    $cacheData['expires'] = time() + ($result['ok'] ? NBA_CACHE_TTL : 60);
    if (is_dir(CACHE_DIR) || @mkdir(CACHE_DIR, 0775, true)) {
        @file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    unset($cacheData['expires']);
    return $result;
}

function nba_parse_event(array $event): ?array
{
    $competition = $event['competitions'][0] ?? null;
    if (!is_array($competition)) {
        return null;
    }

    $home = null;
    $away = null;
    foreach (($competition['competitors'] ?? []) as $competitor) {
        if (($competitor['homeAway'] ?? '') === 'home') {
            $home = $competitor;
        } elseif (($competitor['homeAway'] ?? '') === 'away') {
            $away = $competitor;
        }
    }

    if ($home === null || $away === null) {
        return null;
    }

    $status = $competition['status']['type'] ?? $event['status']['type'] ?? [];
    $state = (string) ($status['state'] ?? 'pre');
    $statusName = (string) ($status['name'] ?? '');
    $detail = (string) ($status['shortDetail'] ?? $status['detail'] ?? '');
    $detail = trim(preg_replace('/\s+/', ' ', $detail) ?? '');

    $postponed = str_contains($statusName, 'POSTPONED') || str_contains(strtolower($detail), 'postponed');
    if ($postponed) {
        $state = 'post';
        $label = 'Postponed';
        $class = 'is-postponed';
    } elseif ($state === 'in') {
        $label = 'Live now';
        $class = 'is-live';
    } elseif ($state === 'post') {
        $label = 'Final';
        $class = 'is-final';
    } else {
        $label = 'Upcoming';
        $class = 'is-upcoming';
    }

    $dateUtc = (string) ($event['date'] ?? $competition['date'] ?? '');
    $phtTime = 'Time TBA';
    $etTime = 'Time TBA';
    try {
        $date = new DateTimeImmutable($dateUtc, new DateTimeZone('UTC'));
        $phtTime = $date->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('g:i A');
        $etTime = $date->setTimezone(new DateTimeZone('America/New_York'))->format('g:i A');
    } catch (Exception) {
        // Leave fallback labels.
    }

    $homeTeam = $home['team'] ?? [];
    $awayTeam = $away['team'] ?? [];

    return [
        'id'           => (string) ($event['id'] ?? ''),
        'date_utc'     => $dateUtc,
        'pht_time'     => $phtTime,
        'et_time'      => $etTime,
        'status_state' => $state,
        'status_label' => $label,
        'status_class' => $class,
        'status_detail'=> $detail,
        'home'         => [
            'name'    => (string) ($homeTeam['displayName'] ?? 'Home'),
            'short'   => (string) ($homeTeam['shortDisplayName'] ?? $homeTeam['name'] ?? 'Home'),
            'abbr'    => (string) ($homeTeam['abbreviation'] ?? 'HOME'),
            'score'   => (string) ($home['score'] ?? ''),
            'winner'  => (bool) ($home['winner'] ?? false),
            'color'   => nba_team_color((string) ($homeTeam['color'] ?? ''), (string) ($homeTeam['abbreviation'] ?? 'HOME')),
            'record'  => (string) ($home['records'][0]['summary'] ?? ''),
        ],
        'away'         => [
            'name'    => (string) ($awayTeam['displayName'] ?? 'Away'),
            'short'   => (string) ($awayTeam['shortDisplayName'] ?? $awayTeam['name'] ?? 'Away'),
            'abbr'    => (string) ($awayTeam['abbreviation'] ?? 'AWAY'),
            'score'   => (string) ($away['score'] ?? ''),
            'winner'  => (bool) ($away['winner'] ?? false),
            'color'   => nba_team_color((string) ($awayTeam['color'] ?? ''), (string) ($awayTeam['abbreviation'] ?? 'AWAY')),
            'record'  => (string) ($away['records'][0]['summary'] ?? ''),
        ],
        'venue'        => (string) ($competition['venue']['fullName'] ?? ''),
        'official_url' => 'https://www.nba.com/games',
    ];
}

/**
 * Fallback schedule from the local table, normalised to the same shape as
 * nba_parse_event(). Used only when the public ESPN feed is unavailable.
 */
function nba_games_from_db(string $date): array
{
    $date = nba_valid_date($date) ?? today_et();
    $zone = new DateTimeZone('America/New_York');
    $start = new DateTimeImmutable($date . ' 00:00:00', $zone);
    $end = $start->modify('+1 day');

    $stmt = db()->prepare('SELECT * FROM nba_games
        WHERE game_date >= :start AND game_date < :end
        ORDER BY game_date ASC');
    $stmt->execute([
        'start' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        'end'   => $end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ]);

    $games = [];
    foreach ($stmt->fetchAll() as $row) {
        $dateUtc = (string) $row['game_date'];
        $phtTime = 'Time TBA';
        $etTime = 'Time TBA';
        $time = new DateTimeImmutable($dateUtc, new DateTimeZone('UTC'));
        $phtTime = $time->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('g:i A');
        $etTime = $time->setTimezone($zone)->format('g:i A');

        $status = (string) $row['game_status'];
        [$label, $class] = match ($status) {
            'live'      => ['Live now', 'is-live'],
            'final'     => ['Final', 'is-final'],
            'postponed' => ['Postponed', 'is-postponed'],
            default     => ['Upcoming', 'is-upcoming'],
        };

        $homeScore = $row['home_score'] !== null ? (string) $row['home_score'] : '';
        $awayScore = $row['away_score'] !== null ? (string) $row['away_score'] : '';
        $homeAbbr = (string) ($row['home_abbr'] ?? 'HOME');
        $awayAbbr = (string) ($row['away_abbr'] ?? 'AWAY');
        $homeWon = $status === 'final' && $homeScore !== '' && $awayScore !== '' && (int) $homeScore > (int) $awayScore;
        $awayWon = $status === 'final' && $homeScore !== '' && $awayScore !== '' && (int) $awayScore > (int) $homeScore;

        $games[] = [
            'id'            => (string) $row['id'],
            'date_utc'      => $dateUtc,
            'pht_time'      => $phtTime,
            'et_time'       => $etTime,
            'status_state'  => $status === 'live' ? 'in' : ($status === 'final' ? 'post' : 'pre'),
            'status_label'  => $label,
            'status_class'  => $class,
            'status_detail' => (string) ($row['status_detail'] ?? ''),
            'home'          => [
                'name'   => (string) $row['home_team'],
                'short'  => (string) $row['home_team'],
                'abbr'   => $homeAbbr,
                'score'  => $homeScore,
                'winner' => $homeWon,
                'color'  => nba_team_color('', $homeAbbr),
                'record' => '',
            ],
            'away'          => [
                'name'   => (string) $row['away_team'],
                'short'  => (string) $row['away_team'],
                'abbr'   => $awayAbbr,
                'score'  => $awayScore,
                'winner' => $awayWon,
                'color'  => nba_team_color('', $awayAbbr),
                'record' => '',
            ],
            'venue'         => (string) ($row['venue'] ?? ''),
            'official_url'  => safe_external_url((string) ($row['official_url'] ?? '')) ?: 'https://www.nba.com/games',
        ];
    }

    return $games;
}

function nba_team_color(string $color, string $abbr = ''): string
{
    $color = ltrim(trim($color), '#');
    if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
        return '#' . strtolower($color);
    }

    $fallback = [
        'LAL' => '#552583', 'GSW' => '#1d428a', 'BOS' => '#007a33', 'NYK' => '#f58426',
        'CHI' => '#ce1141', 'MIA' => '#98002e', 'DEN' => '#0e2240', 'PHX' => '#1d1160',
        'MIL' => '#00471b', 'DAL' => '#00538c', 'PHI' => '#006bb6', 'BKN' => '#000000',
        'LAC' => '#c8102e', 'SAS' => '#c4ced4', 'TOR' => '#ce1141', 'UTA' => '#002b5c',
        'POR' => '#e03a3e', 'SAC' => '#5a2d81', 'CLE' => '#860038', 'IND' => '#fdbb30',
        'DET' => '#c8102e', 'ATL' => '#e03a3e', 'CHA' => '#1d1160', 'ORL' => '#0077c0',
        'WAS' => '#002b5c', 'MEM' => '#5d76a9', 'NOP' => '#0c2340', 'MIN' => '#0c2340',
        'OKC' => '#007ac1', 'HOU' => '#ce1141',
    ];

    return $fallback[strtoupper($abbr)] ?? '#5b6472';
}

function nba_date_label(string $date): string
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('America/New_York'));
    return $parsed ? $parsed->format('D, M j, Y') : $date;
}

function nba_shift_date(string $date, int $days): string
{
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('America/New_York'));
    if (!$parsed) {
        return today_et();
    }

    return $parsed->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');
}

function nba_viewing_options(): array
{
    return [
        [
            'name'        => 'NBA League Pass',
            'access'      => 'subscription',
            'label'       => 'Subscription required',
            'url'         => 'https://www.nba.com/watch/',
            'description' => 'The NBA’s official live-game and replay service. A paid League Pass subscription and an NBA account are required. Blackout and regional rules apply.',
            'icon'        => 'fa-basketball',
            'note'        => 'Not free. Not embedded by japjapTV.',
        ],
        [
            'name'        => 'NBA TV Philippines',
            'access'      => 'subscription',
            'label'       => 'Subscription required',
            'url'         => 'https://www.cignalplay.com/',
            'description' => 'NBA TV Philippines is carried by Cignal. Access through Cignal Play requires a qualifying Cignal subscription and login.',
            'icon'        => 'fa-satellite-dish',
            'note'        => 'Not free. Official provider login required.',
        ],
        [
            'name'        => 'NBA on YouTube',
            'access'      => 'free',
            'label'       => 'Free highlights',
            'url'         => 'https://www.youtube.com/@NBA',
            'description' => 'Official NBA highlights, clips, press conferences, and league updates. This is not a free full-game livestream channel.',
            'icon'        => 'fa-circle-play',
            'note'        => 'Free. Highlights and clips only.',
        ],
        [
            'name'        => 'NBA.com schedule',
            'access'      => 'info',
            'label'       => 'Official schedule',
            'url'         => 'https://www.nba.com/schedule',
            'description' => 'Verify game dates, tip-off times, and the official broadcaster for your territory at NBA.com.',
            'icon'        => 'fa-calendar-days',
            'note'        => 'Free schedule information.',
        ],
    ];
}

function sports_destinations(): array
{
    return [
        [
            'name'        => 'PBA',
            'access'      => 'free',
            'label'       => 'Official league site',
            'url'         => 'https://www.pba.ph/',
            'description' => 'Philippine Basketball Association news, schedules, and official viewing information. Selected games may be available through official partner platforms.',
            'icon'        => 'fa-basketball',
        ],
        [
            'name'        => 'FIBA',
            'access'      => 'free',
            'label'       => 'Selected free events',
            'url'         => 'https://www.fiba.basketball/en',
            'description' => 'FIBA’s official competitions hub. Some qualifying games and events are streamed free on FIBA’s official channels; availability varies by event and territory.',
            'icon'        => 'fa-globe',
        ],
        [
            'name'        => 'ONE Championship',
            'access'      => 'free',
            'label'       => 'Selected free events',
            'url'         => 'https://www.onefc.com/',
            'description' => 'Official ONE Championship news and event information. Selected bouts and shows are offered free on official ONE platforms in some regions.',
            'icon'        => 'fa-hand-fist',
        ],
        [
            'name'        => 'Olympics',
            'access'      => 'free',
            'label'       => 'Official event coverage',
            'url'         => 'https://olympics.com/',
            'description' => 'Official Olympic news, results, and event coverage. Free livestream availability depends on the event and your territory.',
            'icon'        => 'fa-medal',
        ],
        [
            'name'        => 'Cignal Play',
            'access'      => 'subscription',
            'label'       => 'Subscription required',
            'url'         => 'https://www.cignalplay.com/',
            'description' => 'Official Philippine pay-TV streaming platform carrying sports channels such as One Sports, PBA Rush, and NBA TV Philippines. Requires a subscription/login.',
            'icon'        => 'fa-tv',
        ],
        [
            'name'        => 'Pilipinas Live',
            'access'      => 'subscription',
            'label'       => 'Subscription required',
            'url'         => 'https://pilipinaslive.com/',
            'description' => 'Official Philippine sports streaming platform for selected PBA, UAAP, and other local events. A paid subscription may be required.',
            'icon'        => 'fa-mobile-screen-button',
        ],
    ];
}

function nba_status_text(array $game): string
{
    if (($game['status_class'] ?? '') === 'is-live') {
        return $game['status_detail'] !== '' ? 'Live · ' . $game['status_detail'] : 'Live now';
    }

    return (string) ($game['status_label'] ?? 'Scheduled');
}

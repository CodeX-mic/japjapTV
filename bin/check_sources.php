<?php
/**
 * CLI source checker for cron or Task Scheduler.
 *
 * Usage:
 *   php bin/check_sources.php
 *   php bin/check_sources.php --channel=12
 *   php bin/check_sources.php --source=34
 *   php bin/check_sources.php --dry-run
 *   php bin/check_sources.php --json
 *
 * This performs the same non-invasive checks as the admin “Check all sources”
 * action. It only reads public pages and manifests; it never bypasses a login,
 * paywall, DRM system, or geographic restriction.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    exit("This script may only be run from the command line.\n");
}

require_once dirname(__DIR__) . '/includes/bootstrap.php';

/**
 * @param array<int,string> $argv
 * @return array{help:bool,dry_run:bool,json:bool,quiet:bool,source:int,channel:int}
 */
function check_sources_parse_args(array $argv): array
{
    $options = [
        'help'    => false,
        'dry_run' => false,
        'json'    => false,
        'quiet'   => false,
        'source'  => 0,
        'channel' => 0,
    ];

    $args = array_slice($argv, 1);
    for ($i = 0; $i < count($args); $i++) {
        $arg = (string) $args[$i];
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
            continue;
        }
        if ($arg === '--dry-run') {
            $options['dry_run'] = true;
            continue;
        }
        if ($arg === '--json') {
            $options['json'] = true;
            continue;
        }
        if ($arg === '--quiet' || $arg === '-q') {
            $options['quiet'] = true;
            continue;
        }

        if (preg_match('/^--(source|channel)(?:=(\d+))?$/', $arg, $matches)) {
            $value = $matches[2] ?? '';
            if ($value === '') {
                $next = $args[$i + 1] ?? '';
                if (!is_string($next) || !ctype_digit($next)) {
                    fwrite(STDERR, "--{$matches[1]} requires a numeric ID.\n");
                    exit(2);
                }
                $value = $next;
                $i++;
            }
            $options[$matches[1]] = (int) $value;
            continue;
        }

        fwrite(STDERR, "Unknown option: {$arg}\n");
        exit(2);
    }

    if ($options['source'] > 0 && $options['channel'] > 0) {
        fwrite(STDERR, "Use either --source or --channel, not both.\n");
        exit(2);
    }

    if ($options['source'] < 0 || $options['channel'] < 0) {
        fwrite(STDERR, "IDs must be positive integers.\n");
        exit(2);
    }

    return $options;
}

function check_sources_usage(): string
{
    return <<<TXT
japjapTV source checker

Usage:
  php bin/check_sources.php [options]

Options:
  --source=ID     Check one configured source by ID
  --channel=ID    Check every active, authorized source for one channel
  --dry-run       List the sources that would be checked, without network calls
  --json          Emit machine-readable JSON instead of progress text
  --quiet, -q     Suppress per-source progress (not used with --json)
  --help, -h      Show this help

The checker updates source status, verification timestamps, history, and channel
availability using the same rules as the admin console. It never promotes a
channel on an HTTP 200 alone. A channel is demoted only after three consecutive
definitive failures on every active, authorized source.

Exit codes:
  0  Checks completed with no definitive source failures
  1  At least one source returned offline or error
  2  Invalid invocation, database problem, or another checker is running

TXT;
}

$options = check_sources_parse_args($_SERVER['argv'] ?? []);
if ($options['help']) {
    fwrite(STDOUT, check_sources_usage() . "\n");
    exit(0);
}

if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}
ignore_user_abort(true);

$lockPath = LOG_DIR . DIRECTORY_SEPARATOR . 'check_sources.lock';
$lock = @fopen($lockPath, 'c');
if ($lock === false) {
    fwrite(STDERR, "Could not open the checker lock file: {$lockPath}\n");
    exit(2);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Another source check is already running. Try again when it finishes.\n");
    fclose($lock);
    exit(2);
}

$baseSql = 'SELECT sx.*, c.channel_name, c.slug AS channel_slug
    FROM channel_sources sx
    INNER JOIN channels c ON c.id = sx.channel_id';

try {
    if ($options['source'] > 0) {
        $stmt = db()->prepare($baseSql . ' WHERE sx.id = :id LIMIT 1');
        $stmt->execute(['id' => $options['source']]);
    } elseif ($options['channel'] > 0) {
        $stmt = db()->prepare($baseSql . ' WHERE sx.channel_id = :channel_id
            AND sx.is_active = 1 AND sx.is_authorized = 1
            ORDER BY sx.priority ASC, sx.id ASC');
        $stmt->execute(['channel_id' => $options['channel']]);
    } else {
        $stmt = db()->query($baseSql . ' WHERE sx.is_active = 1 AND sx.is_authorized = 1
            ORDER BY sx.channel_id ASC, sx.priority ASC, sx.id ASC');
    }

    $sources = $stmt->fetchAll();
} catch (Throwable $exception) {
    fwrite(STDERR, "Could not load sources: " . $exception->getMessage() . "\n");
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(2);
}

if ($sources === []) {
    $message = $options['source'] > 0
        ? "No source with ID {$options['source']} was found.\n"
        : ($options['channel'] > 0
            ? "No active, authorized sources were found for channel {$options['channel']}.\n"
            : "No active, authorized sources are configured.\n");
    fwrite(STDERR, $message);
    flock($lock, LOCK_UN);
    fclose($lock);
    exit(2);
}

$total = count($sources);

if ($options['dry_run']) {
    if (!$options['json']) {
        fwrite(STDOUT, "Dry run — {$total} source" . ($total === 1 ? '' : 's') . " would be checked.\n");
        foreach ($sources as $index => $source) {
            fwrite(STDOUT, sprintf(
                "  %d. %s — %s (%s, %s)\n",
                $index + 1,
                (string) $source['channel_name'],
                (string) $source['label'],
                source_type_label((string) $source['source_type']),
                (int) $source['is_active'] === 1 && (int) $source['is_authorized'] === 1 ? 'active and authorized' : 'not active/authorized'
            ));
        }
    } else {
        fwrite(STDOUT, json_encode([
            'dry_run' => true,
            'total'   => $total,
            'sources' => array_map(static fn (array $source): array => [
                'id'           => (int) $source['id'],
                'channel_id'   => (int) $source['channel_id'],
                'channel_name' => (string) $source['channel_name'],
                'label'        => (string) $source['label'],
                'type'         => (string) $source['source_type'],
                'active'       => (int) $source['is_active'] === 1,
                'authorized'   => (int) $source['is_authorized'] === 1,
            ], $sources),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    flock($lock, LOCK_UN);
    fclose($lock);
    exit(0);
}

$startedAt = utc_now();
$counts = [
    'live'       => 0,
    'reachable'  => 0,
    'unverified' => 0,
    'offline'    => 0,
    'error'      => 0,
    'other'      => 0,
];
$failures = [];
$results = [];

if (!$options['json'] && !$options['quiet']) {
    fwrite(STDOUT, "japjapTV source checker\n");
    fwrite(STDOUT, 'Started ' . date('Y-m-d H:i:s T') . " (UTC " . gmdate('Y-m-d H:i:s') . ")\n");
    fwrite(STDOUT, "Checking {$total} source" . ($total === 1 ? '' : 's') . ".\n\n");
}

foreach ($sources as $index => $source) {
    $sourceId = (int) $source['id'];
    $label = (string) $source['channel_name'] . ' — ' . (string) $source['label'];

    if (!$options['json'] && !$options['quiet']) {
        fwrite(STDOUT, sprintf('[%d/%d] %s … ', $index + 1, $total, $label));
    }

    try {
        $result = check_source_record($source, true, null);
        $status = (string) $result['status'];
        $message = (string) $result['message'];
    } catch (Throwable $exception) {
        $status = 'error';
        $message = 'Checker exception: ' . $exception->getMessage();
        error_log('[japjapTV] check_sources.php source ' . $sourceId . ': ' . $message);
    }

    if (isset($counts[$status])) {
        $counts[$status]++;
    } else {
        $counts['other']++;
    }

    $isFailure = in_array($status, ['offline', 'error'], true);
    if ($isFailure) {
        $failures[] = [
            'source_id'    => $sourceId,
            'channel_id'   => (int) $source['channel_id'],
            'channel_name' => (string) $source['channel_name'],
            'label'        => (string) $source['label'],
            'status'       => $status,
            'message'      => $message,
        ];
    }

    $results[] = [
        'source_id'    => $sourceId,
        'channel_id'   => (int) $source['channel_id'],
        'channel_name' => (string) $source['channel_name'],
        'label'        => (string) $source['label'],
        'type'         => (string) $source['source_type'],
        'status'       => $status,
        'message'      => $message,
    ];

    if (!$options['json'] && !$options['quiet']) {
        fwrite(STDOUT, strtoupper($status) . "\n");
        fwrite(STDOUT, '        ' . $message . "\n");
    }

    // Be polite to broadcasters' public endpoints.
    if ($index < $total - 1) {
        usleep(200000);
    }
}

$finishedAt = utc_now();
$failedCount = $counts['offline'] + $counts['error'];
$exitCode = $failedCount > 0 ? 1 : 0;

if ($options['json']) {
    fwrite(STDOUT, json_encode([
        'started_at'  => $startedAt,
        'finished_at' => $finishedAt,
        'total'       => $total,
        'counts'      => $counts,
        'failures'    => $failures,
        'results'     => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
} else {
    fwrite(STDOUT, "\nSummary: {$total} checked; "
        . "{$counts['live']} live, "
        . "{$counts['reachable']} reachable, "
        . "{$counts['unverified']} unverified, "
        . "{$counts['offline']} offline, "
        . "{$counts['error']} error.\n");

    if ($failures !== []) {
        fwrite(STDOUT, "Definitive failures:\n");
        foreach ($failures as $failure) {
            fwrite(STDOUT, "  - {$failure['channel_name']} / {$failure['label']}: {$failure['message']}\n");
        }
        fwrite(STDOUT, "A channel is demoted only when every active, authorized source reaches three consecutive failures.\n");
    }

    fwrite(STDOUT, 'Finished ' . date('Y-m-d H:i:s T') . ".\n");
}

flock($lock, LOCK_UN);
fclose($lock);
exit($exitCode);

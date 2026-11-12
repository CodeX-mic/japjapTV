<?php
/**
 * Shared helpers, output escaping, and channel queries.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Output and URLs
// ---------------------------------------------------------------------------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    $version = is_file($full) ? (string) filemtime($full) : APP_VERSION;

    return url($path) . '?v=' . rawurlencode($version);
}

function redirect(string $path, int $code = 302): never
{
    if (preg_match('~^https?://~i', $path)) {
        $location = $path;
    } else {
        $location = url($path);
    }

    header('Location: ' . $location, true, $code);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post_string(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function post_int(string $key, int $default = 0): int
{
    $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
    return $value === false || $value === null ? $default : (int) $value;
}

function get_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function safe_external_url(?string $url): ?string
{
    if ($url === null || $url === '') {
        return null;
    }

    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    return $url;
}

function truncate_text(string $text, int $limit = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(mb_substr($text, 0, max(0, $limit - 1))) . '…';
}

function format_views(int $count): string
{
    if ($count >= 1000000) {
        return number_format($count / 1000000, 1) . 'M';
    }
    if ($count >= 1000) {
        return number_format($count / 1000, 1) . 'K';
    }
    return (string) $count;
}

function query_url(array $overrides = []): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? url('index.php'), PHP_URL_PATH) ?: url('index.php');
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }

    $query = http_build_query($params);
    return $path . ($query !== '' ? '?' . $query : '');
}

function nav_active(string $page): string
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return $current === $page ? ' is-active' : '';
}

// ---------------------------------------------------------------------------
// Flash messages and CSRF
// ---------------------------------------------------------------------------

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($flashes) ? $flashes : [];
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token ??= isset($_POST['_csrf']) && is_string($_POST['_csrf']) ? $_POST['_csrf'] : '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return $token !== '' && is_string($sessionToken) && hash_equals($sessionToken, $token);
}

function csrf_failure(string $redirectPath = ''): never
{
    http_response_code(419);
    flash('error', 'Your session expired or the request could not be verified. Please try again.');

    if ($redirectPath !== '') {
        redirect($redirectPath);
    }

    exit('Request could not be verified.');
}

function client_ip_hash(): string
{
    return hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|japjaptv');
}

// ---------------------------------------------------------------------------
// Dates (database stores UTC; website displays Asia/Manila)
// ---------------------------------------------------------------------------

function utc_now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function pht_datetime(?string $utc, string $format = 'M j, Y g:i A'): string
{
    if (!$utc) {
        return '—';
    }

    try {
        $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone(APP_TIMEZONE))->format($format);
    } catch (Exception) {
        return '—';
    }
}

function pht_date(?string $utc, string $format = 'M j, Y'): string
{
    return pht_datetime($utc, $format);
}

function time_ago(?string $utc): string
{
    if (!$utc) {
        return 'never';
    }

    try {
        $then = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        $diff = time() - $then->getTimestamp();
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' min ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . ' hr ago';
        }
        if ($diff < 2592000) {
            return floor($diff / 86400) . ' days ago';
        }
        return pht_date($utc, 'M j, Y');
    } catch (Exception) {
        return 'unknown';
    }
}

function utc_to_local_input(?string $utc): string
{
    if (!$utc) {
        return '';
    }

    try {
        $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
        return $date->setTimezone(new DateTimeZone(APP_TIMEZONE))->format('Y-m-d\TH:i');
    } catch (Exception) {
        return '';
    }
}

function local_input_to_utc(string $local): ?string
{
    $local = trim($local);
    if ($local === '') {
        return null;
    }

    foreach (['Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $local, new DateTimeZone(APP_TIMEZONE));
        if ($date instanceof DateTimeImmutable) {
            return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
    }

    return null;
}

function today_pht(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d');
}

function today_et(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('America/New_York')))->format('Y-m-d');
}

// ---------------------------------------------------------------------------
// Validation and slugs
// ---------------------------------------------------------------------------

function valid_http_url(?string $url): bool
{
    return safe_external_url($url) !== null;
}

function slugify(string $text): string
{
    $text = trim(mb_strtolower($text));
    if (function_exists('transliterator_transliterate')) {
        $transliterated = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
        if (is_string($transliterated) && $transliterated !== '') {
            $text = $transliterated;
        }
    }

    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'channel';
}

function unique_channel_slug(string $slug, ?int $ignoreId = null): string
{
    $base = slugify($slug);
    $candidate = $base;
    $suffix = 2;

    while (true) {
        $sql = 'SELECT id FROM channels WHERE slug = :slug';
        $params = ['slug' => $candidate];
        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }
        $stmt = db()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        if (!$stmt->fetch()) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

// ---------------------------------------------------------------------------
// Channel query helpers
// ---------------------------------------------------------------------------

function channel_base_select(): string
{
    return "SELECT c.*,
            co.country_name, co.country_code,
            cat.category_name, cat.category_slug,
            (SELECT COUNT(*) FROM channel_sources sx
                WHERE sx.channel_id = c.id AND sx.is_active = 1 AND sx.is_authorized = 1) AS active_source_count,
            (SELECT sx.source_type FROM channel_sources sx
                WHERE sx.channel_id = c.id AND sx.is_active = 1 AND sx.is_authorized = 1
                ORDER BY sx.priority ASC, sx.id ASC LIMIT 1) AS primary_source_type,
            (SELECT sx.last_verified FROM channel_sources sx
                WHERE sx.channel_id = c.id AND sx.is_active = 1 AND sx.is_authorized = 1
                ORDER BY sx.priority ASC, sx.id ASC LIMIT 1) AS source_last_verified
        FROM channels c
        INNER JOIN countries co ON co.id = c.country_id
        INNER JOIN categories cat ON cat.id = c.category_id";
}

function normalize_channel_filters(): array
{
    $access = get_string('access');
    if (!in_array($access, ['free', 'subscription', 'unknown'], true)) {
        $access = '';
    }

    $status = get_string('status');
    if ($status === 'live') {
        $status = 'available';
    }
    if (!in_array($status, ['available', 'external_only', 'unavailable', 'coming_soon'], true)) {
        $status = '';
    }

    $region = strtolower(get_string('region'));
    if (!in_array($region, ['ph', 'international'], true)) {
        $region = '';
    }

    $sort = get_string('sort', 'recommended');
    if (!in_array($sort, ['recommended', 'name', 'newest', 'popular'], true)) {
        $sort = 'recommended';
    }

    $category = strtolower(get_string('category'));
    if ($category !== '' && !preg_match('/^[a-z0-9-]{1,80}$/', $category)) {
        $category = '';
    }

    $country = strtoupper(get_string('country'));
    if ($country !== '' && !preg_match('/^[A-Z]{2}$/', $country)) {
        $country = '';
    }

    return [
        'q'        => mb_substr(get_string('q'), 0, 100),
        'category' => $category,
        'country'  => $country,
        'access'   => $access,
        'status'   => $status,
        'region'   => $region,
        'sort'     => $sort,
    ];
}

function build_channel_where(array $filters): array
{
    $where = [];
    $params = [];

    if ($filters['q'] !== '') {
        $where[] = '(c.channel_name LIKE :q_name OR c.description LIKE :q_desc
                     OR co.country_name LIKE :q_country OR cat.category_name LIKE :q_category)';
        $like = '%' . $filters['q'] . '%';
        $params['q_name'] = $like;
        $params['q_desc'] = $like;
        $params['q_country'] = $like;
        $params['q_category'] = $like;
    }

    if ($filters['category'] !== '') {
        $where[] = 'cat.category_slug = :category';
        $params['category'] = $filters['category'];
    }

    if ($filters['country'] !== '') {
        $where[] = 'co.country_code = :country';
        $params['country'] = $filters['country'];
    }

    if ($filters['access'] !== '') {
        $where[] = 'c.access_type = :access';
        $params['access'] = $filters['access'];
    }

    if ($filters['status'] !== '') {
        $where[] = 'c.availability_status = :status';
        $params['status'] = $filters['status'];

        if ($filters['status'] === 'available') {
            $where[] = 'EXISTS (SELECT 1 FROM channel_sources sx
                                WHERE sx.channel_id = c.id AND sx.is_active = 1 AND sx.is_authorized = 1)';
        }
    }

    if ($filters['region'] === 'ph') {
        $where[] = "co.country_code = 'PH'";
    } elseif ($filters['region'] === 'international') {
        $where[] = "co.country_code <> 'PH'";
    }

    return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
}

function search_channels(array $filters, int $limit = 24, int $offset = 0): array
{
    [$where, $params] = build_channel_where($filters);

    $order = match ($filters['sort']) {
        'name'    => 'c.channel_name ASC',
        'newest'  => 'c.created_at DESC, c.channel_name ASC',
        'popular' => 'c.view_count DESC, c.is_featured DESC, c.display_order ASC',
        default   => 'c.is_featured DESC, c.display_order ASC, c.channel_name ASC',
    };

    $limit = max(1, min(96, $limit));
    $offset = max(0, $offset);

    $stmt = db()->prepare(channel_base_select() . $where . " ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function count_channels(array $filters): int
{
    [$where, $params] = build_channel_where($filters);

    $sql = 'SELECT COUNT(*) FROM channels c
            INNER JOIN countries co ON co.id = c.country_id
            INNER JOIN categories cat ON cat.id = c.category_id' . $where;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

function get_channel_by_slug(string $slug): ?array
{
    $stmt = db()->prepare(channel_base_select() . ' WHERE c.slug = :slug LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $channel = $stmt->fetch();

    return $channel ?: null;
}

function get_channel_by_id(int $id): ?array
{
    $stmt = db()->prepare(channel_base_select() . ' WHERE c.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $channel = $stmt->fetch();

    return $channel ?: null;
}

function get_primary_source(int $channelId, ?int $sourceId = null): ?array
{
    if ($sourceId !== null) {
        $stmt = db()->prepare('SELECT * FROM channel_sources
            WHERE id = :id AND channel_id = :channel_id AND is_active = 1 AND is_authorized = 1
            LIMIT 1');
        $stmt->execute(['id' => $sourceId, 'channel_id' => $channelId]);
        $source = $stmt->fetch();
        if ($source) {
            return $source;
        }
    }

    $stmt = db()->prepare('SELECT * FROM channel_sources
        WHERE channel_id = :channel_id AND is_active = 1 AND is_authorized = 1
        ORDER BY priority ASC, id ASC LIMIT 1');
    $stmt->execute(['channel_id' => $channelId]);

    return $stmt->fetch() ?: null;
}

function get_active_sources(int $channelId): array
{
    $stmt = db()->prepare('SELECT * FROM channel_sources
        WHERE channel_id = :channel_id AND is_active = 1 AND is_authorized = 1
        ORDER BY priority ASC, id ASC');
    $stmt->execute(['channel_id' => $channelId]);

    return $stmt->fetchAll();
}

function get_channel_sources(int $channelId): array
{
    $stmt = db()->prepare('SELECT * FROM channel_sources
        WHERE channel_id = :channel_id ORDER BY priority ASC, id ASC');
    $stmt->execute(['channel_id' => $channelId]);

    return $stmt->fetchAll();
}

function get_source_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT sx.*, c.channel_name, c.slug AS channel_slug
        FROM channel_sources sx INNER JOIN channels c ON c.id = sx.channel_id
        WHERE sx.id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);

    return $stmt->fetch() ?: null;
}

function get_featured_channels(int $limit = 6): array
{
    $limit = max(1, min(24, $limit));
    $stmt = db()->prepare(channel_base_select() . "
        INNER JOIN featured_channels fc ON fc.channel_id = c.id
        ORDER BY fc.display_order ASC, c.display_order ASC
        LIMIT {$limit}");
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_live_now(int $limit = 12): array
{
    $limit = max(1, min(48, $limit));
    $stmt = db()->prepare(channel_base_select() . "
        WHERE c.availability_status = 'available'
          AND EXISTS (SELECT 1 FROM channel_sources sx
                      WHERE sx.channel_id = c.id AND sx.is_active = 1 AND sx.is_authorized = 1)
        ORDER BY c.is_featured DESC, c.display_order ASC, c.channel_name ASC
        LIMIT {$limit}");
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_popular_channels(int $limit = 10): array
{
    $limit = max(1, min(48, $limit));
    $stmt = db()->prepare(channel_base_select() . "
        ORDER BY c.view_count DESC, c.is_featured DESC, c.display_order ASC, c.channel_name ASC
        LIMIT {$limit}");
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_related_channels(array $channel, int $limit = 8): array
{
    $limit = max(1, min(24, $limit));
    $stmt = db()->prepare(channel_base_select() . "
        WHERE c.id <> :id
        ORDER BY (c.category_id = :category_id) DESC,
                 (c.country_id = :country_id) DESC,
                 c.is_featured DESC, c.display_order ASC
        LIMIT {$limit}");
    $stmt->execute([
        'id'          => $channel['id'],
        'category_id' => $channel['category_id'],
        'country_id'  => $channel['country_id'],
    ]);

    return $stmt->fetchAll();
}

function get_adjacent_channel(array $channel, string $direction = 'next'): ?array
{
    if ($direction === 'prev') {
        $sql = "SELECT id, slug, channel_name, logo FROM channels
                WHERE (display_order < :order_lt)
                   OR (display_order = :order_eq AND id < :id)
                ORDER BY display_order DESC, id DESC LIMIT 1";
        $params = [
            'order_lt' => $channel['display_order'],
            'order_eq' => $channel['display_order'],
            'id'       => $channel['id'],
        ];
    } else {
        $sql = "SELECT id, slug, channel_name, logo FROM channels
                WHERE (display_order > :order_gt)
                   OR (display_order = :order_eq AND id > :id)
                ORDER BY display_order ASC, id ASC LIMIT 1";
        $params = [
            'order_gt' => $channel['display_order'],
            'order_eq' => $channel['display_order'],
            'id'       => $channel['id'],
        ];
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() ?: null;
}

function get_category_tiles(): array
{
    $stmt = db()->query("SELECT cat.*, COUNT(c.id) AS channel_count,
            SUM(CASE WHEN c.availability_status = 'available' THEN 1 ELSE 0 END) AS live_count
        FROM categories cat
        LEFT JOIN channels c ON c.category_id = cat.id
        GROUP BY cat.id
        ORDER BY cat.display_order ASC, cat.category_name ASC");

    return $stmt->fetchAll();
}

function get_country_options(): array
{
    $stmt = db()->query('SELECT co.*, COUNT(c.id) AS channel_count
        FROM countries co LEFT JOIN channels c ON c.country_id = co.id
        GROUP BY co.id
        ORDER BY co.country_name ASC');

    return $stmt->fetchAll();
}

function get_catalog_stats(): array
{
    $row = db()->query("SELECT
            COUNT(*) AS total,
            SUM(availability_status = 'available') AS available,
            SUM(availability_status = 'external_only') AS external_only,
            SUM(availability_status = 'unavailable') AS unavailable,
            SUM(access_type = 'free') AS free_channels,
            SUM(access_type = 'subscription') AS subscription_channels
        FROM channels")->fetch();

    $row['total_sources'] = (int) db()->query('SELECT COUNT(*) FROM channel_sources')->fetchColumn();
    $row['live_sources'] = (int) db()->query("SELECT COUNT(*) FROM channel_sources WHERE is_active = 1 AND is_authorized = 1 AND last_check_status = 'live'")->fetchColumn();

    return $row ?: [];
}

function increment_channel_view(int $channelId): void
{
    $key = 'viewed_channel_' . $channelId;
    if (isset($_SESSION[$key]) && (time() - (int) $_SESSION[$key]) < 1800) {
        return;
    }

    $stmt = db()->prepare('UPDATE channels SET view_count = view_count + 1 WHERE id = :id');
    $stmt->execute(['id' => $channelId]);
    $_SESSION[$key] = time();
}

function get_upcoming_nba_games(int $limit = 8): array
{
    $limit = max(1, min(30, $limit));
    $stmt = db()->prepare("SELECT * FROM nba_games
        WHERE game_status IN ('scheduled', 'live')
        ORDER BY game_date ASC LIMIT {$limit}");
    $stmt->execute();

    return $stmt->fetchAll();
}

// ---------------------------------------------------------------------------
// Presentation metadata
// ---------------------------------------------------------------------------

function channel_logo_url(?array $channel): string
{
    $logo = trim((string) ($channel['logo'] ?? ''));

    if ($logo !== '' && !str_contains($logo, '..') && !preg_match('~^https?://~i', $logo)) {
        $full = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($logo, '/'));
        if (is_file($full)) {
            return url($logo);
        }
    }

    $slug = (string) ($channel['slug'] ?? '');
    if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
        $generated = 'assets/images/logos/' . $slug . '.svg';
        if (is_file(APP_ROOT . '/' . $generated)) {
            return url($generated);
        }
    }

    return url(DEFAULT_LOGO);
}

function channel_accent(?array $channel): string
{
    $accent = (string) ($channel['accent_color'] ?? '');
    return preg_match('/^#[0-9a-fA-F]{6}$/', $accent) ? $accent : '#e50914';
}

function availability_meta(string $status): array
{
    return match ($status) {
        'available'      => ['label' => 'Live now', 'short' => 'Live', 'class' => 'is-live', 'icon' => 'fa-tower-broadcast'],
        'external_only'  => ['label' => 'Official site', 'short' => 'Official', 'class' => 'is-external', 'icon' => 'fa-arrow-up-right-from-square'],
        'unavailable'    => ['label' => 'Unavailable', 'short' => 'Offline', 'class' => 'is-offline', 'icon' => 'fa-circle-slash'],
        'coming_soon'    => ['label' => 'Coming soon', 'short' => 'Soon', 'class' => 'is-soon', 'icon' => 'fa-clock'],
        default          => ['label' => 'Check availability', 'short' => 'Check', 'class' => 'is-unknown', 'icon' => 'fa-circle-info'],
    };
}

function access_label(string $access): string
{
    return match ($access) {
        'free'         => 'Free',
        'subscription' => 'Subscription',
        default        => 'Check access',
    };
}

function source_type_label(string $type): string
{
    return match ($type) {
        'youtube_live'  => 'YouTube live',
        'youtube_video' => 'YouTube video',
        'hls'           => 'HLS stream',
        'iframe'        => 'Official iframe',
        'external'      => 'External link',
        default         => ucfirst($type),
    };
}

function channel_fallback_reason(array $channel, ?array $source = null): string
{
    if (($channel['availability_status'] ?? '') === 'unavailable') {
        return 'This channel is currently unavailable or has ceased broadcasting. No player is loaded.';
    }

    if (($channel['availability_status'] ?? '') === 'external_only') {
        return 'No authorized, embeddable livestream is currently available for this channel. Open the official broadcaster destination to watch it there.';
    }

    if ($source === null) {
        return 'No active source is configured for this channel yet. An administrator can add or restore the official source.';
    }

    return 'The official stream could not be loaded. It may be offline, restricted in your region, or currently refusing embedding.';
}

function source_status_badge(string $status): string
{
    return match ($status) {
        'live'        => 'badge badge-live',
        'reachable'   => 'badge badge-ok',
        'offline'     => 'badge badge-offline',
        'error'       => 'badge badge-error',
        'unverified'  => 'badge badge-muted',
        default       => 'badge badge-muted',
    };
}

function sort_label(string $sort): string
{
    return match ($sort) {
        'name'    => 'Name A–Z',
        'newest'  => 'Recently added',
        'popular' => 'Most watched',
        default   => 'Recommended',
    };
}

function selection_options(): array
{
    return [
        'recommended' => 'Recommended',
        'popular'     => 'Most watched',
        'name'        => 'Name A–Z',
        'newest'      => 'Recently added',
    ];
}

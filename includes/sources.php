<?php
/**
 * Authorized source helpers, YouTube live resolution, and availability checks.
 *
 * The checker only inspects public pages/manifests. It never bypasses a login,
 * paywall, DRM system, or geographic restriction.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Low-level HTTP
// ---------------------------------------------------------------------------

function http_fetch(string $url, int $timeout = SOURCE_CHECK_TIMEOUT, int $maxBytes = 4_500_000): array
{
    $result = [
        'ok'            => false,
        'http_code'     => 0,
        'body'          => '',
        'error'         => '',
        'effective_url' => $url,
        'content_type'  => '',
    ];

    if (!is_safe_public_url($url, true)) {
        $result['error'] = 'URL is not a safe public HTTPS destination.';
        return $result;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => HTTP_USER_AGENT,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/json,application/vnd.apple.mpegurl,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ],
        ]);

        if (str_contains($url, 'youtube.com')) {
            curl_setopt($ch, CURLOPT_COOKIE, 'CONSENT=YES+cb.20240101-00-p0.en+FX+000; SOCS=CAI');
        }

        $body = curl_exec($ch);
        $result['http_code'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $result['effective_url'] = (string) (curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url);
        $result['content_type'] = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $result['error'] = curl_error($ch);
        curl_close($ch);

        if (is_string($body) && strlen($body) > $maxBytes) {
            $body = substr($body, 0, $maxBytes);
        }

        $result['body'] = is_string($body) ? $body : '';
        $result['ok'] = $result['http_code'] >= 200 && $result['http_code'] < 400 && $result['body'] !== '';
    } else {
        $context = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => $timeout,
                'follow_location' => 1,
                'max_redirects' => 3,
                'user_agent'    => HTTP_USER_AGENT,
                'header'        => "Accept: text/html,application/xhtml+xml,application/json,*/*;q=0.8\r\nAccept-Language: en-US,en;q=0.9\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $headers = $http_response_header ?? [];
        $result['http_code'] = 0;
        foreach ($headers as $header) {
            if (preg_match('~^HTTP/\S+\s+(\d{3})~', $header, $m)) {
                $result['http_code'] = (int) $m[1];
            }
        }
        $result['body'] = is_string($body) ? substr($body, 0, $maxBytes) : '';
        $result['ok'] = $result['http_code'] >= 200 && $result['http_code'] < 400 && $result['body'] !== '';
        if (!$result['ok']) {
            $result['error'] = 'The remote server did not return a usable response.';
        }
    }

    return $result;
}

function ip_is_public(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) !== false;
}

/**
 * Reject localhost, private ranges, link-local metadata endpoints and
 * non-HTTPS destinations before the server makes an outbound request.
 */
function is_safe_public_url(string $url, bool $requireHttps = true): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($requireHttps ? $scheme !== 'https' : !in_array($scheme, ['http', 'https'], true)) {
        return false;
    }

    if (!empty($parts['user']) || !empty($parts['pass'])) {
        return false;
    }

    $host = strtolower(rtrim($parts['host'], '.'));
    if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return ip_is_public($host);
    }

    $ips = [];
    $v4 = @gethostbynamel($host);
    if (is_array($v4)) {
        $ips = array_merge($ips, $v4);
    }

    $aaaa = @dns_get_record($host, DNS_AAAA);
    if (is_array($aaaa)) {
        foreach ($aaaa as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }
    }

    if ($ips === []) {
        return false;
    }

    foreach ($ips as $ip) {
        if (!ip_is_public($ip)) {
            return false;
        }
    }

    return true;
}

// ---------------------------------------------------------------------------
// YouTube helpers
// ---------------------------------------------------------------------------

function extract_youtube_channel_id(?string $input): ?string
{
    $input = trim((string) $input);
    if ($input === '') {
        return null;
    }

    if (preg_match('/^UC[A-Za-z0-9_-]{22}$/', $input)) {
        return $input;
    }

    if (preg_match('~/channel/(UC[A-Za-z0-9_-]{22})~', $input, $m)) {
        return $m[1];
    }

    return null;
}

/**
 * Resolve an @handle or channel URL to a UC… channel ID. Only used by the
 * administrator form; the public site always reads stored IDs.
 */
function resolve_youtube_channel_input(string $input): ?string
{
    $direct = extract_youtube_channel_id($input);
    if ($direct !== null) {
        return $direct;
    }

    $url = null;
    if (preg_match('~^https?://(www\.)?youtube\.com/@([A-Za-z0-9_.-]+)~i', $input)) {
        $url = $input;
    } elseif (preg_match('/^@([A-Za-z0-9_.-]+)$/', $input)) {
        $url = 'https://www.youtube.com/' . $input;
    }

    if ($url === null || !is_safe_public_url($url, true)) {
        return null;
    }

    $response = http_fetch($url, 10, 3_000_000);
    if (!$response['ok']) {
        return null;
    }

    if (preg_match('/"externalId":"(UC[A-Za-z0-9_-]{22})"/', $response['body'], $m)) {
        return $m[1];
    }

    return null;
}

function extract_youtube_video_id(?string $input): ?string
{
    $input = trim((string) $input);
    if ($input === '') {
        return null;
    }

    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
        return $input;
    }

    if (preg_match('~(?:youtube\.com/(?:watch\?[^#]*v=|embed/|live/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $input, $m)) {
        return $m[1];
    }

    return null;
}

function youtube_channel_live_url(string $channelId): string
{
    return 'https://www.youtube.com/channel/' . rawurlencode($channelId) . '/live';
}

function youtube_live_embed_url(string $channelId): string
{
    return 'https://www.youtube.com/embed/live_stream?channel=' . rawurlencode($channelId);
}

function youtube_video_embed_url(string $videoId): string
{
    return 'https://www.youtube.com/embed/' . rawurlencode($videoId);
}

function youtube_cache_path(string $channelId): string
{
    return CACHE_DIR . DIRECTORY_SEPARATOR . 'yt-' . preg_replace('/[^A-Za-z0-9_-]/', '', $channelId) . '.json';
}

/**
 * Inspect the public /live page of a YouTube channel. Confirmed live means:
 *  - the page reports isLive=true
 *  - YouTube's playability status is OK
 *  - playableInEmbed=true
 *  - a videoId is present
 */
function youtube_live_status(string $channelId, bool $fresh = false): array
{
    $channelId = trim($channelId);
    $result = [
        'channel_id' => $channelId,
        'live'       => false,
        'embeddable' => false,
        'video_id'   => null,
        'http_code'  => 0,
        'error'      => '',
        'message'    => 'No live stream detected.',
        'checked_at' => time(),
    ];

    if (!preg_match('/^UC[A-Za-z0-9_-]{22}$/', $channelId)) {
        $result['error'] = 'Invalid YouTube channel ID.';
        $result['message'] = $result['error'];
        return $result;
    }

    $cacheFile = youtube_cache_path($channelId);
    if (!$fresh && is_file($cacheFile)) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && (int) ($cached['expires'] ?? 0) > time()) {
            unset($cached['expires']);
            return $cached;
        }
    }

    $response = http_fetch(youtube_channel_live_url($channelId), 9, 4_000_000);
    $result['http_code'] = $response['http_code'];

    if (!$response['ok']) {
        $result['error'] = $response['error'] !== ''
            ? $response['error']
            : 'YouTube returned HTTP ' . $response['http_code'];
        $result['message'] = 'Could not reach the official YouTube live page.';
    } else {
        $body = $response['body'];
        $isLive = (bool) preg_match('/"isLive":true/', $body);
        $playable = (bool) preg_match('/"playabilityStatus":\{"status":"OK"/', $body);
        $embeddable = (bool) preg_match('/"playableInEmbed":true/', $body);

        $result['live'] = $isLive && $playable;
        $result['embeddable'] = $isLive && $playable && $embeddable;

        if (preg_match('/"videoId":"([A-Za-z0-9_-]{11})"/', $body, $m)) {
            $result['video_id'] = $m[1];
        }

        if ($result['live'] && $result['embeddable'] && $result['video_id']) {
            $result['message'] = 'Live and embeddable on the official YouTube channel.';
        } elseif ($result['live'] && !$embeddable) {
            $result['message'] = 'A live stream is present but embedding is not permitted.';
        } elseif ($isLive) {
            $result['message'] = 'The live page is present but YouTube reports it as offline.';
        } else {
            $result['message'] = 'No live stream is currently detected on the official channel.';
        }
    }

    $cacheData = $result;
    $cacheData['expires'] = time() + ($result['error'] !== '' ? 60 : YOUTUBE_CACHE_TTL);
    if (is_dir(CACHE_DIR) || @mkdir(CACHE_DIR, 0775, true)) {
        @file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    unset($cacheData['expires']);
    return $result;
}

function resolve_youtube_live_video(string $channelId): ?string
{
    $status = youtube_live_status($channelId);
    return ($status['live'] && $status['embeddable'] && is_string($status['video_id']))
        ? $status['video_id']
        : null;
}

// ---------------------------------------------------------------------------
// HLS verification
// ---------------------------------------------------------------------------

function resolve_manifest_uri(string $baseUrl, string $uri): string
{
    $uri = trim($uri);
    if (preg_match('~^https?://~i', $uri)) {
        return $uri;
    }

    $parts = parse_url($baseUrl);
    if (!is_array($parts) || empty($parts['host'])) {
        return $uri;
    }

    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'];
    $port = isset($parts['port']) ? ':' . $parts['port'] : '';

    if (str_starts_with($uri, '//')) {
        return $scheme . ':' . $uri;
    }

    if (str_starts_with($uri, '/')) {
        return $scheme . '://' . $host . $port . $uri;
    }

    $path = $parts['path'] ?? '/';
    $dir = rtrim(str_contains($path, '/') ? substr($path, 0, strrpos($path, '/') + 1) : '/', '/');

    return $scheme . '://' . $host . $port . $dir . '/' . ltrim($uri, '/');
}

function check_hls_source(string $url): array
{
    $result = [
        'status'     => 'error',
        'http_code'  => 0,
        'message'    => 'The HLS manifest could not be verified.',
        'verified'   => false,
    ];

    if (!is_safe_public_url($url, true)) {
        $result['message'] = 'The HLS URL is not a safe public HTTPS destination.';
        return $result;
    }

    $response = http_fetch($url, SOURCE_CHECK_TIMEOUT, 1_500_000);
    $result['http_code'] = $response['http_code'];

    if (!$response['ok']) {
        $result['message'] = 'The HLS manifest was not reachable.';
        return $result;
    }

    $body = ltrim($response['body']);
    if (!str_starts_with($body, '#EXTM3U')) {
        $result['message'] = 'The response is not an HLS manifest.';
        return $result;
    }

    if (preg_match('/#EXT-X-STREAM-INF/i', $body)) {
        $lines = preg_split('/\r?\n/', $body) ?: [];
        $variant = null;
        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), '#EXT-X-STREAM-INF')) {
                for ($i = $index + 1; $i < count($lines); $i++) {
                    $candidate = trim($lines[$i]);
                    if ($candidate !== '' && !str_starts_with($candidate, '#')) {
                        $variant = $candidate;
                        break 2;
                    }
                }
            }
        }

        if ($variant === null) {
            $result['message'] = 'The HLS master playlist contains no playable variant.';
            return $result;
        }

        $variantUrl = resolve_manifest_uri($response['effective_url'], $variant);
        $variantResponse = http_fetch($variantUrl, SOURCE_CHECK_TIMEOUT, 1_500_000);
        if (!$variantResponse['ok'] || !str_starts_with(ltrim($variantResponse['body']), '#EXTM3U')) {
            $result['message'] = 'The HLS variant playlist could not be verified.';
            return $result;
        }
        $body = $variantResponse['body'];
    }

    if (!preg_match('/#EXTINF|#EXT-X-TARGETDURATION/i', $body)) {
        $result['message'] = 'The HLS manifest does not contain media segments.';
        return $result;
    }

    $result['status'] = 'reachable';
    $result['verified'] = true;
    $result['message'] = preg_match('/#EXT-X-KEY:[^\n]*METHOD=(SAMPLE-AES|SAMPLE-AES-CTR)/i', $body)
        ? 'Manifest and variant are reachable; encrypted playback requires the provider player.'
        : 'Manifest and variant playlist are reachable.';

    return $result;
}

// ---------------------------------------------------------------------------
// Source validation for the admin
// ---------------------------------------------------------------------------

function iframe_url_is_allowed(string $url): bool
{
    $parts = parse_url($url);
    if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) {
        return false;
    }

    $host = strtolower(rtrim($parts['host'], '.'));
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return false;
    }

    foreach (ALLOWED_IFRAME_HOSTS as $allowed) {
        $allowed = strtolower($allowed);
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
            return true;
        }
    }

    return false;
}

/**
 * Build canonical source fields from an administrator's input.
 *
 * @return array{source_url:?string,embed_url:?string,provider_ref:?string,error:?string}
 */
function normalize_source_input(string $type, string $raw, string $label = ''): array
{
    $raw = trim($raw);
    $out = ['source_url' => null, 'embed_url' => null, 'provider_ref' => null, 'error' => null];

    if (!in_array($type, ['youtube_live', 'youtube_video', 'hls', 'iframe', 'external'], true)) {
        $out['error'] = 'Unknown source type.';
        return $out;
    }

    if ($raw === '') {
        $out['error'] = 'Enter the official source URL or identifier.';
        return $out;
    }

    if ($type === 'youtube_live') {
        $channelId = resolve_youtube_channel_input($raw);
        if ($channelId === null) {
            $out['error'] = 'Enter a YouTube channel ID (UC…), a /channel/UC… URL, or an @handle from the official broadcaster.';
            return $out;
        }
        $out['provider_ref'] = $channelId;
        $out['source_url'] = youtube_channel_live_url($channelId);
        $out['embed_url'] = youtube_live_embed_url($channelId);
        return $out;
    }

    if ($type === 'youtube_video') {
        $videoId = extract_youtube_video_id($raw);
        if ($videoId === null) {
            $out['error'] = 'Enter an official YouTube video URL or an 11-character video ID.';
            return $out;
        }
        $out['provider_ref'] = $videoId;
        $out['source_url'] = 'https://www.youtube.com/watch?v=' . rawurlencode($videoId);
        $out['embed_url'] = youtube_video_embed_url($videoId);
        return $out;
    }

    if ($type === 'hls') {
        if (!valid_http_url($raw) || !str_starts_with(strtolower($raw), 'https://')) {
            $out['error'] = 'HLS sources must use a valid HTTPS URL.';
            return $out;
        }
        if (!is_safe_public_url($raw, true)) {
            $out['error'] = 'The HLS URL must resolve to a public internet host.';
            return $out;
        }
        $out['source_url'] = $raw;
        return $out;
    }

    if ($type === 'iframe') {
        if (!valid_http_url($raw) || !iframe_url_is_allowed($raw)) {
            $out['error'] = 'That iframe domain is not on the approved list. Only official, verified embeddable players may be added.';
            return $out;
        }
        $out['source_url'] = $raw;
        $out['embed_url'] = $raw;
        return $out;
    }

    if (!valid_http_url($raw)) {
        $out['error'] = 'Enter a valid official viewing URL (http or https).';
        return $out;
    }
    $out['source_url'] = $raw;
    return $out;
}

function source_embed_url(array $source): ?string
{
    $type = (string) ($source['source_type'] ?? '');
    $ref = (string) ($source['provider_ref'] ?? '');

    if ($type === 'youtube_live' && extract_youtube_channel_id($ref)) {
        return youtube_live_embed_url($ref);
    }
    if ($type === 'youtube_video' && extract_youtube_video_id($ref)) {
        return youtube_video_embed_url($ref);
    }
    if ($type === 'iframe') {
        $embed = (string) ($source['embed_url'] ?? '');
        return iframe_url_is_allowed($embed) ? $embed : null;
    }

    return null;
}

function source_is_embeddable(array $source): bool
{
    return in_array($source['source_type'] ?? '', ['youtube_live', 'youtube_video', 'hls', 'iframe'], true)
        && source_embed_url($source) !== null;
}

// ---------------------------------------------------------------------------
// Availability checks
// ---------------------------------------------------------------------------

/**
 * Check one source and persist the result. This never marks a browser-facing
 * player as working based only on an HTTP 200 webpage.
 */
function check_source_record(array $source, bool $persist = true, ?int $adminId = null): array
{
    $type = (string) $source['source_type'];
    $status = 'unknown';
    $message = 'No verification method is available for this source type.';
    $httpCode = null;

    if ($type === 'youtube_live') {
        $channelId = (string) ($source['provider_ref'] ?? '');
        if (!preg_match('/^UC[A-Za-z0-9_-]{22}$/', $channelId)) {
            $status = 'error';
            $message = 'Missing or invalid YouTube channel ID.';
        } else {
            $live = youtube_live_status($channelId, true);
            $httpCode = $live['http_code'];
            if ($live['error'] !== '') {
                $status = 'error';
                $message = $live['message'];
            } elseif ($live['live'] && $live['embeddable']) {
                $status = 'live';
                $message = 'Live and embeddable (YouTube video ' . ($live['video_id'] ?? 'unknown') . ').';
            } else {
                $status = 'offline';
                $message = $live['message'];
            }
        }
    } elseif ($type === 'youtube_video') {
        $videoId = (string) ($source['provider_ref'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            $status = 'error';
            $message = 'Missing or invalid YouTube video ID.';
        } else {
            $oembed = http_fetch(
                'https://www.youtube.com/oembed?url=' . rawurlencode('https://www.youtube.com/watch?v=' . $videoId) . '&format=json',
                8,
                500_000
            );
            $httpCode = $oembed['http_code'];
            if ($oembed['ok']) {
                $status = 'reachable';
                $message = 'The official YouTube video is public. Live status is not guaranteed for a fixed video source.';
            } else {
                $status = 'error';
                $message = 'YouTube did not confirm the video as publicly available.';
            }
        }
    } elseif ($type === 'hls') {
        $manifest = check_hls_source((string) ($source['source_url'] ?? ''));
        $status = $manifest['status'];
        $message = $manifest['message'];
        $httpCode = $manifest['http_code'];
    } elseif ($type === 'iframe') {
        $status = 'unverified';
        $message = 'Iframe playback cannot be verified automatically. Confirm it manually in a browser.';
    } elseif ($type === 'external') {
        $status = 'unverified';
        $message = 'External destinations are not embedded or automatically verified.';
    }

    $result = [
        'source_id'  => (int) $source['id'],
        'status'     => $status,
        'message'    => $message,
        'http_code'  => $httpCode,
        'checked_at' => utc_now(),
    ];

    if (!$persist) {
        return $result;
    }

    $failures = (int) ($source['consecutive_failures'] ?? 0);
    $failures = in_array($status, ['offline', 'error'], true) ? $failures + 1 : 0;
    $verified = $status === 'live' ? utc_now() : ($source['last_verified'] ?: null);

    $stmt = db()->prepare('UPDATE channel_sources SET
            last_checked = :last_checked,
            last_verified = :last_verified,
            last_check_status = :status,
            last_check_message = :message,
            last_http_code = :http_code,
            consecutive_failures = :failures,
            updated_at = :updated_at
        WHERE id = :id');
    $stmt->execute([
        'last_checked'  => utc_now(),
        'last_verified' => $verified,
        'status'        => $status,
        'message'       => mb_substr($message, 0, 255),
        'http_code'     => $httpCode,
        'failures'      => $failures,
        'updated_at'    => utc_now(),
        'id'            => $source['id'],
    ]);

    $log = db()->prepare('INSERT INTO source_checks
        (source_id, status, http_code, message, checked_by, checked_at)
        VALUES (:source_id, :status, :http_code, :message, :checked_by, :checked_at)');
    $log->execute([
        'source_id'  => $source['id'],
        'status'     => $status,
        'http_code'  => $httpCode,
        'message'    => mb_substr($message, 0, 255),
        'checked_by' => $adminId,
        'checked_at' => utc_now(),
    ]);

    // Keep the log small.
    db()->prepare('DELETE FROM source_checks WHERE checked_at < (UTC_TIMESTAMP() - INTERVAL 90 DAY)')->execute();

    sync_channel_availability((int) $source['channel_id']);

    return $result;
}

/**
 * Promote a channel to "available" when one of its sources is confirmed live.
 * Demote to "external_only" only after three consecutive definitive failures
 * on every active source. Uncertain results never change availability.
 */
function sync_channel_availability(int $channelId): void
{
    $stmt = db()->prepare("SELECT availability_status FROM channels WHERE id = :id");
    $stmt->execute(['id' => $channelId]);
    $current = (string) ($stmt->fetchColumn() ?: '');
    if (in_array($current, ['unavailable', 'coming_soon'], true)) {
        return;
    }

    $stmt = db()->prepare("SELECT last_check_status, consecutive_failures
        FROM channel_sources
        WHERE channel_id = :channel_id AND is_active = 1 AND is_authorized = 1");
    $stmt->execute(['channel_id' => $channelId]);
    $sources = $stmt->fetchAll();

    if ($sources === []) {
        return;
    }

    $anyLive = false;
    $allDefinitelyDown = true;

    foreach ($sources as $source) {
        if ($source['last_check_status'] === 'live') {
            $anyLive = true;
        }
        if (!in_array($source['last_check_status'], ['offline', 'error'], true) || (int) $source['consecutive_failures'] < 3) {
            $allDefinitelyDown = false;
        }
    }

    $newStatus = null;
    if ($anyLive) {
        $newStatus = 'available';
    } elseif ($allDefinitelyDown) {
        $newStatus = 'external_only';
    }

    if ($newStatus !== null && $newStatus !== $current) {
        $stmt = db()->prepare('UPDATE channels SET availability_status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute(['status' => $newStatus, 'updated_at' => utc_now(), 'id' => $channelId]);
    }
}

function check_all_sources(?int $adminId = null): array
{
    $stmt = db()->query('SELECT * FROM channel_sources
        WHERE is_active = 1 AND is_authorized = 1
        ORDER BY channel_id ASC, priority ASC');
    $sources = $stmt->fetchAll();
    $results = [];

    foreach ($sources as $source) {
        $results[] = check_source_record($source, true, $adminId);
        usleep(200000);
    }

    return $results;
}

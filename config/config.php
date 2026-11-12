<?php
/**
 * japjapTV — application configuration
 *
 * XAMPP defaults: MySQL host 127.0.0.1, user root, blank password.
 * Change these values (or set environment variables) before deploying.
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Database
// ---------------------------------------------------------------------------
define('DB_HOST', getenv('JAPJAP_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('JAPJAP_DB_PORT') ?: '3306');
define('DB_NAME', getenv('JAPJAP_DB_NAME') ?: 'japjaptv_db');
define('DB_USER', getenv('JAPJAP_DB_USER') ?: 'root');
define('DB_PASS', getenv('JAPJAP_DB_PASS') !== false ? (string) getenv('JAPJAP_DB_PASS') : '');

// ---------------------------------------------------------------------------
// Application
// ---------------------------------------------------------------------------
define('APP_NAME', 'japjapTV');
define('APP_TAGLINE', 'Your Channels. One Streaming Hub.');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('JAPJAP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('JAPJAP_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));
define('APP_TIMEZONE', 'Asia/Manila');

define('APP_ROOT', dirname(__DIR__));
define('LOG_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'logs');
define('UPLOAD_DIR', APP_ROOT . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'channel_logos');
define('UPLOAD_URL', 'uploads/channel_logos');
define('CACHE_DIR', rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'japjaptv_cache');
define('DEFAULT_LOGO', 'assets/images/placeholder.svg');
define('MAX_LOGO_BYTES', 2 * 1024 * 1024);

// Admin session
define('SESSION_NAME', 'japjaptvsess');
define('ADMIN_IDLE_TIMEOUT', 2 * 60 * 60);           // 2 hours
define('ADMIN_SESSION_REFRESH', 15 * 60);            // regenerate id every 15 min
define('ADMIN_MAX_ATTEMPTS', 5);
define('ADMIN_ATTEMPT_WINDOW', 15 * 60);             // 15 minutes

// Stream source checking
define('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36 japjapTV-source-check/1.0');
define('SOURCE_CHECK_TIMEOUT', 9);
define('YOUTUBE_CACHE_TTL', 120);                    // seconds
define('NBA_CACHE_TTL', 300);                        // seconds

/**
 * Iframe hosts that administrators are allowed to configure.
 * YouTube is handled by the dedicated YouTube source types, so generic
 * iframes should only be added for a broadcaster you have explicitly
 * confirmed permits embedding. Keep this list short and auditable.
 */
define('ALLOWED_IFRAME_HOSTS', [
    'www.youtube.com',
    'youtube.com',
    'www.youtube-nocookie.com',
]);

// ---------------------------------------------------------------------------
// Base URL detection (supports http://localhost/japjaptv/ and vhosts)
// ---------------------------------------------------------------------------
if (!function_exists('japjap_detect_base_url')) {
    function japjap_detect_base_url(): string
    {
        $override = getenv('JAPJAP_BASE_URL');
        if ($override !== false && $override !== '') {
            return rtrim($override, '/');
        }

        if (PHP_SAPI === 'cli' || empty($_SERVER['DOCUMENT_ROOT'])) {
            return '';
        }

        $docRoot = realpath((string) $_SERVER['DOCUMENT_ROOT']);
        $appRoot = realpath(APP_ROOT);

        if ($docRoot === false || $appRoot === false) {
            return '';
        }

        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        $appRoot = str_replace('\\', '/', $appRoot);

        if ($appRoot === $docRoot) {
            return '';
        }

        if (str_starts_with($appRoot, $docRoot . '/')) {
            return '/' . trim(substr($appRoot, strlen($docRoot)), '/');
        }

        return '';
    }
}

define('BASE_URL', japjap_detect_base_url());

/**
 * Absolute origin (scheme + host) used as the YouTube API origin.
 * The host header is sanitised and never rendered raw.
 */
if (!function_exists('japjap_origin')) {
    function japjap_origin(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $host) ?: 'localhost';

        return ($https ? 'https' : 'http') . '://' . $host;
    }
}

define('APP_ORIGIN', japjap_origin());

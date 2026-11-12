<?php
/**
 * Application bootstrap: configuration, database, helpers, sessions, headers.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/sources.php';
require_once dirname(__DIR__) . '/includes/nba.php';

date_default_timezone_set(APP_TIMEZONE);

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

foreach ([LOG_DIR, CACHE_DIR, UPLOAD_DIR] as $directory) {
    if (!is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }
}

ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'php-error.log');

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        "[japjapTV] %s: %s in %s:%d\n%s",
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    ));

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    if (APP_DEBUG) {
        echo '<pre style="white-space:pre-wrap;padding:2rem;background:#111;color:#f5f5f5;">';
        echo htmlspecialchars((string) $exception, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        echo '</pre>';
    } else {
        require APP_ROOT . '/errors/500.php';
    }

    exit;
});

if (PHP_SAPI !== 'cli') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', '86400');

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? '') === '443');

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (!headers_sent()) {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header('X-Permitted-Cross-Domain-Policies: none');

        $frameHosts = array_values(array_unique(array_merge(
            ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com'],
            ALLOWED_IFRAME_HOSTS
        )));
        $frameSrc = implode(' ', array_map(static fn (string $host): string => 'https://' . $host, $frameHosts));

        $csp = "default-src 'self'; "
            . "base-uri 'self'; "
            . "object-src 'none'; "
            . "frame-ancestors 'self'; "
            . "form-action 'self'; "
            . "script-src 'self' https://www.youtube.com https://s.ytimg.com https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
            . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; "
            . "img-src 'self' data: https:; "
            . "media-src 'self' https: blob:; "
            . "connect-src 'self' https:; "
            . "worker-src 'self' blob:; "
            . "manifest-src 'self'; "
            . "frame-src " . $frameSrc . ';';

        header('Content-Security-Policy: ' . $csp);

        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $isAdmin = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
        header($isAdmin ? 'Cache-Control: no-store, private' : 'Cache-Control: no-cache, private');
    }
}

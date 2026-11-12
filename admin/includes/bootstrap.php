<?php
/**
 * Admin bootstrap. Protected pages are guarded automatically.
 *
 * A public admin page (login.php) must define ADMIN_PUBLIC_PAGE before
 * requiring this file.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/admin-functions.php';

if (!defined('ADMIN_PUBLIC_PAGE')) {
    admin_require_login();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

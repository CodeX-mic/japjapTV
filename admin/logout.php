<?php
/**
 * Admin sign-out. POST-only and CSRF protected.
 */

declare(strict_types=1);

define('ADMIN_PUBLIC_PAGE', true);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    if (!verify_csrf()) {
        csrf_failure('admin/login.php');
    }

    admin_logout();
    redirect('admin/login.php');
}

redirect('admin/index.php');

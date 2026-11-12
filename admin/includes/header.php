<?php
/**
 * Admin chrome: sidebar, top bar, flash messages, and content opener.
 *
 * Expects $adminTitle and optional $adminActive / $adminSubtitle.
 */

declare(strict_types=1);

$adminTitle = $adminTitle ?? 'Dashboard';
$adminSubtitle = $adminSubtitle ?? '';
$adminActive = $adminActive ?? 'dashboard';
$admin = current_admin();
$adminFlashes = pull_flashes();
$unreadMessages = admin_message_count();

$adminNav = [
    ['key' => 'dashboard',  'label' => 'Dashboard',  'href' => 'index.php',     'icon' => 'fa-gauge-high'],
    ['key' => 'channels',   'label' => 'Channels',   'href' => 'channels.php',  'icon' => 'fa-tv'],
    ['key' => 'sources',    'label' => 'Sources',    'href' => 'sources.php',   'icon' => 'fa-satellite-dish'],
    ['key' => 'featured',   'label' => 'Featured',   'href' => 'featured.php',  'icon' => 'fa-star'],
    ['key' => 'nba',        'label' => 'NBA games',  'href' => 'nba-games.php', 'icon' => 'fa-basketball'],
    ['key' => 'categories', 'label' => 'Categories', 'href' => 'categories.php','icon' => 'fa-layer-group'],
    ['key' => 'countries',  'label' => 'Countries',  'href' => 'countries.php', 'icon' => 'fa-globe'],
    ['key' => 'messages',   'label' => 'Messages',   'href' => 'messages.php',  'icon' => 'fa-envelope', 'badge' => $unreadMessages],
    ['key' => 'account',    'label' => 'Account',    'href' => 'account.php',   'icon' => 'fa-user-shield'],
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0f">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($adminTitle) ?> · japjapTV Admin</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<a class="skip-link" href="#adminMain">Skip to admin content</a>

<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
        <a class="admin-brand" href="<?= e(admin_url('index.php')) ?>">
            <img src="<?= e(url('assets/images/logo-mark.svg')) ?>" width="36" height="36" alt="">
            <span>
                <strong>japjap<span>TV</span></strong>
                <small>Admin console</small>
            </span>
        </a>

        <nav class="admin-nav">
            <p class="admin-nav-label">Manage</p>
            <?php foreach ($adminNav as $item): ?>
                <?php if ($item['key'] === 'account'): ?>
                    <p class="admin-nav-label admin-nav-label-spaced">Account</p>
                <?php endif; ?>
                <a class="admin-nav-link<?= $adminActive === $item['key'] ? ' is-active' : '' ?>" href="<?= e(admin_url($item['href'])) ?>"<?= $adminActive === $item['key'] ? ' aria-current="page"' : '' ?>>
                    <i class="fa-solid <?= e($item['icon']) ?>" aria-hidden="true"></i>
                    <span><?= e($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="admin-nav-badge"><?= (int) $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">
                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> View site
            </a>
            <form action="<?= e(admin_url('logout.php')) ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out</button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-menu-toggle" type="button" data-admin-menu aria-controls="adminSidebar" aria-expanded="false">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
                <span class="sr-only">Toggle admin navigation</span>
            </button>
            <div class="admin-topbar-copy">
                <p class="admin-eyebrow">Admin console</p>
                <h1><?= e($adminTitle) ?></h1>
                <?php if ($adminSubtitle !== ''): ?><p><?= e($adminSubtitle) ?></p><?php endif; ?>
            </div>
            <div class="admin-topbar-actions">
                <a class="admin-user" href="<?= e(admin_url('account.php')) ?>">
                    <span class="admin-avatar"><?= e(mb_strtoupper(mb_substr((string) ($admin['display_name'] ?? 'A'), 0, 1))) ?></span>
                    <span>
                        <strong><?= e($admin['display_name'] ?? 'Administrator') ?></strong>
                        <small>@<?= e($admin['username'] ?? 'admin') ?></small>
                    </span>
                </a>
            </div>
        </header>

        <main id="adminMain" class="admin-content">
            <?php if ((int) ($admin['must_change_password'] ?? 0) === 1): ?>
                <div class="admin-alert is-warning">
                    <i class="fa-solid fa-key" aria-hidden="true"></i>
                    <div>
                        <strong>You are still using the default password.</strong>
                        <p>Replace it now on the <a href="<?= e(admin_url('account.php')) ?>">account page</a> before configuring sources.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($adminFlashes !== []): ?>
                <div class="flash-stack" aria-live="polite">
                    <?php foreach ($adminFlashes as $flash): ?>
                        <?php
                        $flashType = in_array($flash['type'] ?? '', ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info';
                        $flashIcon = match ($flashType) {
                            'success' => 'fa-circle-check',
                            'error' => 'fa-circle-exclamation',
                            'warning' => 'fa-triangle-exclamation',
                            default => 'fa-circle-info',
                        };
                        ?>
                        <div class="flash flash-<?= e($flashType) ?>" role="<?= $flashType === 'error' ? 'alert' : 'status' ?>">
                            <i class="fa-solid <?= e($flashIcon) ?>" aria-hidden="true"></i>
                            <span><?= e($flash['message'] ?? '') ?></span>
                            <button type="button" class="flash-close" data-dismiss-flash aria-label="Dismiss message"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

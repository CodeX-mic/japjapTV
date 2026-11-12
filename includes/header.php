<?php
/**
 * Public page head, navigation, flash messages, and <main> opener.
 *
 * Pages set $pageTitle / $pageDescription / $bodyClass before including this
 * file. They must load includes/bootstrap.php first.
 */

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Live TV, curated from official sources';
$fullTitle = $fullTitle ?? ($pageTitle . ' · ' . APP_NAME);
$pageDescription = $pageDescription ?? 'Browse Philippine and international livestreams from official broadcasters. Play authorized embedded streams or open the broadcaster’s own destination.';
$bodyClass = $bodyClass ?? '';
$canonical = $canonical ?? (APP_ORIGIN . ($_SERVER['REQUEST_URI'] ?? url('index.php')));
$metaImage = $metaImage ?? url('assets/images/og-cover.png');
$robots = $robots ?? 'index, follow';
$includePlayer = $includePlayer ?? false;
$activeNav = $activeNav ?? '';
$flashes = pull_flashes();
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#080a0f">
    <meta name="color-scheme" content="dark">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="japjapTV">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($metaImage) ?>">
    <meta property="og:image:alt" content="japjapTV — your channels, one streaming hub">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($fullTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e($metaImage) ?>">

    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('assets/images/favicon-32.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('assets/images/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
</head>
<body class="<?= e(trim($bodyClass)) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<div class="site-shell">
    <?php require __DIR__ . '/navbar.php'; ?>

    <?php if ($flashes !== []): ?>
        <div class="flash-stack container" aria-live="polite">
            <?php foreach ($flashes as $flash): ?>
                <?php
                $flashType = in_array($flash['type'] ?? '', ['success', 'error', 'warning', 'info'], true)
                    ? $flash['type']
                    : 'info';
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

    <main id="main" class="site-main">

<?php
/**
 * Public site header and primary navigation.
 */

declare(strict_types=1);

$navItems = [
    'index.php' => 'Home',
    'channels.php' => 'Channels',
    'philippines.php' => 'Philippines',
    'international.php' => 'International',
    'sports.php' => 'Sports',
    'nba.php' => 'NBA',
    'about.php' => 'About',
];

$isActive = static function (string $page) use ($activeNav): bool {
    if ($activeNav !== '') {
        return $activeNav === $page;
    }

    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $page;
};

$searchValue = get_string('q');
?><div class="topbar">
    <div class="container topbar-inner">
        <span class="topbar-note"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Official broadcaster sources only</span>
        <span class="topbar-links">
            <a href="<?= e(url('about.php')) ?>">How it works</a>
            <span aria-hidden="true">·</span>
            <a href="<?= e(url('contact.php')) ?>">Report an issue</a>
        </span>
    </div>
</div>

<header class="site-header" data-site-header>
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="japjapTV home">
            <img src="<?= e(url('assets/images/logo-mark.svg')) ?>" width="42" height="42" alt="">
            <span class="brand-word">japjap<span>TV</span></span>
        </a>

        <nav class="main-nav" aria-label="Primary navigation">
            <?php foreach ($navItems as $page => $label): ?>
                <a class="nav-link<?= $isActive($page) ? ' is-active' : '' ?>" href="<?= e(url($page)) ?>"<?= $isActive($page) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="header-actions">
            <form class="nav-search" action="<?= e(url('search.php')) ?>" method="get" role="search">
                <label class="sr-only" for="navSearch">Search channels</label>
                <i class="fa-solid fa-magnifying-glass nav-search-icon" aria-hidden="true"></i>
                <input id="navSearch" type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Search channels" maxlength="100" autocomplete="off">
                <button type="submit" aria-label="Search"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </form>

            <button class="mobile-toggle" type="button" data-nav-toggle aria-controls="mobileNav" aria-expanded="false" aria-label="Open menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <div class="mobile-nav" id="mobileNav" hidden>
        <div class="container">
            <form class="mobile-search" action="<?= e(url('search.php')) ?>" method="get" role="search">
                <label class="sr-only" for="mobileSearch">Search channels</label>
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input id="mobileSearch" type="search" name="q" value="<?= e($searchValue) ?>" placeholder="Search channels or countries" maxlength="100" autocomplete="off">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </form>
            <nav class="mobile-nav-links" aria-label="Mobile navigation">
                <?php foreach ($navItems as $page => $label): ?>
                    <a class="mobile-nav-link<?= $isActive($page) ? ' is-active' : '' ?>" href="<?= e(url($page)) ?>"<?= $isActive($page) ? ' aria-current="page"' : '' ?>>
                        <span><?= e($label) ?></span>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="mobile-nav-footer">
                <a href="<?= e(url('contact.php')) ?>"><i class="fa-regular fa-message" aria-hidden="true"></i> Contact &amp; corrections</a>
                <a href="<?= e(url('admin/login.php')) ?>"><i class="fa-solid fa-lock" aria-hidden="true"></i> Admin</a>
            </div>
        </div>
    </div>
</header>

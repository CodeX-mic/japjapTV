<?php
/**
 * Admin sign-in.
 */

declare(strict_types=1);

define('ADMIN_PUBLIC_PAGE', true);
require_once __DIR__ . '/includes/bootstrap.php';

if (current_admin() !== null) {
    redirect('admin/index.php');
}

$username = '';
$error = '';
$flashes = pull_flashes();

if (is_post()) {
    if (!verify_csrf()) {
        csrf_failure('admin/login.php');
    }

    $username = post_string('username');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $result = admin_attempt_login($username, $password);

    if ($result['ok']) {
        flash('success', 'Welcome back, ' . ($result['admin']['display_name'] ?? 'administrator') . '.');
        $intended = (string) ($_SESSION['admin_intended'] ?? '');
        unset($_SESSION['admin_intended']);
        if ($intended !== '' && str_starts_with($intended, 'admin/') && !str_contains($intended, '..') && !preg_match('~^[a-z]+://~i', $intended)) {
            redirect($intended);
        }
        if ((int) ($result['admin']['must_change_password'] ?? 0) === 1) {
            redirect('admin/account.php?required=1');
        }
        redirect('admin/index.php');
    }

    $error = $result['error'];
    http_response_code(401);
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0f">
    <meta name="color-scheme" content="dark">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin sign in · japjapTV</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin-body admin-login-body">
<main class="admin-login">
    <a class="admin-login-brand" href="<?= e(url('index.php')) ?>">
        <img src="<?= e(url('assets/images/logo-mark.svg')) ?>" width="38" height="38" alt="">
        <strong>japjap<span>TV</span></strong>
    </a>

    <div class="admin-login-card">
        <p class="admin-eyebrow">Restricted area</p>
        <h1>Admin sign in</h1>
        <p>Manage channels, official sources, featured picks, NBA schedule entries, and visitor messages.</p>

        <?php if ($flashes !== []): ?>
            <?php foreach ($flashes as $flash): ?>
                <?php $flashType = in_array($flash['type'] ?? '', ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info'; ?>
                <div class="flash flash-<?= e($flashType) ?>" role="<?= $flashType === 'error' ? 'alert' : 'status' ?>" style="margin-bottom:14px;">
                    <i class="fa-solid <?= $flashType === 'success' ? 'fa-circle-check' : 'fa-circle-info' ?>" aria-hidden="true"></i>
                    <span><?= e($flash['message'] ?? '') ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="form-errors" role="alert">
                <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= e(admin_url('login.php')) ?>" method="post" novalidate>
            <?= csrf_field() ?>
            <div class="field" style="margin-bottom:13px;">
                <label for="loginUsername">Username</label>
                <input id="loginUsername" type="text" name="username" value="<?= e($username) ?>" maxlength="60" required autocomplete="username" autofocus>
            </div>
            <div class="field" style="margin-bottom:17px;">
                <label for="loginPassword">Password</label>
                <input id="loginPassword" type="password" name="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary btn-block" type="submit"><i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Sign in</button>
        </form>

        <p class="admin-login-note">
            Protected by a CSRF check, session hardening, and a five-attempt / 15-minute lockout. New installations use the starter credentials documented in <span class="admin-kbd">README.md</span>; replace the password immediately after signing in.
        </p>
    </div>

    <a class="admin-login-back" href="<?= e(url('index.php')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to japjapTV</a>
</main>
</body>
</html>

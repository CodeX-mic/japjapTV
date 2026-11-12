<?php
/**
 * Admin authentication, lockout, and password helpers.
 */

declare(strict_types=1);

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

function admin_fingerprint(): string
{
    $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    return hash('sha256', $agent . '|japjaptv-admin-v1');
}

function current_admin(): ?array
{
    static $cached = false;
    static $admin = null;

    if ($cached) {
        return $admin;
    }
    $cached = true;

    $adminId = $_SESSION['admin_id'] ?? null;
    if (!is_int($adminId) && !(is_string($adminId) && ctype_digit($adminId))) {
        $admin = null;
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM admins WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => (int) $adminId]);
    $row = $stmt->fetch();

    if (!$row) {
        admin_clear_session();
        $admin = null;
        return null;
    }

    if (hash_equals((string) ($_SESSION['admin_fingerprint'] ?? ''), admin_fingerprint()) === false) {
        admin_clear_session();
        flash('error', 'Your admin session could not be verified. Please sign in again.');
        $admin = null;
        return null;
    }

    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity > 0 && (time() - $lastActivity) > ADMIN_IDLE_TIMEOUT) {
        admin_clear_session();
        flash('warning', 'You were signed out after a period of inactivity.');
        $admin = null;
        return null;
    }

    $_SESSION['admin_last_activity'] = time();

    $lastRefresh = (int) ($_SESSION['admin_last_refresh'] ?? 0);
    if ($lastRefresh > 0 && (time() - $lastRefresh) > ADMIN_SESSION_REFRESH) {
        session_regenerate_id(true);
        $_SESSION['admin_last_refresh'] = time();
    }

    $admin = $row;
    return $admin;
}

function admin_clear_session(): void
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_fingerprint'],
        $_SESSION['admin_last_activity'],
        $_SESSION['admin_last_refresh']
    );
}

/**
 * Enforce authentication for protected admin pages. Bootstrap calls this
 * automatically unless ADMIN_PUBLIC_PAGE is defined before it loads.
 */
function admin_require_login(): array
{
    $admin = current_admin();
    if ($admin === null) {
        $target = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
        if ($target !== 'login.php') {
            $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
            $_SESSION['admin_intended'] = 'admin/' . $target . ($query !== '' ? '?' . $query : '');
        }
        redirect('admin/login.php');
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ((int) $admin['must_change_password'] === 1 && !in_array($script, ['account.php', 'logout.php'], true)) {
        flash('warning', 'Please replace the default password before continuing.');
        redirect('admin/account.php?required=1');
    }

    return $admin;
}

function admin_login_blocked(string $username): bool
{
    $window = max(60, (int) ADMIN_ATTEMPT_WINDOW);
    $stmt = db()->prepare("SELECT COUNT(*) FROM login_attempts
        WHERE success = 0
          AND attempted_at > (UTC_TIMESTAMP() - INTERVAL {$window} SECOND)
          AND (username = :username OR ip_hash = :ip_hash)");
    $stmt->bindValue(':username', mb_substr($username, 0, 60));
    $stmt->bindValue(':ip_hash', client_ip_hash());
    $stmt->execute();

    return (int) $stmt->fetchColumn() >= ADMIN_MAX_ATTEMPTS;
}

function admin_record_attempt(string $username, bool $success): void
{
    $stmt = db()->prepare('INSERT INTO login_attempts (username, ip_hash, success, attempted_at)
        VALUES (:username, :ip_hash, :success, :attempted_at)');
    $stmt->execute([
        'username'     => mb_substr($username, 0, 60),
        'ip_hash'      => client_ip_hash(),
        'success'      => $success ? 1 : 0,
        'attempted_at' => utc_now(),
    ]);

    db()->prepare('DELETE FROM login_attempts WHERE attempted_at < (UTC_TIMESTAMP() - INTERVAL 7 DAY)')->execute();

    if ($success) {
        $stmt = db()->prepare('DELETE FROM login_attempts WHERE success = 0 AND username = :username');
        $stmt->execute(['username' => mb_substr($username, 0, 60)]);
    }
}

/**
 * @return array{ok:bool,error:string,admin:?array}
 */
function admin_attempt_login(string $username, string $password): array
{
    if ($username === '' || $password === '') {
        return ['ok' => false, 'error' => 'Enter your username and password.', 'admin' => null];
    }

    if (admin_login_blocked($username)) {
        return ['ok' => false, 'error' => 'Too many failed attempts. Wait 15 minutes and try again.', 'admin' => null];
    }

    $stmt = db()->prepare('SELECT * FROM admins WHERE username = :username AND is_active = 1 LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
        admin_record_attempt($username, false);
        return ['ok' => false, 'error' => 'The username or password is incorrect.', 'admin' => null];
    }

    if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
        $rehash = db()->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $admin['id']]);
    }

    admin_record_attempt($username, true);
    session_regenerate_id(true);

    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_fingerprint'] = admin_fingerprint();
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_last_refresh'] = time();
    unset($_SESSION['admin_intended']);

    $update = db()->prepare('UPDATE admins SET last_login_at = :now, last_login_ip = :ip WHERE id = :id');
    $update->execute([
        'now' => utc_now(),
        'ip'  => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'id'  => $admin['id'],
    ]);

    return ['ok' => true, 'error' => '', 'admin' => $admin];
}

function admin_logout(): void
{
    admin_clear_session();
    session_regenerate_id(true);
    flash('success', 'You have been signed out.');
}

/**
 * @return array<int,string>
 */
function admin_password_errors(string $current, string $new, string $confirm, array $admin): array
{
    $errors = [];

    if ($current === '' || !password_verify($current, (string) $admin['password_hash'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (strlen($new) < 12) {
        $errors[] = 'The new password must be at least 12 characters long.';
    }
    if (preg_match('/[A-Z]/', $new) !== 1 || preg_match('/[a-z]/', $new) !== 1 || preg_match('/[0-9]/', $new) !== 1) {
        $errors[] = 'Use at least one uppercase letter, one lowercase letter, and one number.';
    }
    if ($new !== $confirm) {
        $errors[] = 'The new password and confirmation do not match.';
    }
    if ($new !== '' && hash_equals($current, $new)) {
        $errors[] = 'Choose a new password that differs from the current one.';
    }

    return $errors;
}

function admin_password_strength_label(string $password): string
{
    $score = 0;
    $score += strlen($password) >= 12 ? 1 : 0;
    $score += preg_match('/[A-Z]/', $password) === 1 ? 1 : 0;
    $score += preg_match('/[a-z]/', $password) === 1 ? 1 : 0;
    $score += preg_match('/[0-9]/', $password) === 1 ? 1 : 0;
    $score += preg_match('/[^A-Za-z0-9]/', $password) === 1 ? 1 : 0;

    return match (true) {
        $score >= 5 => 'Strong',
        $score >= 3 => 'Fair',
        default     => 'Weak',
    };
}

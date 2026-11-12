<?php
/**
 * Admin profile and password management.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$admin = current_admin();
if ($admin === null) {
    redirect('admin/login.php');
}

$profile = [
    'display_name' => (string) $admin['display_name'],
    'email'        => (string) ($admin['email'] ?? ''),
];
$profileErrors = [];
$passwordErrors = [];
$activeForm = '';
$passwordRequired = (int) $admin['must_change_password'] === 1 || get_string('required') === '1';

if (is_post()) {
    admin_verify_csrf('admin/account.php');
    $action = post_string('action');

    if ($action === 'profile') {
        $activeForm = 'profile';
        $profile['display_name'] = post_string('display_name');
        $profile['email'] = post_string('email');

        if (mb_strlen($profile['display_name']) < 2 || mb_strlen($profile['display_name']) > 100) {
            $profileErrors[] = 'Display name must be 2–100 characters.';
        }
        if ($profile['email'] !== '' && (!filter_var($profile['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($profile['email']) > 190)) {
            $profileErrors[] = 'Enter a valid email address, or leave the field empty.';
        }

        if ($profileErrors === []) {
            $stmt = db()->prepare('UPDATE admins SET display_name = :display_name, email = :email, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'display_name' => $profile['display_name'],
                'email'        => $profile['email'] !== '' ? $profile['email'] : null,
                'updated_at'   => utc_now(),
                'id'           => $admin['id'],
            ]);
            flash('success', 'Your profile details were saved.');
            redirect('admin/account.php');
        }

        http_response_code(422);
    } elseif ($action === 'password') {
        $activeForm = 'password';
        $currentPassword = isset($_POST['current_password']) && is_string($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) && is_string($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $passwordErrors = admin_password_errors($currentPassword, $newPassword, $confirmPassword, $admin);

        if ($passwordErrors === []) {
            $stmt = db()->prepare('UPDATE admins SET password_hash = :hash, must_change_password = 0, updated_at = :updated_at WHERE id = :id');
            $stmt->execute([
                'hash'       => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => utc_now(),
                'id'         => $admin['id'],
            ]);

            session_regenerate_id(true);
            $_SESSION['admin_last_refresh'] = time();
            $_SESSION['admin_last_activity'] = time();

            flash('success', 'Your password was changed. Keep it somewhere safe.');
            redirect('admin/index.php');
        }

        http_response_code(422);
    } else {
        flash('error', 'Unknown account action.');
        redirect('admin/account.php');
    }
}

$adminTitle = 'Account';
$adminSubtitle = 'Update your profile or replace the starter password.';
$adminActive = 'account';

require __DIR__ . '/includes/header.php';
?>
<?php if ($passwordRequired): ?>
    <div class="admin-alert is-warning">
        <i class="fa-solid fa-key" aria-hidden="true"></i>
        <div>
            <strong>Password change required</strong>
            <p>The starter password is public knowledge. Set a unique password before you configure or publish this site. Other admin pages stay locked until the change is complete.</p>
        </div>
    </div>
<?php endif; ?>

<div class="admin-grid">
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Account profile</h2>
                    <p>Your username and sign-in identity cannot be changed here.</p>
                </div>
            </div>

            <?php if ($profileErrors !== []): ?>
                <div class="form-errors" role="alert">
                    <strong>The profile could not be saved:</strong>
                    <ul><?php foreach ($profileErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form action="<?= e(admin_url('account.php')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">

                <div class="admin-form-grid">
                    <div class="field">
                        <label for="accountUsername">Username</label>
                        <input id="accountUsername" type="text" value="<?= e($admin['username']) ?>" disabled>
                        <span class="hint">Set in the database. The primary administrator username is <code><?= e($admin['username']) ?></code>.</span>
                    </div>
                    <div class="field">
                        <label for="accountDisplayName">Display name *</label>
                        <input id="accountDisplayName" type="text" name="display_name" value="<?= e($profile['display_name']) ?>" maxlength="100" required autocomplete="name">
                    </div>
                    <div class="field field-full">
                        <label for="accountEmail">Contact email</label>
                        <input id="accountEmail" type="email" name="email" value="<?= e($profile['email']) ?>" maxlength="190" autocomplete="email">
                        <span class="hint">Used only in the admin console and for account reference. It is not shown publicly.</span>
                    </div>
                </div>

                <div class="form-actions" style="margin-top:16px;">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save profile</button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <div class="admin-card-head">
                <div>
                    <h2>Change password</h2>
                    <p>At least 12 characters, with uppercase, lowercase, and a number.</p>
                </div>
            </div>

            <?php if ($passwordErrors !== []): ?>
                <div class="form-errors" role="alert">
                    <strong>The password was not changed:</strong>
                    <ul><?php foreach ($passwordErrors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form action="<?= e(admin_url('account.php')) ?>" method="post" autocomplete="off">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">

                <div class="field" style="margin-bottom:13px;">
                    <label for="currentPassword">Current password *</label>
                    <input id="currentPassword" type="password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="admin-form-grid">
                    <div class="field">
                        <label for="newPassword">New password *</label>
                        <input id="newPassword" type="password" name="new_password" minlength="12" required autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="confirmPassword">Confirm new password *</label>
                        <input id="confirmPassword" type="password" name="confirm_password" minlength="12" required autocomplete="new-password">
                    </div>
                </div>

                <div class="policy-note" style="margin-top:14px;">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span>Passwords are stored with <code>password_hash()</code>. Changing your password keeps this session active, so sign out separately on devices you no longer use.</span>
                </div>

                <div class="form-actions" style="margin-top:16px;">
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-key" aria-hidden="true"></i> Update password</button>
                </div>
            </form>
        </div>
    </div>

    <div>
        <div class="admin-card">
            <div class="admin-card-head"><h2>Account details</h2></div>
            <dl class="admin-meta-list">
                <div><dt>Username</dt><dd><?= e($admin['username']) ?></dd></div>
                <div><dt>Display name</dt><dd><?= e($admin['display_name']) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($admin['email'] ?: 'Not set') ?></dd></div>
                <div><dt>Created</dt><dd><?= e(pht_date((string) $admin['created_at'])) ?></dd></div>
                <div><dt>Last sign-in</dt><dd><?= e($admin['last_login_at'] ? pht_datetime((string) $admin['last_login_at']) : 'This session') ?></dd></div>
                <div><dt>Password state</dt><dd><?= (int) $admin['must_change_password'] === 1 ? 'Starter password — change required' : 'Custom password set' ?></dd></div>
            </dl>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h2>Session security</h2></div>
            <ul class="account-checklist">
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> CSRF tokens on every state-changing form</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Five-attempt, 15-minute sign-in lockout</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Idle timeout and periodic session ID rotation</li>
                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Secure, HTTP-only, SameSite cookie</li>
            </ul>
            <p class="admin-help">Sign out when you finish, especially on a shared computer. If you believe the account is compromised, change the password immediately and inspect recent source changes.</p>
            <form action="<?= e(admin_url('logout.php')) ?>" method="post">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm btn-block" type="submit"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

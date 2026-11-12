<?php
/**
 * Contact and corrections form. Messages are stored for the administrator.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$channelContext = null;
$channelSlug = get_string('channel');
if ($channelSlug !== '' && preg_match('/^[a-z0-9-]{1,140}$/', $channelSlug)) {
    $channelContext = get_channel_by_slug($channelSlug);
}

$form = [
    'name'    => '',
    'email'   => '',
    'subject' => $channelContext !== null ? 'Correction or issue: ' . $channelContext['channel_name'] : '',
    'message' => '',
];
$errors = [];
$sent = get_string('sent') === '1';
$consent = false;

if (is_post()) {
    if (!verify_csrf()) {
        csrf_failure('contact.php');
    }

    $form['name'] = post_string('name');
    $form['email'] = post_string('email');
    $form['subject'] = post_string('subject');
    $form['message'] = post_string('message');
    $consent = isset($_POST['consent']) && $_POST['consent'] === '1';
    $honeypot = post_string('website');
    $lastSent = (int) ($_SESSION['contact_last_sent'] ?? 0);

    if ($honeypot !== '') {
        $errors[] = 'The form could not be submitted. Please try again.';
    }
    if ($lastSent > 0 && (time() - $lastSent) < 90) {
        $errors[] = 'Please wait a moment before sending another message.';
    }
    if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
        $errors[] = 'Enter your name (2–100 characters).';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 190) {
        $errors[] = 'Enter a valid email address so we can follow up.';
    }
    if (mb_strlen($form['subject']) < 3 || mb_strlen($form['subject']) > 150) {
        $errors[] = 'Add a short subject (3–150 characters).';
    }
    if (mb_strlen($form['message']) < 10 || mb_strlen($form['message']) > 4000) {
        $errors[] = 'Write a message between 10 and 4,000 characters.';
    }
    if (!$consent) {
        $errors[] = 'Please confirm that you understand how this message is handled.';
    }

    if ($errors === []) {
        $stmt = db()->prepare('INSERT INTO contact_messages
            (name, email, subject, message, status, ip_hash, user_agent, created_at)
            VALUES (:name, :email, :subject, :message, :status, :ip_hash, :user_agent, :created_at)');
        $stmt->execute([
            'name'       => $form['name'],
            'email'      => $form['email'],
            'subject'    => $form['subject'],
            'message'    => $form['message'],
            'status'     => 'new',
            'ip_hash'    => client_ip_hash(),
            'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'created_at' => utc_now(),
        ]);

        $_SESSION['contact_last_sent'] = time();
        flash('success', 'Thank you — your message was received. The administrator will review it.');
        redirect('contact.php?sent=1');
    }

    http_response_code(422);
}

$pageTitle = 'Contact and corrections';
$pageDescription = 'Report a broken stream, suggest a channel correction, or ask a privacy question. Messages go directly to the japjapTV administrator.';
$bodyClass = 'page-contact';
$activeNav = '';
$canonical = APP_ORIGIN . url('contact.php');

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Contact</span>
        </nav>
        <p class="eyebrow">Contact &amp; corrections</p>
        <h1>Something broken? Tell us.</h1>
        <p class="lead">Broadcasters change their streams without warning. If a channel is mislabelled, a player is broken, or an official link has moved, send the details. Every message is stored for the site administrator.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container contact-layout">
        <div class="content-card">
            <?php if ($sent && $errors === []): ?>
                <div class="form-errors" style="border-color:rgba(57,217,138,.28);background:rgba(16,52,36,.45);color:#b8f5d5;">
                    <strong>Your message was received.</strong> The administrator will review it. Please include a link if you are reporting a specific channel.
                </div>
            <?php endif; ?>

            <h2>Send a message</h2>
            <p class="text-soft" style="font-size:12px;">Fields marked with an asterisk are required. We use your email only to follow up on this report.</p>

            <?php if ($errors !== []): ?>
                <div class="form-errors" role="alert">
                    <strong>Please fix the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($channelContext !== null): ?>
                <div class="policy-note" style="margin-bottom:18px;">
                    <i class="fa-solid fa-tv" aria-hidden="true"></i>
                    <span>Reporting about <strong><?= e($channelContext['channel_name']) ?></strong>. <a href="<?= e(url('watch.php?id=' . rawurlencode((string) $channelContext['slug']))) ?>">Open the channel page</a>.</span>
                </div>
            <?php endif; ?>

            <form action="<?= e(url('contact.php')) ?><?= $channelContext !== null ? '?channel=' . rawurlencode((string) $channelContext['slug']) : '' ?>" method="post" novalidate>
                <?= csrf_field() ?>
                <div class="honeypot" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="form-grid">
                    <div class="field<?= in_array(true, array_map(static fn (string $e): bool => str_contains($e, 'your name'), $errors), true) ? ' has-error' : '' ?>">
                        <label for="contactName">Your name *</label>
                        <input id="contactName" type="text" name="name" value="<?= e($form['name']) ?>" maxlength="100" required autocomplete="name">
                    </div>
                    <div class="field">
                        <label for="contactEmail">Email address *</label>
                        <input id="contactEmail" type="email" name="email" value="<?= e($form['email']) ?>" maxlength="190" required autocomplete="email">
                    </div>
                    <div class="field field-full">
                        <label for="contactSubject">Subject *</label>
                        <input id="contactSubject" type="text" name="subject" value="<?= e($form['subject']) ?>" maxlength="150" required>
                    </div>
                    <div class="field field-full">
                        <label for="contactMessage">Message *</label>
                        <textarea id="contactMessage" name="message" maxlength="4000" required placeholder="Include the channel name, what you expected, and what happened. Screenshots cannot be uploaded here, but you can describe them."><?= e($form['message']) ?></textarea>
                        <span class="hint">10–4,000 characters. Do not include passwords or sensitive personal information.</span>
                    </div>
                </div>

                <div class="field" style="margin-top:16px;">
                    <label class="checkbox-label" style="display:flex;gap:9px;align-items:flex-start;font-weight:500;">
                        <input type="checkbox" name="consent" value="1"<?= $consent ? ' checked' : '' ?> required style="width:auto;min-height:auto;margin-top:3px;">
                        <span>I understand this message and my email address are stored for the site administrator to review, as described in the <a href="<?= e(url('privacy.php')) ?>" style="color:#a7d4ff;">privacy policy</a>. *</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit"><i class="fa-regular fa-paper-plane" aria-hidden="true"></i> Send message</button>
                    <a class="btn btn-ghost" href="<?= e(url('channels.php')) ?>">Back to channels</a>
                </div>
                <p class="form-note">We never ask for your password. Messages are not used for marketing. Repeated or automated submissions are rate-limited.</p>
            </form>
        </div>

        <aside class="contact-info">
            <div class="contact-info-card">
                <i class="fa-solid fa-satellite-dish" aria-hidden="true"></i>
                <h3>Broken stream</h3>
                <p>Tell us the channel name and whether the player stayed blank or showed an offline message. Include the approximate time you tried.</p>
            </div>
            <div class="contact-info-card">
                <i class="fa-solid fa-globe" aria-hidden="true"></i>
                <h3>Moved official link</h3>
                <p>If a broadcaster changed its website or streaming provider, send the new official destination so the listing can be updated.</p>
            </div>
            <div class="contact-info-card">
                <i class="fa-solid fa-right-left" aria-hidden="true"></i>
                <h3>Access correction</h3>
                <p>If a channel is labelled free when it requires a subscription — or the other way around — tell us and include where the restriction is shown.</p>
            </div>
            <div class="contact-info-card">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <h3>Privacy question</h3>
                <p>Use this form and select a privacy-related subject. The policy explains what is stored, how long messages are kept, and how to ask for removal.</p>
            </div>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

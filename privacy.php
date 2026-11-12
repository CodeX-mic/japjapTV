<?php
/**
 * Privacy policy.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Privacy policy';
$pageDescription = 'What japjapTV stores, why it stores it, how long it is kept, and the choices available to visitors and people who contact the site.';
$bodyClass = 'page-privacy';
$activeNav = '';
$canonical = APP_ORIGIN . url('privacy.php');

require __DIR__ . '/includes/header.php';
?>
<section class="content-shell">
    <div class="container content-narrow">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Privacy</span>
        </nav>

        <p class="eyebrow">Privacy policy</p>
        <h1>Your data, kept to a minimum.</h1>
        <p class="lead">japjapTV has no visitor accounts, no advertising network, and no behavioural analytics. This page explains the small amount of data the site does handle.</p>
        <div class="legal-meta">
            <span class="meta-chip"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Effective 12 November 2026</span>
            <span class="meta-chip"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Philippines</span>
        </div>

        <div class="content-card prose">
            <h2>1. Who runs this site</h2>
            <p>japjapTV is a small, independently operated channel directory. The site administrator is the data controller for information submitted through the contact form. Questions about this policy can be sent through the <a href="<?= e(url('contact.php')) ?>">contact page</a>.</p>

            <h2>2. What the site stores</h2>
            <h3>Essential session cookie</h3>
            <p>Visitors receive a single first-party session cookie (named <code>japjaptvsess</code>). It keeps track of a CSRF token, flash messages, a temporary “already viewed” marker so a channel view is not counted repeatedly, and—if you sign in as an administrator—the admin session. The cookie is HTTP-only, SameSite=Lax, and marked Secure when the site is served over HTTPS. It is not used for advertising or cross-site tracking.</p>

            <h3>Contact messages</h3>
            <p>If you use the contact form, the site stores the name, email address, subject, message, a salted hash of your IP address, your browser’s user-agent string, and the time of submission. Your email is used only to follow up on the report. It is not added to a mailing list and is not sold or shared.</p>

            <h3>Administrator data</h3>
            <p>Administrator accounts store a username, a password hash produced with PHP’s <code>password_hash()</code>, a display name, an optional email address, login timestamps, and the IP address of the most recent login. Failed login attempts are recorded with a salted IP hash and username to slow down abuse.</p>

            <h3>Channel view counts</h3>
            <p>Opening a channel increments a simple counter. The site uses a session marker to avoid counting the same visitor repeatedly within 30 minutes. No visitor identifier is stored in the database for this feature.</p>

            <h3>Server logs</h3>
            <p>The web server (Apache) and PHP may write access and error logs that include IP addresses, request paths, timestamps, and error details. These logs are used for security and troubleshooting. They are not combined with the contact form or used to build visitor profiles.</p>

            <h2>3. What the site does not do</h2>
            <ul>
                <li>No visitor registration, login, or public profile.</li>
                <li>No Google Analytics, advertising pixels, or social tracking SDKs.</li>
                <li>No selling, renting, or sharing personal data with data brokers.</li>
                <li>No cross-site behavioural advertising.</li>
            </ul>

            <h2>4. Third-party players and links</h2>
            <p>When you press play on a YouTube-hosted channel, YouTube’s embedded player loads inside the page. Google/YouTube may receive technical information such as your IP address, browser details, and the page you came from, and may set its own cookies once you interact with the player. japjapTV does not control that processing. Review <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Google’s privacy policy</a> for details.</p>
            <p>External destination links (broadcaster sites, NBA.com, Cignal Play, and similar) take you to services with their own privacy policies. japjapTV does not receive information about what you do after you leave.</p>
            <p>NBA schedule data is fetched server-side from ESPN’s public scoreboard API. Your browser does not contact ESPN for the schedule display. The site does load fonts and icons from Google Fonts and Cloudflare’s cdnjs; those providers receive the request metadata normally sent by any browser fetching a file.</p>

            <h2>5. Why the site processes this data</h2>
            <ul>
                <li><strong>Site operation:</strong> serving pages, remembering form tokens, and preventing abuse.</li>
                <li><strong>Security:</strong> rate-limiting login attempts and investigating errors or attacks.</li>
                <li><strong>Answering you:</strong> following up on a correction or privacy request you initiated.</li>
                <li><strong>Aggregate interest:</strong> a simple channel view count, with no individual profile.</li>
            </ul>

            <h2>6. Retention</h2>
            <p>Contact messages are kept until the report is resolved and then for a reasonable period for reference, normally no longer than 24 months. Server logs are rotated according to the hosting configuration, typically within 30 days. Failed login records are pruned periodically. Channel view counts are aggregate and retained indefinitely.</p>

            <h2>7. Your choices and rights</h2>
            <p>Under the Philippine Data Privacy Act of 2012 (Republic Act No. 10173), you may ask for access to personal data about you, correction of inaccurate data, erasure in appropriate circumstances, or information about how your data is processed. Use the <a href="<?= e(url('contact.php')) ?>">contact form</a> and describe your request. The administrator may need to verify that you are the person who submitted the original message before acting.</p>
            <p>You can also clear or block the session cookie in your browser. Blocking it may break form submissions, because the CSRF check depends on the session. The site has no “Do Not Track” behaviour to configure because it does not run behavioural analytics.</p>

            <h2>8. Security</h2>
            <p>The application uses prepared database statements, output escaping, CSRF tokens, password hashing, server-side upload validation, and session hardening. No online service is completely secure; if you discover a vulnerability, report it privately through the contact form rather than publicly.</p>

            <h2>9. Children</h2>
            <p>The site is a general-audience television directory and is not directed at children under 13. The contact form should not be used by children to submit personal information. Parents or guardians can contact the administrator to request removal.</p>

            <h2>10. Changes</h2>
            <p>This policy may be updated when the site’s features change. The effective date at the top will change, and material updates will be described on this page. Continued use after an update means you accept the revised policy.</p>

            <hr>
            <p><strong>Questions?</strong> <a href="<?= e(url('contact.php')) ?>">Contact the administrator</a>. See also the <a href="<?= e(url('terms.php')) ?>">terms of use</a>.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

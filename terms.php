<?php
/**
 * Terms of use.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Terms of use';
$pageDescription = 'The terms that apply to using japjapTV, including source limitations, external links, intellectual property, and acceptable use.';
$bodyClass = 'page-terms';
$activeNav = '';
$canonical = APP_ORIGIN . url('terms.php');

require __DIR__ . '/includes/header.php';
?>
<section class="content-shell">
    <div class="container content-narrow">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= e(url('index.php')) ?>">Home</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Terms</span>
        </nav>

        <p class="eyebrow">Terms of use</p>
        <h1>The rules of the road.</h1>
        <p class="lead">japjapTV is an informational directory that links to official broadcaster sources. By using the site, you agree to these terms and to the way the catalogue presents availability.</p>
        <div class="legal-meta">
            <span class="meta-chip"><i class="fa-solid fa-calendar" aria-hidden="true"></i> Effective 12 November 2026</span>
            <span class="meta-chip"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Official sources only</span>
        </div>

        <div class="content-card prose">
            <h2>1. What japjapTV is</h2>
            <p>japjapTV is a curated directory of television channels and official viewing destinations. It is not a broadcaster, cable operator, IPTV provider, or streaming service. It does not own or operate the streams it embeds. Playable content is delivered by the broadcaster or its platform — currently official YouTube embeds and, where configured by the administrator, authorized HLS or iframe sources.</p>

            <h2>2. No affiliation</h2>
            <p>japjapTV is not affiliated with, endorsed by, or sponsored by any broadcaster, television network, league, team, or streaming provider mentioned on the site. Channel names, programme names, league marks, and team marks are the property of their respective owners and are used for identification and reference only. The logo marks shown in the catalogue are original text placeholders, not official broadcaster artwork.</p>

            <h2>3. Availability is not guaranteed</h2>
            <p>A “Live now” label reflects the result of an automated check at a particular moment. Broadcasters can end a stream, change embedding permissions, impose a regional restriction, or go offline without notice. A “Subscription” label means the official provider may require payment, a TV package, or an account. japjapTV does not promise that any channel is free, uninterrupted, or available in your territory.</p>

            <h2>4. External destinations</h2>
            <p>Where embedding is not authorized or not available, the site links to the broadcaster’s own website or streaming service. Those sites have their own terms, privacy policies, prices, and regional restrictions. You are responsible for complying with them. japjapTV does not control and is not responsible for third-party content, availability, or billing.</p>

            <h2>5. Acceptable use</h2>
            <p>You agree not to:</p>
            <ul>
                <li>Use the site to access content in violation of a broadcaster’s terms or applicable law.</li>
                <li>Attempt to bypass geographic restrictions, paywalls, DRM, or access controls.</li>
                <li>Scrape, overload, probe, or interfere with the site’s servers or security features.</li>
                <li>Submit false reports, spam, malware, or unlawful material through the contact form.</li>
                <li>Misrepresent an affiliation with japjapTV or use its name to promote an unauthorized service.</li>
            </ul>

            <h2>6. Intellectual property</h2>
            <p>The site’s original design, code, and written content are protected by applicable copyright law. You may view the site and share links for personal, non-commercial use. Do not republish the catalogue in bulk or present it as your own service without permission. Broadcaster content remains the property of the broadcaster or its licensors.</p>

            <h2>7. No warranty</h2>
            <p>The site is provided “as is” and “as available,” without warranties of any kind, express or implied, including merchantability, fitness for a particular purpose, and non-infringement. japjapTV does not warrant that the catalogue is complete, accurate, current, or free of errors, or that any stream will work on your device or in your region.</p>

            <h2>8. Limitation of liability</h2>
            <p>To the fullest extent permitted by law, japjapTV and its operator are not liable for indirect, incidental, special, consequential, or punitive damages arising from your use of the site or from any third-party service you reach through it. This includes loss of data, device damage, subscription charges, or inability to view a programme. Nothing in these terms limits rights that cannot be limited under applicable law.</p>

            <h2>9. Reporting problems</h2>
            <p>If a source is broken, a channel is mislabelled, or you believe the site links to unauthorized content, use the <a href="<?= e(url('contact.php')) ?>">contact page</a>. Include the channel and page URL. Reports are reviewed by the administrator, and a listing may be corrected, demoted, or removed.</p>

            <h2>10. Changes and governing law</h2>
            <p>The catalogue and these terms may change as the site develops. The effective date at the top will be updated. These terms are governed by the laws of the Republic of the Philippines, without regard to conflict-of-law principles. Any dispute should first be raised through the contact form so it can be resolved informally.</p>

            <hr>
            <p><strong>By continuing to use japjapTV, you accept these terms.</strong> Read the <a href="<?= e(url('privacy.php')) ?>">privacy policy</a> and the <a href="<?= e(url('about.php')) ?>">source policy</a>.</p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

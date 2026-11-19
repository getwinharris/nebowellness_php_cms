<?php
$credentialFields = \App\Services\SecretService::FORM_CREDENTIALS;
$configuredCredentials = array_filter($credentialFields, static fn(string $key): bool =>
    !empty($secrets[$key]) || ($key === 'ai_api_key' && (!empty($secrets['agent_api_key']) || !empty($secrets['support_bot_google_api_key']))),
    ARRAY_FILTER_USE_KEY
);
?>
<div class="admin-card" style="border-left:4px solid var(--color-gold); margin-bottom:var(--space-lg);">
    <h2 style="font-size:1rem; margin:0 0 var(--space-sm);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> API Setup</h2>
    <p style="margin:0; color:var(--color-text-muted); font-size:0.9rem;">These settings are for the website owner only. Visitors use program, campaign, journal, product, account, and enquiry pages. All site secrets for payments, email, analytics, and AI are stored encrypted in the project secret store and managed here &mdash; they are never kept in <code>.env</code>.</p>
</div>
<div class="admin-card">
    <form method="post" action="/admin/integrations/save" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <div style="margin:0 0 var(--space-lg); padding:var(--space-sm) var(--space-md); border-radius:var(--radius-md); border:1px solid var(--color-border); background:var(--color-bg-alt); font-size:0.85rem; color:var(--color-text-muted);">
            <strong style="color:var(--color-ink);">Database access</strong><br>
            The <code>/remotedb</code> endpoint authenticates with your MySQL password from <code>.env</code>.
            There is nothing to configure here, and no separate token to keep in sync.
        </div>

        <h2 style="font-size:1rem; margin:0 0 var(--space-sm);">Razorpay Payments</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Add test and live keys from Razorpay Dashboard, then choose which mode checkout and wallet top-ups should use.
            <a href="https://razorpay.com/docs/payments/dashboard/account-settings/api-keys/" target="_blank" rel="noopener">Razorpay API key guide</a>
        </p>
        <div class="admin-form__row">
            <label>Razorpay Mode
                <select name="razorpay_mode">
                    <option value="test" <?= (($secrets['razorpay_mode']??'test') === 'test') ? 'selected' : '' ?>>Test mode</option>
                    <option value="live" <?= (($secrets['razorpay_mode']??'test') === 'live') ? 'selected' : '' ?>>Live mode</option>
                </select>
            </label>
            <label>Active Key ID
                <input value="<?= e($secrets['razorpay_key_id']??'') ?>" readonly placeholder="Selected mode key id">
            </label>
        </div>
        <div class="admin-form__row">
            <label>Test Key ID<input name="razorpay_test_key_id" value="<?= e($secrets['razorpay_test_key_id']??'') ?>" placeholder="rzp_test_xxxx"></label>
            <label>Test Key Secret<input type="password" name="razorpay_test_key_secret" placeholder="<?= isset($configuredCredentials['razorpay_test_key_secret']) ? 'Saved · leave blank to keep' : 'Paste test key secret' ?>" autocomplete="new-password"></label>
        </div>
        <div class="admin-form__row">
            <label>Live Key ID<input name="razorpay_live_key_id" value="<?= e($secrets['razorpay_live_key_id']??'') ?>" placeholder="rzp_live_xxxx"></label>
            <label>Live Key Secret<input type="password" name="razorpay_live_key_secret" placeholder="<?= isset($configuredCredentials['razorpay_live_key_secret']) ? 'Saved · leave blank to keep' : 'Paste live key secret' ?>" autocomplete="new-password"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">Test mode uses test keys for trial payments. Switch to live mode only when production keys are saved and real customer payments are ready.</p>

        <details class="therapy-request"><summary>Therapy payment notifications</summary>
            <p>In the Razorpay Dashboard, subscribe to <code>payment.captured</code>. Use the matching endpoint and webhook signing secret for each mode. Leave the secret blank to keep the saved value.</p>
            <?php foreach (['test','live'] as $mode): ?>
                <label><?= e(ucfirst($mode)) ?> webhook · <code>/payment/therapy/webhook/<?= e($mode) ?></code>
                    <input type="password" name="therapy_webhook_<?= e($mode) ?>" autocomplete="new-password" placeholder="<?= empty($secrets['therapy_webhook_' . $mode]) ? 'Enter signing secret' : 'Configured' ?>">
                </label>
            <?php endforeach; ?>
        </details>
        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">Stripe Payments</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Optional Stripe payment gateway. Enter your Stripe secret key to enable Stripe as an alternative payment method.
            <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener">Stripe Dashboard</a>
        </p>
        <div class="admin-form__row">
            <label>Stripe Secret Key<input type="password" name="stripe_secret_key" placeholder="<?= isset($configuredCredentials['stripe_secret_key']) ? 'Saved · leave blank to keep' : 'sk_live_xxxx or sk_test_xxxx' ?>" autocomplete="new-password"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">Stripe is available as an alternative to Razorpay. The secret key is stored encrypted and never kept in <code>.env</code>.</p>

        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">Google Login</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Customers can sign in or link a verified Google identity during registration or password confirmation. Use this exact OAuth callback URL in Google Cloud: <code><?= e($googleCallbackUri) ?></code>.
            <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Credentials</a>
        </p>
        <div class="admin-form__row">
            <label>Google Client ID<input name="google_client_id" value="<?= e($secrets['google_client_id']??'') ?>" placeholder="xxxx.apps.googleusercontent.com"></label>
            <label>Google Client Secret<input type="password" name="google_client_secret" placeholder="<?= isset($configuredCredentials['google_client_secret']) ? 'Saved · leave blank to keep' : 'GOCSPX-xxxx' ?>" autocomplete="new-password"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">Only sign-in permissions are used. Calendar and Google Meet are not used for this platform.</p>

        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">AI Model (Agent + Support Bot)</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Configure an OpenAI-compatible API for the site AI agent and the support bot.
            Works with OpenAI, OpenRouter, or any OpenAI-compatible provider.
        </p>
        <div class="admin-form__row">
            <label>Agent Name<input name="agent_name" value="<?= e($secrets['agent_name']??'Agent') ?>" placeholder="Agent"></label>
            <label>API Endpoint (base URL)<input name="api_endpoint" value="<?= e($secrets['api_endpoint']??'') ?>" placeholder="https://api.openai.com/v1"></label>
            <label>API Key<input type="password" name="ai_api_key" placeholder="<?= isset($configuredCredentials['ai_api_key']) ? 'Saved · leave blank to keep' : 'sk-... or AIza...' ?>" autocomplete="new-password"></label>
        </div>
        <div class="admin-form__row">
            <label>Model<input name="agent_model" value="<?= e($secrets['agent_model']??$secrets['support_bot_model']??'gemma-4-31b-it') ?>" placeholder="gemma-4-31b-it"></label>
        </div>
        <input type="hidden" name="support_bot_purge_policy" value="always_purge">
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.85rem;">
            Current model: <strong><?= e($secrets['agent_model'] ?? $secrets['support_bot_model'] ?? 'gemma-4-31b-it') ?></strong>.
            OpenRouter: endpoint <code>https://openrouter.ai/api/v1</code>.
            Google: endpoint <code>https://generativelanguage.googleapis.com/v1beta/models/</code>.
        </p>

        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">WebRTC TURN Server</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Optional TURN server for WebRTC call connectivity when peer-to-peer STUN fails (firewall/NAT). Run a coturn server and enter its credentials here.
        </p>
        <div class="admin-form__row">
            <label>TURN Server URL<input name="turn_server_url" value="<?= e($secrets['turn_server_url']??'') ?>" placeholder="turn:example.com:3478"></label>
            <label>TURN Username<input name="turn_username" value="<?= e($secrets['turn_username']??'') ?>" placeholder="turnuser"></label>
        </div>
        <div class="admin-form__row">
            <label>TURN Credential<input type="password" name="turn_credential" placeholder="<?= isset($configuredCredentials['turn_credential']) ? 'Saved · leave blank to keep' : 'TURN shared secret' ?>" autocomplete="new-password"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">When configured, the TURN server is added to ICE servers for all WebRTC calls. Leave blank to use STUN only.</p>

        <h2 id="google-reporting">Google reporting</h2>
        <p>Share the selected GA4 property with a dedicated service account as a Viewer. Add the same account as a restricted user of the Search Console property. Enable both reporting APIs in its Google Cloud project. <a href="/admin/search-performance">View reports</a></p>
        <div class="admin-form__row">
            <label>GA4 reporting property ID<input name="google_reporting_property" inputmode="numeric" pattern="[0-9]+" value="<?= e($secrets['google_reporting_property'] ?? '') ?>" placeholder="123456789"></label>
            <label>Search Console property<input name="google_search_property" value="<?= e($secrets['google_search_property'] ?? '') ?>" placeholder="sc-domain:example.com"></label>
        </div>
        <label>Service account JSON key <span><?= empty($secrets['google_reporting_key']) ? 'No key saved' : 'Key saved. Leave blank to keep it.' ?></span>
            <textarea name="google_reporting_key" rows="3" autocomplete="off" spellcheck="false" placeholder="Paste a new service account JSON key"></textarea>
        </label>
        <?php if (!empty($secrets['google_reporting_key'])): ?>
            <label><input type="checkbox" name="disconnect_google_reports" value="1"> Disconnect reports and remove the stored key</label>
        <?php endif; ?>
        <p>Use the exact property shown in Search Console. Domain properties use DNS verification. URL-prefix properties can use the HTML verification token on the homepage. Submit <a href="/sitemap.xml">the XML sitemap</a> in Search Console.</p>
        <label>HTML verification token<input name="google_site_verification" value="<?= e($secrets['google_site_verification'] ?? '') ?>" placeholder="Token from the verification meta tag"></label>

        <h2>Optional audience measurement</h2>
        <p>Visitors can opt into measurement of signed-out homepage and About page visits in Privacy. For general campaign links, Analytics accepts a single <code>utm_source</code> of google, facebook, or instagram. Accounts, therapy details, support conversations and forms are excluded. Enable an advertising destination only after checking the clinic’s eligibility with its provider.</p>
        <div class="admin-form__row">
            <label>GA4 measurement ID<input name="google_analytics_id" value="<?= e($secrets['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX"></label>
            <label>Google Ads ID<input name="google_ads_id" value="<?= e($secrets['google_ads_id'] ?? '') ?>" placeholder="AW-XXXXXXXXX"></label>
            <label>Meta Pixel ID<input name="meta_pixel_id" value="<?= e($secrets['meta_pixel_id'] ?? '') ?>" placeholder="1234567890"></label>
        </div>
        <label>Advertising destinations
            <select name="clinic_ads_enabled">
                <option value="0">Off</option>
                <option value="1" <?= ($secrets['clinic_ads_enabled'] ?? '') === '1' ? 'selected' : '' ?>>Available after visitor consent</option>
            </select>
        </label>

        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">SEO Defaults</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Configure default SEO metadata used across all pages. These can be overridden per page automatically by the SEO service.
        </p>
        <div class="admin-form__row">
            <label>Site Name<input name="seo_site_name" value="<?= e($secrets['seo_site_name']??'') ?>" placeholder="Nebo Lifestyle Clinic"></label>
            <label>Twitter Handle<input name="seo_twitter_handle" value="<?= e($secrets['seo_twitter_handle']??'') ?>" placeholder="@sps"></label>
        </div>
        <div class="admin-form__row">
            <label>Default OG Image URL<input name="seo_default_og_image" value="<?= e($secrets['seo_default_og_image']??'') ?>" placeholder="https://example.com/og-image.jpg"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">Each public page automatically gets a unique title, description, and OG tags. Site Name is used in JSON-LD structured data and page titles.</p>

        <h2 style="font-size:1rem; margin:var(--space-xl) 0 var(--space-sm);">Outbound Email (SMTP)</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Transactional email is sent through the saved SMTP mailbox. Credentials are stored in the project secret store. SMTP is required; failed delivery remains visible in Email Outbox for diagnosis and retry.
        </p>
        <div class="admin-form__row">
            <label>SMTP Host<input name="smtp_host" value="<?= e($secrets['smtp_host']??'') ?>" placeholder="smtp.hostinger.com"></label>
            <label>SMTP Port<input name="smtp_port" value="<?= e($secrets['smtp_port']??'') ?>" placeholder="465"></label>
        </div>
        <div class="admin-form__row">
            <label>Encryption
                <select name="smtp_encryption">
                    <option value="ssl" <?= (($secrets['smtp_encryption']??'ssl') === 'ssl') ? 'selected' : '' ?>>SSL (465)</option>
                    <option value="tls" <?= (($secrets['smtp_encryption']??'ssl') === 'tls') ? 'selected' : '' ?>>TLS / STARTTLS (587)</option>
                </select>
            </label>
            <label>SMTP Username<input name="smtp_username" value="<?= e($secrets['smtp_username']??'') ?>" placeholder="support@nebowellness.com"></label>
        </div>
        <div class="admin-form__row">
            <label>SMTP Password<input type="password" name="smtp_password" placeholder="<?= isset($configuredCredentials['smtp_password']) ? 'Saved · leave blank to keep' : 'SMTP password' ?>" autocomplete="new-password"></label>
            <label>From Email<input name="mail_from_email" value="<?= e($secrets['mail_from_email']??'') ?>" placeholder="support@nebowellness.com"></label>
        </div>
        <div class="admin-form__row">
            <label>From Name<input name="mail_from_name" value="<?= e($secrets['mail_from_name']??'Nebo Lifestyle Clinic') ?>" placeholder="Nebo Lifestyle Clinic"></label>
            <label>Admin Notification Email<input name="admin_notification_email" value="<?= e($secrets['admin_notification_email']??'') ?>" placeholder="nebolifestyleclinic@gmail.com"></label>
        </div>
        <p style="margin:var(--space-xs) 0 0; color:var(--color-text-muted); font-size:0.8rem;">
            <strong>Hostinger mailbox:</strong> host <code>smtp.hostinger.com</code>,
            port <code>465</code> with SSL (or <code>587</code> with TLS/STARTTLS).
            <strong>SMTP Username</strong> and <strong>From Email</strong> must both be the
            full mailbox address, e.g. <code>support@nebowellness.com</code> — providers
            reject a From Email that does not match the authenticated mailbox.
            <strong>Admin Notification Email</strong> is different: it is where <em>you</em> get told
            about new orders and contact enquiries, so it can be a personal inbox such as a Gmail address.
            Customers are always written to from the From Email, and replies come back there.
            Mail is sent immediately when generated — no cron job is involved.
        </p>

        <div class="admin-card" style="background:var(--color-bg-alt); margin-top:var(--space-xl); padding:var(--space-md);">
            <h3 style="font-size:0.9rem; margin:0 0 var(--space-sm);">Platform Scope</h3>
            <p style="margin:0; color:var(--color-text-muted); font-size:0.85rem;">This site presents Nebo Lifestyle Clinic programs, campaigns, practitioners, wellness guidance, products, and enquiry support. Historical service records remain owner-only in the admin panel.</p>
        </div>
        <?php if ($configuredCredentials): ?>
        <details class="credential-removal">
            <summary>Remove saved credentials</summary>
            <p>Select individual credentials and save to disconnect them. Revoke the original keys at the provider when rotating credentials.</p>
            <?php foreach ($configuredCredentials as $field=>$label): ?>
                <label><input type="checkbox" name="remove_credentials[]" value="<?= e($field) ?>"> <?= e($label) ?></label>
            <?php endforeach; ?>
        </details>
        <?php endif; ?>
        <button class="btn btn-primary" style="margin-top:var(--space-lg);">Save Integrations</button>
    </form>

    <div class="admin-card" style="margin-top:var(--space-xl);">
        <h2 style="font-size:1rem; margin:0 0 var(--space-sm);">Send a Test Email</h2>
        <p style="margin:0 0 var(--space-md); color:var(--color-text-muted); font-size:0.85rem;">
            Save your SMTP settings first, then send a real message through them. The result below
            reports the transport used and the exact server error if delivery fails.
        </p>
        <?php $mailTest = $_SESSION['mail_test_result'] ?? null; unset($_SESSION['mail_test_result']); ?>
        <?php if ($mailTest): ?>
            <div style="margin-bottom:var(--space-md); padding:var(--space-sm) var(--space-md); border-radius:var(--radius-sm); border:1px solid <?= $mailTest['ok'] ? 'var(--color-success, #15803d)' : 'var(--color-error, #b91c1c)' ?>; background:<?= $mailTest['ok'] ? 'rgba(21,128,61,0.08)' : 'rgba(185,28,28,0.08)' ?>;">
                <strong><?= $mailTest['ok'] ? 'Delivered' : 'Failed' ?></strong>
                <?php if (!empty($mailTest['transport'])): ?>
                    <span style="color:var(--color-text-muted);">via <?= e($mailTest['transport']) ?></span>
                <?php endif; ?>
                <div style="margin-top:var(--space-2xs); font-size:0.85rem; white-space:pre-wrap;"><?= e($mailTest['message']) ?></div>
            </div>
        <?php endif; ?>
        <form class="admin-form" method="post" action="/admin/integrations/test-email">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <div class="admin-form__row">
                <label>Send To
                    <input type="email" name="test_email" required
                           value="<?= e($secrets['admin_notification_email'] ?? $secrets['mail_from_email'] ?? '') ?>"
                           placeholder="you@your-domain.com">
                </label>
            </div>
            <button class="btn btn-secondary" style="margin-top:var(--space-md);">Send Test Email</button>
        </form>
    </div>
</div>

<?php
// Both agents fall back to a canned reply when the provider rejects a call, which is
// right for a customer but left the owner with no way to tell "the model is off" from
// "the model is broken". This asks the provider one real question and prints exactly
// what came back.
$aiTest = $_SESSION['ai_test_result'] ?? null;
unset($_SESSION['ai_test_result']);
?>
<div class="admin-card" style="margin-top:var(--space-lg);">
    <h2 style="font-size:1.1rem; margin:0 0 var(--space-sm);">Test the AI connection</h2>
    <p style="color:var(--color-text-muted); margin:0 0 var(--space-md); font-size:0.9rem;">
        Sends one short question to the configured model. Both the support bot and this admin
        assistant use these settings, so if this fails they are both falling back to fixed replies.
    </p>
    <?php if ($aiTest): ?>
        <div style="margin-bottom:var(--space-md); padding:var(--space-sm) var(--space-md); border-radius:var(--radius-sm); border:1px solid <?= $aiTest['ok'] ? 'var(--color-success, #15803d)' : 'var(--color-error, #b91c1c)' ?>; background:<?= $aiTest['ok'] ? 'rgba(21,128,61,0.08)' : 'rgba(185,28,28,0.08)' ?>;">
            <strong><?= $aiTest['ok'] ? 'The model answered' : 'The model did not answer' ?></strong>
            <div style="margin-top:var(--space-2xs); font-size:0.82rem; color:var(--color-text-muted);">
                <?= e((string)($aiTest['model'] ?? '')) ?> · <?= e((string)($aiTest['endpoint'] ?? '')) ?>
            </div>
            <div style="margin-top:var(--space-2xs); font-size:0.85rem; white-space:pre-wrap;"><?= e((string)$aiTest['message']) ?></div>
        </div>
    <?php endif; ?>
    <form class="admin-form" method="post" action="/admin/integrations/test-ai">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button class="btn btn-secondary">Test AI Connection</button>
    </form>
</div>

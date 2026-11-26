<?php
$__settings=(new \App\Services\SettingsService())->public();
$__profile=(new \App\Services\SiteProfileService())->published();
$__logo=$__profile['logo_url'];
$__favicon=$__profile['favicon_url'];
$__faviconMime=\App\Services\SiteProfileService::faviconMime($__favicon);
?>
<!doctype html>
<html lang="en" class="public-site">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="format-detection" content="telephone=no">
<title><?= e($pageTitle ?? $__profile['name']) ?></title>
<meta name="description" content="<?= e($metaDescription ?? $__profile['description']) ?>">
<meta name="robots" content="<?= e($metaRobots ?? 'index, follow') ?>">
<?php $__seoKeywords = $seo['keywords'] ?? ''; if ($__seoKeywords !== ''): ?><meta name="keywords" content="<?= e($__seoKeywords) ?>"><?php endif; ?>
<?php
$__modules = (new \App\Services\SettingsService())->modules();
$__clinicChannels = (new \App\Services\ClinicContactService())->channels();
?>
<?php if ($__favicon!==''): ?><link rel="icon" type="<?= e($__faviconMime) ?>" href="<?= e($__favicon) ?>"><?php endif; ?>
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="<?= e($__profile['palette']['palette_primary']) ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?= e($__profile['name']) ?>">
<?php if ($__favicon!==''): ?><link rel="apple-touch-icon" href="<?= e($__favicon) ?>"><?php endif; ?>
<link rel="service-desc" type="application/json" href="/openapi.json">
<link rel="canonical" href="<?= e($seo['canonical'] ?? \App\Services\SeoService::publicUrl(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<meta property="og:type" content="<?= e($seo['og_type'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($seo['og_site_name'] ?? $__profile['name']) ?>">
<meta property="og:title" content="<?= e($seo['og_title'] ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($seo['og_description'] ?? $metaDescription) ?>">
<meta property="og:url" content="<?= e($seo['og_url'] ?? \App\Services\SeoService::publicUrl(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<?php $__shareImage=$seo['og_image']??($__profile['mode']==='reference'?\App\Services\SeoService::publicUrl('/assets/images/nebo-clinic-hero.webp'):''); if ($__shareImage!==''): ?><meta property="og:image" content="<?= e($__shareImage) ?>"><?php endif; ?>
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['twitter_title'] ?? $seo['og_title'] ?? $pageTitle) ?>">
<meta name="twitter:description" content="<?= e($seo['twitter_description'] ?? $seo['og_description'] ?? $metaDescription) ?>">
<?php $__twitterImage=$seo['twitter_image']??$__shareImage; if ($__twitterImage!==''): ?><meta name="twitter:image" content="<?= e($__twitterImage) ?>"><?php endif; ?>
<link rel="stylesheet" href="/assets/css/band.css?v=<?= filemtime(__DIR__ . '/../../assets/css/band.css') ?>">
<script src="/assets/navigation-drawer.js?v=<?= filemtime(__DIR__ . '/../../assets/navigation-drawer.js') ?>" defer></script>
<?php if (basename($viewFile ?? '') === 'home.php'): ?>
<script src="/assets/editorial-rails.js?v=<?= filemtime(__DIR__ . '/../../assets/editorial-rails.js') ?>" defer></script>
<?php endif; ?>
<script src="/assets/editorial-stack.js?v=<?= filemtime(__DIR__ . '/../../assets/editorial-stack.js') ?>" defer></script>
<?php if (in_array(basename($viewFile ?? ''), ['home.php', 'campaigns.php', 'campaign.php'], true)): ?>
<script src="/assets/therapy-media.js?v=<?= filemtime(__DIR__ . '/../../assets/therapy-media.js') ?>" defer></script>
<?php endif; ?>
<script src="/assets/mobile-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/mobile-nav.js') ?>" defer></script>
<script src="/assets/site-motion.js?v=<?= filemtime(app_path('assets/site-motion.js')) ?>" defer></script>
<script src="/assets/site-feedback.js?v=<?= filemtime(__DIR__ . '/../../assets/site-feedback.js') ?>" defer></script>
<script src="/assets/public-shell.js?v=<?= filemtime(app_path('assets/public-shell.js')) ?>" defer></script>
<?php if (in_array(basename($viewFile ?? ''), ['shop.php', 'product.php'], true)): ?>
<script src="/assets/product-cart.js?v=<?= filemtime(app_path('assets/product-cart.js')) ?>" defer></script>
<?php endif; ?>
<script src="/assets/header-contact.js?v=<?= filemtime(__DIR__ . '/../../assets/header-contact.js') ?>" defer></script>
<script src="/assets/agent-status.js?v=<?= filemtime(__DIR__ . '/../../assets/agent-status.js') ?>" defer></script>
<script src="/assets/agent-conversation.js?v=<?= filemtime(__DIR__ . '/../../assets/agent-conversation.js') ?>" defer></script>
<script src="/assets/a2ui-readonly.js?v=<?= filemtime(app_path('assets/a2ui-readonly.js')) ?>" defer></script>
<script src="/assets/support-chat.js?v=<?= filemtime(__DIR__ . '/../../assets/support-chat.js') ?>" defer></script>
<style>
<?php
$__palette = $__profile['palette'];
$__palette_semantic = [
    '--color-primary' => ['set' => $__palette['palette_primary'], 'alias' => '--color-maroon'],
    '--color-secondary' => ['set' => $__palette['palette_secondary'], 'alias' => '--color-gold'],
    '--color-canvas' => ['set' => $__palette['palette_canvas'], 'alias' => '--color-bg'],
    '--color-text-primary' => ['set' => $__palette['palette_text'], 'alias' => '--color-ink'],
    '--color-link' => ['set' => $__palette['palette_link'], 'alias' => ''],
];
$__palette_css = ':root{';
foreach ($__palette_semantic as $__n => $__c) {
    $__v = $__c['set'];
    $__palette_css .= $__n . ':' . e($__v) . ';';
    if ($__c['alias'] !== '') $__palette_css .= $__c['alias'] . ':' . e($__v) . ';';
}
$__palette_css .= '}';
echo $__palette_css;
?>
</style>

<?php
$__tagSettings = $secrets ?? (new \App\Services\SecretService())->all();
$__clinicSeo = new \App\Services\SeoService($__tagSettings);
?>
<?= \App\Services\SeoService::jsonLdScript($__clinicSeo->organizationSchema()) ?>
<?php if (!empty($seo['json_ld'])): ?><?= $seo['json_ld'] ?><?php endif; ?>
<?php
$__measurement = \App\Integrations\GoogleSiteKit\GoogleSiteKitClient::measurement($__tagSettings, $_SERVER['REQUEST_URI'] ?? '/', !empty($_SESSION['user']));
$__verification = \App\Integrations\GoogleSiteKit\GoogleSiteKitClient::verificationToken((string)($__tagSettings['google_site_verification'] ?? ''));
if (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === '/' && preg_match('/^[a-zA-Z0-9_-]{1,255}$/D', $__verification)): ?>
<meta name="google-site-verification" content="<?= e($__verification) ?>">
<?php endif; ?>
<?php if ($__measurement): ?>
<script defer id="clinic-measurement" src="/assets/measurement-consent.js?v=<?= filemtime(app_path('assets/measurement-consent.js')) ?>"
    data-ga="<?= e($__measurement['ga']) ?>" data-ads="<?= e($__measurement['ads']) ?>"
    data-pixel="<?= e($__measurement['pixel']) ?>" data-page="<?= e($__measurement['page']) ?>"></script>
<?php endif; ?>
</head>
<body>
<a class="skip-content" href="#main-content">Skip to content</a>
<?php $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
<?php $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16)); ?>
<header class="site-header" id="site-header">
    <a href="/" class="brand" aria-label="<?= e($__profile['name']) ?> home">
        <?php if ($__logo!==''): ?><img src="<?= e($__logo) ?>" width="160" height="88" alt="<?= e($__profile['name']) ?>"><?php else: ?><span class="brand__text"><?= e($__profile['name']) ?></span><?php endif; ?>
    </a>
<?php try { $__blogCats = (new \App\Services\BlogService())->categories(); } catch (\Throwable $e) { $__blogCats = []; } ?>
    <details class="navigation-fallback" id="navigation-fallback">
        <summary class="menu-toggle" aria-label="Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M3 8h18M3 16h18"/></svg>
            <span>Menu</span>
        </summary>
        <nav id="primary-nav" aria-label="Main navigation">
            <div class="navigation-drawer__primary">
                <p class="nav-section-label">Explore <?= e($__profile['short_name']) ?></p>
                <details class="nav-branch">
                    <summary class="nav-item"><span class="nav-item__label"><?php $careIcon='program'; require __DIR__.'/../partials/care-icon.php'; ?> Programs</span> <span aria-hidden="true">›</span></summary>
                    <div class="nav-branch__links" id="menu-programs">
                        <a class="nav-item" href="/#programs" data-programs-nav>Our Programs</a>
                        <?php
                        $__programNav = [];
                        if ($__modules['campaigns']) {
                            try {
                                $__programNav = \App\Services\CampaignPageService::programAreas($programs ?? \App\Services\CampaignPageService::homepagePrograms((new \App\Services\CampaignPageService())->newestPublished()));
                            } catch (\Throwable $error) { error_log('Programme navigation unavailable: '.$error->getMessage()); }
                        }
                        foreach ($__programNav as $__area => $__label): ?>
                            <a class="nav-item" href="/#programs-<?= e($__area) ?>" data-programs-nav><?= e($__label) ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a class="nav-item" href="/#focus-areas"><span class="nav-item__label"><?php $careIcon='focus'; require __DIR__.'/../partials/care-icon.php'; ?> Our Focus Areas</span></a>
                <?php if ($__modules['campaigns']): ?><a class="nav-item" href="/campaigns"<?= ($currentPath === '/campaigns' || str_starts_with($currentPath, '/campaigns/')) ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='clinician'; require __DIR__.'/../partials/care-icon.php'; ?> Services</span></a><?php endif; ?>
                <a class="nav-item" href="/#movement-and-sound"><span class="nav-item__label"><?php $careIcon='workplace'; require __DIR__.'/../partials/care-icon.php'; ?> Workplace wellness</span></a>
                <a class="nav-item" href="/about"<?= str_starts_with($currentPath, '/about') ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='leaf'; require __DIR__.'/../partials/care-icon.php'; ?> About <?= e($__profile['short_name']) ?></span></a>
                <?php if ($__modules['blog']): ?>
                <?php if ($__blogCats !== []): ?>
                <details class="nav-branch">
                    <summary class="nav-item"><span class="nav-item__label"><?php $careIcon='program'; require __DIR__.'/../partials/care-icon.php'; ?> Blog</span> <span aria-hidden="true">›</span></summary>
                    <div class="nav-branch__links" id="menu-blog">
                        <a class="nav-item" href="/blog"<?= $currentPath === '/blog' ? ' aria-current="page"' : '' ?>>All blog articles</a>
                        <?php foreach ($__blogCats as $__cat): ?>
                        <a class="nav-item" href="/blog/category/<?= e($__cat['slug'] ?? '') ?>"><?= e($__cat['name'] ?? '') ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php else: ?>
                <a class="nav-item" href="/blog"<?= str_starts_with($currentPath, '/blog') ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='program'; require __DIR__.'/../partials/care-icon.php'; ?> Blog</span></a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if ($__modules['shop']): ?>
                <a class="nav-item" href="/shop"<?= str_starts_with($currentPath, '/shop') ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='bag'; require __DIR__.'/../partials/care-icon.php'; ?> Shop</span></a>
                <?php endif; ?>
                <div class="navigation-drawer__utility">
                    <a class="nav-item" href="/contact"<?= $currentPath === '/contact' ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='phone'; require __DIR__.'/../partials/care-icon.php'; ?> Contact</span></a>
                    <?php if(!empty($_SESSION['user'])): ?>
                        <a class="nav-item" href="/account/dashboard"<?= str_starts_with($currentPath, '/account/dashboard') ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='chart'; require __DIR__.'/../partials/care-icon.php'; ?> Dashboard</span></a>
                        <?php if (($_SESSION['user']['role'] ?? '') === 'customer'): ?>
                            <a class="nav-item" href="/account/therapy"<?= $currentPath === '/account/therapy' ? ' aria-current="page"' : '' ?>><span class="nav-item__label"><?php $careIcon='calendar'; require __DIR__.'/../partials/care-icon.php'; ?> Therapy requests</span></a>
                        <?php endif; ?>
                        <a class="nav-item" href="/logout">Logout</a>
                    <?php else: ?>
                        <a class="nav-item" href="/login"><span class="nav-item__label"><?php $careIcon='lock'; require __DIR__.'/../partials/care-icon.php'; ?> Login</span></a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="navigation-drawer__footer">
                <a class="btn btn-primary" href="/contact#contact-form">Book a consultation <span aria-hidden="true">↗</span></a>
            </div>
        </nav>
    </details>
    <dialog class="navigation-drawer" id="navigation-drawer" aria-label="Site menu">
        <div class="navigation-drawer__header">
            <button class="nav-close" type="button" aria-label="Close menu" autofocus>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M5 5l14 14M19 5 5 19"/></svg>
                <span>Close</span>
            </button>
            <a href="/" class="navigation-drawer__brand" aria-label="<?= e($__profile['name']) ?> home">
                <?php if ($__logo!==''): ?><img src="<?= e($__logo) ?>" width="160" height="88" alt=""><?php else: ?><span class="brand__text"><?= e($__profile['name']) ?></span><?php endif; ?>
            </a>
        </div>
        <div class="navigation-drawer__panel"></div>
    </dialog>
    <div class="header-actions">
        <?php if ($__clinicChannels['phones']): ?>
        <details class="header-contact-details header-call-details">
            <summary aria-label="Call the clinic"><?php $careIcon='phone'; require __DIR__.'/../partials/care-icon.php'; ?><span aria-hidden="true">⌄</span></summary>
            <div class="header-contact-details__links"><p>Call the clinic</p>
                <?php foreach ($__clinicChannels['phones'] as $number): ?><a href="tel:<?= e($number) ?>"><?= e($number) ?></a><?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
        <?php if ($__clinicChannels['whatsapp']): ?>
        <details class="header-contact-details header-whatsapp-details">
            <summary aria-label="Contact via WhatsApp"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a9 9 0 0 1-13 8L3 21l1.5-5a9 9 0 1 1 16.5-4.5Z"/><path d="M8.5 7.5c-.8.3-1.2 1.2-.8 2.4 1 2.7 3.2 4.9 5.9 5.8 1.2.4 2.2 0 2.5-.9l.4-1.2-2.6-1.2-1 1c-1.5-.6-2.8-1.9-3.3-3.3l1-1-1.1-2.1Z"/></svg><span aria-hidden="true">⌄</span></summary>
            <div class="header-contact-details__links"><p>Message on WhatsApp</p>
                <?php foreach ($__clinicChannels['whatsapp'] as $number): ?><a href="<?= e(\App\Services\ClinicContactService::whatsappUrl($number)) ?>"><?= e($number) ?></a><?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>
        <a href="/contact" class="header-contact">Contact</a>
        <a href="/contact#contact-form" class="header-book">Book a consultation</a>
        <?php if ($__modules['shop']): ?><a href="/cart" class="cart-btn" aria-label="Cart">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
            <?php $cartCount = 0; if(!empty($_SESSION['cart'])){foreach($_SESSION['cart'] as $c){$cartCount += $c['qty'] ?? 1;}} ?><span class="cart-count"><?= $cartCount ?></span>
        </a><?php endif; ?>
    </div>
</header>
<main id="main-content" tabindex="-1" class="<?= $currentPath === '/' ? 'home-main' : '' ?>">
<?php
$__flash = $_SESSION['flash'] ?? null;
if ($__flash):
    $__msg = is_array($__flash) ? $__flash['message'] : $__flash;
    $__type = is_array($__flash) ? ($__flash['type'] ?? 'info') : 'info';
    unset($_SESSION['flash']);
?>
<script>document.addEventListener('DOMContentLoaded',function(){showToast(<?= json_encode($__msg) ?>,<?= json_encode($__type) ?>);});</script>
<?php endif; ?>
<?php require $viewFile; ?>
</main>



<?php if ($__modules['shop'] && !in_array($currentPath, ['/cart', '/login', '/register'], true)): ?>
<a href="/cart" class="mobile-cart-tray" id="mobile-cart-tray" <?= $cartCount > 0 ? '' : 'hidden' ?>>
    <span><strong id="mobile-cart-count"><?= (int)$cartCount ?></strong> <span id="mobile-cart-label"><?= $cartCount === 1 ? 'item' : 'items' ?></span> in cart</span>
    <span>View cart <span aria-hidden="true">→</span></span>
</a>
<?php endif; ?>

<?php if (!in_array($currentPath, ['/login', '/register'])): ?>
<button class="support-fab" type="button" aria-controls="support-panel" aria-expanded="false" title="Support">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>
    <span class="sr-only">Support</span>
</button>
<?php $supportChat = new \App\Services\AgentConversation('support'); ?>
<section class="support-panel" id="support-panel" data-conversation="<?= e($supportChat->id()) ?>" data-has-turns="<?= $supportChat->hasTurns() ? '1' : '0' ?>" aria-labelledby="support-title" data-support-key="nebo-support-chat-<?= !empty($_SESSION['user']['email']) ? hash('sha256', strtolower((string)$_SESSION['user']['email'])) : 'guest' ?>" hidden>
    <div class="support-panel__head">
        <div class="support-panel__identity">
            <span class="support-panel__mark" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 9h8m-8 4h5"/></svg></span>
            <div><strong id="support-title"><?= e($__profile['short_name']) ?> Support</strong><span>Clinic information assistant</span></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" id="support-clear" hidden>New chat</button>
        <button type="button" class="support-panel__close" aria-label="Close support">×</button>
    </div>
    <div class="support-panel__body" id="support-log" role="log" aria-label="Conversation" tabindex="0">
        <p class="support-panel__intro">Hello! How can we help? Ask about programmes or consultations. This is an automated assistant. Please do not share medical records or passwords.</p>
    </div>
    <form class="support-panel__form" id="support-form">
        <input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
        <details id="support-prompts" class="support-prompts" open>
            <summary>Suggested questions</summary>
            <div class="support-starters" role="group" aria-label="Suggested questions">
                <button type="button" class="btn btn-outline btn-sm" data-support-prompt="<?= e(\App\Services\SupportBotService::PROGRAMS_PROMPT) ?>">Explore programs</button>
                <button type="button" class="btn btn-outline btn-sm" data-support-prompt="<?= e(\App\Services\SupportBotService::CONSULTATION_PROMPT) ?>">Request a consultation</button>
                <?php if (!empty($_SESSION['user']['email'])): ?>
                    <button type="button" class="btn btn-outline btn-sm" data-support-prompt="My support tickets">My tickets</button>
                <?php endif; ?>
            </div>
        </details>
        <label for="support-message">Your message</label>
        <div class="support-panel__compose">
            <textarea id="support-message" name="message" rows="2" maxlength="2000" required></textarea>
            <button class="btn btn-primary" type="submit">Send</button>
        </div>
        <label class="support-archive-consent"><input type="checkbox" name="archive_session" value="1"> Save this conversation for clinic follow-up</label>
        <small class="support-archive-hint">Sending a ticket shares this chat and tool activity with clinic staff. <a href="/privacy#support-privacy">Privacy</a>.</small>
        <div class="support-panel__links">
            <a href="/contact#contact-form">Contact the clinic</a>
            <?php if ($__clinicChannels['whatsapp']): ?><a href="<?= e(\App\Services\ClinicContactService::whatsappUrl($__clinicChannels['whatsapp'][0])) ?>">WhatsApp</a><?php endif; ?>
            <?php if (!empty($_SESSION['user']['email'])): ?>
                <button class="support-panel__handoff" type="submit" value="ticket" title="Share this conversation and request with clinic staff">Send to clinic</button>
            <?php else: ?>
                <a href="/login">Sign in to track tickets</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!in_array($currentPath, ['/login', '/register'])): ?>
<footer class="site-footer site-footer--contact" role="contentinfo" aria-label="<?= e($__profile['name']) ?> information">
    <div class="container">
        <div class="footer-contact">
            <div>
                <h2><?= e($__profile['name']) ?></h2>
                <?php if ($__profile['footer_description']!==''): ?><p class="footer-desc"><?= e($__profile['footer_description']) ?></p><?php endif; ?>
            </div>
            <a class="footer-contact__action" href="/contact#contact-form">Contact the clinic <span aria-hidden="true">↗</span></a>
        </div>
        <div class="footer-grid">
            <?php if ($__profile['instagram_url']!==''||$__profile['facebook_url']!==''||$__profile['youtube_url']!==''): ?>
            <div>
                <h3 class="footer-heading">Follow <?= e($__profile['short_name']) ?></h3>
                <div class="footer-social" aria-label="<?= e($__profile['short_name']) ?> social channels">
                    <?php if ($__profile['instagram_url']!==''): ?><a href="<?= e($__profile['instagram_url']) ?>" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a><?php endif; ?>
                    <?php if ($__profile['facebook_url']!==''): ?><a href="<?= e($__profile['facebook_url']) ?>" target="_blank" rel="noopener noreferrer">Facebook <span aria-hidden="true">↗</span></a><?php endif; ?>
                    <?php if ($__profile['youtube_url']!==''): ?><a href="<?= e($__profile['youtube_url']) ?>" target="_blank" rel="noopener noreferrer">YouTube <span aria-hidden="true">↗</span></a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <h3 class="footer-heading">Clinic</h3>
                <ul class="footer-links">
                    <?php if ($__modules['shop']): ?><li><a href="/shop">Shop</a></li><?php endif; ?>
                    <li><a href="/#programs">Programs</a></li>
                    <?php if ($__modules['campaigns']): ?><li><a href="/campaigns">Services</a></li><?php endif; ?>
                    <li><a href="/about">About <?= e($__profile['short_name']) ?></a></li>
                    <?php if ($__modules['blog']): ?><li><a href="/blog">Blog</a></li><?php endif; ?>
                </ul>
            </div>
            <?php if ($__modules['blog'] && !empty($__blogCats)): ?>
            <div class="footer-topics">
                <h3 class="footer-heading">Blog topics</h3>
                <ul class="footer-links">
                    <?php foreach ($__blogCats as $__cat): ?>
                    <li><a href="/blog/category/<?= e($__cat['slug'] ?? '') ?>"><?= e($__cat['name'] ?? '') ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <div class="footer-details">
                <h3 class="footer-heading">Visit or call</h3>
                <ul class="footer-links">
                    <?php foreach (($__clinicChannels['phones']??[]) as $number): ?><li><a href="tel:<?= e($number) ?>"><?= e($number) ?></a></li><?php endforeach; ?>
                    <?php if ($__profile['public_email']!==''): ?><li><a href="mailto:<?= e($__profile['public_email']) ?>"><?= e($__profile['public_email']) ?></a></li><?php endif; ?>
                    <?php if ($__profile['address']!==''): ?><li><?= e($__profile['address']) ?></li><?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($__profile['name']) ?></span>
            <div class="footer-legal">
                <a href="/terms">Terms</a>
                <a href="/privacy">Privacy</a>
            </div>
            <span>Developed with ♥️ by <a href="https://mediahub.bapx.in/" target="_blank" rel="noopener noreferrer">@bapxmediahub</a></span>
        </div>
    </div>
</footer>
<?php endif; ?>
<div id="toast-container" role="alert" aria-live="polite"></div>
</body>
</html>

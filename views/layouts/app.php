<!doctype html>
<html lang="en" class="public-site">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="format-detection" content="telephone=no">
<title><?= e($pageTitle ?? 'Nebo Lifestyle Clinic') ?></title>
<meta name="description" content="<?= e($metaDescription ?? 'Naturopathy, functional medicine, and integrative wellness solutions for sustainable health transformation.') ?>">
<meta name="robots" content="<?= e($metaRobots ?? 'index, follow') ?>">
<?php $__seoKeywords = $seo['keywords'] ?? ''; if ($__seoKeywords !== ''): ?><meta name="keywords" content="<?= e($__seoKeywords) ?>"><?php endif; ?>
<?php
$__settings = (new \App\Services\SettingsService())->public();
$__modules = (new \App\Services\SettingsService())->modules();
$__logo = (string)($__settings['logo_url'] ?? '');
if ($__logo === '' || str_contains(strtolower($__logo), 'logo-small.jpeg') || str_contains(strtolower($__logo), 'sripanchami') || str_contains($__logo, 'nebo-symbol.')) {
    $__logo = '/assets/images/nebo-official-logo.jpg';
}
$__favicon = (string)($__settings['favicon_url'] ?? '');
if ($__favicon === '' || str_contains(strtolower($__favicon), 'sps-') || str_contains(strtolower($__favicon), 'sripanchami')) {
    $__favicon = '/assets/images/nebo-symbol.png';
}
$__faviconMime = str_contains($__favicon,'.svg') ? 'image/svg+xml' : 'image/png';
?>
<link rel="icon" type="<?= e($__faviconMime) ?>" href="<?= e($__favicon) ?>">
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#4472C4">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Nebo Lifestyle Clinic">
<link rel="apple-touch-icon" href="/assets/images/nebo-symbol.png">
<link rel="service-desc" type="application/json" href="/openapi.json">
<link rel="canonical" href="<?= e($seo['canonical'] ?? \App\Services\SeoService::publicUrl(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<meta property="og:type" content="<?= e($seo['og_type'] ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($seo['og_site_name'] ?? 'Nebo Lifestyle Clinic') ?>">
<meta property="og:title" content="<?= e($seo['og_title'] ?? $pageTitle) ?>">
<meta property="og:description" content="<?= e($seo['og_description'] ?? $metaDescription) ?>">
<meta property="og:url" content="<?= e($seo['og_url'] ?? \App\Services\SeoService::publicUrl(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/')) ?>">
<meta property="og:image" content="<?= e($seo['og_image'] ?? \App\Services\SeoService::publicUrl('/assets/images/nebo-clinic-hero.webp')) ?>">
<meta property="og:locale" content="en_IN">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['twitter_title'] ?? $seo['og_title'] ?? $pageTitle) ?>">
<meta name="twitter:description" content="<?= e($seo['twitter_description'] ?? $seo['og_description'] ?? $metaDescription) ?>">
<meta name="twitter:image" content="<?= e($seo['twitter_image'] ?? $seo['og_image'] ?? \App\Services\SeoService::publicUrl('/assets/images/nebo-clinic-hero.webp')) ?>">
<link rel="stylesheet" href="/assets/css/band.css?v=<?= filemtime(__DIR__ . '/../../assets/css/band.css') ?>">
<script src="/assets/navigation-drawer.js?v=<?= filemtime(__DIR__ . '/../../assets/navigation-drawer.js') ?>" defer></script>
<?php if (basename($viewFile ?? '') === 'home.php'): ?>
<script src="/assets/editorial-rails.js?v=<?= filemtime(__DIR__ . '/../../assets/editorial-rails.js') ?>" defer></script>
<?php endif; ?>
<script src="/assets/editorial-stack.js?v=<?= filemtime(__DIR__ . '/../../assets/editorial-stack.js') ?>" defer></script>
<?php if (basename($viewFile ?? '') === 'home.php'): ?>
<script src="/assets/therapy-media.js?v=<?= filemtime(__DIR__ . '/../../assets/therapy-media.js') ?>" defer></script>
<?php endif; ?>
<script src="/assets/mobile-nav.js?v=<?= filemtime(__DIR__ . '/../../assets/mobile-nav.js') ?>" defer></script>
<script src="/assets/site-feedback.js?v=<?= filemtime(__DIR__ . '/../../assets/site-feedback.js') ?>" defer></script>
<script src="/assets/public-shell.js?v=<?= filemtime(app_path('assets/public-shell.js')) ?>" defer></script>
<?php if (in_array(basename($viewFile ?? ''), ['shop.php', 'product.php'], true)): ?>
<script src="/assets/product-cart.js?v=<?= filemtime(app_path('assets/product-cart.js')) ?>" defer></script>
<?php endif; ?>
<script src="/assets/page-finder.js?v=<?= filemtime(__DIR__ . '/../../assets/page-finder.js') ?>" defer></script>
<script src="/assets/agent-status.js?v=<?= filemtime(__DIR__ . '/../../assets/agent-status.js') ?>" defer></script>
<script src="/assets/agent-conversation.js?v=<?= filemtime(__DIR__ . '/../../assets/agent-conversation.js') ?>" defer></script>
<script src="/assets/support-chat.js?v=<?= filemtime(__DIR__ . '/../../assets/support-chat.js') ?>" defer></script>
<style>
<?php
$__palette_semantic = [
    '--color-primary' => ['set' => ($__settings['palette_primary'] ?? '#4472C4'), 'alias' => '--color-maroon'],
    '--color-secondary' => ['set' => ($__settings['palette_secondary'] ?? '#70AD47'), 'alias' => '--color-gold'],
    '--color-canvas' => ['set' => ($__settings['palette_canvas'] ?? '#F7F9FA'), 'alias' => '--color-bg'],
    '--color-text-primary' => ['set' => ($__settings['palette_text'] ?? '#222222'), 'alias' => '--color-ink'],
    '--color-link' => ['set' => ($__settings['palette_link'] ?? '#4472C4'), 'alias' => ''],
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
    <a href="/" class="brand" aria-label="Nebo Lifestyle Clinic home">
        <img src="<?= e($__logo) ?>" width="160" height="88" alt="Nebo Lifestyle Clinic">
    </a>
<?php try { $__blogCats = (new \App\Services\BlogService())->categories(); } catch (\Throwable $e) { $__blogCats = []; } ?>
    <details class="navigation-fallback" id="navigation-fallback">
        <summary class="menu-toggle" aria-label="Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            <span>Menu</span>
        </summary>
        <nav id="primary-nav" aria-label="Main navigation">
            <a href="/#programs" data-programs-nav>Programs</a>
            <?php if ($__modules['campaigns']): ?><a href="/campaigns"<?= ($currentPath === '/campaigns' || str_starts_with($currentPath, '/campaigns/')) ? ' aria-current="page"' : '' ?>>Services</a><?php endif; ?>
            <?php if ($__modules['shop']): ?>
            <a href="/shop"<?= str_starts_with($currentPath, '/shop') ? ' aria-current="page"' : '' ?>>Shop</a>
            <?php endif; ?>
            <?php if ($__modules['blog']): ?>
            <div class="nav-dropdown">
                <a href="/blog" class="nav-dropdown__trigger"<?= str_starts_with($currentPath, '/blog') ? ' aria-current="page"' : '' ?>>Blog <span class="nav-dropdown__arrow">▾</span></a>
                <div class="nav-dropdown__menu">
                    <?php foreach ($__blogCats as $__cat): ?>
                    <a href="/blog/category/<?= e($__cat['slug'] ?? '') ?>"><?= e($__cat['name'] ?? '') ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <a href="/about"<?= str_starts_with($currentPath, '/about') ? ' aria-current="page"' : '' ?>>About Nebo</a>
            <a href="/contact"<?= str_starts_with($currentPath, '/contact') ? ' aria-current="page"' : '' ?>>Contact</a>
            <?php if(!empty($_SESSION['user'])): ?>
                <a href="/account/dashboard"<?= str_starts_with($currentPath, '/account/dashboard') ? ' aria-current="page"' : '' ?>>Dashboard</a>
                <?php if (($_SESSION['user']['role'] ?? '') === 'customer'): ?>
                    <a href="/account/therapy"<?= $currentPath === '/account/therapy' ? ' aria-current="page"' : '' ?>>Therapy requests</a>
                <?php endif; ?>
                <a href="/logout">Logout</a>
            <?php else: ?>
                <a href="/login">Login</a>
            <?php endif; ?>
        </nav>
    </details>
    <dialog class="navigation-drawer" id="navigation-drawer" aria-label="Site menu">
        <div class="navigation-drawer__header">
            <a href="/" class="navigation-drawer__brand" aria-label="Nebo Lifestyle Clinic home">
                <img src="<?= e($__logo) ?>" width="108" height="60" alt="">
            </a>
            <button class="nav-close" type="button" aria-label="Close menu" autofocus>×</button>
        </div>
    </dialog>
    <div class="header-actions">
        <button class="page-finder-trigger" type="button" data-page-finder="primary-nav" aria-haspopup="dialog" aria-label="Find a page" hidden>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
            <span>Find a page</span>
        </button>
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
        <strong id="support-title">Nebo Support</strong>
        <button type="button" class="btn btn-outline btn-sm" id="support-clear" hidden>New chat</button>
        <button type="button" class="support-panel__close" aria-label="Close support">×</button>
    </div>
    <div class="support-panel__body" id="support-log" role="log" aria-label="Conversation" tabindex="0">
        <p class="support-panel__intro">Ask about programs, consultations, or clinic contact details. Please leave medical details out of chat.</p>
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
        <textarea id="support-message" name="message" rows="2" maxlength="2000" required></textarea>
        <div class="support-panel__actions">
            <button class="btn btn-primary btn-sm" type="submit">Send</button>
            <?php if (!empty($_SESSION['user']['email'])): ?>
                <button class="btn btn-outline btn-sm" type="submit" value="ticket" title="Share this message with clinic staff">Send to clinic</button>
            <?php endif; ?>
        </div>
        <div class="support-panel__links">
            <a href="/contact#contact-form">Contact the clinic team</a>
            <?php if (empty($_SESSION['user']['email'])): ?>
                <a href="/login">Sign in to track tickets</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php endif; ?>

<?php if (!in_array($currentPath, ['/login', '/register'])): ?>
<footer class="site-footer site-footer--contact" role="contentinfo" aria-label="Nebo clinic information">
    <div class="container">
        <div class="footer-contact">
            <div>
                <h2>Nebo Lifestyle Clinic</h2>
                <p class="footer-desc">Personalised naturopathy, functional medicine, and lifestyle care in Kanyakumari.</p>
            </div>
            <a class="footer-contact__action" href="/contact#contact-form">Contact the clinic <span aria-hidden="true">↗</span></a>
        </div>
        <div class="footer-grid">
            <div>
                <h3 class="footer-heading">Follow Nebo</h3>
                <div class="footer-social" aria-label="Nebo social channels">
                    <a href="https://www.instagram.com/nebolifestyleclinic/" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a>
                    <a href="https://www.facebook.com/nebolifestyleclinic" target="_blank" rel="noopener noreferrer">Facebook <span aria-hidden="true">↗</span></a>
                    <a href="https://www.youtube.com/@nebolifestyleclinic" target="_blank" rel="noopener noreferrer">YouTube <span aria-hidden="true">↗</span></a>
                </div>
            </div>
            <div>
                <h3 class="footer-heading">Clinic</h3>
                <ul class="footer-links">
                    <?php if ($__modules['shop']): ?><li><a href="/shop">Shop</a></li><?php endif; ?>
                    <li><a href="/#programs">Programs</a></li>
                    <?php if ($__modules['campaigns']): ?><li><a href="/campaigns">Services</a></li><?php endif; ?>
                    <li><a href="/about">About Nebo</a></li>
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
                    <li><a href="tel:+917200182025">+91 72001 82025</a></li>
                    <li><a href="tel:+919585182025">+91 95851 82025</a></li>
                    <li><a href="mailto:nebolifestyleclinic@gmail.com">nebolifestyleclinic@gmail.com</a></li>
                    <li>Kaliyal, Kanyakumari, Tamil Nadu 629101</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Nebo Lifestyle Clinic</span>
            <div class="footer-legal">
                <a href="/terms">Terms</a>
                <a href="/privacy">Privacy</a>
            </div>
            <span>Developed by <a href="https://mediahub.bapx.in/" target="_blank" rel="noopener noreferrer">@bapxmediahub</a></span>
        </div>
    </div>
</footer>
<?php endif; ?>
<div id="toast-container" role="alert" aria-live="polite"></div>
</body>
</html>

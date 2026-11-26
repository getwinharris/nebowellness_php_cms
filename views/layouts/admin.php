<?php $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16)); $csrf = $_SESSION['csrf_token'];
$__modules = (new \App\Services\SettingsService())->modules(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> — Nebo Wellness</title>
<meta name="robots" content="noindex, nofollow">
<link rel="manifest" href="/admin/manifest.json">
<meta name="theme-color" content="#4472C4">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Nebo Admin">
<link rel="apple-touch-icon" href="/assets/images/nebo-symbol.png">
<link rel="stylesheet" href="/assets/css/band.css?v=<?= filemtime(__DIR__.'/../../assets/css/band.css') ?>">
<script src="/assets/admin-tables.js?v=<?= filemtime(__DIR__ . '/../../assets/admin-tables.js') ?>" defer></script>
<script src="/assets/site-motion.js?v=<?= filemtime(app_path('assets/site-motion.js')) ?>" defer></script>
<script src="/assets/site-feedback.js?v=<?= filemtime(__DIR__ . '/../../assets/site-feedback.js') ?>" defer></script>
<script src="/assets/page-finder.js?v=<?= filemtime(__DIR__ . '/../../assets/page-finder.js') ?>" defer></script>
<script src="/assets/agent-status.js?v=<?= filemtime(__DIR__.'/../../assets/agent-status.js') ?>" defer></script>
<script src="/assets/admin-navigation.js?v=<?= filemtime(app_path('assets/admin-navigation.js')) ?>" defer></script>

</head>
<body>
<a class="skip-content" href="#main-content">Skip to content</a>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar">
        <div class="admin-sidebar__brand">
            <span>Nebo Wellness</span>
            <small>Admin Panel</small>
        </div>
        <a class="admin-nav-close" href="#main-content" data-admin-menu-close>Close navigation</a>
        <nav aria-label="Clinic administration" class="admin-sidebar__nav" id="admin-nav">
            <a href="/admin" class="admin-nav-top <?= ($_SERVER['REQUEST_URI'] === '/admin' ? 'active' : '') ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <button type="button" class="admin-nav-toggle" aria-controls="menu-clinic" aria-expanded="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 000 20 14.5 14.5 0 000-20"/><path d="M2 12h20"/></svg>
                Clinic
                <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="admin-submenu" id="menu-clinic">
                <a href="/admin/consultants" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/consultants') === 0 ? 'active' : '') ?>">Consultants</a>
                <a href="/admin/appointments" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/appointments') === 0 ? 'active' : '') ?>">Sessions</a>
                <a href="/admin/consultation-analytics" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/consultation-analytics') === 0 ? 'active' : '') ?>">Analytics</a>
            </div>
            <?php if ($__modules['blog'] || $__modules['campaigns']): ?>
            <button type="button" class="admin-nav-toggle" aria-controls="menu-content" aria-expanded="true"><?php $careIcon='program'; require __DIR__.'/../partials/care-icon.php'; ?> Content <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
            <div class="admin-submenu" id="menu-content"><?php if ($__modules['blog']): ?><a href="/admin/blog" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/blog') === 0 ? 'active' : '') ?>">Blog</a><?php endif; ?><?php if ($__modules['campaigns']): ?><a href="/admin/campaigns" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/campaigns') === 0 ? 'active' : '') ?>">Campaign Pages</a><?php endif; ?></div>
            <?php endif; ?>
            <?php if ($__modules['shop']): ?><button type="button" class="admin-nav-toggle" aria-controls="menu-commerce" aria-expanded="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                Commerce
                <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="admin-submenu" id="menu-commerce">
                <a href="/admin/products" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/products') === 0 ? 'active' : '') ?>">Products</a>
                <a href="/admin/categories" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/categories') === 0 ? 'active' : '') ?>">Categories</a>
                <a href="/admin/coupons" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/coupons') === 0 ? 'active' : '') ?>">Coupons</a>
                <a href="/admin/orders" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/orders') === 0 ? 'active' : '') ?>">Orders</a>
                <a href="/admin/shipping" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/shipping') === 0 ? 'active' : '') ?>">Shipping</a>
                <a href="/admin/tax-report" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/tax-report') === 0 ? 'active' : '') ?>">Tax Report</a>
            </div>
            <?php endif; ?>
            <button type="button" class="admin-nav-toggle" aria-controls="menu-enquiries" aria-expanded="true"><?php $careIcon='message'; require __DIR__.'/../partials/care-icon.php'; ?> Enquiries <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
            <div class="admin-submenu" id="menu-enquiries"><a href="/admin/contact-submissions" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/contact-submissions') === 0 ? 'active' : '') ?>">Contacts</a><a href="/admin/therapy" class="<?= str_starts_with($_SERVER['REQUEST_URI'], '/admin/therapy') ? 'active' : '' ?>">Therapy requests</a><a href="/admin/support-tickets" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/support-tickets') === 0 ? 'active' : '') ?>">Support</a><a href="/admin/email-inbox" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/email-inbox') === 0 ? 'active' : '') ?>">Email Inbox</a><a href="/admin/email-outbox" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/email-outbox') === 0 ? 'active' : '') ?>">Email Outbox</a></div>
            <button type="button" class="admin-nav-toggle" aria-controls="menu-appearance" aria-expanded="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                Appearance
                <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="admin-submenu" id="menu-appearance">
                <a href="/admin/media" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/media') === 0 ? 'active' : '') ?>">Media Library</a>
                <a href="/admin/appearance" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/appearance') === 0 ? 'active' : '') ?>">Logo & Favicon</a>
            </div>
            <button type="button" class="admin-nav-toggle" aria-controls="menu-settings" aria-expanded="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                Settings
                <svg class="admin-nav-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="admin-submenu" id="menu-settings">
                <a href="/admin/integrations" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/integrations') === 0 ? 'active' : '') ?>">Integrations</a>
                <a href="/admin/agent" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/agent') === 0 ? 'active' : '') ?>">Enterprise Agent</a>
                <a href="/admin/settings" class="<?= (strpos($_SERVER['REQUEST_URI'], '/admin/settings') === 0 ? 'active' : '') ?>">Site Settings</a>
            </div>
        </nav>
        <div class="admin-sidebar__footer">
            <a href="/">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                View Site
            </a>
            <a href="/logout">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 17"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </aside>
    <main id="main-content" tabindex="-1" class="admin-main">
        <div class="admin-topbar">
            <button type="button" class="btn btn-sm btn-ghost" id="sidebarToggle" hidden aria-label="Admin navigation" aria-controls="admin-sidebar" aria-expanded="false">
                <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <noscript><a class="admin-nav-fallback" href="#admin-sidebar">Menu</a></noscript>
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
            <div class="admin-topbar__actions">
        <button class="page-finder-trigger" type="button" data-page-finder="admin-nav" aria-haspopup="dialog" aria-label="Find a module" hidden>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/></svg>
            <span>Find a module</span>
        </button>
                <a href="/" class="btn btn-sm btn-ghost" target="_blank">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View Site
                </a>
                <a href="/logout" class="btn btn-sm btn-ghost">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 17"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
            </div>
        </div>
        <nav aria-label="Breadcrumb" class="admin-breadcrumb">
            <a href="/admin">Dashboard</a>
            <?php
            $__path = parse_url($_SERVER['REQUEST_URI'] ?? '/admin', PHP_URL_PATH);
            $__segments = array_values(array_filter(explode('/', $__path)));
            $__crumb = '/admin';
            $__labelMap = [
                'products'=>'Products','categories'=>'Categories','coupons'=>'Coupons',
                'orders'=>'Orders','shipping'=>'Shipping','tax-report'=>'Tax Report','consultants'=>'Consultants',
                'appointments'=>'Sessions','therapy'=>'Therapy requests','agent'=>'Enterprise Agent','search-performance'=>'Search performance',
                'consultation-analytics'=>'Analytics','campaigns'=>'Campaign Pages',
                'settings'=>'Site Settings','integrations'=>'Integrations',
                'backups'=>'Backups','audit-log'=>'Audit Log',
                'contact-submissions'=>'Contacts','support-tickets'=>'Support',
                'email-inbox'=>'Email Inbox','email-outbox'=>'Email Outbox',
                'appearance'=>'Logo & Favicon','media'=>'Media Library',
                'environment'=>'Environment','developer'=>'Developer',
                'project-map'=>'Project Map','contact_submissions'=>'Contacts',
            ];
            foreach ($__segments as $__i => $__seg):
                if ($__seg === 'admin' && $__i === 0) continue;
                $__crumb .= '/' . $__seg;
                $__label = $__labelMap[$__seg] ?? ucwords(str_replace(['-','_'], ' ', $__seg));
            ?>
                <span aria-hidden="true">/</span>
                <?php if ($__i === count($__segments) - 1): ?>
                    <span aria-current="page"><?= e($__label) ?></span>
                <?php else: ?>
                    <a href="<?= e($__crumb) ?>"><?= e($__label) ?></a>
            <?php endif; endforeach; ?>
        </nav>
        <div class="admin-body">
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
        </div>
    </main>
</div>
<script>
if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/admin/sw.js', { scope: '/admin/' }).catch(function(){}); }
var installPrompt=null;window.addEventListener('beforeinstallprompt',function(e){e.preventDefault();installPrompt=e;document.getElementById('pwa-install-btn').style.display='flex';});window.addEventListener('appinstalled',function(){installPrompt=null;document.getElementById('pwa-install-btn').style.display='none';});document.addEventListener('click',function(e){if(e.target.closest('#pwa-install-btn')){if(!installPrompt)return;installPrompt.prompt();installPrompt.userChoice.then(function(){installPrompt=null;document.getElementById('pwa-install-btn').style.display='none';});}});
</script>
<div id="toast-container"></div>
<button id="pwa-install-btn" class="pwa-install-btn">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Install App
</button>
<script>
// Saves travel over HTTP to the remote database and take seconds. Without this the
// button stays live, an impatient second click posts the form again, and two records
// are created. Disable on first submit and say what is happening.
(function () {
    document.querySelectorAll('form.admin-form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) return;
            var btn = form.querySelector('button[type="submit"], button:not([type])');
            if (!btn || btn.dataset.saving === '1') return;
            btn.dataset.saving = '1';
            btn.dataset.label = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Saving\u2026';
            // Re-enable if the browser restores the page from cache instead of navigating.
            setTimeout(function () {
                btn.disabled = false;
                btn.dataset.saving = '';
                if (btn.dataset.label) btn.textContent = btn.dataset.label;
            }, 20000);
        });
    });
})();
</script>
</body>
</html>

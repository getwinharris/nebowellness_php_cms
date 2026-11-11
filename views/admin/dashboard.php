<style>
.dashboard-overview { display:grid; gap:var(--space-lg); margin-bottom:var(--space-lg); }
.dashboard-heading { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:var(--space-md); }
.dashboard-heading h2,.dashboard-chart h3 { margin:0; }
.dashboard-heading p,.dashboard-chart p,.dashboard-telemetry-note { margin:.35rem 0 0; color:var(--color-text-muted); }
.dashboard-overview .dashboard-actions,.dashboard-overview .dashboard-period { display:flex; flex-wrap:wrap; gap:.5rem; }
.dashboard-period a[aria-current] { border-color:currentColor; font-weight:700; }
.dashboard-insights { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:var(--space-sm); margin-block:var(--space-md); }
.dashboard-insight { padding:var(--space-md); border:1px solid var(--color-border); border-radius:var(--radius-md); }
.dashboard-insight h3 { margin:0; font-size:1rem; }
.dashboard-insight dl { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin:.75rem 0; }
.dashboard-insight dt { font-size:.78rem; color:var(--color-text-muted); }
.dashboard-insight dd { font-size:clamp(1rem,2.1vw,1.5rem); font-weight:700; margin:0; font-variant-numeric:tabular-nums; }
.dashboard-insight p { margin:0; font-size:.84rem; color:var(--color-text-muted); }
.dashboard-chart { padding:var(--space-md); border:1px solid var(--color-border); border-radius:var(--radius-md); }
.dashboard-plot { display:grid; grid-auto-flow:column; grid-auto-columns:minmax(0,1fr); align-items:end; gap:clamp(2px, .6vw, 10px); height:100px; border-bottom:1px solid var(--color-border); margin-top:1rem; }
.dashboard-bar { background:var(--color-primary, #2d579c); display:flex; flex-direction:column; justify-content:end; border-radius:2px 2px 0 0; }
.dashboard-bar span { background:#a53335; }
.dashboard-axis { display:flex; justify-content:space-between; gap:.5rem; font-size:.76rem; color:var(--color-text-muted); }
.dashboard-key { display:flex; flex-wrap:wrap; gap:.4rem 1rem; font-size:.8rem; margin-top:.6rem; }
.dashboard-key span::before { content:''; display:inline-block; width:.65em; height:.65em; margin-right:.35em; background:var(--color-primary, #2d579c); }
.dashboard-key span:last-child::before { background:#a53335; }
.dashboard-chart details { margin-top:.75rem; }
.dashboard-chart summary { cursor:pointer; width:fit-content; }
@media(max-width:740px) { .dashboard-insights { grid-template-columns:minmax(0,1fr); } }
</style>
<div class="dashboard-overview">
<div class="dashboard-heading">
    <div><h2>Clinic overview</h2><p>Review enquiries and check how both agents are responding.</p></div>
    <nav class="dashboard-actions" aria-label="Clinic workflows">
        <a href="/admin/contact-submissions" class="btn btn-sm btn-outline">Contact enquiries</a>
        <a href="/admin/support-tickets" class="btn btn-sm btn-outline">Support tickets</a>
        <a href="/admin/agent" class="btn btn-sm btn-outline">Enterprise Agent</a>
    </nav>
</div>
<section class="admin-card" id="agent-insights" aria-labelledby="agent-insights-title">
    <div class="dashboard-heading">
        <div><h2 id="agent-insights-title">Agent health</h2><p>Recorded activity by UTC day, including today.</p></div>
        <nav class="dashboard-period" aria-label="Agent date range">
            <?php foreach ([7,30] as $days): ?>
                <a class="btn btn-sm btn-outline" href="/admin?days=<?= $days ?>#agent-insights"<?= ($dashboardDays ?? 7) === $days ? ' aria-current="true"' : '' ?>><?= $days ?> days</a>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php if (($agentMonitoring ?? null) === null): ?>
        <p role="status">Monitoring is temporarily unavailable.</p>
    <?php else: ?>
        <div class="dashboard-insights">
        <?php foreach ($agentMonitoring['surfaces'] as $surface => $metrics): $provider = $agentMonitoring['providers'][$surface]; ?>
            <article class="dashboard-insight">
                <h3><?= $surface === 'support' ? 'Customer support' : 'Enterprise Agent' ?></h3>
                <dl>
                    <div><dt>Runs</dt><dd><?= (int)$metrics['requests'] ?></dd></div>
                    <div><dt>Run errors</dt><dd><?= $metrics['error_percent'] === null ? '—' : e(number_format($metrics['error_percent'], 1) . '%') ?></dd></div>
                    <div><dt>P95 time</dt><dd><?= $metrics['p95_ms'] === null ? '—' : e(number_format($metrics['p95_ms'] / 1000, 2) . ' s') ?></dd></div>
                </dl>
                <p><?= $provider['attempts'] === 0 ? 'No provider attempts traced.' : (int)$provider['failures'] . ' of ' . (int)$provider['attempts'] . ' traced provider attempts failed.' ?></p>
                <?php if ($metrics['requests'] > 0): ?>
                    <details style="margin-top:.5rem"><summary>Run outcomes</summary>
                        <ul>
                            <?php foreach (['model' => 'Model answers', 'fallback' => 'Local responses or fallbacks', 'private_account' => 'Sign-in guidance', 'draft' => 'Generated drafts', 'manual' => 'Manual forms', 'draft_unavailable' => 'Drafts unavailable', 'error' => 'Run errors'] as $key => $label): ?>
                                <?php if ($metrics[$key] > 0): ?><li><?= e($label) ?>: <?= (int)$metrics[$key] ?></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
        </div>
        <?php $daily = $agentMonitoring['daily']; $peak = max(array_column($daily, 'runs')); ?>
        <div class="dashboard-chart">
            <h3>Daily agent runs</h3>
            <?php if ($peak === 0): ?>
                <p>No runs recorded in this period.</p>
            <?php else: ?>
                <p>Both agents combined · busiest day: <?= (int)$peak ?> <?= $peak === 1 ? 'run' : 'runs' ?>.</p>
                <div aria-hidden="true">
                    <div class="dashboard-plot">
                    <?php foreach ($daily as $point): ?>
                        <div class="dashboard-bar" style="height:<?= 100 * $point['runs'] / $peak ?>%"><span style="height:<?= $point['runs'] ? 100 * $point['errors'] / $point['runs'] : 0 ?>%"></span></div>
                    <?php endforeach; ?>
                    </div>
                    <div class="dashboard-axis"><span><?= e(array_key_first($daily)) ?></span><span><?= e(array_key_last($daily)) ?></span></div>
                    <div class="dashboard-key"><span>Other outcomes</span><span>Run errors</span></div>
                </div>
                <details><summary>View daily counts</summary>
                    <div class="table-wrap"><table>
                        <thead><tr><th scope="col">Date (UTC)</th><th scope="col">Runs</th><th scope="col">Run errors</th></tr></thead>
                        <tbody><?php foreach (array_reverse($daily, true) as $date => $point): ?>
                            <tr><th scope="row"><?= e($date) ?></th><td><?= (int)$point['runs'] ?></td><td><?= (int)$point['errors'] ?></td></tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                </details>
            <?php endif; ?>
        </div>
        <p class="dashboard-telemetry-note">Snapshot: <time datetime="<?= e(gmdate('c', $agentMonitoring['as_of'])) ?>"><?= e(gmdate('M j, H:i', $agentMonitoring['as_of'])) ?> UTC</time>. Refresh to update. P95 is the time within which 95% of recorded runs finished. A failed provider attempt may recover or fall back. <a href="/admin/agent#agent-traces-title">Inspect recent traces</a>.</p>
    <?php endif; ?>
</section>
</div>
<div class="admin-stats">
    <?php if (!empty($shopEnabled)): ?>
    <div class="admin-stat">
        <div class="admin-stat__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        </div>
        <div class="admin-stat__value"><?= (int)($productCount ?? 0) ?></div>
        <div class="admin-stat__label">Products</div>
    </div>
    <div class="admin-stat">
        <div class="admin-stat__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="admin-stat__value"><?= (int)($orderCount ?? 0) ?></div>
        <div class="admin-stat__label">Orders</div>
    </div>
    <?php endif; ?>
    <div class="admin-stat">
        <div class="admin-stat__icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-gold)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div class="admin-stat__value"><?= (int)($bookingCount ?? 0) ?></div>
        <div class="admin-stat__label">Saved Sessions</div>
    </div>
</div>

<?php
$__secretsGa = (new \App\Services\SecretService())->all();
$__gaConfigured = !empty($__secretsGa['google_analytics_id']);
$__adsConfigured = !empty($__secretsGa['google_ads_id']);
$__gsvConfigured = !empty($__secretsGa['google_site_verification']);
$__googleSiteKitEnabled = $__gaConfigured || $__adsConfigured || $__gsvConfigured;
?>

<?php if ($__googleSiteKitEnabled): ?>
<div class="admin-card" style="margin-bottom:var(--space-lg); border-left:3px solid var(--color-gold);">
    <h2 style="display:flex; align-items:center; gap:var(--space-sm); font-size:1rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 000 20 14.5 14.5 0 000-20"/><path d="M2 12h20"/></svg>
        Google integrations
    </h2>
    <div style="display:flex; flex-wrap:wrap; gap:var(--space-md); font-size:0.85rem;">
        <?php if ($__gaConfigured): ?>
        <span style="display:flex; align-items:center; gap:var(--space-xs);">
            <span style="color:var(--color-success);">●</span> Analytics
            <a href="https://analytics.google.com/analytics/web/" target="_blank" rel="noopener" style="font-size:0.75rem;">Open GA4 →</a>
        </span>
        <?php endif; ?>
        <?php if ($__adsConfigured): ?>
        <span style="display:flex; align-items:center; gap:var(--space-xs);">
            <span style="color:var(--color-success);">●</span> Ads
            <a href="https://ads.google.com/" target="_blank" rel="noopener" style="font-size:0.75rem;">Open Ads →</a>
        </span>
        <?php endif; ?>
        <?php if ($__gsvConfigured): ?>
        <span style="display:flex; align-items:center; gap:var(--space-xs);">
            <span style="color:var(--color-success);">●</span> Search Console
            <a href="https://search.google.com/search-console" target="_blank" rel="noopener" style="font-size:0.75rem;">Open Console →</a>
        </span>
        <?php endif; ?>
    </div>
    <p style="margin:var(--space-sm) 0 0; font-size:0.78rem; color:var(--color-text-muted);">
        Links are shown for configured integrations. Reports open in the respective Google service.
        <a href="/admin/integrations" style="font-weight:500;">Configure in Integrations</a>
    </p>
</div>
<?php else: ?>
<div class="admin-card" style="margin-bottom:var(--space-lg); border-left:3px solid var(--color-border);">
    <h2 style="display:flex; align-items:center; gap:var(--space-sm); font-size:1rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 000 20 14.5 14.5 0 000-20"/><path d="M2 12h20"/></svg>
        Google Analytics
    </h2>
    <p style="margin:0; font-size:0.85rem; color:var(--color-text-muted);">
        Google Analytics is not configured. 
        <a href="/admin/integrations">Set up GA4, Ads, and Search Console in Integrations →</a>
    </p>
</div>
<?php endif; ?>

<div class="admin-quick-grid">
    <?php if (!empty($shopEnabled)): ?>
    <div class="admin-card">
        <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Catalog</h2>
        <p>Manage products, categories, and discount coupons.</p>
        <div class="admin-card__actions">
            <a href="/admin/products" class="btn btn-sm btn-ghost">Products</a>
            <a href="/admin/categories" class="btn btn-sm btn-ghost">Categories</a>
            <a href="/admin/coupons" class="btn btn-sm btn-ghost">Coupons</a>
        </div>
    </div>
    <?php endif; ?>
    <div class="admin-card">
        <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 000 20 14.5 14.5 0 000-20"/><path d="M2 12h20"/></svg> Services</h2>
        <p>Review saved sessions and manage service records.</p>
        <div class="admin-card__actions">
            <a href="/admin/consultants" class="btn btn-sm btn-ghost">Consultants</a>
            <a href="/admin/appointments" class="btn btn-sm btn-ghost">Sessions</a>
            <a href="/admin/consultation-analytics" class="btn btn-sm btn-ghost">Analytics</a>
            <?php if (!empty($campaignsEnabled)): ?><a href="/admin/campaigns" class="btn btn-sm btn-ghost">Campaign pages</a><?php endif; ?>
        </div>
    </div>
    <div class="admin-card">
        <h2><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> Operations</h2>
        <p><?= !empty($shopEnabled) ? 'Orders, shipping, settings, and integrations.' : 'Site settings and integrations.' ?></p>
        <div class="admin-card__actions">
            <?php if (!empty($shopEnabled)): ?><a href="/admin/orders" class="btn btn-sm btn-ghost">Orders</a>
            <a href="/admin/shipping" class="btn btn-sm btn-ghost">Shipping</a><?php endif; ?>
            <a href="/admin/settings" class="btn btn-sm btn-ghost">Settings</a>
            <a href="/admin/integrations" class="btn btn-sm btn-ghost">Integrations</a>
        </div>
    </div>
</div>

<div class="admin-card" style="margin-top:var(--space-lg);">
    <div style="display:flex; align-items:baseline; justify-content:space-between; gap:var(--space-md); margin-bottom:var(--space-md);">
        <div>
            <h2 style="font-size:1.1rem; margin:0;">Recent Sessions</h2>
            <p style="color:var(--color-text-muted); margin:var(--space-xs) 0 0;">Historical appointment requests are retained here for follow-up.</p>
        </div>
        <a href="/admin/appointments" class="btn btn-sm btn-ghost">View all</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>Requested for</th><th>Status</th><th>Submitted</th></tr></thead>
            <tbody>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="4" style="text-align:center; color:var(--color-text-muted); padding:var(--space-lg);">No saved session requests yet.</td></tr>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= e((string)($appointment['customer_email'] ?? 'Guest')) ?></td>
                    <td><?= e(trim((string)($appointment['preferred_date'] ?? '') . ' ' . (string)($appointment['preferred_time'] ?? '')) ?: 'Not specified') ?></td>
                    <td><?= e(ucfirst(str_replace('_', ' ', (string)($appointment['status'] ?? 'requested')))) ?></td>
                    <td><?= e(substr((string)($appointment['created_at'] ?? ''), 0, 10) ?: '—') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

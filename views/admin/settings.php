<div class="admin-card" style="border-left:4px solid var(--color-gold); margin-bottom:var(--space-lg);">
    <h2 style="font-size:1rem; margin:0 0 var(--space-sm);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Protected Settings</h2>
    <p style="margin:0; color:var(--color-text-muted); font-size:0.9rem;">Live secrets are stored outside Git-tracked files. Use the Integrations page to manage API keys safely.</p>
</div>
<div class="admin-card" style="margin-bottom:var(--space-lg);">
    <h2 style="font-size:1rem; margin:0 0 var(--space-sm);">Admin Login</h2>
    <p style="margin:0 0 var(--space-lg); color:var(--color-text-muted); font-size:0.9rem;">These credentials are loaded from <code>.env</code>. Change them before production use.</p>
    <form class="admin-form" method="post" action="/admin/settings/admin-credentials">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="admin-form__row">
            <div class="form-group">
                <label>Admin Username</label>
                <input type="text" name="admin_username" value="<?= e($adminCredentials['username'] ?? '') ?>" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label>Admin Email</label>
                <input type="email" name="admin_email" value="<?= e($adminCredentials['email'] ?? '') ?>" autocomplete="email" required>
            </div>
        </div>
        <div class="form-group">
            <label>Admin Password</label>
            <input type="password" name="admin_password" placeholder="Leave blank to keep current password" autocomplete="new-password">
        </div>
        <button class="btn btn-primary btn-sm">Save Admin Login</button>
    </form>
</div>
<?php $__settingsModules = (new \App\Services\SettingsService())->modules(); ?>
<div class="admin-card">
    <h2>Public contact channels</h2>
    <p>Enter confirmed clinic telephone and WhatsApp numbers separately, one international number per line. The first WhatsApp number is used for the assistant’s optional handoff. Leave a channel blank to hide it.</p>
    <form class="admin-form" method="post" action="/admin/settings/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="contact_revision" value="<?= e(\App\Services\ResourceService::revision($settings)) ?>">
        <label for="clinic-phones">Telephone numbers</label><textarea id="clinic-phones" name="clinic_phones" rows="3"><?= e(implode("\n",is_array($settings['clinic_phones']??null)?$settings['clinic_phones']:[])) ?></textarea>
        <label for="clinic-whatsapp">WhatsApp numbers</label><textarea id="clinic-whatsapp" name="clinic_whatsapp" rows="3"><?= e(implode("\n",is_array($settings['clinic_whatsapp']??null)?$settings['clinic_whatsapp']:[])) ?></textarea>
        <button class="btn btn-primary" type="submit">Save contact channels</button>
    </form>
</div>
<?php
try { $feeCatalogue=\App\Services\ConsultationFeeService::fromSettings($settings,false); }
catch (\Throwable) { $feeCatalogue=null; }
?>
<section class="admin-card" id="consultation-catalogue" aria-labelledby="fee-catalogue-title">
    <h2 id="fee-catalogue-title">Consultation fee catalogue</h2>
    <p>Manage the fees shown on Contact and available to both agents. Save a draft to preview or choose Published to display it. These are informational fees only, not session charges or bookings.</p>
    <?php if ($feeCatalogue===null): ?><p role="alert">Catalogue unavailable. Reload after checking site settings.</p><?php else:
        $feeRevision=\App\Services\ResourceService::revision($settings);
        $feeRows=$feeCatalogue['fees'];
        if (count($feeRows)<\App\Services\ConsultationFeeService::LIMIT) $feeRows[]=['id'=>'','title'=>'','description'=>'','amount_minor'=>0,'currency'=>'INR','min_minutes'=>30,'max_minutes'=>30,'display_order'=>count($feeRows)*10,'status'=>'draft','inclusions'=>[]];
        foreach ($feeRows as $fee):
            $key=$fee['id']?:'new';
    ?>
        <details class="fee-editor"<?= $key==='new'?'':' open' ?>>
            <summary><?= e($key==='new'?'Add consultation fee':$fee['title'].' · '.\App\Services\ConsultationFeeService::price($fee).' · '.ucfirst($fee['status'])) ?></summary>
            <form class="admin-form" method="post" action="/admin/settings/save">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="fee_action" value="save">
                <input type="hidden" name="fee_revision" value="<?= e($feeRevision) ?>"><input type="hidden" name="fee_id" value="<?= e($fee['id']) ?>">
                <div class="admin-form__row">
                    <div class="form-group"><label for="fee-title-<?= e($key) ?>">Title</label><input id="fee-title-<?= e($key) ?>" name="title" value="<?= e($fee['title']) ?>" maxlength="100" required></div>
                    <div class="form-group"><label for="fee-status-<?= e($key) ?>">Visibility</label><select id="fee-status-<?= e($key) ?>" name="status"><option value="draft"<?= $fee['status']==='draft'?' selected':'' ?>>Draft (hidden)</option><option value="published"<?= $fee['status']==='published'?' selected':'' ?>>Published</option></select></div>
                </div>
                <div class="admin-form__row">
                    <div class="form-group"><label for="fee-amount-<?= e($key) ?>">Amount</label><input id="fee-amount-<?= e($key) ?>" name="amount" type="number" min="0" max="9999999.99" step=".01" value="<?= e(\App\Services\ConsultationFeeService::amountInput($fee['amount_minor'])) ?>" required></div>
                    <div class="form-group"><label for="fee-currency-<?= e($key) ?>">Currency</label><select id="fee-currency-<?= e($key) ?>" name="currency"><?php foreach (array_keys(\App\Services\ConsultationFeeService::CURRENCIES) as $currency): ?><option<?= $currency===$fee['currency']?' selected':'' ?>><?= e($currency) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label for="fee-min-<?= e($key) ?>">Minimum minutes</label><input id="fee-min-<?= e($key) ?>" name="min_minutes" type="number" min="1" max="1440" value="<?= (int)$fee['min_minutes'] ?>" required></div>
                    <div class="form-group"><label for="fee-max-<?= e($key) ?>">Maximum minutes</label><input id="fee-max-<?= e($key) ?>" name="max_minutes" type="number" min="1" max="1440" value="<?= (int)$fee['max_minutes'] ?>" required></div>
                </div>
                <label for="fee-description-<?= e($key) ?>">Description</label><textarea id="fee-description-<?= e($key) ?>" name="description" rows="2" maxlength="500"><?= e($fee['description']) ?></textarea>
                <label for="fee-inclusions-<?= e($key) ?>">Specific inclusions (one per line; optional)</label><textarea id="fee-inclusions-<?= e($key) ?>" name="inclusions" rows="3" maxlength="4000"><?= e(implode("\n",$fee['inclusions'])) ?></textarea>
                <label for="fee-order-<?= e($key) ?>">Display order</label><input id="fee-order-<?= e($key) ?>" name="display_order" type="number" min="0" max="9999" value="<?= (int)$fee['display_order'] ?>" required>
                <button type="submit" class="btn btn-primary">Save fee</button>
            </form>
        </details>
    <?php endforeach; ?>
        <form class="admin-form" method="post" action="/admin/settings/save">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="fee_action" value="inclusions"><input type="hidden" name="fee_revision" value="<?= e($feeRevision) ?>">
            <label for="fee-shared-inclusions">General consultation inclusions (one per line)</label><textarea id="fee-shared-inclusions" name="shared_inclusions" rows="6" maxlength="4000"><?= e(implode("\n",$feeCatalogue['inclusions'])) ?></textarea>
            <button type="submit" class="btn btn-primary">Save general inclusions</button>
        </form>
    <?php endif; ?>
</section>
<div class="admin-card">
    <h2>Homepage Foundation</h2>
    <p>Publish a clinic-confirmed minimum historical customer count. This is not the number of website accounts, orders or enquiries. Leave blank to hide the figure. Programmes and practitioner totals are calculated from published records.</p>
    <form class="admin-form" method="post" action="/admin/settings/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="foundation_revision" value="<?= e(\App\Services\ResourceService::revision($settings)) ?>">
        <label for="foundation-customers-min">Customers served (minimum; displayed with +)</label>
        <input id="foundation-customers-min" name="foundation_customers_min" type="number" min="1" max="99999999" step="1" inputmode="numeric" value="<?= e((string)($settings['foundation_customers_min'] ?? '')) ?>">
        <button type="submit" class="btn btn-primary">Save Foundation figure</button>
    </form>
</div>
<div class="admin-card">
    <h2 style="font-size:1rem; margin:0 0 var(--space-sm);">Site Modules</h2>
    <p style="margin:0 0 var(--space-lg); font-size:0.85rem; color:var(--color-text-muted);">
        Switching a module off hides its navigation links and home page section, and makes its
        public pages return 404. Existing data is kept and reappears when you switch it back on.
    </p>
    <form class="admin-form" method="post" action="/admin/settings/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <?php $__mods = (new \App\Services\SettingsService())->modules(); ?>
        <?php foreach (\App\Services\SettingsService::MODULES as $__key => $__label): ?>
            <div class="form-group" style="display:flex; flex-direction:row; align-items:center; justify-content:flex-start; gap:var(--space-sm); margin-bottom:var(--space-sm);">
                <input type="hidden" name="module_<?= e($__key) ?>" value="0">
                <input type="checkbox" id="module_<?= e($__key) ?>" name="module_<?= e($__key) ?>" value="1"
                       style="width:18px; height:18px; flex:0 0 auto; margin:0;" <?= $__mods[$__key] ? 'checked' : '' ?>>
                <label for="module_<?= e($__key) ?>" style="margin:0; font-weight:600; text-transform:none; letter-spacing:0; cursor:pointer;"><?= e($__label) ?></label>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Save Modules</button>
    </form>
</div>
<?php if ($__settingsModules['shop']): ?><div class="admin-card">
    <h2 style="font-size:1rem; margin:0 0 var(--space-lg);">Shipping Configuration</h2>
    <form class="admin-form" method="post" action="/admin/settings/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="admin-form__row">
            <div class="form-group">
                <label>Shipping Mode</label>
                <?php $mode = $settings['shipping_mode'] ?? 'free'; ?>
                <select name="shipping_mode">
                    <option value="flat" <?= $mode === 'flat' ? 'selected' : '' ?>>Flat rate</option>
                    <option value="free" <?= $mode === 'free' ? 'selected' : '' ?>>Free shipping</option>
                </select>
            </div>
            <div class="form-group">
                <label>Flat Rate (₹)</label>
                <input type="number" name="flat_rate" value="<?= e((string)($settings['flat_rate'] ?? 0)) ?>" min="0" step="1">
            </div>
        </div>
        <div class="admin-form__row">
            <div class="form-group">
                <label>Currency</label>
                <input type="text" name="currency" value="<?= e($settings['currency'] ?? 'INR') ?>">
            </div>
            <div class="form-group">
                <label>Timezone</label>
                <input type="text" name="timezone" value="<?= e($settings['timezone'] ?? 'Asia/Kolkata') ?>">
            </div>
        </div>
        <button class="btn btn-primary btn-sm">Save Settings</button>
    </form>
</div>
<div class="admin-card" style="margin-top:var(--space-lg);">
    <h2 style="font-size:1rem; margin:0 0 var(--space-sm);">GST Configuration</h2>
    <p style="margin:0 0 var(--space-lg); font-size:0.85rem; color:var(--color-text-muted);">
        Prices are GST-inclusive. The default rate below applies to any product that does not
        set its own rate on the product form. Confirm the rate and HSN code with your accountant.
    </p>
    <form class="admin-form" method="post" action="/admin/settings/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="admin-form__row">
            <div class="form-group">
                <label>Default GST Rate (%)</label>
                <input type="number" name="default_gst_rate" step="0.01" min="0" max="100"
                       value="<?= e((string)($settings['default_gst_rate'] ?? \App\Services\TaxService::DEFAULT_GST_RATE)) ?>" placeholder="5">
            </div>
            <div class="form-group">
                <label>Default HSN Code</label>
                <input type="text" name="default_hsn_code" maxlength="8"
                       value="<?= e((string)($settings['default_hsn_code'] ?? \App\Services\TaxService::DEFAULT_HSN_CODE)) ?>" placeholder="8306">
            </div>
        </div>
        <div class="admin-form__row">
            <div class="form-group">
                <label>GSTIN</label>
                <input type="text" name="gstin" value="<?= e($settings['gstin'] ?? '') ?>" placeholder="33ABCDE1234F1Z5" maxlength="15">
            </div>
            <div class="form-group">
                <label>State Code</label>
                <input type="text" name="gst_state_code" value="<?= e($settings['gst_state_code'] ?? '33') ?>" placeholder="33" maxlength="2">
            </div>
        </div>
        <div class="admin-form__row">
            <div class="form-group">
                <label>Legal Name</label>
                <input type="text" name="gst_legal_name" value="<?= e($settings['gst_legal_name'] ?? '') ?>" placeholder="Registered business name">
            </div>
            <div class="form-group">
                <label>Trade Name</label>
                <input type="text" name="gst_trade_name" value="<?= e($settings['gst_trade_name'] ?? '') ?>" placeholder="Brand / trade name">
            </div>
        </div>
        <div class="form-group">
            <label>Address</label>
            <textarea name="gst_address" rows="3" placeholder="Registered business address"><?= e($settings['gst_address'] ?? '') ?></textarea>
        </div>
        <div class="admin-form__row">
            <div class="form-group">
                <label>State</label>
                <input type="text" name="gst_state" value="<?= e($settings['gst_state'] ?? 'Tamil Nadu') ?>" placeholder="Tamil Nadu">
            </div>
            <div class="form-group">
                <label></label>
                <span style="font-size:0.8rem; color:var(--color-text-muted); display:block; padding-top:0.5rem;">Used for supply-type determination (intrastate vs interstate).</span>
            </div>
        </div>
        <button class="btn btn-primary btn-sm">Save Settings</button>
    </form>
</div>
<?php endif; ?>

<div class="admin-card">
    <h2>Site identity</h2>
    <p>Manage the public identity shared by the site header, footer, contact details, default search metadata and app manifest. Logo, favicon and colors remain in <a href="/admin/appearance">Appearance</a>; phone and WhatsApp remain in <a href="/admin/settings">Site Settings</a>.</p>
    <p>Existing programme content, page bodies, contact form questions and legal text do not change when you publish an identity.</p>
    <?php if (!empty($profileError)): ?><p class="flash flash--error" role="alert"><?= e($profileError) ?></p><?php endif; ?>
</div>
<?php if (is_array($editor)): $state=$editor['state'];$values=$editor['values']; ?>
<div class="site-profile-workspace">
    <section class="admin-card">
        <h2>Edit draft</h2>
        <p>Save a draft, review the saved preview, then confirm publication. Empty optional values are hidden; they do not inherit another business's details.</p>
        <form class="admin-form" method="post" action="/admin/site-profile/save">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="profile_revision" value="<?= e($editor['revision']) ?>">
            <div class="site-profile-fields">
            <?php foreach (\App\Services\SiteProfileService::FIELDS as $key=>[$label,$limit]): ?>
                <label for="profile-<?= e($key) ?>"><?= e($label) ?>
                    <?php if ($key==='organization_type'): ?>
                        <select id="profile-<?= e($key) ?>" name="profile[<?= e($key) ?>]" required>
                        <?php foreach (['Organization','LocalBusiness','MedicalClinic','ProfessionalService'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= $values[$key]===$type?'selected':'' ?>><?= e($type) ?></option>
                        <?php endforeach; ?></select>
                    <?php elseif (in_array($key,['description','footer_description','address'],true)): ?>
                        <textarea id="profile-<?= e($key) ?>" name="profile[<?= e($key) ?>]" rows="3" maxlength="<?= $limit ?>"><?= e($values[$key]) ?></textarea>
                    <?php else: ?>
                        <input id="profile-<?= e($key) ?>" name="profile[<?= e($key) ?>]" type="<?= $key==='public_email'?'email':(str_ends_with($key,'_url')?'url':'text') ?>" maxlength="<?= $limit ?>" value="<?= e($values[$key]) ?>" <?= in_array($key,['name','short_name','admin_name'],true)?'required':'' ?>>
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            </div>
            <button class="btn btn-primary" name="profile_action" value="save_draft">Save draft</button>
        </form>
    </section>
    <aside class="admin-card site-profile-preview">
        <h2>Published identity</h2>
        <dl><dt>Site name</dt><dd><?= e($publishedProfile['name']) ?></dd><dt>Public email</dt><dd><?= e($publishedProfile['public_email']?:'Not shown') ?></dd><dt>Address</dt><dd><?= e($publishedProfile['address']?:'Not shown') ?></dd></dl>
        <?php if ($state['draft']!==null): ?>
        <h2>Saved draft preview</h2>
        <div class="site-profile-preview__sample">
            <strong><?= e($state['draft']['name']) ?></strong>
            <p><?= e($state['draft']['tagline']) ?></p>
            <p><?= e($state['draft']['description']) ?></p>
            <dl><dt>Footer</dt><dd><?= e($state['draft']['footer_description']?:'Not shown') ?></dd>
            <dt>Email</dt><dd><?= e($state['draft']['public_email']?:'Not shown') ?></dd>
            <dt>Address</dt><dd><?= e($state['draft']['address']?:'Not shown') ?></dd></dl>
        </div>
        <details>
            <summary>Review every changed field</summary>
            <dl>
            <?php foreach (\App\Services\SiteProfileService::FIELDS as $field=>[$label,$limit]): ?>
                <?php if (($publishedProfile[$field]??'')!==$state['draft'][$field]): ?>
                    <dt><?= e($label) ?></dt>
                    <dd>Published: <?= e(($publishedProfile[$field]??'')?:'Not shown') ?><br>Draft: <?= e($state['draft'][$field]?:'Not shown') ?></dd>
                <?php endif; ?>
            <?php endforeach; ?>
            </dl>
        </details>
        <form method="post" action="/admin/site-profile/save" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="profile_revision" value="<?= e($editor['revision']) ?>">
            <label><input type="checkbox" name="confirm" value="1" required> Apply the selected action to this saved draft.</label>
            <button class="btn btn-primary" name="profile_action" value="publish">Publish saved draft</button>
            <button class="btn btn-outline" name="profile_action" value="discard">Discard draft</button>
        </form>
        <?php else: ?><p>No unpublished draft.</p><?php endif; ?>
        <?php if ($state['can_restore']): ?>
        <form method="post" action="/admin/site-profile/save" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="profile_revision" value="<?= e($editor['revision']) ?>">
            <label><input type="checkbox" name="confirm" value="1" required> Restore the preceding published identity and discard any draft.</label>
            <button class="btn btn-outline" name="profile_action" value="restore">Restore previous identity</button>
        </form>
        <?php endif; ?>
    </aside>
</div>
<?php endif; ?>

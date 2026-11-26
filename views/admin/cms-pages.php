<?php use App\Services\{CmsPageService,CmsSectionRegistry}; ?>
<div class="admin-card">
    <h2>Native pages</h2><p>Create independent pages at <code>/pages/your-slug</code>. Saving or arranging sections changes a private draft only. The existing website and published pages stay unchanged until you publish.</p>
    <?php if ($pageError!==''): ?><p class="flash flash--error" role="alert"><?= e($pageError) ?></p><?php endif; ?>
    <?php if ($schemaReady===false): ?>
        <p role="status">Page storage is not yet initialized. Existing website content and media remain available. Initialize only the new page collection to enable authoring.</p>
        <form class="cms-editor-form" method="post" action="/admin/pages/save">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="page_action" value="initialize">
            <label class="cms-confirm"><input type="checkbox" name="confirm" value="1" required> Initialize native page storage without replacing existing records.</label>
            <button class="btn btn-primary" type="submit">Initialize page storage</button>
        </form>
    <?php elseif ($schemaReady===true && $pageError===''): ?>
    <div class="cms-page-list">
    <?php foreach ($pages as $item): ?>
        <a href="/admin/pages?slug=<?= e($item['slug']) ?>"><?= e($item['title']) ?> <small><?= $item['published']?'Published':'Unpublished' ?><?= $item['draft']?' · Draft':'' ?></small></a>
    <?php endforeach; ?>
    </div>
    <form class="cms-editor-form" method="post" action="/admin/pages/save">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="page_action" value="create">
        <div class="cms-editor-fields"><label>New page title<input name="title" maxlength="120" required></label><label>Permanent slug<input name="slug" maxlength="36" pattern="[a-z0-9]+(-[a-z0-9]+)*" required></label></div>
        <button class="btn btn-primary" type="submit">Create draft</button>
    </form>
    <?php endif; ?>
</div>
<?php if ($editor!==null): $state=$editor['state']; $values=CmsPageService::editDocument($state); ?>
<div class="cms-page-workspace">
    <div class="admin-card">
        <h2>Edit page</h2><p><code>/pages/<?= e($state['id']) ?></code> · <?= $state['published']!==null?'Published':'Unpublished' ?></p>
        <form class="cms-editor-form" method="post" action="/admin/pages/save">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="slug" value="<?= e($state['id']) ?>">
            <input type="hidden" name="revision" value="<?= e($editor['revision']) ?>">
            <label>Page title<input name="page[title]" maxlength="120" value="<?= e($values['title']) ?>" required></label>
            <label>Page description<textarea name="page[description]" maxlength="300" rows="3"><?= e($values['description']) ?></textarea></label>
            <?php foreach ($values['sections'] as $i=>$section): $prefix='page[sections]['.$i.']'; $type=$section['type']; ?>
                <fieldset class="cms-editor-section">
                    <legend><?= $i+1 ?>. <?= e(CmsSectionRegistry::TYPES[$type]['label']) ?></legend>
                    <input type="hidden" name="<?= e($prefix) ?>[id]" value="<?= e($section['id']) ?>">
                    <input type="hidden" name="<?= e($prefix) ?>[type]" value="<?= e($type) ?>">
                    <label>Visibility<select name="<?= e($prefix) ?>[visible]"><option value="1" <?= $section['visible']?'selected':'' ?>>Visible</option><option value="0" <?= !$section['visible']?'selected':'' ?>>Hidden</option></select></label>
                    <?php foreach (CmsSectionRegistry::TYPES[$type]['fields'] as $field=>$label): ?>
                        <label><?= e($label) ?>
                        <?php if ($field==='display_limit'): ?><select name="<?= e($prefix) ?>[content][display_limit]"><?php for ($count=1;$count<=12;$count++): ?><option value="<?= $count ?>" <?= (string)$count===$section['content']['display_limit']?'selected':'' ?>><?= $count ?></option><?php endfor; ?></select>
                        <?php elseif ($field==='body'): ?><textarea name="<?= e($prefix) ?>[content][body]" rows="5" maxlength="6000"><?= e($section['content']['body']) ?></textarea>
                        <?php else: ?><input name="<?= e($prefix) ?>[content][<?= e($field) ?>]" maxlength="<?= CmsSectionRegistry::LIMITS[$field] ?>" value="<?= e($section['content'][$field]) ?>"><?php endif; ?>
                        </label>
                        <?php if ($field==='image_url'): ?><small>Use a local raster image path from the <a href="/admin/media" target="_blank" rel="noopener">media library</a>.</small><?php endif; ?>
                    <?php endforeach; ?>
                    <div class="cms-actions">
                        <?php foreach (['up'=>'Move up','down'=>'Move down','duplicate'=>'Duplicate','remove'=>'Remove'] as $op=>$label): ?>
                            <button type="submit" class="btn btn-outline" name="page_action" value="<?= e($op.':'.$section['id']) ?>" <?= ($op==='up'&&$i===0)||($op==='down'&&$i===count($values['sections'])-1)||($op==='duplicate'&&count($values['sections'])>=CmsSectionRegistry::LIMIT)?'disabled':'' ?>><?= e($label) ?></button>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>
            <div class="cms-actions"><button class="btn btn-primary" type="submit" name="page_action" value="save">Save draft</button></div>
            <h3>Add a section</h3>
            <div class="cms-actions"><?php foreach (CmsSectionRegistry::TYPES as $type=>$definition): ?><button class="btn btn-outline" name="page_action" value="add:<?= e($type) ?>" type="submit" <?= count($values['sections'])>=CmsSectionRegistry::LIMIT?'disabled':'' ?>>+ <?= e($definition['label']) ?></button><?php endforeach; ?></div>
            <p><small>Section actions save the current valid fields. Live sections read current published service records; they never duplicate or publish those records. Preview shows only saved visible sections. Maximum <?= CmsSectionRegistry::LIMIT ?> sections.</small></p>
        </form>
    </div>
    <aside class="admin-card cms-preview">
        <h2>Saved <?= $state['draft']!==null?'draft':'publication' ?> preview</h2>
        <?php if ($state['published']!==null): ?><p><a href="/pages/<?= e($state['id']) ?>" target="_blank" rel="noopener">Open published page ↗</a></p><?php endif; ?>
        <?php $document=$values;$preview=true; require __DIR__.'/../partials/cms-page-document.php'; ?>
        <?php $actions=[]; if ($state['draft']!==null) {$actions['publish']='Publish saved draft';$actions['discard']='Discard draft';} if ($state['published']!==null) $actions['unpublish']='Unpublish'; if ($state['can_restore']) $actions['restore']='Restore previous publication'; ?>
        <?php if ($actions): ?>
            <form class="cms-editor-form" method="post" action="/admin/pages/save">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <input type="hidden" name="slug" value="<?= e($state['id']) ?>">
                <input type="hidden" name="revision" value="<?= e($editor['revision']) ?>">
                <label class="cms-confirm"><input type="checkbox" name="confirm" value="1" required> Confirm the selected action using saved content. Restore discards any draft.</label>
                <div class="cms-actions"><?php foreach ($actions as $action=>$label): ?><button class="btn <?= $action==='publish'?'btn-primary':'btn-outline' ?>" type="submit" name="page_action" value="<?= e($action) ?>"><?= e($label) ?></button><?php endforeach; ?></div>
            </form>
        <?php endif; ?>
    </aside>
</div>
<?php endif; ?>

<div class="admin-card">
    <h2>Upload Media</h2>
    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="admin-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="admin-form__row">
            <label>Context
                <select name="context">
                    <option value="shared">Shared</option>
                    <?php if (module_on('shop')): ?><option value="products">Products</option><?php endif; ?>
                    <option value="campaigns">Campaigns</option>
                    <option value="consultants">Consultants</option>
                </select>
            </label>
            <label>Description
                <input type="text" name="description" placeholder="e.g. Product hero image" maxlength="255">
            </label>
            <label>Files
                <input type="file" name="media_files[]" accept="image/png,image/jpeg,image/webp,image/gif" multiple required>
            </label>
        </div>
        <button class="btn btn-primary">Upload</button>
    </form>
</div>

<div class="admin-card">
    <div class="admin-media-picker__head">
        <h2><?= $archived ? 'Archived uploads' : 'Media Library' ?></h2>
        <span><?= count($items) ?> file<?= count($items) === 1 ? '' : 's' ?> · newest first</span>
    </div>
    <a class="btn btn-sm btn-ghost" href="<?= $archived ? '/admin/media' : '/admin/media?view=archive' ?>"><?= $archived ? 'Back to library' : 'View archived uploads' ?></a>
    <?php if(empty($items)): ?>
        <p><?= $archived ? 'No archived uploads.' : 'No media files yet.' ?></p>
    <?php else: ?>
        <form method="post" action="/admin/media/archive" data-media-selection>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="action" value="<?= $archived ? 'restore' : 'archive' ?>">
        <?php if (in_array(true, array_column($items, 'selectable'), true)): ?>
        <fieldset class="admin-media-actions">
            <legend><?= $archived ? 'Restore uploads' : 'Archive uploads' ?></legend>
            <?php if (!$archived): ?><p>Archived uploads leave the library. Existing image links keep working.</p><?php endif; ?>
            <label hidden data-upload-filter><input type="checkbox"> Show selectable uploads only</label>
            <output aria-live="polite" data-selection-count></output>
            <label><input type="checkbox" name="confirm_change" value="1" required> Confirm <?= $archived ? 'restoration' : 'archiving' ?> of selected uploads</label>
            <button class="btn btn-secondary" type="submit"><?= $archived ? 'Restore' : 'Archive' ?> selected uploads</button>
        </fieldset>
        <?php endif; ?>
        <div class="admin-media-grid">
            <?php foreach($items as $media): ?>
                <div class="admin-media-tile">
                    <img loading="lazy" src="<?= e($media['url'] ?? $media['path']) ?>" alt="<?= e($media['original_name'] ?? $media['filename'] ?? 'Media') ?>">
                    <?php if ($media['selectable']): ?>
                    <label><input type="checkbox" name="media_ids[]" value="<?= e($media['id']) ?>">
                        Select <span class="sr-only"><?= e($media['original_name'] ?? $media['filename']) ?></span>
                    </label>
                    <?php else: ?>
                    <small><?= $media['in_use'] ? 'In use' : 'Library asset' ?></small>
                    <?php endif; ?>
                    <strong><?= e($media['original_name'] ?? $media['filename'] ?? 'Media') ?></strong>
                    <?php if(!empty($media['description'])): ?>
                        <small class="media-desc"><?= e($media['description']) ?></small>
                    <?php endif; ?>
                    <span class="badge badge--info"><?= e($media['context'] ?? 'shared') ?></span>
                    <span><?= e(substr((string)($media['created_at'] ?? ''), 0, 10)) ?></span>
                    <code><?= e($media['url'] ?? $media['path']) ?></code>
                    <?php if(!empty($media['used_in'])): ?>
                        <details>
                            <summary>Used in <?= count($media['used_in']) ?> place(s)</summary>
                            <ul>
                                <?php foreach($media['used_in'] as $u): ?>
                                    <li><a href="/admin/<?= e($u['type'] ?? '') ?>"><?= e($u['name'] ?? $u['id'] ?? '') ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        </form>
        <script src="/assets/media-selection.js?v=<?= filemtime(app_path('assets/media-selection.js')) ?>" defer></script>
    <?php endif; ?>
</div>

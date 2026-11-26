<?php
// Caller passes only validated native projection and the known section type.
?>
<?php if ($dynamic['state']==='disabled'): ?>
    <?php if (!empty($preview)): ?><p role="status">This service module is disabled. The section is hidden on the public page.</p><?php endif; ?>
<?php elseif ($dynamic['state']==='unavailable'): ?>
    <p role="status">Current published information is temporarily unavailable.</p>
<?php elseif ($dynamic['state']==='empty'): ?>
    <p>No matching published entries are currently available.</p>
<?php elseif ($dynamic['state']==='ready'): ?>
    <div class="cms-live-grid">
    <?php foreach ($dynamic['rows'] as $entry): ?>
        <div class="cms-live-item">
            <?php if ($section['type']==='team'): ?>
                <<?= $itemTag ?>><?= e($entry['name']) ?></<?= $itemTag ?>>
                <?php if ($entry['speciality']!==''): ?><p><?= e($entry['speciality']) ?></p><?php endif; ?>
                <?php if ($entry['credentials']!==''): ?><p><?= e($entry['credentials']) ?></p><?php endif; ?>
                <?php if ($entry['registration_number']!==''): ?><p>Registration: <?= e($entry['registration_number']) ?></p><?php endif; ?>
            <?php elseif ($section['type']==='programmes'): ?>
                <<?= $itemTag ?>><?= e($entry['title']) ?></<?= $itemTag ?>>
                <?php if ($entry['duration']!==''): ?><p class="cms-live-detail"><?= e($entry['duration']) ?></p><?php endif; ?>
                <?php if ($entry['summary']!==''): ?><p><?= e($entry['summary']) ?></p><?php endif; ?>
                <a href="<?= e($entry['url']) ?>">View programme <span aria-hidden="true">↗</span><span class="sr-only">: <?= e($entry['title']) ?></span></a>
            <?php elseif ($section['type']==='fee_table'): ?>
                <<?= $itemTag ?>><?= e($entry['title']) ?></<?= $itemTag ?>>
                <p class="cms-live-price"><?= e($entry['display_price']) ?></p>
                <p class="cms-live-detail"><?= e($entry['duration']) ?></p>
                <?php if ($entry['description']!==''): ?><p><?= e($entry['description']) ?></p><?php endif; ?>
                <?php if ($entry['inclusions']): ?><ul><?php foreach ($entry['inclusions'] as $inclusion): ?><li><?= e($inclusion) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <?php if ($dynamic['inclusions']): ?><<?= $itemTag ?>>Shared inclusions</<?= $itemTag ?>><ul><?php foreach ($dynamic['inclusions'] as $inclusion): ?><li><?= e($inclusion) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <?php if ($dynamic['scope']!==''): ?><p class="cms-live-note"><?= e($dynamic['scope']) ?></p><?php endif; ?>
    <?php if ($dynamic['total']>count($dynamic['rows'])): ?><p>Showing <?= count($dynamic['rows']) ?> of <?= (int)$dynamic['total'] ?> published entries.</p><?php endif; ?>
    <p><a href="<?= e($dynamic['source']) ?>">View source information</a></p>
<?php endif; ?>

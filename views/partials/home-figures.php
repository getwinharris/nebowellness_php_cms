<dl class="nebo-home-figures">
    <?php foreach ($homeFigures as [$count, $name, $description]): ?>
        <?php $formattedCount = number_format($count); ?>
        <div>
            <dt><?= e($name) ?></dt>
            <dd class="nebo-home-figures__value">
                <span aria-hidden="true" data-home-count="<?= $count ?>"><?= $formattedCount ?></span>
                <span class="sr-only"><?= $formattedCount ?></span>
            </dd>
            <dd class="nebo-home-figures__description"><?= e($description) ?></dd>
        </div>
    <?php endforeach; ?>
</dl>

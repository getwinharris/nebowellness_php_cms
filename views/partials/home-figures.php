<dl class="nebo-home-figures">
    <?php foreach ($homeFigures as $figure):
        [$count, $name, $description] = $figure;
        $suffix = ($figure[3] ?? '') === '+' ? '+' : '';
        $careIcon = $figure[4] ?? '';
        $formattedCount = number_format($count);
    ?>
        <div>
            <dt><?= e($name) ?></dt>
            <dd class="nebo-home-figures__value">
                <?php if ($careIcon !== '') require __DIR__ . '/care-icon.php'; ?>
                <span class="nebo-home-figures__digits<?= strlen($formattedCount) > 5 ? ' nebo-home-figures__digits--long' : '' ?>"><span aria-hidden="true" data-home-count="<?= $count ?>"><?= $formattedCount ?></span><?php if ($suffix !== ''): ?><span class="nebo-home-figures__suffix" aria-hidden="true"><?= e($suffix) ?></span><?php endif; ?></span>
                <span class="sr-only"><?= e($formattedCount . $suffix) ?></span>
            </dd>
            <dd class="nebo-home-figures__description"><?= e($description) ?></dd>
        </div>
    <?php endforeach; ?>
</dl>

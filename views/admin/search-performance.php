<header class="admin-card search-performance-prompt">
    <h2>Reporting period</h2>
    <form method="get" action="/admin/search-performance" class="report-period">
        <label>Date range
            <select name="days">
                <?php foreach ([7, 28, 90] as $length): ?>
                <option value="<?= $length ?>" <?= $days === $length ? 'selected' : '' ?>>Previous <?= $length ?> days</option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit" class="btn btn-sm btn-ghost">Apply</button>
    </form>
</header>
<?php
$states = [
    'setup'=>'Set a property and reporting key in Integrations.',
    'access'=>'Google denied this request. Check the property, API access and service account key.',
    'unavailable'=>'Google could not provide a report. Try again later.',
    'stale'=>'The latest request failed. Showing the last successful report.',
    'empty'=>'Google returned no rows for this period.',
    'connected'=>'Connected',
];
foreach (['analytics'=>'Google Analytics 4', 'search'=>'Search Console'] as $key=>$title):
    $report = $reports[$key];
?>
<section class="admin-card search-report">
    <h2><?= e($title) ?></h2>
    <?php if ($report['property'] !== ''): ?><p class="report-property"><?= e($report['property']) ?></p><?php endif; ?>
    <p><?= e($report['dates']['startDate']) ?> to <?= e($report['dates']['endDate']) ?> · <?= e($states[$report['state']]) ?></p>
    <?php if ($report['fetched'] !== null): ?><p>Retrieved <?= e(gmdate('Y-m-d H:i', $report['fetched'])) ?> UTC</p><?php endif; ?>
    <?php if (in_array($report['state'], ['setup', 'access'], true)): ?><a href="/admin/integrations#google-reporting">Manage Google reporting</a><?php endif; ?>
    <?php if ($report['data'] !== null): ?>
        <dl class="report-metrics">
            <?php foreach ($report['data']['totals'] as $label=>$value): ?>
                <div><dt><?= e($label) ?></dt><dd><?= e((string)$value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <?php foreach ($report['data']['tables'] as $label=>$rows): if (!$rows) continue; ?>
            <h3><?= e($label) ?></h3>
            <ol class="report-ranking">
                <?php foreach ($rows as [$name, $value]): ?>
                    <li><span><?= e($name) ?></span> <strong><?= (int)$value ?></strong></li>
                <?php endforeach; ?>
            </ol>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php endforeach; ?>

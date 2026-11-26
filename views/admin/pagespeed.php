<?php use App\Integrations\GoogleSiteKit\PageSpeedClient; ?>
<section class="admin-card">
    <h2>Website performance</h2>
    <p>Analyze a public page with Google PageSpeed Insights. Lab measurements are simulated by Lighthouse; field measurements, when available, come from real-user data. They are not interchangeable.</p>
    <p>Configured site: <?= e($origin) ?> · <?= $keyConfigured?'API key saved':'No API key configured; public quota may apply' ?> · <a href="/admin/integrations">Manage integrations</a></p>
    <form method="post" action="/admin/pagespeed/run" class="cms-editor-form">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="cms-editor-fields">
            <label>Public page<select name="path"><?php foreach (PageSpeedClient::PATHS as $path=>$label): ?><option value="<?= e($path) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
            <label>Device<select name="strategy"><?php foreach (PageSpeedClient::STRATEGIES as $strategy=>$label): ?><option value="<?= e($strategy) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
        </div>
        <button class="btn btn-primary" type="submit">Run PageSpeed report</button>
    </form>
    <p>Only a selected public page is sent to Google. Results are cached for this owner session; recent successful reports are reused.</p>
</section>
<?php
$messages=['ready'=>'Report available','stale'=>'Previous report — refresh failed or report is no longer fresh','access'=>'Google denied the request or configuration is invalid','quota'=>'Google quota exceeded; try later','unavailable'=>'Report unavailable; no performance score inferred'];
$metricList=static function(array $rows):void {
    if ($rows===[]) {echo '<p>No data available for this scope.</p>';return;}
    echo '<dl class="report-metrics">';
    foreach ($rows as $row) {
        echo '<div><dt>'.e($row['label']).'</dt><dd>'.e((string)round($row['value'],3)).' '.e($row['unit']).'</dd>';
        if (!empty($row['category'])&&$row['category']!=='NONE') echo '<small>'.e($row['category']).'</small>';
        echo '</div>';
    }
    echo '</dl>';
};
?>
<?php foreach (PageSpeedClient::PATHS as $path=>$label): foreach (PageSpeedClient::STRATEGIES as $strategy=>$device):
    $report=$reports[$path.'|'.$strategy]??null;if ($report===null) continue;
?>
<section class="admin-card search-report">
    <h2><?= e($label) ?> — <?= e($device) ?></h2>
    <p role="status"><?= e($messages[$report['state']]??'Report unavailable') ?></p>
    <?php if (is_array($report['data']??null)): $data=$report['data']; ?>
        <p><?= e($data['target']) ?> · Google analysis <?= e(gmdate('Y-m-d H:i',$data['analysis_at'])) ?> UTC</p>
        <h3>Lighthouse performance</h3><dl class="report-metrics"><div><dt>Score</dt><dd><?= e((string)$data['score']) ?> / 100</dd></div></dl>
        <?php $metricList($data['lab']); ?>
        <h3>Real-user data — page</h3><?php $metricList($data['page_field']); ?>
        <h3>Real-user data — origin</h3><?php $metricList($data['origin_field']); ?>
    <?php endif; ?>
</section>
<?php endforeach; endforeach; ?>
<?php if ($reports===[]): ?><section class="admin-card"><p>No reports requested in this session. Choose a page and device above.</p></section><?php endif; ?>

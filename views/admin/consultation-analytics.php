<?php
$total = (int) ($metrics['total_sessions'] ?? 0);
$statusRows = [
    'Accepted or progressed' => (int) ($metrics['accepted_sessions'] ?? 0),
    'Completed' => (int) ($metrics['completed_sessions'] ?? 0),
];
$detailRows = [
    'Messages' => number_format((int) ($metrics['message_count'] ?? 0)),
    'Credits spent' => number_format((int) ($metrics['credits_spent'] ?? 0)),
    'Average completed session' => !empty($metrics['completed_sessions']) ? number_format((int) ($metrics['average_duration_seconds'] ?? 0)) . ' sec' : '—',
    'Average response' => ($metrics['average_response_seconds'] ?? null) !== null ? number_format((int) $metrics['average_response_seconds']) . ' sec' : '—',
];
?>
<div class="analytics-overview">
    <section class="admin-card analytics-summary" aria-labelledby="analytics-title">
        <div class="analytics-total">
            <h2 id="analytics-title">Consultation history</h2>
            <strong><?= e(number_format($total)) ?></strong>
            <p>Retained sessions · all time</p>
        </div>
        <div>
            <h2>Session status</h2>
            <?php if ($total > 0): ?>
            <p>Share of retained sessions. Completed sessions also count in the first row.</p>
            <ul class="analytics-bars">
                <?php foreach ($statusRows as $label => $count):
                    $share = max(0, min(100, 100 * $count / $total));
                ?>
                <li>
                    <div class="analytics-bar-label"><span><?= e($label) ?></span><strong><?= e(number_format($count)) ?> · <?= e(number_format($share, 1)) ?>%</strong></div>
                    <span class="analytics-bar-track" aria-hidden="true"><span class="analytics-bar-fill" style="width:<?= e(number_format($share, 2, '.', '')) ?>%"></span></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p>No retained sessions to compare yet.</p>
            <?php endif; ?>
        </div>
    </section>
    <section class="admin-card" aria-labelledby="analytics-details-title">
        <h2 id="analytics-details-title">Session activity and timing</h2>
        <p>Historical appointment records only. Public consultation booking is retired.</p>
        <dl class="analytics-details">
            <?php foreach ($detailRows as $label => $value): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>
</div>

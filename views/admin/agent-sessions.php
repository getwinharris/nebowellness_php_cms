<div class="admin-card crm-heading">
    <div><h2>Agent sessions</h2><p>Private records of visible messages and observed tool/provider activity. Support sessions are saved only when shared; owner sessions are archived automatically. Records older than 30 days are hidden. New conversations use private JSON files outside the web root. Legacy remote records remain readable within their retention window.</p></div>
    <form method="post" action="/admin/agent-sessions/purge"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><label><input type="checkbox" name="confirm" value="1" required> Confirm expired private-file cleanup</label> <button class="btn btn-outline" type="submit">Clean up expired files</button></form>
</div>
<?php if ($unavailable): ?><div class="admin-card">One or more session archive sources are unavailable. Any displayed list may be incomplete.</div><?php endif; ?>
<div class="crm-workspace crm-workspace--two">
    <section class="admin-card crm-queue" aria-label="Archived sessions"><h3>Recent sessions <small>Up to 100</small></h3>
        <?php if (!$items&&!$unavailable): ?><p>No shared sessions yet.</p><?php endif; ?>
        <?php foreach ($items as $item): ?>
            <a class="crm-ticket<?= ($selected['id']??'')===$item['id']?' is-selected':'' ?>" href="?session=<?= e((string)$item['id']) ?>"><strong><?= e(ucfirst((string)($item['surface']??'agent'))) ?> · <?= e((string)($item['actor_type']??'')) ?></strong><span><?= (int)($item['turns']??0) ?> recorded turns</span><small><?= e((string)($item['updated_at']??'')) ?></small></a>
        <?php endforeach; ?>
    </section>
    <section class="admin-card crm-conversation">
        <?php if ($selected): ?>
            <div class="crm-conversation-head"><div><h3><?= e(ucfirst((string)$selected['surface'])) ?> session</h3><small><?= e((string)$selected['id']) ?></small></div><a class="btn btn-outline" href="/admin/agent-sessions/<?= e((string)$selected['id']) ?>/json">Download JSON</a></div>
            <p class="crm-muted"><?= e((string)($selected['scope']??'')) ?></p>
            <?php if (!empty($selected['prior_history_unavailable']) || !empty($selected['omitted_turns'])): ?><p role="status">Some conversation history is unavailable or exceeds recording limits. The export marks this explicitly.</p><?php endif; ?>
            <?php foreach ($selected['events']??[] as $event): ?>
                <article class="crm-turn"><small><?= e((string)($event['at']??'')) ?> · <?= e((string)($event['outcome']??'unknown')) ?> · HTTP <?= (int)($event['http_status']??0) ?></small>
                    <div class="crm-message"><strong>Question</strong><p><?= nl2br(e((string)($event['question']??''))) ?></p></div>
                    <div class="crm-message crm-message--reply"><strong>Visible response</strong><p><?= nl2br(e((string)($event['reply']??''))) ?></p></div>
                    <details><summary>Observed activity · <?= count($event['trace']['steps']??[]) ?> steps<?= !empty($event['trace']['omitted_steps'])?' (additional steps omitted)':'' ?></summary>
                        <small>Trace <?= e((string)($event['trace']['trace_id']??'')) ?></small>
                        <?php foreach ($event['trace']['steps']??[] as $step): ?><div class="crm-timeline-event"><strong><?= e((string)($step['kind']??'')) ?> · <?= e((string)($step['name']??'')) ?> <?= e((string)($step['model']??'')) ?></strong><span><?= e((string)($step['outcome']??'')) ?> · <?= (int)($step['duration_ms']??0) ?> ms<?= isset($step['origin'])?' · '.e((string)$step['origin']):'' ?><?= isset($step['total_tokens'])?' · '.(int)$step['total_tokens'].' tokens':'' ?></span></div><?php endforeach; ?>
                    </details>
                </article>
            <?php endforeach; ?>
        <?php else: ?><p>Select a session to inspect its actual conversation and execution activity.</p><?php endif; ?>
    </section>
</div>

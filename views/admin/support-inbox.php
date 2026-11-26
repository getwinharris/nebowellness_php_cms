<div class="admin-card crm-heading">
    <div><h2>Support inbox</h2><p>Manage requests, review the conversation and add private notes.</p></div>
    <a class="btn btn-outline" href="/admin/customers">Customers</a>
    <a class="btn btn-outline" href="/admin/agent-sessions">Agent sessions</a>
</div>
<nav class="crm-filters" aria-label="Ticket status">
    <?php foreach ($counts as $key=>$count): ?>
        <a class="crm-filter<?= $filter===$key?' is-current':'' ?>" href="?status=<?= e($key) ?>&amp;q=<?= e(rawurlencode($search)) ?>"<?= $filter===$key?' aria-current="page"':'' ?>><?= e(ucfirst($key)) ?> <strong><?= (int)$count ?></strong></a>
    <?php endforeach; ?>
</nav>
<form class="crm-search" method="get">
    <input type="hidden" name="status" value="<?= e($filter) ?>"><label for="ticket-search">Search tickets</label>
    <div><input id="ticket-search" name="q" value="<?= e($search) ?>" placeholder="Email, reference or message"><button class="btn btn-primary" type="submit">Search</button></div>
</form>
<div class="crm-workspace">
    <section class="admin-card crm-queue" aria-label="Ticket queue">
        <h3>Tickets <small>Up to 100 results</small></h3>
        <?php if (!$tickets): ?><p>No matching tickets.</p><?php endif; ?>
        <?php foreach ($tickets as $ticket): ?>
            <a class="crm-ticket<?= ($selected['id']??'')===$ticket['id']?' is-selected':'' ?>" href="?status=<?= e($filter) ?>&amp;q=<?= e(rawurlencode($search)) ?>&amp;ticket=<?= e($ticket['id']) ?>"<?= ($selected['id']??'')===$ticket['id']?' aria-current="page"':'' ?>>
                <strong><?= e((string)($ticket['customer_email']??'Guest')) ?></strong>
                <span><?= e(mb_substr((string)$ticket['message'],0,100)) ?></span>
                <small><?= e(ucfirst((string)($ticket['status']??'open'))) ?> · <?= e(substr((string)($ticket['created_at']??''),0,10)) ?></small>
            </a>
        <?php endforeach; ?>
    </section>
    <section class="admin-card crm-conversation" aria-label="Selected ticket">
        <?php if ($selected): $revision=\App\Services\ResourceService::revision($selected); $id=(string)$selected['id']; ?>
            <div class="crm-conversation-head"><div><small>Ticket <?= e($id) ?></small><h3><?= e((string)($selected['customer_email']??'Guest')) ?></h3></div><span class="agent-chip"><?= e(ucfirst((string)($selected['status']??'open'))) ?></span></div>
            <div class="crm-message"><strong>Customer request</strong><p><?= nl2br(e((string)($selected['message']??''))) ?></p></div>
            <?php if (!empty($selected['agent_note'])): ?><div class="crm-message crm-message--note"><strong>Agent handoff note · private</strong><p><?= nl2br(e((string)$selected['agent_note'])) ?></p></div><?php endif; ?>
            <?php if (!empty($selected['reply'])): ?><div class="crm-message crm-message--reply"><strong>Clinic reply</strong><p><?= nl2br(e((string)$selected['reply'])) ?></p></div><?php endif; ?>
            <?php foreach (($selected['internal_notes']??[]) as $note): if (!is_array($note)) continue; ?>
                <div class="crm-message crm-message--note"><strong>Internal note · <?= e((string)($note['at']??'')) ?></strong><p><?= nl2br(e((string)($note['text']??''))) ?></p></div>
            <?php endforeach; ?>
            <?php if (!empty($selected['session_id'])): ?><div class="crm-session-link">
                <?php if ($session): ?><a href="/admin/agent-sessions?session=<?= e((string)$session['id']) ?>">View conversation and tool activity</a> · <a href="/admin/agent-sessions/<?= e((string)$session['id']) ?>/json">Download JSON</a>
                <?php else: ?><p><?= $archiveError?'Session archive unavailable.':'Session archive not available or expired.' ?></p><?php endif; ?>
            </div><?php endif; ?>
            <div class="crm-compose-grid">
                <?php foreach (['reply'=>'Customer reply','note'=>'Internal note'] as $action=>$label): ?>
                    <form class="admin-form" method="post" action="/admin/support-tickets/save">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="revision" value="<?= e($revision) ?>"><input type="hidden" name="action" value="<?= e($action) ?>">
                        <label for="ticket-<?= e($action) ?>"><?= e($label) ?></label>
                        <textarea id="ticket-<?= e($action) ?>" name="value" rows="4" maxlength="<?= $action==='note'?4000:8000 ?>" required placeholder="<?= $action==='note'?'Only clinic staff can see this.':'Customer can track this reply in Support.' ?>"></textarea>
                        <button class="btn <?= $action==='reply'?'btn-primary':'btn-outline' ?>" type="submit"><?= $action==='reply'?'Save customer reply':'Add private note' ?></button>
                    </form>
                <?php endforeach; ?>
            </div>
            <form class="crm-status-form" method="post" action="/admin/support-tickets/save">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><input type="hidden" name="id" value="<?= e($id) ?>"><input type="hidden" name="revision" value="<?= e($revision) ?>"><input type="hidden" name="action" value="status">
                <label for="ticket-status">Status</label><select id="ticket-status" name="value"><?php foreach (['open','answered','closed'] as $state): ?><option value="<?= e($state) ?>"<?= ($selected['status']??'open')===$state?' selected':'' ?>><?= e(ucfirst($state)) ?></option><?php endforeach; ?></select><button class="btn btn-outline" type="submit">Update</button>
            </form>
        <?php else: ?><p>Select a ticket to review its details.</p><?php endif; ?>
    </section>
    <aside class="admin-card crm-context">
        <h3>Customer context</h3>
        <?php if ($selected && !empty($selected['customer_email'])): ?>
            <a href="/admin/customers?email=<?= e(rawurlencode((string)$selected['customer_email'])) ?>">View customer timeline ↗</a>
            <p class="crm-muted">Records with the same reported email. This is not proof of identity.</p>
            <?php foreach (array_slice($timeline,0,12) as $event): ?><div class="crm-timeline-event"><strong><?= e($event['label']) ?></strong><span><?= e($event['status']) ?> · <?= e(substr($event['date'],0,10)) ?></span></div><?php endforeach; ?>
            <?php if (!$timeline): ?><p>No associated records found, or context unavailable.</p><?php endif; ?>
        <?php else: ?><p>Select a ticket to see related records.</p><?php endif; ?>
    </aside>
</div>

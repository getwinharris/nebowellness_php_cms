<div class="admin-card crm-heading"><div><h2>Customers and enquiries</h2><p>Search recent accounts, contacts, tickets and therapy requests. Guest emails are reported addresses, not verified accounts. This directory does not merge or modify identities.</p></div><a class="btn btn-outline" href="/admin/support-tickets">Support inbox</a></div>
<?php if ($unavailable): ?><div class="admin-card">Customer context is temporarily unavailable.</div><?php endif; ?>
<form method="get" class="crm-search"><label for="customer-search">Search by name or email</label><div><input id="customer-search" name="q" value="<?= e($search) ?>"><button class="btn btn-primary" type="submit">Search</button></div></form>
<div class="crm-workspace crm-workspace--two">
    <section class="admin-card crm-queue"><h3>Recent contacts <small>Up to 100 results; last 300 records per source</small></h3>
        <?php foreach ($items as $person): ?><a class="crm-ticket<?= $email===$person['email']?' is-selected':'' ?>" href="?email=<?= e(rawurlencode($person['email'])) ?>&amp;q=<?= e(rawurlencode($search)) ?>"><strong><?= e($person['name']?:$person['email']) ?></strong><span><?= e($person['email']) ?></span><small><?= $person['account']?'Registered account':'Reported email' ?> · <?= array_sum($person['counts']) ?> recent records</small></a><?php endforeach; ?>
        <?php if (!$items): ?><p>No matching records.</p><?php endif; ?>
    </section>
    <section class="admin-card crm-context">
        <?php if ($email!==''): ?><h3><?= e($email) ?></h3><p class="crm-muted">Related activity by exact email. Check identity before contacting or combining records.</p>
            <?php foreach ($timeline as $event): ?><div class="crm-timeline-event"><strong><?= e($event['label']) ?></strong><span><?= e($event['status']) ?> · <?= e(substr($event['date'],0,10)) ?></span>
                <?php if ($event['source']==='support_tickets'): ?><a href="/admin/support-tickets?ticket=<?= e(rawurlencode($event['id'])) ?>">Open ticket ↗</a><?php elseif ($event['source']==='contact_submissions'): ?><a href="/admin/contact-submissions">Contacts ↗</a><?php elseif ($event['source']==='therapy_sessions'): ?><a href="/admin/therapy">Therapy requests ↗</a><?php elseif ($event['source']==='orders'): ?><a href="/admin/orders/<?= e(rawurlencode($event['id'])) ?>">Order ↗</a><?php endif; ?></div><?php endforeach; ?>
            <?php if (!$timeline): ?><p>No associated activity found.</p><?php endif; ?>
        <?php else: ?><p>Select a customer or enquiry address to see related activity.</p><?php endif; ?>
    </section>
</div>

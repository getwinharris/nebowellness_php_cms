<?php use App\Services\TherapySessionService; ?>
<div class="admin-card">
    <h1>Therapy requests</h1>
    <p>Review incoming enquiries, offer a session and follow up with the customer. Clinic times use <?= e($timezone) ?>.</p>
    <?php if ($requests): ?>
        <label class="therapy-filter" data-therapy-filter hidden>Show requests
            <select><option value="">All requests</option><option value="requested">Awaiting review</option><option value="approved">Approved</option><option value="paid_waiting">Paid, awaiting a time</option><option value="confirmed">Confirmed</option><option value="closed">Completed or closed</option></select>
        </label>
        <p data-therapy-count role="status" hidden></p>
    <?php endif; ?>
    <?php if (!$requests): ?><p>No therapy requests yet.</p><?php endif; ?>
    <?php foreach ($requests as $r):
        $status = $r['status']; $paid = $r['payment_status'] === 'paid';
        $date = static fn(int $timestamp): string => (new DateTimeImmutable('@'.$timestamp))->setTimezone(new DateTimeZone($timezone))->format('Y-m-d\TH:i');
    ?>
    <details id="request-<?= e($r['id']) ?>" class="therapy-request" data-therapy-status="<?= e($status) ?>" data-paid="<?= $paid ? 'true' : 'false' ?>">
        <summary><strong><?= e($r['campaign_title']) ?></strong> · <?= e($r['contact_name'] ?? 'Customer request') ?> · <?= e(ucfirst(str_replace('_',' ', $status))) ?></summary>
        <form action="/admin/therapy/<?= e(rawurlencode($r['id'])) ?>" method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="revision" value="<?= (int)$r['revision'] ?>">
            <p>Payment · <?= e($paid && $status === 'approved' ? 'Received, choose an available time to confirm' : ucfirst($r['payment_status'])) ?>
                <?php if (empty($r['user_id'])): ?> · Guest enquiry. Contact this customer directly.<?php endif; ?></p>
            <?php if ($status === 'approved' && in_array($r['payment_status'], ['ordering','pending'], true) && $r['starts_at'] <= time()): ?>
                <p>The proposed start has passed. Agree a new time with the customer and update this request. The same fee and payment order apply.</p>
            <?php endif; ?>
            <div class="admin-form__row">
                <label>Status<select name="status"><?php foreach (TherapySessionService::TRANSITIONS[$status] as $option): ?>
                    <option value="<?= e($option) ?>" <?= $option === $status ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ', $option))) ?></option>
                <?php endforeach; ?></select></label>
                <label>Assigned team member<select name="assignee_id">
                    <option value="">Unassigned</option>
                    <?php $assigned = $r['assignee_id'] ?? (empty($r['assigned_to']) ? '' : 'clinic');
                    $choices = $team;
                    if ($assigned !== '' && !isset($choices[$assigned])) $choices[$assigned] = $r['assigned_to'];
                    foreach ($choices as $key=>$name): ?>
                        <option value="<?= e($key) ?>" <?= $assigned === $key ? 'selected' : '' ?>><?= e($name) ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Follow-up date<input name="follow_up" type="date" value="<?= e($r['follow_up'] ?? '') ?>"></label>
            </div>
            <div class="admin-form__row">
                <label>Proposed start (<?= e($timezone) ?>)<input type="datetime-local" name="start" value="<?= isset($r['starts_at']) ? e($date($r['starts_at'])) : '' ?>"></label>
                <label>Duration in minutes<input type="number" name="duration" min="1" max="1440" value="<?= isset($r['starts_at']) ? (int)(($r['ends_at']-$r['starts_at'])/60) : '' ?>"></label>
                <label>Session fee in INR<input type="number" name="fee" min="0" max="999999.99" step=".01" value="<?= e(number_format(($r['fee_paise'] ?? 0)/100,2,'.','')) ?>"></label>
            </div>
            <label>What the fee covers<input name="fee_description" maxlength="180" value="<?= e($r['fee_description'] ?? '') ?>"></label>
            <?php if (!empty($r['notes'])): ?><ul><?php foreach ($r['notes'] as $note): ?><li><time datetime="<?= e($note['at']) ?>"><?= e((new DateTimeImmutable($note['at']))->setTimezone(new DateTimeZone($timezone))->format('d M Y, g:i a')) ?></time> · <?= e($note['text']) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <label>Add a private follow-up note<textarea name="note" rows="2" maxlength="1000"></textarea></label>
            <button type="submit" class="btn btn-primary">Save request</button>
        </form>
    </details>
    <?php endforeach; ?>
</div>
<script src="/assets/therapy-inbox.js" defer></script>

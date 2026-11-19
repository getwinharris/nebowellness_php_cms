<div class="section account-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <section class="account-content">
        <h1>My therapy requests</h1>
        <?php if (!$requests): ?>
            <p>Requests submitted while signed in appear here. For a guest enquiry, contact the clinic for an update.</p>
            <a href="/contact">Contact the clinic</a>
        <?php endif; ?>
        <?php foreach ($requests as $r): ?>
        <article class="therapy-request">
            <h2><?= e($r['campaign_title']) ?></h2>
            <p><?= e($r['payment_status'] === 'paid' && $r['status'] === 'approved' ? 'Payment received. The clinic will contact you to confirm an available time.' : ucfirst(str_replace('_', ' ', $r['status']))) ?></p>
            <?php if ($r['status'] === 'cancelled' && $r['payment_status'] === 'paid'): ?><p>Please contact the clinic to arrange a refund or an alternative session.</p><?php endif; ?>
            <?php if (!empty($r['starts_at'])): ?>
                <p><?= $r['status'] === 'approved' ? 'Proposed time' : 'Session time' ?> · <?= e((new DateTimeImmutable('@'.$r['starts_at']))->setTimezone(new DateTimeZone($r['timezone']))->format('d M Y, g:i a T')) ?></p>
            <?php endif; ?>
            <?php if (!empty($r['fee_paise'])): ?>
                <p><?= e($r['fee_description']) ?> · INR <?= e(number_format($r['fee_paise']/100, 2)) ?> · <?= e($r['payment_status'] === 'paid' ? 'Paid' : 'Unpaid') ?></p>
                <?php if ($r['status'] === 'approved' && $r['payment_status'] !== 'paid'): ?>
                <p>After payment, your session is confirmed if the proposed time is still available. Otherwise the clinic will arrange a new time with you.</p>
                <form method="post" action="/account/therapy/<?= e(rawurlencode($r['id'])) ?>/pay">
                    <input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="btn btn-primary">Continue to payment</button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </article>
        <?php endforeach; ?>
    </section>
</div>

<div class="section account-layout">
    <?php require __DIR__ . '/_nav.php'; ?>
    <section class="account-content">
        <h1>Pay for your therapy session</h1>
        <p><?= e($request['campaign_title']) ?> · <?= e($request['fee_description']) ?></p>
        <p>Total · INR <?= e(number_format($request['fee_paise']/100,2)) ?></p>
        <?php if ($request['payment_mode'] === 'test'): ?><p>Test checkout. Use Razorpay test payment details.</p><?php endif; ?>
        <form data-therapy-checkout data-key="<?= e($key) ?>" data-order="<?= e($request['order_id']) ?>" data-amount="<?= (int)$request['fee_paise'] ?>"
              method="post" action="/account/therapy/<?= e(rawurlencode($request['id'])) ?>/verify">
            <input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="razorpay_payment_id"><input type="hidden" name="razorpay_order_id"><input type="hidden" name="razorpay_signature">
            <button type="button" class="btn btn-primary" data-pay hidden>Open Razorpay</button>
            <p role="status" data-status>Checkout requires JavaScript and access to Razorpay.</p>
        </form>
        <a href="/account/therapy">Return to my requests</a>
        <script src="https://checkout.razorpay.com/v1/checkout.js" defer></script>
        <script src="/assets/therapy-checkout.js" defer></script>
    </section>
</div>

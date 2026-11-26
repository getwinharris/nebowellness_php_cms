<?php $accountPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'; ?>
<aside class="account-nav" aria-label="Account navigation">
    <a href="/account/dashboard/orders"<?= str_starts_with($accountPath, '/account/dashboard/orders') ? ' class="active" aria-current="page"' : '' ?>>Past receipts</a>
    <?php if (($_SESSION['user']['role'] ?? '') === 'customer'): ?><a href="/account/therapy"<?= $accountPath === '/account/therapy' ? ' class="active" aria-current="page"' : '' ?>>Therapy requests</a><?php endif; ?>
    <a href="/account/dashboard/install"<?= str_starts_with($accountPath, '/account/dashboard/install') ? ' class="active" aria-current="page"' : '' ?>>Install App</a>
    <a href="/">Back to Home</a>
</aside>

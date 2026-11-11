<?php
$featuredProduct = $therapyProducts[0];
$productHeading = !empty($therapyCatalogPage) ? 'h2' : 'h3';
?>
<div class="therapy-grid<?= count($therapyProducts) === 1 ? ' therapy-grid--single' : '' ?>">
    <article class="therapy-feature">
        <img src="<?= e(trim((string)($featuredProduct['image_url'] ?? '')) ?: '/assets/images/nebo-programs.png') ?>" alt="" loading="lazy" width="1024" height="640">
        <div class="therapy-feature__body">
            <span class="therapy-feature__label">Featured therapy</span>
            <<?= $productHeading ?>><a href="/campaigns/<?= e(rawurlencode($featuredProduct['slug'])) ?>"><?= e($featuredProduct['title']) ?></a></<?= $productHeading ?>>
            <p><?= e($featuredProduct['summary'] ?? '') ?></p>
            <a class="therapy-product__link" href="/campaigns/<?= e(rawurlencode($featuredProduct['slug'])) ?>#campaign-enquiry" aria-label="Request a session for <?= e($featuredProduct['title']) ?>">Request a session <span aria-hidden="true">↗</span></a>
        </div>
    </article>
    <?php if (count($therapyProducts) > 1): ?>
        <div class="therapy-grid__older">
            <?php foreach (array_slice($therapyProducts, 1) as $product): ?>
                <article class="therapy-product">
                    <<?= $productHeading ?>><a href="/campaigns/<?= e(rawurlencode($product['slug'])) ?>"><?= e($product['title']) ?></a></<?= $productHeading ?>>
                    <p><?= e($product['summary'] ?? '') ?></p>
                    <a class="therapy-product__link" href="/campaigns/<?= e(rawurlencode($product['slug'])) ?>" aria-label="View therapy: <?= e($product['title']) ?>">View therapy <span aria-hidden="true">↗</span></a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

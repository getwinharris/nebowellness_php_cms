<?php
$featuredProduct = $therapyProducts[0];
$isCatalog = !empty($therapyCatalogPage);
$productHeading = $isCatalog ? 'h2' : 'h3';
$otherServices = array_slice($therapyProducts, 1);
?>
<div class="therapy-grid<?= count($therapyProducts) === 1 ? ' therapy-grid--single' : '' ?>">
    <article class="therapy-feature">
        <img src="<?= e(trim((string)($featuredProduct['image_url'] ?? '')) ?: '/assets/images/nebo-programs.webp') ?>" alt="" loading="lazy" data-therapy-image width="1024" height="640">
        <div class="therapy-feature__body">
            <span class="therapy-feature__label"><?= $isCatalog ? 'Featured therapy' : 'Featured service' ?></span>
            <<?= $productHeading ?>><a href="/campaigns/<?= e(rawurlencode($featuredProduct['slug'])) ?>"><?= e($featuredProduct['title']) ?></a></<?= $productHeading ?>>
            <p><?= e($featuredProduct['summary'] ?? '') ?></p>
            <a class="therapy-product__link" href="/campaigns/<?= e(rawurlencode($featuredProduct['slug'])) ?>#campaign-enquiry" aria-label="Request a session for <?= e($featuredProduct['title']) ?>">Request a session <span aria-hidden="true">↗</span></a>
        </div>
    </article>
    <?php if ($otherServices): ?>
        <div class="therapy-services">
            <?php if (!$isCatalog): ?>
                <div class="therapy-services__heading">
                    <p>More services</p>
                    <div class="therapy-services__controls">
                        <button type="button" class="rail-control" data-rail-prev="service-rail" aria-controls="service-rail" aria-label="Previous service" hidden>←</button>
                        <button type="button" class="rail-control" data-rail-next="service-rail" aria-controls="service-rail" aria-label="Next service" hidden>→</button>
                    </div>
                </div>
            <?php endif; ?>
            <div class="therapy-grid__older"<?= !$isCatalog ? ' id="service-rail" role="region" aria-label="More services" data-editorial-rail data-overflow-controls' : '' ?>>
                <?php foreach ($otherServices as $product): ?>
                    <article class="therapy-product">
                        <div class="therapy-product__media">
                            <img src="<?= e(trim((string)($product['image_url'] ?? '')) ?: '/assets/images/nebo-programs.webp') ?>" alt="" loading="lazy" decoding="async" data-therapy-image>
                        </div>
                        <div class="therapy-product__body">
                            <<?= $productHeading ?>><a href="/campaigns/<?= e(rawurlencode($product['slug'])) ?>"><?= e($product['title']) ?></a></<?= $productHeading ?>>
                            <p><?= e($product['summary'] ?? '') ?></p>
                            <a class="therapy-product__link" href="/campaigns/<?= e(rawurlencode($product['slug'])) ?>" aria-label="View service: <?= e($product['title']) ?>">View service <span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

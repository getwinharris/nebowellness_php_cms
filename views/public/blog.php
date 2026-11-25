<?php $categoryNames = array_column($categories ?? [], 'name', 'slug'); ?>
<section class="blog-page blog-editorial">
  <div class="container">
    <header class="blog-editorial__header">
      <h1 class="page-title"><?= e($categoryName ?? 'Nebo Wellness Journal') ?></h1>
      <p>Practical wellness guidance and clinic updates from the Nebo team.</p>
    </header>

    <?php if (!empty($categories)): ?>
    <nav class="blog-categories" aria-label="Journal topics">
      <a href="/blog" class="pill <?= $activeCategory === null ? 'pill--active' : '' ?>"<?= $activeCategory === null ? ' aria-current="page"' : '' ?>>All</a>
      <?php foreach ($categories as $cat): ?>
        <?php $isCurrent = $activeCategory === ($cat['slug'] ?? ''); ?>
        <a href="/blog/category/<?= e($cat['slug'] ?? '') ?>"
           class="pill <?= $isCurrent ? 'pill--active' : '' ?>"<?= $isCurrent ? ' aria-current="page"' : '' ?>>
          <?= e($cat['name'] ?? $cat['slug'] ?? '') ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
      <div class="empty-state">
        <p>No articles yet. Check back soon for practical wellness guidance and clinic updates.</p>
      </div>
    <?php else: ?>
      <div class="blog-grid">
        <?php foreach ($posts as $index => $post): ?>
          <?php
          $postImage = $post['image'] ?? $post['og_image'] ?? '/assets/images/nebo-programs.webp';
          $postCategory = $post['category'] ?? '';
          $publishedAt = strtotime($post['published_at'] ?? '');
          $titleId = 'journal-article-' . $index;
          ?>
          <article class="blog-card <?= $index === 0 ? 'blog-card--featured' : '' ?>">
            <a class="blog-card__link" href="/blog/<?= e($post['slug'] ?? '') ?>" aria-labelledby="<?= e($titleId) ?>">
              <div class="blog-card__media">
                <img class="blog-card__image" src="<?= e($postImage) ?>" alt="<?= e($post['image_alt'] ?? '') ?>" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" decoding="async">
              </div>
              <div class="blog-card__body">
                <div class="blog-card__kicker">
                  <?php if ($postCategory !== ''): ?><span class="blog-card__category"><?= e($categoryNames[$postCategory] ?? $postCategory) ?></span><?php endif; ?>
                  <?php if ($publishedAt !== false): ?><time class="blog-card__date" datetime="<?= e(date('Y-m-d', $publishedAt)) ?>"><?= e(date('M j, Y', $publishedAt)) ?></time><?php endif; ?>
                </div>
                <h2 id="<?= e($titleId) ?>" class="blog-card__title"><?= e($post['title'] ?? '') ?></h2>
                <?php if (!empty($post['excerpt'])): ?>
                  <p class="blog-card__excerpt"><?= e($post['excerpt']) ?></p>
                <?php endif; ?>
                <span class="blog-card__read" aria-hidden="true">Read article <span>→</span></span>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

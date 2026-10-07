<section class="campaign-index">
  <div class="container">
    <header class="campaign-index__intro">
      <span class="eyebrow">Focused wellness</span>
      <h1>Campaigns for healthier everyday living</h1>
      <p>Explore focused clinic initiatives and contact our team to discuss which next step fits your needs.</p>
    </header>
    <?php if (empty($pages)): ?>
      <div class="campaign-empty"><h2>New initiatives are coming soon</h2><p>Our team can still help you explore Nebo's current programs.</p><a class="btn btn-primary" href="/contact">Contact the clinic</a></div>
    <?php else: ?>
      <div class="campaign-grid">
        <?php foreach ($pages as $page): ?>
          <article class="campaign-card">
            <a class="campaign-card__image" href="/campaigns/<?= e($page['slug']) ?>"><img src="<?= e(($page['image_url'] ?? '') ?: '/assets/images/nebo-programs.png') ?>" alt="<?= e(($page['title'] ?? 'Nebo campaign') . ' campaign') ?>" loading="lazy"></a>
            <div class="campaign-card__body"><span class="eyebrow">Nebo initiative</span><h2><a href="/campaigns/<?= e($page['slug']) ?>"><?= e($page['title']) ?></a></h2><p><?= e($page['summary'] ?? '') ?></p><a class="campaign-card__link" href="/campaigns/<?= e($page['slug']) ?>">Explore campaign <span aria-hidden="true">→</span></a></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

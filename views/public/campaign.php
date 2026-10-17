<?php
$ctaUrl = trim((string)($campaign['cta_url'] ?? ''));
if ($ctaUrl === '' || !str_starts_with($ctaUrl, '/') || str_starts_with($ctaUrl, '//')) $ctaUrl = '/contact';
$ctaLabel = trim((string)($campaign['cta_label'] ?? '')) ?: 'Talk to our team';
?>
<article class="campaign-page">
  <header class="campaign-hero">
    <img class="campaign-hero__image" src="<?= e($campaign['image_url'] ?: '/assets/images/nebo-programs.png') ?>" alt="" loading="eager">
    <div class="container campaign-hero__inner"><span class="eyebrow">Nebo campaign</span><h1><?= e($campaign['title']) ?></h1><p><?= e($campaign['summary'] ?? '') ?></p><a class="btn btn-primary" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?></a></div>
  </header>
  <div class="container campaign-page__layout">
    <div class="campaign-page__body"><?= (new \App\Services\MarkdownRenderer())->render((string)($campaign['body'] ?? '')) ?></div>
    <aside class="campaign-page__aside"><span class="eyebrow">Your next step</span><h2>Discuss your goals with Nebo</h2><p>Our team can explain the program, who it may suit, and what to expect before you decide.</p><a class="btn btn-primary" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?></a></aside>
  </div>
  <div class="container campaign-page__back"><a href="/campaigns">← All campaigns</a></div>
</article>

<?php
$ctaUrl = trim((string)($campaign['cta_url'] ?? ''));
if ($ctaUrl === '' || !str_starts_with($ctaUrl, '/') || str_starts_with($ctaUrl, '//')) $ctaUrl = '/contact';
$ctaLabel = trim((string)($campaign['cta_label'] ?? '')) ?: 'Talk to our team';
$formState = $_SESSION['campaign_form'] ?? [];
if (($formState['slug'] ?? '') !== $campaign['slug']) $formState = [];
else unset($_SESSION['campaign_form']);
if (empty($formState['success'])) {
$submissionToken = bin2hex(random_bytes(16));
$_SESSION['campaign_tokens'][$campaign['slug']][$submissionToken] = 'pending';
$_SESSION['campaign_tokens'][$campaign['slug']] = array_slice($_SESSION['campaign_tokens'][$campaign['slug']], -20, null, true);
}
$formValues = $formState['values'] ?? [];
$copy = static fn(string $field, string $fallback): string => trim((string)($campaign[$field] ?? '')) ?: $fallback;
?>
<article class="campaign-page">
  <header class="campaign-hero">
    <img class="campaign-hero__image" src="<?= e($campaign['image_url'] ?: '/assets/images/nebo-programs.png') ?>" alt="" loading="eager">
    <div class="container campaign-hero__inner"><span class="eyebrow">Nebo campaign</span><h1><?= e($campaign['title']) ?></h1><p><?= e($campaign['summary'] ?? '') ?></p><a class="btn btn-primary" href="#campaign-enquiry"><?= e($copy('form_button', 'Enquire about this program')) ?></a></div>
  </header>
  <div class="container campaign-page__layout">
    <div class="campaign-page__body"><?= (new \App\Services\MarkdownRenderer())->render((string)($campaign['body'] ?? '')) ?></div>
    <aside class="campaign-page__aside"><span class="eyebrow">Your next step</span><h2>Discuss your goals with Nebo</h2><p>Our team can explain the program, who it may suit, and what to expect before you decide.</p><a class="btn btn-primary" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?></a></aside>
  </div>
  <section class="campaign-enquiry" id="campaign-enquiry" aria-labelledby="campaign-form-title">
    <div class="container campaign-enquiry__layout">
      <div><span class="eyebrow">Your next step</span><h2 id="campaign-form-title"><?= e($copy('form_title', 'Begin your wellness journey')) ?></h2><p><?= e($copy('form_intro', 'Tell us how to reach you. Our team will explain the program and help you decide on your next step.')) ?></p></div>
      <div>
      <?php if (!empty($formState['success'])): ?>
        <p class="campaign-form-success" role="status"><?= e($copy('form_success', 'Thank you. Your enquiry has reached Nebo and our team will contact you soon.')) ?></p>
      <?php else: ?>
        <?php if (!empty($formState['errors'])): ?><div role="alert" class="campaign-form-errors"><?php foreach ($formState['errors'] as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div><?php endif; ?>
        <form method="post" action="/campaigns/<?= e(rawurlencode($campaign['slug'])) ?>/enquire" class="contact-form">
          <input type="hidden" name="submission_token" value="<?= e($submissionToken) ?>">
          <input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf_token'] ??= bin2hex(random_bytes(16))) ?>">
          <div class="contact-form__row">
            <div class="form-group"><label for="campaign-name">Name</label><input id="campaign-name" name="name" autocomplete="name" maxlength="150" value="<?= e($formValues['name'] ?? '') ?>" required></div>
            <div class="form-group"><label for="campaign-email">Email</label><input id="campaign-email" name="email" type="email" autocomplete="email" maxlength="254" value="<?= e($formValues['email'] ?? '') ?>" required></div>
          </div>
          <?php if (($campaign['form_phone'] ?? '') !== 'hidden'): ?><div class="form-group"><label for="campaign-phone">Phone<?= ($campaign['form_phone'] ?? '') === 'required' ? '' : ' (optional)' ?></label><input id="campaign-phone" name="phone" type="tel" autocomplete="tel" maxlength="40" value="<?= e($formValues['phone'] ?? '') ?>" <?= ($campaign['form_phone'] ?? '') === 'required' ? 'required' : '' ?>></div><?php endif; ?>
          <?php if (($campaign['form_message'] ?? '') !== 'hidden'): ?><div class="form-group"><label for="campaign-message">Message<?= ($campaign['form_message'] ?? '') === 'required' ? '' : ' (optional)' ?></label><textarea id="campaign-message" name="message" rows="4" maxlength="5000" <?= ($campaign['form_message'] ?? '') === 'required' ? 'required' : '' ?>><?= e($formValues['message'] ?? '') ?></textarea></div><?php endif; ?>
          <p class="campaign-enquiry__privacy">We use these details to respond to this enquiry. <a href="/privacy">Privacy policy</a></p>
          <button class="btn btn-primary" type="submit"><?= e($copy('form_button', 'Request a call back')) ?></button>
        </form>
      <?php endif; ?>
      </div>
    </div>
  </section>
  <div class="container campaign-page__back"><a href="/campaigns">← All campaigns</a></div>
</article>

<?php
use App\Services\CampaignPageService;
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
$customFields = is_array($campaign['form_fields'] ?? null) ? $campaign['form_fields'] : [];
$copy = static fn(string $field, string $fallback): string => trim((string)($campaign[$field] ?? '')) ?: $fallback;
?>
<article class="campaign-page">
  <header class="campaign-hero">
    <div class="campaign-hero__inner">
      <a class="campaign-hero__back" href="/campaigns">← All programs</a>
      <p class="eyebrow">Nebo Lifestyle Clinic · Therapy programs</p>
      <h1><?= e($campaign['title']) ?></h1>
      <p class="campaign-hero__summary"><?= e($campaign['summary'] ?? '') ?></p>
      <div class="campaign-hero__actions">
        <a class="btn btn-primary" href="#campaign-enquiry">Go to enquiry form</a>
        <a class="campaign-text-link" href="#campaign-overview">About this program</a>
      </div>
    </div>
    <img class="campaign-hero__image" src="<?= e(($campaign['image_url'] ?? '') ?: '/assets/images/nebo-programs.png') ?>" alt="" loading="eager" fetchpriority="high">
  </header>
  <div class="container campaign-page__layout" id="campaign-overview">
    <div class="campaign-page__body"><span class="eyebrow">Program overview</span><?= (new \App\Services\MarkdownRenderer())->render((string)($campaign['body'] ?? '')) ?></div>
    <aside class="campaign-page__aside" aria-labelledby="campaign-steps-title">
      <h2 id="campaign-steps-title">What happens next</h2>
      <ol class="campaign-steps">
        <li><strong>Send your enquiry</strong><span>Share your contact details with the clinic.</span></li>
        <li><strong>Talk with the team</strong><span>Ask about availability, fees and whether the program fits your needs.</span></li>
      </ol>
      <a class="campaign-text-link" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?> →</a>
    </aside>
  </div>
  <section class="campaign-enquiry" id="campaign-enquiry" aria-labelledby="campaign-form-title">
    <div class="container campaign-enquiry__layout">
      <div class="campaign-enquiry__intro"><span class="eyebrow">Enquire with Nebo</span><h2 id="campaign-form-title"><?= e($copy('form_title', CampaignPageService::FORM_DEFAULTS['form_title'])) ?></h2><p><?= e($copy('form_intro', CampaignPageService::FORM_DEFAULTS['form_intro'])) ?></p><p class="campaign-enquiry__note"><?= e(CampaignPageService::FORM_NOTE) ?></p></div>
      <div class="campaign-enquiry__card">
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
          <?php foreach ($customFields as $field): if (!is_array($field)) continue; $key=preg_replace('/[^a-z0-9_]/','',strtolower((string)($field['key']??''))); if ($key==='') continue; $type=(string)($field['type']??'text'); $label=(string)($field['label']??$key); ?>
            <div class="form-group<?= $type === 'checkbox' ? ' campaign-field--checkbox' : '' ?>"><label for="campaign-custom-<?= e($key) ?>"><?= e($label) ?><?= empty($field['required']) ? ' (optional)' : '' ?></label>
            <?php $customValue=(string)($formValues['custom'][$key]??''); ?>
            <?php if ($type==='textarea'): ?><textarea id="campaign-custom-<?= e($key) ?>" name="custom[<?= e($key) ?>]" rows="4" maxlength="2000" <?= !empty($field['required'])?'required':'' ?>><?= e($customValue) ?></textarea>
            <?php elseif ($type==='select'): ?><select id="campaign-custom-<?= e($key) ?>" name="custom[<?= e($key) ?>]" <?= !empty($field['required'])?'required':'' ?>><option value="">Choose an option</option><?php foreach (($field['options']??[]) as $option): ?><option value="<?= e((string)$option) ?>" <?= $customValue===(string)$option?'selected':'' ?>><?= e((string)$option) ?></option><?php endforeach; ?></select>
            <?php elseif ($type==='checkbox'): ?><input id="campaign-custom-<?= e($key) ?>" type="checkbox" name="custom[<?= e($key) ?>]" value="Yes" <?= $customValue==='Yes'?'checked':'' ?> <?= !empty($field['required'])?'required':'' ?>>
            <?php else: ?><input id="campaign-custom-<?= e($key) ?>" type="<?= in_array($type,['email','tel'],true)?e($type):'text' ?>" name="custom[<?= e($key) ?>]" maxlength="2000" value="<?= e($customValue) ?>" <?= !empty($field['required'])?'required':'' ?>><?php endif; ?></div>
          <?php endforeach; ?>
          <?php if (!empty($campaign['form_require_otp'])): ?><div class="form-group email-otp" data-otp-context="campaign:<?= e($campaign['slug']) ?>" data-email-input="campaign-email"><label for="campaign-email-otp">Email verification code</label><div class="contact-form__row"><input id="campaign-email-otp" name="email_otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="6-digit code"><button class="btn btn-secondary otp-send" type="button">Send code</button></div><small class="otp-status" role="status">We will send the code to the email address above.</small></div><?php endif; ?>
          <p class="campaign-enquiry__privacy">We use these details to respond to this enquiry. <a href="/privacy">Privacy policy</a></p>
          <button class="btn btn-primary" type="submit"><?= e($copy('form_button', CampaignPageService::FORM_DEFAULTS['form_button'])) ?></button>
        </form>
      <?php endif; ?>
      </div>
    </div>
  </section>
</article>
<?php if (!empty($campaign['form_require_otp'])): ?><script src="/assets/email-otp.js?v=<?= filemtime(app_path('assets/email-otp.js')) ?>" defer></script><?php endif; ?>

<?php
$contactToken = bin2hex(random_bytes(16));
$_SESSION['contact_tokens'][$contactToken] = 'pending';
$_SESSION['contact_tokens'] = array_slice($_SESSION['contact_tokens'], -20, null, true);
// Values retained by a failed email-verification POST, so the visitor never has to
// retype the form. The controller clears the session entry after this render.
$contactValues = is_array($formValues ?? null) ? $formValues : [];
$contactSubjects = [
    'gut' => 'Gut Health Program',
    'metabolic' => 'Metabolic Reset',
    'fertility' => 'Fertility & Maternal Wellness',
    'lifestyle' => 'Lifestyle Transformation',
    'corporate' => 'Corporate Wellness',
    'general' => 'General Question',
];
if (!array_key_exists('subject', $contactValues) && is_string($subject ?? null) && isset($contactSubjects[$subject])) {
    $contactValues['subject'] = $subject;
}
if (!array_key_exists('message', $contactValues) && ($consultant ?? '') !== '') {
    $contactValues['message'] = 'I would like to enquire about a consultation with ' . $consultant . '.';
    $contactValues['subject'] ??= 'general';
}
$contactErrors = is_array($formErrors ?? null) ? $formErrors : [];
$clinicChannels = (new \App\Services\ClinicContactService())->channels();
try { $consultationCatalogue=(new \App\Services\ConsultationFeeService())->published(); }
catch (\Throwable) { $consultationCatalogue=null; }
$feeId=is_string($_GET['fee']??null)?$_GET['fee']:'';
if ($feeId!==''&&!array_key_exists('message',$contactValues)) {
    foreach ($consultationCatalogue['fees']??[] as $fee) if ($fee['id']===$feeId) {
        $contactValues['message']='I would like to enquire about '.$fee['title'].'.';
        $contactValues['subject']??='general'; break;
    }
}
?>
<section class="nebo-contact-page" aria-labelledby="contact-title">
    <div class="container container--wide">
        <header class="contact-intro">
            <div class="contact-intro__copy">
                <span class="eyebrow">Nebo Lifestyle Clinic</span>
                <h1 id="contact-title">Contact the <em>clinic</em></h1>
                <p class="lede">Talk to our team about consultations, wellness programs or a session for your workplace.</p>
                <div class="contact-intro__actions">
                    <a class="btn btn-primary" href="#contact-form">Send an enquiry <span aria-hidden="true">↓</span></a>
                    <div class="contact-intro__channels" aria-label="Direct contact">
                        <?php if ($clinicChannels['phones']): ?><a class="contact-text-link" href="tel:<?= e($clinicChannels['phones'][0]) ?>" aria-label="Call the clinic"><?php $careIcon='phone'; require __DIR__.'/../partials/care-icon.php'; ?> Call</a><?php endif; ?>
                        <?php if ($clinicChannels['whatsapp']): ?><a class="contact-text-link" href="<?= e(\App\Services\ClinicContactService::whatsappUrl($clinicChannels['whatsapp'][0])) ?>" aria-label="Message the clinic on WhatsApp"><?php $careIcon='message'; require __DIR__.'/../partials/care-icon.php'; ?> WhatsApp</a><?php endif; ?>
                        <a class="contact-text-link" href="mailto:nebolifestyleclinic@gmail.com" aria-label="Email the clinic"><?php $careIcon='mail'; require __DIR__.'/../partials/care-icon.php'; ?> Email</a>
                    </div>
                </div>
            </div>
            <figure class="contact-intro__image">
                <img src="/assets/images/nebo-clinic-hero.webp" alt="" width="1774" height="887" fetchpriority="high">
                <figcaption>Illustrative scene</figcaption>
            </figure>
        </header>
        <?php if (!empty($consultationCatalogue['fees'])): ?>
        <section class="consultation-fees" id="consultation-fees" aria-labelledby="consultation-fees-title">
            <header><span class="eyebrow">Consultations</span><h2 id="consultation-fees-title">Online consultation fees</h2><p>Explore the clinic’s published consultation options. Send an enquiry to arrange a time; an enquiry is not a confirmed appointment.</p></header>
            <div class="consultation-fees__grid">
                <?php foreach ($consultationCatalogue['fees'] as $fee): ?>
                <article class="consultation-fee">
                    <div class="consultation-fee__header"><h3><?= e($fee['title']) ?></h3><strong class="consultation-fee__price"><?= e($fee['display_price']) ?></strong></div>
                    <p class="consultation-fee__duration"><?= e($fee['duration']) ?></p>
                    <?php if ($fee['description']!==''): ?><p><?= e($fee['description']) ?></p><?php endif; ?>
                    <?php if ($fee['inclusions']): ?><ul><?php foreach ($fee['inclusions'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul><?php endif; ?>
                    <a class="btn btn-outline" href="/contact?fee=<?= e(rawurlencode($fee['id'])) ?>#contact-form">Enquire about this consultation <span aria-hidden="true">↗</span></a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if ($consultationCatalogue['inclusions']): ?><div class="consultation-fees__inclusions"><h3>What consultations include</h3><ul><?php foreach ($consultationCatalogue['inclusions'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        </section>
        <?php elseif ($consultationCatalogue===null): ?><p class="consultation-fees__notice">Consultation fees are temporarily unavailable. Please contact the clinic for current information.</p><?php endif; ?>
        <div class="contact-layout">
            <section class="contact-form-card" id="contact-form" aria-labelledby="contact-form-title">
                <h2 class="contact-form-card__title" id="contact-form-title">Send an enquiry</h2>
                <p class="contact-form-card__lede contact-form-card__lede--general">Tell us what you’d like help with and our team will follow up.</p>
                <p class="contact-form-card__lede contact-form-card__lede--corporate">To plan a team session, tell us your organisation, approximate team size, preferred location and dates, and which session interests you.</p>
                <?php if (!empty($success)): ?>
                    <p class="flash flash--success contact-form__notice" role="status">Enquiry received. Nebo Lifestyle Clinic will contact you using the details you shared.</p>
                <?php endif; ?>
                <?php if ($contactErrors !== []): ?>
                    <div class="flash flash--error contact-form__notice" role="alert">
                        <?php foreach ($contactErrors as $contactError): ?><p><?= e($contactError) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form method="post" action="/contact#contact-form" class="contact-form">
                    <?php $csrf = $_SESSION['csrf_token'] ??= bin2hex(random_bytes(16)); ?>
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="submission_token" value="<?= e($contactToken) ?>">
                    <div class="contact-form__row">
                        <div class="form-group">
                            <label for="contact-name">Name</label>
                            <input id="contact-name" type="text" name="name" autocomplete="name" required value="<?= e((string)($contactValues['name'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact-email">Email</label>
                            <input id="contact-email" type="email" name="email" autocomplete="email" required value="<?= e((string)($contactValues['email'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="contact-form__row">
                        <div class="form-group">
                            <label for="contact-phone">Phone <span>(optional)</span></label>
                            <input id="contact-phone" type="tel" name="phone" autocomplete="tel" value="<?= e((string)($contactValues['phone'] ?? '')) ?>">
                        </div>
                        <div class="form-group">
                            <label for="contact-subject">Subject</label>
                            <select id="contact-subject" name="subject" required>
                                <option value="">Select a subject</option>
                                <?php foreach ($contactSubjects as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= ($contactValues['subject'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="contact-message">Message</label>
                        <textarea id="contact-message" name="message" rows="5" required><?= e((string)($contactValues['message'] ?? '')) ?></textarea>
                    </div>
                    <?php if (!empty($otpAvailable)): ?>
                        <div class="form-group email-otp" data-otp-context="contact">
                            <label for="contact-email-otp">Email verification code</label>
                            <div class="contact-form__row contact-form__verification">
                                <input id="contact-email-otp" name="email_otp" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="6-digit code" aria-describedby="contact-otp-status">
                                <button class="btn btn-secondary otp-send" type="button">Send code</button>
                            </div>
                            <small class="otp-status" id="contact-otp-status" role="status">Send a code to the email address you entered.</small>
                        </div>
                    <?php else: ?>
                        <p class="flash flash--info contact-form__notice" role="status">Email verification is temporarily unavailable. You can send this enquiry without a code.</p>
                    <?php endif; ?>
                    <button class="btn btn-primary contact-form__submit" type="submit">Send enquiry <span aria-hidden="true">→</span></button>
                </form>
            </section>
            <aside class="contact-details" aria-labelledby="contact-details-title">
                <h2 id="contact-details-title">Clinic details</h2>
                <div class="contact-details__group">
                    <h3>Call or email the clinic</h3>
                    <ul class="contact-details__links">
                        <?php foreach ($clinicChannels['phones'] as $number): ?><li><a class="contact-text-link" href="tel:<?= e($number) ?>"><?= e($number) ?></a></li><?php endforeach; ?>
                        <li><a class="contact-text-link" href="mailto:nebolifestyleclinic@gmail.com">nebolifestyleclinic@gmail.com</a></li>
                    </ul>
                </div>
                <?php if ($clinicChannels['whatsapp']): ?><div class="contact-details__group">
                    <h3>Message us on WhatsApp</h3>
                    <ul class="contact-details__links">
                        <?php foreach ($clinicChannels['whatsapp'] as $number): ?><li><a class="contact-text-link" href="<?= e(\App\Services\ClinicContactService::whatsappUrl($number)) ?>"><?= e($number) ?> <span aria-hidden="true">↗</span></a></li><?php endforeach; ?>
                    </ul>
                </div><?php endif; ?>
                <div class="contact-details__group">
                    <h3>Clinic hours</h3>
                    <dl class="contact-details__hours">
                        <div><dt>Monday to Saturday</dt><dd>9:00 AM – 7:00 PM</dd></div>
                        <div><dt>Sunday</dt><dd>10:00 AM – 5:00 PM</dd></div>
                    </dl>
                </div>
                <div class="contact-details__group">
                    <h3>Visit the clinic</h3>
                    <address>Kaliyal, Kanyakumari<br>Tamil Nadu, India 629101</address>
                    <a class="contact-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=Nebo%20Lifestyle%20Clinic%2C%20Kaliyal%2C%20Kanyakumari%2C%20Tamil%20Nadu%20629101">Find us on Google Maps <span aria-hidden="true">↗</span></a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?= \App\Services\SeoService::jsonLdScript(($__clinicSeo ?? new \App\Services\SeoService())->contactPageSchema()) ?>
<?php if (!empty($otpAvailable)): ?><script src="/assets/email-otp.js?v=<?= filemtime(app_path('assets/email-otp.js')) ?>" defer></script><?php endif; ?>

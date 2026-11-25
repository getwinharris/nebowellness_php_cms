<?php require app_path('config/nebo-foundation-copy.php'); ?>
<section class="section nebo-about-page">
    <div class="container">
        <header class="nebo-about-intro">
            <div class="nebo-about-intro__copy">
                <span class="eyebrow">Our story</span>
                <h1>About Nebo Lifestyle Clinic</h1>
                <p>We bring naturopathy, functional medicine and lifestyle care together for individuals and families in Kanyakumari and across South India.</p>
                <a class="btn btn-primary" href="/contact#contact-form">Request a consultation</a>
            </div>
            <figure class="nebo-about-photo">
                <img src="/assets/images/nebo-garden.webp" width="1672" height="941" alt="Tropical garden and shaded terrace" fetchpriority="high">
                <figcaption>Illustrative scene</figcaption>
            </figure>
        </header>

        <div class="about-story-grid">
            <div class="about-story-card reveal">
                <h2>The Nebo Foundation</h2>
                <p><?= e($foundationIntro) ?></p>
            </div>
            <div class="about-story-card reveal">
                <h2>Our Vision</h2>
                <p><?= e($foundationVision) ?></p>
            </div>
        </div>

        <div class="section-header nebo-about-section-heading">
            <h2 class="section-title">Our Pillars</h2>
        </div>
        <div class="about-feature-grid">
            <?php foreach ($foundationPillars as [$title, $description]): ?>
                <article class="about-feature-card reveal">
                    <h3><?= e($title) ?></h3>
                    <p><?= e($description) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="section-header nebo-about-section-heading">
            <h2 class="section-title">Why Choose Nebo?</h2>
        </div>
        <div class="value-strip">
            <?php foreach ($whyChooseNebo as [$title, $description]): ?>
                <article class="value-card reveal">
                    <h3><?= e($title) ?></h3>
                    <p><?= e($description) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="section-header nebo-about-section-heading">
            <span class="eyebrow serif-accent">Our Commitment</span>
            <h2 class="section-title">Your Wellness Partner</h2>
        </div>

        <div class="about-story-grid">
            <div class="about-story-card reveal">
                <h3>Patient-Centred Philosophy</h3>
                <p>We see you as a whole person—not a diagnosis. Every treatment plan is co-created with you, honouring your goals, preferences, and pace of transformation.</p>
            </div>
            <div class="about-story-card reveal">
                <h3>Continuous Innovation</h3>
                <p>We stay at the forefront of integrative medicine, continuously updating our knowledge and techniques with the latest research in functional medicine, naturopathy, and nutritional science.</p>
            </div>
        </div>

        <div class="page-cta-card reveal" style="margin-top:var(--space-2xl);">
            <div>
                <span class="page-cta-card__eyebrow">Ready to Begin?</span>
                <h3>Book Your Wellness Consultation</h3>
                <p>Start your journey toward vibrant health. Our team is here to guide you every step of the way with personalised, compassionate care.</p>
            </div>
            <a class="btn btn-primary page-cta-card__button" href="/contact#contact-form">Schedule Consultation →</a>
        </div>
    </div>
</section>

<?= \App\Services\SeoService::jsonLdScript(($__clinicSeo ?? new \App\Services\SeoService())->aboutPageSchema()) ?>

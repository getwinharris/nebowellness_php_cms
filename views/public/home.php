<?php
$programAreas = ['all' => 'All programs', 'gut' => 'Gut health', 'metabolic' => 'Metabolic health', 'fertility' => 'Fertility & maternal care', 'lifestyle' => 'Lifestyle'];
$programs = [
    ['Gut Restoration Program','6–12 weeks','Nutrition, microbiome support and daily habits shaped around your symptoms and clinical assessment.'],
    ['Metabolic Reset','Personalised duration','A practical plan for insulin sensitivity, movement, sleep, stress and sustainable weight management.'],
    ['Fertility & Preconception Wellness','Personalised duration','Nutrition and lifestyle guidance that supports preconception preparation and reproductive wellbeing.'],
    ['Maternal Vitality','Stage based','Gentle wellbeing support through pregnancy and after delivery, coordinated around each mother’s needs.'],
    ['Lifestyle Transformation','90 days','A guided pathway that brings nutrition, movement, stress care and habit coaching into daily life.'],
];
$programSubjects = ['gut', 'metabolic', 'fertility', 'fertility', 'lifestyle'];
// Illustrative Nebo-owned media; not clinical outcome or patient evidence.
$programImages = ['blog/gut-health-food.webp', 'nebo-programs.webp', 'nebo-garden.webp', 'nebo-clinic-hero.webp', 'blog/wellness-habits.webp'];
$consultationSteps = [
    ['Tell us what matters', 'Let us know your goals and the support you are looking for.'],
    ['Connect with our team', 'Discuss the relevant program and what a consultation involves.'],
    ['Take your next step', 'Plan care around your needs with guidance from a Nebo practitioner.'],
];
?>
<div class="editorial-stack" data-editorial-stack>
<section class="editorial-stack-card home-hero nebo-home-hero" style="--stack-order:1" data-stack-card data-nebo-chapter="arrival">
    <div class="nebo-hero-media" aria-hidden="true">
        <img src="/assets/images/editorial/nebo-arrival.webp"
            srcset="/assets/images/editorial/nebo-arrival-small.webp 840w, /assets/images/editorial/nebo-arrival.webp 1672w"
            sizes="(max-width: 743px) 720px, 100vw" alt="" width="1672" height="941" fetchpriority="high">
    </div>
    <span class="nebo-hero-art-label">Illustrative scene</span>
    <div class="container home-hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Integrative care in Kanyakumari</span>
            <h1>Where Science Meets Nature for <em>Lifelong Wellness</em></h1>
            <p class="lede">Personalised naturopathy, functional medicine, and lifestyle care. Find support for gut health, metabolic health, and maternal wellness.</p>
            <div class="hero-actions">
                <a href="/contact#contact-form" class="btn btn-primary">Book Your Consultation</a>
                <a href="#programs" class="btn btn-secondary">Explore Our Programs</a>
            </div>
        </div>
    </div>
</section>

<!-- About Nebo Foundation -->
<section class="editorial-stack-card section section--alt nebo-intro-section" style="--stack-order:2" data-stack-card data-nebo-chapter="foundation">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow serif-accent">About Us</span>
            <h2 class="section-title">The Nebo Foundation</h2>
            <p class="lede">Nebo Lifestyle Clinic is a flagship initiative of the Nebo Foundation, dedicated to making integrative, root-cause healthcare accessible and sustainable.</p>
        </div>
        <?php
        $homeFigures = [
            [count($programs), 'Program pathways', 'Personalised options to explore before you enquire.'],
            [count($programAreas) - 1, 'Care areas', 'Explore gut, metabolic, fertility, maternal, and lifestyle support.'],
        ];
        require __DIR__ . '/../partials/home-figures.php';
        ?>
        <div class="nebo-intro-copy">
            <div>
                <h3>Our Vision</h3>
                <p>We bring naturopathy and functional medicine together to make personalised care a practical part of everyday life.</p>
            </div>
            <div>
                <h3>Our Pillars</h3>
                <p>Gut and digestive health, metabolic support, and lifestyle habits you can sustain.</p>
            </div>
        </div>
    </div>
</section>

<!-- Programs -->
<span class="editorial-anchor" id="programs" aria-hidden="true"></span>
<?php foreach (array_slice($programAreas, 1, null, true) as $area => $label): ?>
    <span class="editorial-anchor" id="programs-<?= e($area) ?>" aria-hidden="true"></span>
<?php endforeach; ?>
<section class="editorial-stack-card section" style="--stack-order:3" data-stack-card data-nebo-chapter="programs">
    <div class="container home-rail-section">
        <div class="home-rail-heading">
            <div>
                <h2 class="section-title">Our Programs</h2>
                <p class="lede">Personalised, practitioner-led pathways designed around your health goals.</p>
            </div>
        </div>
        <div class="program-toolbar">
            <div class="program-filters" role="group" aria-label="Filter programs by care area" data-rail-filters="program-rail" hidden>
                <?php foreach ($programAreas as $area => $label): ?>
                    <button type="button" data-rail-filter="<?= e($area) ?>" aria-controls="program-rail" aria-pressed="<?= $area === 'all' ? 'true' : 'false' ?>"><?= e($label) ?></button>
                <?php endforeach; ?>
                <span class="sr-only" data-rail-filter-status role="status"></span>
            </div>
            <div class="rail-controls" aria-label="Program slides">
                <button type="button" class="rail-control" data-rail-prev="program-rail" aria-label="Previous programs">←</button>
                <button type="button" class="rail-control" data-rail-next="program-rail" aria-label="Next programs">→</button>
            </div>
        </div>
        <div class="editorial-rail" id="program-rail" data-editorial-rail tabindex="0" aria-label="Nebo wellness programs">
            <?php foreach ($programs as $index => $program): ?>
            <article class="program-slide" data-rail-area="<?= e($programSubjects[$index]) ?>">
                <a class="program-slide__card-link" href="/contact?subject=<?= e($programSubjects[$index]) ?>#contact-form" aria-label="Enquire about <?= e($program[0]) ?>">
                    <div class="program-slide__media">
                        <img src="/assets/images/<?= e($programImages[$index]) ?>" alt="" loading="lazy" decoding="async">
                    </div>
                    <div class="program-slide__body">
                        <span class="program-slide__meta"><?= e($program[1]) ?></span>
                        <h3><?= e($program[0]) ?></h3>
                        <p><?= e($program[2]) ?></p>
                        <span class="program-slide__link">Enquire about this program <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Team -->
<section class="editorial-stack-card section section--alt" style="--stack-order:4" data-stack-card data-nebo-chapter="team">
    <div class="container home-rail-section">
        <div class="home-rail-heading">
            <div>
                <h2 class="section-title">Meet Our Team</h2>
                <p class="lede">A multidisciplinary team of naturopathy and wellness practitioners.</p>
            </div>
            <div class="rail-controls" aria-label="Team slides" data-team-controls hidden>
                <button type="button" class="rail-control" data-rail-prev="team-rail" aria-controls="team-detail" aria-label="Previous team members">←</button>
                <button type="button" class="rail-control" data-rail-next="team-rail" aria-controls="team-detail" aria-label="Next team members">→</button>
            </div>
        </div>
        <div class="team-showcase" data-team-showcase>
        <div class="team-rail" id="team-rail" aria-label="Nebo practitioner profiles">
            <?php foreach ($consultants as $teamIndex => $consultant):
                $portrait = trim((string)($consultant['photo_url'] ?? '')) ?: '/assets/images/consultants/default-female.webp';
                $placeholder = in_array($portrait, ['/assets/images/consultants/default-female.webp', '/assets/images/consultants/default-male.webp'], true);
            ?>
            <article class="team-slide">
                <button type="button" class="team-slide__media" disabled aria-controls="team-detail" aria-label="View <?= e($consultant['name'] ?? 'practitioner') ?>" aria-pressed="<?= $teamIndex === 0 ? 'true' : 'false' ?>">
                    <img src="<?= e($portrait) ?>" alt="" loading="lazy">
                    <?php if ($placeholder): ?><span class="team-placeholder">Portrait placeholder</span><?php endif; ?>
                    <span class="team-preview-name"><?= e($consultant['name'] ?? '') ?></span>
                </button>
                <div class="team-slide__body">
                    <h3><?= e($consultant['name'] ?? '') ?></h3>
                    <p class="team-slide__role"><?= e($consultant['speciality'] ?? '') ?></p>
                    <?php if (!empty($consultant['description'])): ?><p><?= e($consultant['description']) ?></p><?php endif; ?>
                    <a href="/contact?consultant=<?= rawurlencode((string)($consultant['name'] ?? '')) ?>#contact-form" class="program-slide__link">Request a consultation <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <div class="team-detail" id="team-detail"></div>
        <span class="sr-only" role="status" data-team-status></span>
        </div>
    </div>
</section>
<script src="/assets/team-showcase.js?v=<?= filemtime(app_path('assets/team-showcase.js')) ?>" defer></script>

<!-- Client-provided testimonials -->
<section class="editorial-stack-card section" style="--stack-order:5" data-stack-card data-nebo-chapter="stories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Experiences shared by Nebo clients</h2>
        </div>
        <div class="nebo-testimonials" aria-label="Client testimonials">
            <div class="nebo-testimonials__art">
                <img src="/assets/images/editorial/nebo-stories.webp" alt="" width="850" height="1275" loading="lazy" decoding="async">
            </div>
            <figure class="nebo-testimonial">
                <blockquote>“After years of struggling with PCOS and gut issues, Nebo's holistic approach finally gave me answers. My cycles are regular, energy is high, and I feel like myself again.”</blockquote>
                <figcaption>Priya S. <span>Bengaluru</span></figcaption>
            </figure>
            <figure class="nebo-testimonial">
                <blockquote>“The IV nutrition and ozone therapy transformed my chronic fatigue. I can finally keep up with my kids and work without crashing.”</blockquote>
                <figcaption>Nebo client <span>Client-provided testimonial</span></figcaption>
            </figure>
        </div>
    </div>
</section>

<section class="nebo-scroll-statement" data-nebo-chapter="statement" aria-labelledby="nebo-scroll-statement-title">
    <h2 class="container nebo-scroll-statement__title" id="nebo-scroll-statement-title">Care for the <em>whole person.</em></h2>
    <div class="nebo-scroll-statement__light" aria-hidden="true">
        <p class="container nebo-scroll-statement__title">Care for the <em>whole person.</em></p>
    </div>
</section>

<!-- Focus Areas -->
<section class="editorial-stack-card section" style="--stack-order:7" data-stack-card data-nebo-chapter="focus">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Care for everyday life</h2>
            <p class="lede">Explore the care areas that shape a Nebo program.</p>
        </div>
        <div class="nebo-care-mosaic">
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__media">
                    <img src="/assets/images/blog/gut-health-food.webp" alt="" width="1599" height="900" loading="lazy" decoding="async">
                </div>
                <h3>Gut Health</h3>
                <p>Explore practitioner-led nutrition and lifestyle support for digestion, bloating, IBS symptoms, and everyday gut wellbeing.</p>
                <a class="focus-program-link" href="#programs-gut">Explore gut health programs <span aria-hidden="true">↗</span></a>
            </article>
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__media">
                    <img src="/assets/images/nebo-programs.webp" alt="" width="1672" height="941" loading="lazy" decoding="async">
                </div>
                <h3>Metabolic Diseases</h3>
                <p>Support insulin sensitivity, PCOS and thyroid care, and sustainable weight goals alongside appropriate medical treatment.</p>
                <a class="focus-program-link" href="#programs-metabolic">Explore metabolic health programs <span aria-hidden="true">↗</span></a>
            </article>
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__media">
                    <img src="/assets/images/blog/wellness-habits.webp" alt="" width="1599" height="900" loading="lazy" decoding="async">
                </div>
                <h3>Lifestyle Transformation</h3>
                <p>Build lasting habits with personalized nutrition, stress management, and movement plans designed for your unique biology and life stage.</p>
                <a class="focus-program-link" href="#programs-lifestyle">Explore lifestyle programs <span aria-hidden="true">↗</span></a>
            </article>
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__media">
                    <img src="/assets/images/nebo-garden.webp" alt="" width="1672" height="941" loading="lazy" decoding="async">
                </div>
                <h3>Maternal & Fertility Wellness</h3>
                <p>Support preconception health, pregnancy vitality, and postpartum recovery with gentle, drug-free therapies that nurture both mother and baby.</p>
                <a class="focus-program-link" href="#programs-fertility">Explore fertility and maternal programs <span aria-hidden="true">↗</span></a>
            </article>
        </div>
    </div>
</section>

<!-- Why Choose Nebo -->
<section class="editorial-stack-card section section--warm" style="--stack-order:8" data-stack-card data-nebo-chapter="confidence">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What makes Nebo different</h2>
            <p class="lede">Get to know the practitioners and care options available as you plan your next step.</p>
        </div>
        <?php
        $homeFigures = [
            [count($consultants), 'Practitioner profiles', 'Meet the team and explore their areas of practice.'],
            [count($consultationSteps), 'Consultation steps', 'From sharing your goals to planning care.'],
        ];
        if (!empty($therapyProducts)) {
            $homeFigures[] = [count($therapyProducts), 'Featured services', 'Browse the clinic-led options highlighted here.'];
        }
        require __DIR__ . '/../partials/home-figures.php';
        ?>
        <div class="value-strip">
            <article class="value-card">
                <h3>Root-cause, personalized care</h3>
                <p>Nebo considers digestion, hormones, metabolism, stress, history, and goals when shaping a personalised care plan.</p>
            </article>
            <article class="value-card">
                <h3>Safe, expert-led, measurable</h3>
                <p>Practitioner-led, non-invasive wellbeing support with clear goals, review points, and coordination with existing medical care.</p>
            </article>
            <article class="value-card">
                <h3>Integrative, maternal-first, sustainable</h3>
                <p>Combines naturopathy, nutrition, lifestyle medicine, and evidence-based testing; offers specialized preconception/pregnancy/postpartum protocols; delivers practical, culturally relevant plans you can maintain long-term.</p>
            </article>
        </div>
    </div>
</section>

<!-- Clinic resources and consultation -->
<section class="editorial-stack-card section section--warm" style="--stack-order:9" data-stack-card data-nebo-chapter="resolve">
    <div class="container">
        <?php if (!empty($therapyProducts)): ?>
            <span class="editorial-anchor" id="therapy-products" aria-hidden="true"></span>
            <section class="therapy-products" aria-labelledby="therapy-products-title">
                <header class="therapy-products__heading">
                    <div><h2 class="section-title" id="therapy-products-title">Services</h2><p class="lede">Explore clinic-led therapies and request a session with the Nebo team.</p></div>
                    <a class="therapy-product__link" href="/campaigns">View all services <span aria-hidden="true">↗</span></a>
                </header>
                <?php require __DIR__ . '/../partials/therapy-products.php'; ?>
            </section>
        <?php endif; ?>
        <div class="consultation-invitation">
            <div class="consultation-invitation__copy">
                <span class="eyebrow serif-accent">Your next chapter</span>
                <h2>Begin your journey<br>to better wellbeing.</h2>
                <p>Share your health goals with Nebo. Our team will help you understand the programs, meet the right practitioner, and choose a practical next step.</p>
                <a class="btn btn-primary" href="/contact#contact-form">Request a consultation <span aria-hidden="true">↗</span></a>
            </div>
            <ol class="consultation-invitation__steps" aria-label="What happens next">
                <?php foreach ($consultationSteps as $index => [$heading, $description]): ?>
                    <li>
                        <span aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                        <div><h3><?= e($heading) ?></h3><p><?= e($description) ?></p></div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <div class="nebo-resource-split">
            <div class="nebo-resource-split__copy">
                <h2 class="section-title">Explore Nebo</h2>
                <p>Get to know the clinic, browse care options, or plan a visit.</p>
                <ul class="nebo-resource-split__links">
                    <li><a href="/about">The clinic and Foundation <span aria-hidden="true">↗</span></a></li>
                    <li><a href="#programs">Find a program <span aria-hidden="true">↗</span></a></li>
                    <?php if (module_on('blog')): ?>
                        <li><a href="/blog">Wellness articles <span aria-hidden="true">↗</span></a></li>
                    <?php endif; ?>
                    <li><a href="/contact">Contact and directions <span aria-hidden="true">↗</span></a></li>
                </ul>
            </div>
            <figure class="nebo-resource-split__media">
                <img src="/assets/images/nebo-clinic-hero.webp" alt="" width="1774" height="887" loading="lazy" decoding="async">
                <figcaption class="nebo-editorial-art-label">Illustrative scene</figcaption>
            </figure>
        </div>
    </div>
</section>

</div>

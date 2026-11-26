<?php
require app_path('config/nebo-foundation-copy.php');
$programs = $programs ?? [];
$programAreas = ['all' => 'All programs'] + \App\Services\CampaignPageService::programAreas($programs);
$programsEnabled = $programsEnabled ?? module_on('campaigns');
$programAreaHref = static fn(string $area): string => isset($programAreas[$area]) && $programsEnabled ? '#programs-'.$area : '/contact#contact-form';
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
    <div class="container home-hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Integrative care in Kanyakumari</span>
            <h1>Where Science Meets Nature for <em>Lifelong Wellness</em></h1>
            <p class="lede">At Nebo Lifestyle Clinic, we specialise in <strong>naturopathy</strong>, <strong>functional medicine</strong>, and <strong>maternal &amp; fertility wellness</strong>, offering personalised care for gut health, metabolic diseases, and sustainable lifestyle transformation.</p>
            <div class="hero-actions">
                <a href="/contact#contact-form" class="btn btn-primary">Book Your Consultation</a>
                <a href="#programs" class="nebo-hero-section-link">Explore Our Programs <span aria-hidden="true">↓</span></a>
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
            <p class="lede"><?= e($foundationIntro) ?></p>
        </div>
        <?php
        $homeFigures = [];
        if ($programsEnabled && empty($programsUnavailable)) {
            $homeFigures[] = [count($programs), 'Program pathways', 'Personalised options to explore before you enquire.'];
        }
        $homeFigures[] = [4, 'Focus areas', 'Explore the health needs our practitioners support.'];
        require __DIR__ . '/../partials/home-figures.php';
        ?>
        <div class="nebo-intro-copy">
            <div>
                <h3>Our Vision</h3>
                <p><?= e($foundationVision) ?></p>
            </div>
            <div>
                <h3>Our Pillars</h3>
                <ul>
                    <?php foreach ($foundationPillars as [$title, $description]): ?>
                        <li><strong><?= e($title) ?></strong>. <?= e($description) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Programs -->
<span class="editorial-anchor" id="programs" aria-hidden="true"></span>
<?php foreach (array_diff_key($programAreas, ['all' => true]) + array_fill_keys(['gut','metabolic','fertility','lifestyle'], '') as $area => $label): ?>
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
        <?php if ($programs === []): ?>
            <p><?= !$programsEnabled || !empty($programsUnavailable) ? 'Programmes are temporarily unavailable.' : 'No programmes are currently published.' ?> <a href="/contact#contact-form">Contact our team</a>.</p>
        <?php else: ?>
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
            <?php foreach ($programs as $program):
                [$image, $width, $height] = $program['image'];
            ?>
            <article class="program-slide" data-rail-area="<?= e($program['area']) ?>">
                <a class="program-slide__card-link" href="/campaigns/<?= e($program['slug']) ?>" aria-label="Explore <?= e($program['title']) ?>">
                    <div class="program-slide__media">
                        <img src="<?= e($image) ?>" alt="" width="<?= $width ?>" height="<?= $height ?>" loading="lazy" decoding="async">
                    </div>
                    <div class="program-slide__body">
                        <?php if ($program['duration'] !== ''): ?><span class="program-slide__meta"><?= e($program['duration']) ?></span><?php endif; ?>
                        <h3><?= e($program['title']) ?></h3>
                        <p><?= e($program['copy']) ?></p>
                        <span class="program-slide__link">Explore program <span aria-hidden="true">↗</span></span>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Team -->
<?php if (!empty($teamUnavailable)): ?>
<section class="section section--alt"><div class="container"><h2 class="section-title">Meet Our Team</h2><p>Practitioner profiles are temporarily unavailable. <a href="/contact#contact-form">Contact our team</a> for assistance.</p></div></section>
<?php elseif ($consultants !== []): ?>
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
                $name = trim((string)($consultant['name'] ?? ''));
                $portrait = trim((string)($consultant['photo_url'] ?? ''));
                $placeholder = $portrait === '' || in_array(basename($portrait), ['default-female.webp', 'default-male.webp'], true);
                $initials = '';
                if ($placeholder) {
                    $nameWithoutTitle = preg_replace('/^dr\.?\s+/iu', '', $name) ?? $name;
                    preg_match_all('/(?:^|\s)(\p{L})/u', $nameWithoutTitle, $letters);
                    $initials = implode('', array_slice($letters[1] ?? [], 0, 2));
                }
            ?>
            <article class="team-slide">
                <button type="button" class="team-slide__media<?= $placeholder ? ' team-slide__media--initials' : '' ?>" disabled aria-controls="team-detail" aria-label="View <?= e($name ?: 'practitioner') ?>" aria-pressed="<?= $teamIndex === 0 ? 'true' : 'false' ?>">
                    <?php if ($placeholder): ?>
                        <span class="team-slide__initials" aria-hidden="true"><?= e($initials) ?></span>
                    <?php else: ?>
                        <img src="<?= e($portrait) ?>" alt="" loading="lazy" decoding="async">
                    <?php endif; ?>
                    <span class="team-preview-name"><?= e($name) ?></span>
                </button>
                <div class="team-slide__body">
                    <h3><?= e($consultant['name'] ?? '') ?></h3>
                    <?php if (!empty($consultant['credentials'])): ?>
                        <p class="team-slide__credentials"><?= e($consultant['credentials']) ?></p>
                    <?php endif; ?>
                    <p class="team-slide__role"><?= e($consultant['speciality'] ?? '') ?></p>
                    <?php if (trim((string)($consultant['registration_number'] ?? '')) !== ''): ?>
                        <p class="team-slide__registration">Reg No: <?= e($consultant['registration_number']) ?></p>
                    <?php endif; ?>
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

<?php endif; ?>

<!-- Client-provided testimonials -->
<section class="editorial-stack-card section" style="--stack-order:5" data-stack-card data-nebo-chapter="stories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Testimonials</h2>
        </div>
        <div class="nebo-testimonials" aria-label="Client testimonials">
            <figure class="nebo-testimonials__art">
                <img src="/assets/images/editorial/nebo-stories.webp" alt="" width="850" height="1275" loading="lazy" decoding="async">
                <figcaption class="nebo-editorial-art-label">Illustrative scene</figcaption>
            </figure>
            <figure class="nebo-testimonial nebo-testimonial--lead">
                <blockquote>“After years of struggling with PCOS and gut issues, Nebo's holistic approach finally gave me answers. My cycles are regular, energy is high, and I feel like myself again.”</blockquote>
                <figcaption>Priya S. <span>Bengaluru</span></figcaption>
            </figure>
            <figure class="nebo-testimonial">
                <blockquote>“I can finally keep up with my kids and work without crashing.”</blockquote>
                <figcaption>Nebo client <span>Working mother</span></figcaption>
            </figure>
        </div>
    </div>
</section>

<section class="nebo-scroll-statement" data-nebo-chapter="statement" aria-labelledby="nebo-scroll-statement-title">
    <h2 class="container nebo-scroll-statement__title" id="nebo-scroll-statement-title">
        <span>Care for the <em>whole person.</em></span>
    </h2>
</section>

<!-- Focus Areas -->
<span class="editorial-anchor" id="focus-areas" aria-hidden="true"></span>
<section class="editorial-stack-card section" style="--stack-order:7" data-stack-card data-nebo-chapter="focus" aria-labelledby="home-care-title">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title" id="home-care-title">Our Focus Areas</h2>
            <p class="lede">Start with the health concerns and life stages you want support with.</p>
        </div>
        <div class="nebo-care-mosaic">
            <article class="nebo-care-mosaic__card nebo-care-mosaic__card--photo nebo-care-mosaic__card--lead">
                <img src="/assets/images/editorial/nebo-daily-practice.webp" alt="" width="1280" height="854" loading="lazy" decoding="async">
                <div class="nebo-care-mosaic__copy">
                    <h3>Gut Health</h3>
                    <p>Support for digestive concerns such as bloating and IBS, with practical guidance on food, daily habits and tracking symptoms.</p>
                    <a class="focus-program-link" href="<?= e($programAreaHref('gut')) ?>"><?= isset($programAreas['gut']) && $programsEnabled ? 'Explore gut health programs' : 'Talk to our team' ?> <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__copy">
                    <h3>Metabolic Diseases</h3>
                    <p>Lifestyle support for concerns such as insulin resistance, PCOS, thyroid conditions and weight management alongside your existing medical care.</p>
                    <a class="focus-program-link" href="<?= e($programAreaHref('metabolic')) ?>"><?= isset($programAreas['metabolic']) && $programsEnabled ? 'Explore metabolic health programs' : 'Talk to our team' ?> <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <article class="nebo-care-mosaic__card nebo-care-mosaic__card--photo">
                <img src="/assets/images/nebo-garden.webp" alt="" width="1672" height="941" loading="lazy" decoding="async">
                <span class="nebo-editorial-art-label">Illustrative scene</span>
                <div class="nebo-care-mosaic__copy">
                    <h3>Lifestyle Transformation</h3>
                    <p>Build routines you can keep with personalised nutrition, stress management and movement suited to your life stage.</p>
                    <a class="focus-program-link" href="<?= e($programAreaHref('lifestyle')) ?>"><?= isset($programAreas['lifestyle']) && $programsEnabled ? 'Explore lifestyle programs' : 'Talk to our team' ?> <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <article class="nebo-care-mosaic__card">
                <div class="nebo-care-mosaic__copy">
                    <h3>Maternal &amp; Fertility Wellness</h3>
                    <p>Support for preconception, pregnancy and postpartum recovery, with nutrition and lifestyle guidance coordinated with your maternity care.</p>
                    <a class="focus-program-link" href="<?= e($programAreaHref('fertility')) ?>"><?= isset($programAreas['fertility']) && $programsEnabled ? 'Explore fertility and maternal programs' : 'Talk to our team' ?> <span aria-hidden="true">↗</span></a>
                </div>
            </article>
        </div>
        <section class="nebo-practice-gallery" id="movement-and-sound" aria-labelledby="nebo-practice-title">
            <div class="nebo-practice-gallery__heading">
                <h3 id="nebo-practice-title">Workplace wellness</h3>
                <p>Bring guided yoga and sound sessions to your employee wellness plans.</p>
            </div>
            <div class="nebo-practice-gallery__images">
                <figure>
                    <img src="/assets/images/editorial/nebo-workplace-yoga.webp" alt="" width="1020" height="680" loading="lazy" decoding="async">
                    <figcaption>Workplace yoga <span>Guided movement and breathing for desk-based teams.</span></figcaption>
                </figure>
                <figure>
                    <img src="/assets/images/editorial/nebo-workplace-sound-healing.webp" alt="" width="1020" height="680" loading="lazy" decoding="async">
                    <figcaption>Sound healing workshops <span>A guided introduction to singing bowls and sound.</span></figcaption>
                </figure>
                <figure>
                    <img src="/assets/images/editorial/nebo-workplace-sound-bath.webp" alt="" width="1020" height="680" loading="lazy" decoding="async">
                    <figcaption>Group sound baths <span>A shared listening session with time to pause.</span></figcaption>
                </figure>
            </div>
            <p class="nebo-practice-gallery__credit">Images are illustrative.</p>
            <div class="nebo-practice-gallery__actions">
                <a class="btn btn-primary" href="/contact?subject=corporate#contact-form">Enquire about a team session</a>
                <a class="focus-program-link" href="https://www.instagram.com/nebolifestyleclinic/">See Nebo’s campaigns <span aria-hidden="true">↗</span></a>
            </div>
        </section>
    </div>
</section>

<!-- Why Choose Nebo -->
<section class="editorial-stack-card section section--warm" style="--stack-order:8" data-stack-card data-nebo-chapter="confidence">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Why Choose Nebo?</h2>
        </div>
        <?php
        $homeFigures = [];
        if (empty($teamUnavailable)) {
            $homeFigures[] = [count($consultants), 'Practitioner profiles', 'Meet the team and see their specialities.'];
        }
        $homeFigures[] = [count($consultationSteps), 'Consultation steps', 'From sharing your goals to planning care.'];
        if (!empty($therapyProducts)) {
            $homeFigures[] = [count($therapyProducts), 'Featured services', 'Clinic-led options to explore.'];
        }
        require __DIR__ . '/../partials/home-figures.php';
        ?>
        <div class="value-strip">
            <?php foreach ($whyChooseNebo as [$title, $description]): ?>
                <article class="value-card">
                    <h3><?= e($title) ?></h3>
                    <p><?= e($description) ?></p>
                </article>
            <?php endforeach; ?>
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
                <h2>Book Your Consultation</h2>
                <p>Request a 30-minute discovery call to share your goals, discuss the support you need, and plan your next step with the clinic.</p>
                <a class="btn btn-primary" href="/contact#contact-form">Request a consultation <span aria-hidden="true">↗</span></a>
                <address class="consultation-invitation__contact">
                    <span>Kaliyal, Kanyakumari, Tamil Nadu, India 629101</span>
                    <a href="tel:+917200182025">+91 72001 82025</a>
                    <a href="tel:+919585182025">+91 95851 82025</a>
                    <a href="mailto:nebolifestyleclinic@gmail.com">nebolifestyleclinic@gmail.com</a>
                </address>
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
                <h2 class="section-title">Resources</h2>
                <ul class="nebo-resource-split__links">
                    <li><a href="/about">The clinic and Foundation <span aria-hidden="true">↗</span></a></li>
                    <li><a href="#focus-areas">Our Focus Areas <span aria-hidden="true">↗</span></a></li>
                    <?php if (module_on('blog')): ?>
                        <li><a href="/blog">Wellness Blog <span aria-hidden="true">↗</span></a></li>
                        <li><a href="/blog/category/help">Free website guides <span aria-hidden="true">↗</span></a></li>
                    <?php endif; ?>
                    <li><a href="/contact#contact-form">Ask about webinars <span aria-hidden="true">↗</span></a></li>
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

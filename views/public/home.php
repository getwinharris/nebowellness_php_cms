<section class="home-hero nebo-home-hero">
    <div class="container home-hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Integrative Health · Natural Healing · Lifestyle Medicine</span>
            <h1>Where Science Meets Nature for Lifelong Wellness</h1>
            <p class="lede">At Nebo Lifestyle Clinic, we specialise in naturopathy, functional medicine, and maternal & fertility wellness, offering personalised care for gut health, metabolic diseases, and sustainable lifestyle transformation.</p>
            <div class="hero-actions">
                <a href="/contact" class="btn btn-primary">Book Your Consultation</a>
                <a href="#programs" class="btn btn-secondary">Explore Our Programs</a>
            </div>
        </div>
    </div>
</section>

<!-- About Nebo Foundation -->
<section class="section section--alt nebo-intro-section">
    <div class="container">
        <div class="nebo-intro-image"><img src="/assets/images/nebo-programs.png" alt="Illustrative nutrition consultation in a calm clinic setting" loading="lazy"></div>
        <div class="section-header">
            <span class="eyebrow serif-accent">About Us</span>
            <h2 class="section-title">The Nebo Foundation</h2>
            <p class="lede">Nebo Lifestyle Clinic is a flagship initiative of the Nebo Foundation, dedicated to making integrative, root-cause healthcare accessible and sustainable.</p>
        </div>
        <div style="max-width:900px; margin:0 auto;">
            <h3 style="color:var(--color-primary); margin-top:var(--space-xl);">Our Vision</h3>
            <p>To transform how people approach chronic disease and wellness—by combining the wisdom of naturopathy with the precision of functional medicine, all delivered through compassionate, personalized care.</p>

            <h3 style="color:var(--color-primary); margin-top:var(--space-xl);">Our Pillars</h3>
            <ul style="list-style:none; padding:0; margin:var(--space-md) 0;">
                <li style="padding:var(--space-xs) 0;">✓ Gut Health & Digestive Restoration</li>
                <li style="padding:var(--space-xs) 0;">✓ Metabolic Health Support (diabetes, PCOS, thyroid, fatty liver)</li>
                <li style="padding:var(--space-xs) 0;">✓ Lifestyle Medicine & Sustainable Habit Change</li>
            </ul>
        </div>
    </div>
</section>

<!-- Focus Areas -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow serif-accent">What We Treat</span>
            <h2 class="section-title">Our Focus Areas</h2>
        </div>
        <div class="value-strip">
            <article class="value-card reveal">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                </div>
                <h3>Gut Health</h3>
                <p>Explore practitioner-led nutrition and lifestyle support for digestion, bloating, IBS symptoms, and everyday gut wellbeing.</p>
            </article>
            <article class="value-card reveal">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3>Metabolic Diseases</h3>
                <p>Support insulin sensitivity, PCOS and thyroid care, and sustainable weight goals alongside appropriate medical treatment.</p>
            </article>
            <article class="value-card reveal">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Lifestyle Transformation</h3>
                <p>Build lasting habits with personalized nutrition, stress management, and movement plans designed for your unique biology and life stage.</p>
            </article>
            <article class="value-card reveal">
                <div class="value-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <h3>Maternal & Fertility Wellness</h3>
                <p>Support preconception health, pregnancy vitality, and postpartum recovery with gentle, drug-free therapies that nurture both mother and baby.</p>
            </article>
        </div>
    </div>
</section>

<!-- Why Choose Nebo -->
<section class="section section--warm">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow serif-accent">Why Nebo</span>
            <h2 class="section-title">Why Choose Nebo Wellness?</h2>
        </div>
        <div class="value-strip" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
            <article class="value-card reveal">
                <h3>Root-cause, personalized care</h3>
                <p>Nebo considers digestion, hormones, metabolism, stress, history, and goals when shaping a personalised care plan.</p>
            </article>
            <article class="value-card reveal">
                <h3>Safe, expert-led, measurable</h3>
                <p>Practitioner-led, non-invasive wellbeing support with clear goals, review points, and coordination with existing medical care.</p>
            </article>
            <article class="value-card reveal">
                <h3>Integrative, maternal-first, sustainable</h3>
                <p>Combines naturopathy, nutrition, lifestyle medicine, and evidence-based testing; offers specialized preconception/pregnancy/postpartum protocols; delivers practical, culturally relevant plans you can maintain long-term.</p>
            </article>
        </div>
    </div>
</section>

<!-- Programs -->
<section class="section" id="programs">
    <div class="container home-rail-section">
        <div class="home-rail-heading">
            <div>
                <span class="eyebrow serif-accent">Our Services</span>
                <h2 class="section-title">Our Programs</h2>
                <p class="lede">Personalised, practitioner-led pathways designed around your health goals.</p>
            </div>
            <div class="rail-controls" aria-label="Program slides">
                <button type="button" class="rail-control" data-rail-prev="program-rail" aria-label="Previous programs">←</button>
                <button type="button" class="rail-control" data-rail-next="program-rail" aria-label="Next programs">→</button>
            </div>
        </div>
        <?php $programs = [
            ['Gut Restoration Program','6–12 weeks','Nutrition, microbiome support and daily habits shaped around your symptoms and clinical assessment.','program-card--blue'],
            ['Metabolic Reset','Personalised duration','A practical plan for insulin sensitivity, movement, sleep, stress and sustainable weight management.','program-card--sage'],
            ['Fertility & Preconception Wellness','Personalised duration','Nutrition and lifestyle guidance that supports preconception preparation and reproductive wellbeing.','program-card--rose'],
            ['Maternal Vitality','Stage based','Gentle wellbeing support through pregnancy and after delivery, coordinated around each mother’s needs.','program-card--mist'],
            ['Lifestyle Transformation','90 days','A guided pathway that brings nutrition, movement, stress care and habit coaching into daily life.','program-card--violet'],
        ]; ?>
        <div class="editorial-rail" id="program-rail" data-editorial-rail tabindex="0" aria-label="Nebo wellness programs">
            <?php foreach ($programs as $index => $program): ?>
            <article class="program-slide reveal <?= e($program[3]) ?>">
                <div class="program-slide__visual" aria-hidden="true"><span><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span></div>
                <div class="program-slide__body">
                    <span class="program-slide__meta"><?= e($program[1]) ?></span>
                    <h3><?= e($program[0]) ?></h3>
                    <p><?= e($program[2]) ?></p>
                    <a href="/contact?program=<?= rawurlencode($program[0]) ?>#contact-form" class="program-slide__link">Enquire about this program <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Team -->
<section class="section section--alt">
    <div class="container home-rail-section">
        <div class="home-rail-heading">
            <div>
                <span class="eyebrow serif-accent">Expert Care</span>
                <h2 class="section-title">Meet Our Team</h2>
                <p class="lede">A multidisciplinary team of naturopathy and wellness practitioners.</p>
            </div>
            <div class="rail-controls" aria-label="Team slides">
                <button type="button" class="rail-control" data-rail-prev="team-rail" aria-label="Previous team members">←</button>
                <button type="button" class="rail-control" data-rail-next="team-rail" aria-label="Next team members">→</button>
            </div>
        </div>
        <div class="editorial-rail team-rail" id="team-rail" data-editorial-rail tabindex="0" aria-label="Nebo practitioner profiles">
            <?php foreach ($consultants as $consultant): ?>
            <article class="team-slide reveal">
                <div class="team-slide__media">
                    <img src="<?= e($consultant['photo_url'] ?? '/assets/images/consultants/default-female.webp') ?>" alt="Temporary portrait placeholder for <?= e($consultant['name'] ?? 'Nebo practitioner') ?>" loading="lazy">
                    <span>Portrait placeholder</span>
                </div>
                <div class="team-slide__body">
                    <h3><?= e($consultant['name'] ?? '') ?></h3>
                    <p class="team-slide__role"><?= e($consultant['speciality'] ?? '') ?></p>
                    <?php if (!empty($consultant['description'])): ?><p><?= e($consultant['description']) ?></p><?php endif; ?>
                    <a href="/contact?consultant=<?= rawurlencode((string)($consultant['name'] ?? '')) ?>#contact-form" class="program-slide__link">Request a consultation <span aria-hidden="true">↗</span></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Client-provided testimonials -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow serif-accent">Client Stories</span>
            <h2 class="section-title">Experiences shared by Nebo clients</h2>
        </div>
        <div class="nebo-testimonials" aria-label="Client testimonials">
            <figure class="nebo-testimonial value-card reveal">
                <blockquote>“After years of struggling with PCOS and gut issues, Nebo's holistic approach finally gave me answers. My cycles are regular, energy is high, and I feel like myself again.”</blockquote>
                <figcaption>Priya S. <span>Bengaluru</span></figcaption>
            </figure>
            <figure class="nebo-testimonial value-card reveal">
                <blockquote>“The IV nutrition and ozone therapy transformed my chronic fatigue. I can finally keep up with my kids and work without crashing.”</blockquote>
                <figcaption>Nebo client <span>Client-provided testimonial</span></figcaption>
            </figure>
        </div>
    </div>
</section>

<!-- Contact CTA -->
<section class="section section--warm">
    <div class="container">
        <div class="page-cta-card reveal" style="text-align:center;">
            <div>
                <span class="page-cta-card__eyebrow">Ready to Transform Your Health?</span>
                <h3>Book Your Consultation</h3>
                <p>Start your journey to optimal health. Schedule a 30-minute discovery call to discuss your goals and create a personalized plan.</p>
            </div>
            <a class="btn btn-primary page-cta-card__button" href="/contact">Book Now →</a>
        </div>
    </div>
</section>

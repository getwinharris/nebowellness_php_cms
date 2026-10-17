<section class="section" style="padding-top:var(--space-xl);">
    <div class="container">
        <span class="eyebrow serif-accent">Support</span>
        <h1 class="section__title">How can we help?</h1>
        <p class="lede">Find program information and ways to reach the Nebo clinic team.</p>

        <div class="support-grid">
            <?php if (!empty($supportNav)): ?>
                <?php foreach ($supportNav as $section): ?>
                <article class="support-card">
                    <h2><?= e($section['section'] ?? '') ?></h2>
                    <ul style="list-style:none; padding:0; margin:0;">
                        <?php foreach ($section['links'] ?? [] as $link): ?>
                        <li style="margin-bottom:var(--space-xs);">
                            <a href="<?= e($link['path'] ?? '#') ?>" style="font-weight:500;">
                                <?= e($link['label'] ?? $link['path'] ?? '') ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
            <article class="support-card">
                <h2>Explore programs</h2>
                <p>Review Nebo's gut health, metabolic wellness, maternal care, and lifestyle programs on the <a href="/#programs">home page</a>.</p>
            </article>
            <article class="support-card">
                <h2>Read the journal</h2>
                <p>Our <a href="/blog">wellness journal</a> explains what to expect and how we approach sustainable habits.</p>
            </article>
            <article class="support-card">
                <h2>Talk to a person</h2>
                <p>Email <a href="mailto:nebolifestyleclinic@gmail.com">nebolifestyleclinic@gmail.com</a> or call <a href="tel:+917200182025">+91 72001 82025</a>.</p>
            </article>
            <?php endif; ?>
        </div>

        <div class="page-cta-card" style="margin-top:var(--space-xl);">
            <div>
                <h2>Still need help?</h2>
                <p>Send us the details and our team will get back to you quickly.</p>
            </div>
            <a href="/contact#contact-form" class="btn btn-primary">Contact support</a>
        </div>
    </div>
</section>

<section class="section support-page">
    <div class="container">
        <div class="support-page__intro">
            <span class="eyebrow serif-accent">Support</span>
            <h1 class="section__title">How can we help?</h1>
            <p class="lede">Find program information and ways to reach the Nebo clinic team.</p>
            <div class="support-page__actions">
                <button class="btn btn-primary" type="button" id="support-page-launch" aria-controls="support-panel" aria-expanded="false" hidden>Chat with Nebo Support</button>
                <a href="/contact#contact-form" class="btn btn-outline">Contact the clinic</a>
            </div>
        </div>

        <div class="support-grid">
            <?php if (!empty($supportNav)): ?>
                <?php foreach ($supportNav as $section): ?>
                <article class="support-card">
                    <h2><?= e($section['section'] ?? '') ?></h2>
                    <ul>
                        <?php foreach ($section['links'] ?? [] as $link): ?>
                        <li>
                            <a href="<?= e($link['path'] ?? '#') ?>"><?= e($link['label'] ?? $link['path'] ?? '') ?> <span aria-hidden="true">↗</span></a>
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
    </div>
</section>

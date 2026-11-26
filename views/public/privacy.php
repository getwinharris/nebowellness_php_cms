<section class="section document-page privacy-page">
    <div class="container container--narrow">
        <header class="document-page__header">
            <span class="eyebrow serif-accent">Legal</span>
            <h1 class="section-title"><?= e($document['title'] ?? 'Privacy Policy') ?></h1>
            <p class="lede">A clear explanation of how the platform handles personal information.</p>
        </header>
        <section id="support-privacy" aria-labelledby="support-privacy-title">
            <h2 id="support-privacy-title">Support conversations</h2>
            <p>The automated assistant uses temporary conversation memory. If you choose to save a conversation or send a ticket, clinic staff can review the visible chat and recorded tool and provider activity. Please do not enter medical records, passwords or verification codes. Saved session records are available to authorized clinic staff for 30 days; expired records are eligible for owner cleanup. Ticket messages and replies are kept separately for follow-up.</p>
        </section>
        <?php if (!empty($__measurement)): ?>
        <section id="measurement" class="measurement-preferences" aria-labelledby="measurement-title" hidden>
            <h2 id="measurement-title">Measurement preferences</h2>
            <p>Choose whether to measure homepage and About page visits in this browser. Optional measurement is off until you choose it. Providers can receive your device’s IP address and browser information.</p>
            <form id="measurement-choices">
                <?php if ($__measurement['ga'] !== ''): ?>
                    <label><input type="checkbox" name="analytics"> Share page visits with Google Analytics</label>
                <?php endif; ?>
                <?php if ($__measurement['ads'] !== '' || $__measurement['pixel'] !== ''): ?>
                    <label><input type="checkbox" name="advertising"> Share page views with <?= e(implode(' and ', array_filter([$__measurement['ads'] !== '' ? 'Google Ads' : '', $__measurement['pixel'] !== '' ? 'Meta' : '']))) ?></label>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Save preferences</button>
                <p role="status" aria-live="polite"></p>
            </form>
        </section>
        <?php endif; ?>
        <article class="document-page__content reveal"><?= $document['html'] ?? '' ?></article>
    </div>
</section>

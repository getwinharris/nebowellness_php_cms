(() => {
    const root = document.getElementById('main-content');
    if (!root || !('IntersectionObserver' in window)) return;
    const preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (preference.matches) return;
    const admin = root.classList.contains('admin-main');
    const home = root.classList.contains('home-main');
    const selectors = admin ? [
        '.admin-body > .admin-card', '.admin-body > .dashboard-overview > .dashboard-heading',
        '.dashboard-insight', '.agent-workspace__panel', '.agent-shell > h2',
        '.admin-body > .admin-form', '.admin-body > .admin-media-grid > .admin-media-tile'
    ] : home ? [] : [
        '.nebo-about-intro__copy > h1', '.nebo-about-intro__copy > p', '.nebo-about-photo',
        '.nebo-about-section-heading', '.about-story-card', '.about-feature-card', '.value-card',
        '.contact-intro__copy > h1', '.contact-intro__copy > .lede', '.contact-intro__image',
        '.contact-form-card__title', '.contact-details__group',
        '.campaign-index__intro', '.campaign-index .therapy-feature', '.campaign-index .therapy-product',
        '.campaign-hero__inner > h1', '.campaign-hero__summary', '.campaign-hero__duration',
        '.campaign-hero__media', '.campaign-page__body > h2', '.campaign-page__aside > h2',
        '.campaign-enquiry__intro > h2', '.campaign-enquiry__intro > p',
        '.blog-editorial__header', '.blog-card', '.blog-post__header', '.blog-post__image',
        '.support-page__intro > h1', '.support-card', '.auth-card > h1', '.auth-visual > h2',
        '.shop-page__heading', '.document-page__header', '.account-content > h1',
        '.account-install__header', '.therapy-request'
    ];
    if (!selectors.length) return;
    const targets = Array.from(root.querySelectorAll(selectors.join(', ')));
    // Generic fallback for route families with a different semantic heading.
    if (!targets.length) {
        const title = root.querySelector('h1');
        if (title) targets.push(title);
    }
    const observer = new IntersectionObserver(entries => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            observer.unobserve(entry.target);
            if (!preference.matches && !entry.target.matches(':focus-within')) {
                entry.target.classList.add('site-motion-entered');
            }
        }
    }, {threshold: 0, rootMargin: '0px 0px -24px 0px'});
    targets.forEach((element, index) => {
        element.style.setProperty('--site-motion-delay', `${Math.min(index % 5, 4) * (admin ? 24 : 50)}ms`);
        observer.observe(element);
    });
    preference.addEventListener?.('change', event => {
        if (event.matches) observer.disconnect();
    });
})();

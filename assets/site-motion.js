(() => {
    const root = document.getElementById('main-content');
    if (!root) return;
    const outline = root.querySelector('[data-program-outline]');
    const body = root.querySelector('.campaign-page__body');
    if (outline && body) {
        const headings = Array.from(body.querySelectorAll('h2')).filter(h => h.textContent.trim()).slice(0, 12);
        const links = outline.querySelector('ul');
        if (headings.length >= 2 && links) {
            const items = headings.map((heading, index) => {
                let id = 'nebo-program-section-' + (index + 1);
                while (document.getElementById(id) && document.getElementById(id) !== heading) id += '-heading';
                // Preserve existing unique fragment targets, but do not accept arbitrary destinations.
                if (!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(heading.id) || document.getElementById(heading.id) !== heading) heading.id = id;
                heading.tabIndex = -1;
                const li = document.createElement('li');
                const link = document.createElement('a');
                link.href = '#' + heading.id;
                link.textContent = heading.textContent.trim();
                li.append(link);
                links.append(li);
                return link;
            });
            outline.hidden = false;
            const markCurrent = index => {
                items.forEach((link, i) => {
                    if (i === index) link.setAttribute('aria-current','location');
                    else link.removeAttribute('aria-current');
                });
            };
            items.forEach((link, i) => link.addEventListener('click', () => markCurrent(i)));
            if ('IntersectionObserver' in window) {
                const sectionObserver = new IntersectionObserver(entries => {
                    if (!entries.some(e => e.isIntersecting)) return;
                    const limit = (document.querySelector('.site-header')?.offsetHeight || 0) + window.innerHeight * .45;
                    let current = -1;
                    headings.forEach((heading, i) => { if (heading.getBoundingClientRect().top <= limit) current = i; });
                    markCurrent(current);
                }, {rootMargin: '0px 0px -55% 0px', threshold: 0});
                headings.forEach(heading => sectionObserver.observe(heading));
            }
        }
    }
    if (!('IntersectionObserver' in window)) return;
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
        '.blog-editorial__header', '.blog-card', '.blog-post__header', '.blog-post__featured',
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

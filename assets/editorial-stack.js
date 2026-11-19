(() => {
    const header = document.querySelector('.site-header');
    const stack = document.querySelector('[data-editorial-stack]');
    if (!header) return;

    function measureHeader() {
        document.documentElement.style.setProperty('--site-header-height', `${header.offsetHeight}px`);
    }

    measureHeader();
    if (!stack) {
        if ('ResizeObserver' in window) new ResizeObserver(measureHeader).observe(header, { box: 'border-box' });
        else window.addEventListener('resize', measureHeader);
        return;
    }

    const footer = document.querySelector('.site-footer');
    if (footer) {
        footer.classList.add('editorial-stack-card', 'landing-footer-card');
        footer.setAttribute('data-stack-card', '');
        footer.style.setProperty('--stack-order', '10');
        stack.append(footer);
    }

    const cards = Array.from(stack.children).filter(card => card.hasAttribute('data-stack-card'));
    function measureCard(card) {
        card.style.setProperty('--stack-height', `${card.offsetHeight}px`);
    }
    function measureLayout() {
        measureHeader();
        cards.forEach(measureCard);
    }

    stack.classList.add('stack-ready');
    measureLayout();

    // Enhanced spacing changes fragment offsets after the browser's first layout.
    const initialHash = location.hash;
    const initialTarget = document.getElementById(initialHash.slice(1));
    const navigation = performance.getEntriesByType('navigation')[0];
    if (initialTarget?.classList.contains('editorial-anchor') && navigation?.type === 'navigate') {
        const input = new AbortController();
        for (const name of ['pointerdown', 'keydown', 'wheel']) {
            window.addEventListener(name, () => input.abort(), { once: true, signal: input.signal });
        }

        function alignFragment() {
            requestAnimationFrame(() => {
                if (input.signal.aborted || location.hash !== initialHash) return;
                const top = initialTarget.getBoundingClientRect().top + window.scrollY - header.offsetHeight;
                window.scrollTo({ top, behavior: 'instant' });
            });
        }

        alignFragment();
        if (document.readyState !== 'complete') {
            window.addEventListener('load', alignFragment, { once: true, signal: input.signal });
        }
    }

    if ('ResizeObserver' in window) {
        const resize = new ResizeObserver(entries => {
            for (const entry of entries) {
                if (entry.target === header) measureHeader();
                else measureCard(entry.target);
            }
        });
        resize.observe(header, { box: 'border-box' });
        cards.forEach(card => resize.observe(card, { box: 'border-box' }));
    } else {
        window.addEventListener('resize', measureLayout);
        window.addEventListener('load', measureLayout);
        // Capture also sees late-loaded images and a successful image fallback.
        stack.addEventListener('load', measureLayout, true);
        document.fonts?.ready.then(measureLayout);
    }

    stack.addEventListener('focusin', event => {
        const target = event.target;
        const keyboardFocus = target.matches(':focus-visible');
        stack.classList.toggle('stack-keyboard-flow', keyboardFocus);
        if (!keyboardFocus) return;

        // A pinned link can sit behind a later chapter even when it is in the viewport.
        requestAnimationFrame(() => {
            if (document.activeElement !== target) return;
            const bounds = target.getBoundingClientRect();
            const controls = document.querySelectorAll('.mobile-cart-tray:not([hidden]), .support-fab');
            const overlapsControl = Array.from(controls).some(control => {
                const area = control.getBoundingClientRect();
                return area.width > 0 && bounds.left < area.right && bounds.right > area.left
                    && bounds.top < area.bottom && bounds.bottom > area.top;
            });
            if (bounds.top < header.getBoundingClientRect().bottom || bounds.bottom > window.innerHeight || overlapsControl) {
                target.scrollIntoView({ block: 'center', behavior: 'instant' });
            }
        });
    });
    stack.addEventListener('focusout', event => {
        if (!stack.contains(event.relatedTarget)) stack.classList.remove('stack-keyboard-flow');
    });

    if (!('IntersectionObserver' in window)) return;
    const chapterObserver = new IntersectionObserver(entries => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const target = entry.target;
            if (target.matches('[data-stack-card], h1, h2')) {
                target.closest('[data-stack-card]').classList.add('chapter-arrived');
            } else {
                target.classList.add('chapter-item-arrived');
            }
            chapterObserver.unobserve(target);
        }
    }, { threshold: 0.15, rootMargin: `-${header.offsetHeight}px 0px -32px 0px` });

    const items = [
        '.value-strip > .value-card', '.therapy-feature', '.therapy-product',
        '.editorial-rail > .program-slide', '.team-rail .team-slide', '.team-detail',
        '.nebo-testimonials > figure', '.consultation-invitation__steps > li', '.footer-grid > div',
    ].join(', ');
    for (const card of cards) {
        chapterObserver.observe(card.querySelector('h1, h2') || card);
        card.querySelectorAll(items).forEach((item, index) => {
            item.style.setProperty('--chapter-delay', `${Math.min(index, 3) * 40}ms`);
            chapterObserver.observe(item);
        });
    }
})();

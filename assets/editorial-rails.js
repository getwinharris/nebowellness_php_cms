(() => {
    const rails = document.querySelectorAll('[data-editorial-rail]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const reveal = 'IntersectionObserver' in window
        ? new IntersectionObserver(entries => {
            for (const entry of entries) {
                if (!entry.isIntersecting) continue;
                entry.target.classList.add('rail-entered');
                reveal.unobserve(entry.target);
            }
        }, { threshold: .16, rootMargin: '0px 0px -40px 0px' })
        : null;

    for (const rail of rails) {
        const allCards = Array.from(rail.children);
        let cards = allCards;
        if (!cards.length) continue;
        const filters = document.querySelector(`[data-rail-filters="${rail.id}"]`);
        const controls = Array.from(document.querySelectorAll('[data-rail-prev], [data-rail-next]'));
        const previous = controls.find(button => button.dataset.railPrev === rail.id);
        const next = controls.find(button => button.dataset.railNext === rail.id);
        let selected = 0;
        let settling;
        let moving = false;

        function stops() {
            const max = Math.max(0, rail.scrollWidth - rail.clientWidth);
            const start = cards[0].offsetLeft;
            const positions = [];
            for (const [index, card] of cards.entries()) {
                const left = Math.min(card.offsetLeft - start, max);
                if (!positions.length || left - positions.at(-1).left > 1) positions.push({ index, left });
            }
            return positions;
        }

        function select(positions, position) {
            selected = position;
            cards.forEach((card, index) => card.classList.toggle('is-rail-current', index === positions[position].index));
            if (previous) previous.disabled = position === 0;
            if (next) next.disabled = position === positions.length - 1;
            if (rail.hasAttribute('data-overflow-controls')) {
                const overflow = positions.length > 1;
                if (!overflow && [previous, next, rail].includes(document.activeElement)) {
                    cards[0].querySelector('a[href]')?.focus();
                }
                if (previous) previous.hidden = !overflow;
                if (next) next.hidden = !overflow;
                if (overflow) rail.tabIndex = 0;
                else rail.removeAttribute('tabindex');
            }
        }

        function sync() {
            const positions = stops();
            const closest = positions.reduce((best, current, index) =>
                Math.abs(current.left - rail.scrollLeft) < Math.abs(positions[best].left - rail.scrollLeft) ? index : best, 0);
            select(positions, closest);
        }

        function revealFilter(button) {
            if (filters.scrollWidth <= filters.clientWidth) return;
            const stripBounds = filters.getBoundingClientRect();
            const buttonBounds = button.getBoundingClientRect();
            const padding = getComputedStyle(filters);
            const clipLeft = stripBounds.left + parseFloat(padding.paddingLeft);
            const clipRight = stripBounds.right - parseFloat(padding.paddingRight);
            const hiddenLeft = Math.min(0, buttonBounds.left - clipLeft);
            const hiddenRight = Math.max(0, buttonBounds.right - clipRight);
            filters.scrollLeft += hiddenLeft || hiddenRight;
        }

        function revealCurrentFilter() {
            const focused = filters?.querySelector('[data-rail-filter]:focus');
            const selectedFilter = focused || filters?.querySelector('[aria-pressed="true"]');
            if (selectedFilter) revealFilter(selectedFilter);
        }

        function resize() {
            sync();
            revealCurrentFilter();
        }

        function move(direction) {
            const positions = stops();
            const position = Math.min(selected, positions.length - 1) + direction;
            if (!positions[position]) return;
            moving = true;
            clearTimeout(settling);
            select(positions, position);
            rail.scrollTo({ left: positions[position].left, behavior: reducedMotion.matches ? 'auto' : 'smooth' });
            settling = setTimeout(() => {
                moving = false;
                sync();
            }, 500);
        }

        previous?.addEventListener('click', () => move(-1));
        next?.addEventListener('click', () => move(1));
        if (filters) {
            const buttons = Array.from(filters.querySelectorAll('[data-rail-filter]'));
            const status = filters.querySelector('[data-rail-filter-status]');

            function applyFilter(button) {
                if (button.getAttribute('aria-pressed') === 'true') return;
                const area = button.dataset.railFilter;
                const matching = allCards.filter(card => area === 'all' || card.dataset.railArea === area);
                if (!matching.length) return;
                clearTimeout(settling);
                moving = false;
                cards = matching;
                for (const card of allCards) {
                    card.classList.remove('is-rail-current');
                    card.hidden = !cards.includes(card);
                }
                for (const filter of buttons) filter.setAttribute('aria-pressed', String(filter === button));
                rail.scrollTo({left: 0, behavior: 'instant'});
                sync();
                if (!reducedMotion.matches) {
                    cards.slice(0, 4).forEach((card, index) => {
                        const body = card.querySelector('.program-slide__body');
                        if (body && typeof body.animate === 'function') {
                            body.getAnimations().filter(animation => animation.id === 'program-filter').forEach(animation => animation.cancel());
                            const animation = body.animate([{opacity: .72, translate: '0 9px'}, {opacity: 1, translate: '0 0'}],
                                {duration: 360, delay: index * 35, easing: 'cubic-bezier(.22,1,.36,1)'});
                            animation.id = 'program-filter';
                        }
                    });
                }
                if (status) status.textContent = cards.length === 1 ? '1 program shown' : `${cards.length} programs shown`;
            }

            function filterFragment(button) {
                const area = button.dataset.railFilter;
                return area === 'all' ? '#programs' : `#programs-${area}`;
            }

            function restoreFilter() {
                const button = buttons.find(filter => filterFragment(filter) === location.hash)
                    || (location.hash.startsWith('#programs-') ? buttons.find(filter => filter.dataset.railFilter === 'all') : null);
                if (!button) return;
                applyFilter(button);
                revealCurrentFilter();
            }

            for (const button of buttons) {
                button.addEventListener('focus', () => revealFilter(button));
                button.addEventListener('click', () => {
                    applyFilter(button);
                    revealFilter(button);
                    if (buttons.some(filter => filterFragment(filter) === location.hash)) {
                        history.replaceState(history.state, '', filterFragment(button));
                    }
                });
            }
            window.addEventListener('hashchange', restoreFilter);
            window.addEventListener('pageshow', () => requestAnimationFrame(restoreFilter));
            document.fonts?.ready.then(restoreFilter);
            filters.hidden = false;
            restoreFilter();
        }
        rail.addEventListener('scroll', () => {
            if (moving) return;
            clearTimeout(settling);
            settling = setTimeout(sync, 60);
        }, { passive: true });
        rail.addEventListener('scrollend', () => {
            if (moving) return;
            clearTimeout(settling);
            sync();
        });
        if ('ResizeObserver' in window) new ResizeObserver(resize).observe(rail);
        else window.addEventListener('resize', resize);
        sync();
        if (reveal) reveal.observe(rail);
        else rail.classList.add('rail-entered');
    }
})();

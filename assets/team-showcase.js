(() => {
    const showcase = document.querySelector('[data-team-showcase]');
    if (!showcase) return;
    const rail = showcase.querySelector('.team-rail');
    const detail = showcase.querySelector('.team-detail');
    const cards = Array.from(rail.children);
    if (!cards.length) return;

    const controls = showcase.closest('section').querySelector('[data-team-controls]');
    const previous = controls.querySelector('[data-rail-prev]');
    const next = controls.querySelector('[data-rail-next]');
    const portraits = cards.map(card => card.querySelector('button'));
    const biographies = cards.map(card => card.querySelector('.team-slide__body'));
    const status = showcase.querySelector('[data-team-status]');
    let selected = 0;
    let lastScrollEnd = 0;

    function select(index) {
        selected = Math.max(0, Math.min(index, cards.length - 1));
        cards.forEach((card, position) => {
            const active = selected === position;
            card.classList.toggle('is-rail-current', active);
            card.style.order = String(active ? cards.length : (position - selected - 1 + cards.length) % cards.length);
            portraits[position].setAttribute('aria-pressed', String(active));
            portraits[position].tabIndex = active ? 0 : -1;
            biographies[position].classList.toggle('is-team-current', active);
        });
        status.textContent = portraits[selected].querySelector('.team-preview-name').textContent;
        lastScrollEnd = rail.scrollWidth - rail.clientWidth;
        rail.scrollLeft = lastScrollEnd;
        const focused = document.activeElement;
        previous.disabled = selected === 0;
        next.disabled = selected === cards.length - 1;
        if (focused === previous && previous.disabled) next.focus({ preventScroll: true });
        if (focused === next && next.disabled) previous.focus({ preventScroll: true });
    }

    portraits.forEach((button, index) => {
        button.disabled = false;
        button.addEventListener('click', () => select(index));
    });
    previous.addEventListener('click', () => select(selected - 1));
    next.addEventListener('click', () => select(selected + 1));
    rail.addEventListener('keydown', event => {
        if (!portraits.includes(event.target)) return;
        const target = { ArrowRight: selected + 1, ArrowLeft: selected - 1, Home: 0, End: cards.length - 1 }[event.key];
        if (target === undefined) return;
        event.preventDefault();
        select(target);
        portraits[selected].focus({ preventScroll: true });
    });

    function keepFeaturedInView() {
        const end = rail.scrollWidth - rail.clientWidth;
        if (Math.abs(rail.scrollLeft - lastScrollEnd) <= 1) rail.scrollLeft = end;
        lastScrollEnd = end;
    }
    detail.append(...biographies);
    showcase.classList.add('team-enhanced');
    controls.hidden = cards.length < 2;
    select(0);
    if ('ResizeObserver' in window) new ResizeObserver(keepFeaturedInView).observe(rail);
    else window.addEventListener('resize', keepFeaturedInView);
})();

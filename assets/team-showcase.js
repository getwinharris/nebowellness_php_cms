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
    const dots = Array.from(controls.querySelectorAll('[data-team-index]'));
    const positionLabel = controls.querySelector('[data-team-position]');
    let selected = 0;

    function select(index) {
        selected = Math.max(0, Math.min(index, cards.length - 1));
        cards.forEach((card, position) => {
            const active = selected === position;
            card.classList.toggle('is-rail-current', active);
            // The reversed rail starts at the featured tile's edge at every width.
            card.style.order = String(active ? 0 : (selected - position + cards.length) % cards.length);
            portraits[position].setAttribute('aria-pressed', String(active));
            portraits[position].tabIndex = active ? 0 : -1;
            biographies[position].classList.toggle('is-team-current', active);
        });
        dots.forEach((dot, position) => dot.setAttribute('aria-pressed', String(selected === position)));
        if (positionLabel) positionLabel.textContent = String(selected + 1).padStart(2, '0') + ' / ' + String(cards.length).padStart(2, '0');
        status.textContent = portraits[selected].querySelector('.team-preview-name').textContent;
        rail.scrollLeft = 0;
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
    dots.forEach((dot, index) => dot.addEventListener('click', () => select(index)));
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

    detail.append(...biographies);
    showcase.dataset.teamCount = String(Math.min(cards.length, 3));
    showcase.classList.add('team-enhanced');
    controls.hidden = cards.length < 2;
    select(0);
})();

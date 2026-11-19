(() => {
    const drawer = document.getElementById('navigation-drawer');
    const fallback = document.getElementById('navigation-fallback');
    const summary = fallback?.querySelector('summary');
    const nav = fallback?.querySelector('#primary-nav');
    const close = drawer?.querySelector('.nav-close');
    if (typeof drawer?.showModal !== 'function' || !summary || !nav || !close) return;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'menu-toggle';
    trigger.setAttribute('aria-label', 'Menu');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', drawer.id);
    trigger.setAttribute('aria-expanded', 'false');
    for (const child of summary.childNodes) trigger.append(child.cloneNode(true));

    trigger.addEventListener('click', () => {
        drawer.showModal();
        trigger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('navigation-drawer-open');
    });
    drawer.addEventListener('close', () => {
        trigger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('navigation-drawer-open');
    });
    close.addEventListener('click', () => drawer.close());
    nav.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('click', () => drawer.close());
    });
    drawer.querySelector('.navigation-drawer__brand').addEventListener('click', () => drawer.close());

    function outside(event) {
        if (event.target !== drawer) return false;
        const bounds = drawer.getBoundingClientRect();
        return event.clientX < bounds.left || event.clientX >= bounds.right
            || event.clientY < bounds.top || event.clientY >= bounds.bottom;
    }

    let startedOutside = false;
    drawer.addEventListener('pointerdown', event => { startedOutside = outside(event); });
    drawer.addEventListener('click', event => {
        if (startedOutside && outside(event)) drawer.close();
        startedOutside = false;
    });
    drawer.append(nav);
    fallback.replaceWith(trigger);
})();

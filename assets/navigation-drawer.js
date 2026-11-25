(() => {
    const drawer = document.getElementById('navigation-drawer');
    const fallback = document.getElementById('navigation-fallback');
    const summary = fallback?.querySelector('summary');
    const nav = fallback?.querySelector('#primary-nav');
    const close = drawer?.querySelector('.nav-close');
    const panel = drawer?.querySelector('.navigation-drawer__panel');
    const footer = nav?.querySelector('.navigation-drawer__footer');
    const primary = nav?.querySelector('.navigation-drawer__primary');
    if (typeof drawer?.showModal !== 'function' || !summary || !nav || !close || !panel || !footer || !primary) return;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'menu-toggle';
    trigger.setAttribute('aria-label', 'Menu');
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', drawer.id);
    trigger.setAttribute('aria-expanded', 'false');
    for (const child of summary.childNodes) trigger.append(child.cloneNode(true));

    const secondary = document.createElement('div');
    secondary.className = 'navigation-drawer__secondary';
    secondary.hidden = true;
    const back = document.createElement('button');
    back.type = 'button';
    back.className = 'nav-back';
    back.textContent = 'Back to menu';
    const heading = document.createElement('h2');
    heading.className = 'nav-branch__heading';
    secondary.append(back, heading);
    nav.append(secondary);

    let currentBranch = null;
    function clearBranch(restoreFocus = false) {
        if (!currentBranch) return;
        const { button, links } = currentBranch;
        button.setAttribute('aria-expanded', 'false');
        links.hidden = true;
        secondary.hidden = true;
        drawer.removeAttribute('data-branch-open');
        currentBranch = null;
        if (restoreFocus) button.focus();
    }

    nav.querySelectorAll('.nav-branch').forEach(details => {
        const label = details.querySelector('summary');
        const links = details.querySelector('.nav-branch__links');
        if (!label || !links) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = label.className;
        button.setAttribute('aria-controls', links.id);
        button.setAttribute('aria-expanded', 'false');
        for (const child of label.childNodes) button.append(child.cloneNode(true));
        links.hidden = true;
        secondary.append(links);
        details.replaceWith(button);

        button.addEventListener('click', () => {
            if (currentBranch?.button === button) {
                clearBranch(true);
                return;
            }
            clearBranch();
            currentBranch = { button, links };
            heading.textContent = label.firstChild.textContent.trim();
            links.hidden = false;
            secondary.hidden = false;
            drawer.setAttribute('data-branch-open', '');
            button.setAttribute('aria-expanded', 'true');
            secondary.scrollTop = 0;
            back.focus({ preventScroll: true });
        });
    });

    back.addEventListener('click', () => clearBranch(true));
    window.matchMedia('(max-width: 743px)').addEventListener?.('change', event => {
        if (!event.matches || !drawer.open || !currentBranch) return;
        const focused = document.activeElement;
        if (primary.contains(focused) || focused === document.body) back.focus();
    });

    function unlockPage() {
        trigger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('navigation-drawer-open');
    }

    function dismiss() {
        drawer.close();
        unlockPage();
    }

    trigger.addEventListener('click', () => {
        clearBranch();
        trigger.focus({ preventScroll: true });
        drawer.showModal();
        trigger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('navigation-drawer-open');
    });
    drawer.addEventListener('close', () => { if (!drawer.open) unlockPage(); });
    drawer.addEventListener('cancel', event => {
        event.preventDefault();
        dismiss();
    });
    close.addEventListener('click', dismiss);

    let startedOutside = false;
    drawer.addEventListener('pointerdown', event => { startedOutside = event.target === drawer; });
    drawer.addEventListener('click', event => {
        if ((startedOutside && event.target === drawer) || event.target.closest('a[href]')) dismiss();
        startedOutside = false;
    });
    panel.append(nav, footer);
    fallback.replaceWith(trigger);
})();

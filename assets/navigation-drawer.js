(() => {
    const drawer = document.getElementById('navigation-drawer');
    const trigger = document.querySelector('[aria-controls="navigation-drawer"]');
    if (!drawer?.showModal || !trigger) return;

    trigger.addEventListener('click', () => {
        drawer.showModal();
        trigger.setAttribute('aria-expanded', 'true');
    });
    drawer.addEventListener('close', () => trigger.setAttribute('aria-expanded', 'false'));
    drawer.querySelector('.nav-close').addEventListener('click', () => drawer.close());
    drawer.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('click', () => drawer.close());
    });

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
})();

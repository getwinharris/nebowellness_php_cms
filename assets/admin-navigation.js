(() => {
    const sidebar = document.getElementById('admin-sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle) return;
    const mobile = window.matchMedia('(max-width: 768px)');
    const groups = Array.from(sidebar.querySelectorAll('.admin-nav-toggle'));
    const close = sidebar.querySelector('[data-admin-menu-close]');
    const main = document.getElementById('main-content');
    function setGroup(button, expanded) {
        const submenu = document.getElementById(button.getAttribute('aria-controls'));
        if (!submenu) return;
        button.setAttribute('aria-expanded', String(expanded));
        submenu.hidden = !expanded;
    }
    function adaptGroups() {
        groups.forEach((button) => {
            const submenu = document.getElementById(button.getAttribute('aria-controls'));
            setGroup(button, !mobile.matches || Boolean(submenu?.querySelector('.active')));
        });
    }
    function setOpen(open) {
        const hadFocus = sidebar.contains(document.activeElement);
        sidebar.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', String(open));
        main.inert = open;
        if (open) (sidebar.querySelector('a.active') || close).focus();
        else if (mobile.matches && hadFocus) toggle.focus();
    }

    groups.forEach((button) => {
        button.addEventListener('click', () => setGroup(button, button.getAttribute('aria-expanded') !== 'true'));
    });
    sidebar.querySelectorAll('a.active').forEach((link) => link.setAttribute('aria-current', 'page'));
    toggle.addEventListener('click', () => setOpen(true));
    close.addEventListener('click', (event) => {
        event.preventDefault();
        setOpen(false);
    });
    document.addEventListener('pointerdown', (event) => {
        if (mobile.matches && sidebar.classList.contains('open') && !sidebar.contains(event.target)) setOpen(false);
    });
    document.addEventListener('keydown', (event) => {
        if (!mobile.matches || !sidebar.classList.contains('open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setOpen(false);
        }
        if (event.key !== 'Tab') return;
        const links = Array.from(sidebar.querySelectorAll('a[href], button')).filter((link) => link.getClientRects().length);
        const first = links[0], last = links[links.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
    mobile.addEventListener('change', () => {
        setOpen(false);
        adaptGroups();
    });
    adaptGroups();
    toggle.hidden = false;
})();

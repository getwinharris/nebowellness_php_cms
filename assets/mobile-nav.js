(() => {
    const programsLinks = document.querySelectorAll('[data-programs-nav]');
    if (!programsLinks.length) return;

    function syncProgramsLink() {
        for (const link of programsLinks) {
            const active = location.pathname === link.pathname && location.hash === link.hash;
            if (active) link.setAttribute('aria-current', 'location');
            else link.removeAttribute('aria-current');
        }
    }

    syncProgramsLink();
    window.addEventListener('hashchange', syncProgramsLink);
    window.addEventListener('popstate', syncProgramsLink);
    window.addEventListener('pageshow', syncProgramsLink);
})();

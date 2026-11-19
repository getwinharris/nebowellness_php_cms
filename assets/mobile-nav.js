(() => {
    const programsLink = document.querySelector('[data-programs-nav]');
    if (!programsLink) return;

    function syncProgramsLink() {
        const active = location.pathname === programsLink.pathname && location.hash === programsLink.hash;
        programsLink.classList.toggle('active', active);
        if (active) {
            programsLink.setAttribute('aria-current', 'location');
        } else {
            programsLink.removeAttribute('aria-current');
        }
    }

    syncProgramsLink();
    window.addEventListener('hashchange', syncProgramsLink);
    window.addEventListener('popstate', syncProgramsLink);
    window.addEventListener('pageshow', syncProgramsLink);
})();

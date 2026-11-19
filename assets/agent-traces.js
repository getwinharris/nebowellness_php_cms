(() => {
    const panel = document.querySelector('[data-agent-traces]');
    const filters = panel?.querySelector('[data-trace-filters]');
    if (!filters) return;

    const runs = Array.from(panel.querySelectorAll('.agent-trace'));
    const surface = filters.querySelector('[data-trace-surface]');
    const result = filters.querySelector('[data-trace-result]');
    const count = filters.querySelector('[data-trace-count]');

    function updateRuns() {
        let visible = 0;
        for (const run of runs) {
            const matchesSurface = !surface.value || run.dataset.surface === surface.value;
            const matchesResult = !result.value || (result.value === 'provider'
                ? run.dataset.providerFailed === 'yes'
                : run.dataset.outcome === result.value);
            run.hidden = !matchesSurface || !matchesResult;
            if (!run.hidden) visible++;
        }
        count.textContent = visible
            ? `Showing ${visible} of ${runs.length} recent runs.`
            : 'No recent runs match these filters.';
    }

    filters.addEventListener('change', updateRuns);
    window.addEventListener('pageshow', updateRuns);
    updateRuns();
    filters.hidden = false;
})();

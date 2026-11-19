(() => {
    'use strict';
    const config = document.getElementById('clinic-measurement')?.dataset;
    if (!config) return;
    const key = 'nebo.measurement.v1';
    const lifetime = 180 * 24 * 60 * 60 * 1000;
    const privacySignal = navigator.globalPrivacyControl === true || navigator.doNotTrack === '1';
    const choices = document.getElementById('measurement-choices');
    const frames = new Map();

    function saved() {
        try {
            const value = JSON.parse(localStorage.getItem(key));
            if (value && value.expires > Date.now()) return value;
        } catch { /* Storage access may be disabled by the browser. */ }
        return {};
    }

    function syncFrame(category, data) {
        const existing = frames.get(category);
        if (!data) {
            existing?.remove();
            frames.delete(category);
            return;
        }
        if (existing) return;
        const target = document.createElement('iframe');
        frames.set(category, target);
        target.hidden = true;
        target.title = 'Optional audience measurement';
        target.referrerPolicy = 'no-referrer';
        target.setAttribute('sandbox', 'allow-scripts');
        target.addEventListener('load', () => {
            if (target === frames.get(category)) target.contentWindow.postMessage(data, '*');
        }, {once: true});
        target.src = '/assets/measurement-frame.html';
        document.body.append(target);
    }

    function update() {
        const consent = saved();
        if (choices) {
            for (const field of choices.querySelectorAll('input')) {
                field.checked = !privacySignal && consent[field.name] === true;
                field.disabled = privacySignal;
            }
            if (privacySignal) choices.querySelector('[role="status"]').textContent = 'Measurement is off because this browser sends a privacy preference.';
        }
        // Keep Google and Meta libraries outside the application document. Forms, chat,
        // referrers, query strings and account state are never available in the frame.
        const query = new URLSearchParams(location.search);
        const sources = query.getAll('utm_source');
        const source = sources.length === 1 && ['google', 'facebook', 'instagram'].includes(sources[0]) ? sources[0] : '';
        const safeQuery = !location.search || (source && [...query.keys()].every(key => key === 'utm_source'));
        const eligible = !privacySignal && ['home', 'about'].includes(config.page) && !location.hash && safeQuery;
        syncFrame('analytics', eligible && consent.analytics === true && config.ga
            ? {analytics: config.ga, ads: '', pixel: '', source, page: config.page} : null);
        syncFrame('advertising', eligible && consent.advertising === true && (config.ads || config.pixel)
            ? {analytics: '', ads: config.ads, pixel: config.pixel, source: '', page: config.page} : null);
    }

    if (choices) {
        document.getElementById('measurement').hidden = false;
        choices.addEventListener('submit', event => {
            event.preventDefault();
            const status = choices.querySelector('[role="status"]');
            if (privacySignal) return;
            try {
                localStorage.setItem(key, JSON.stringify({
                    expires: Date.now() + lifetime,
                    analytics: !!choices.elements.analytics?.checked,
                    advertising: !!choices.elements.advertising?.checked
                }));
                update();
                status.textContent = 'Measurement preferences saved for this browser.';
            } catch {
                status.textContent = 'This browser could not save your preferences. Measurement stays off.';
            }
        });
    }
    window.addEventListener('storage', event => { if (event.key === key || event.key === null) update(); });
    // A restored page can outlive consent changed elsewhere while it was frozen.
    window.addEventListener('pageshow', event => { if (event.persisted) update(); });
    update();
})();

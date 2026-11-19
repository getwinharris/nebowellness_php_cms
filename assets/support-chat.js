(() => {
    const launcher = document.querySelector('.support-fab');
    const panel = document.getElementById('support-panel');
    const form = document.getElementById('support-form');
    const log = document.getElementById('support-log');
    if (!launcher || !panel || !form || !log) return;

    const close = panel.querySelector('.support-panel__close');
    const message = form.elements.message;
    const controls = form.querySelectorAll('textarea, button');
    let pendingTicket = null;

    function isActionPath(path) {
        return typeof path === 'string' && /^\/(?!\/)[a-z0-9/#-]*$/i.test(path);
    }

    function appendMessage(turn, speaker, value) {
        const line = document.createElement('p');
        const label = document.createElement('strong');
        label.textContent = speaker + ':';
        line.append(label, ' ' + value);
        turn.append(line);
    }

    function appendActions(turn, actions) {
        const links = document.createElement('div');
        links.className = 'support-actions';
        for (const action of actions) {
            const link = document.createElement('a');
            link.className = 'btn btn-sm btn-outline support-action-btn';
            link.href = action.path;
            link.textContent = action.label;
            links.append(link);
        }
        if (links.childElementCount) turn.append(links);
    }

    function toggle(open) {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        (open ? close : launcher).focus();
    }

    function saveLog() {
        try {
            const copy = log.cloneNode(true);
            copy.querySelectorAll('[data-support-private]').forEach(node => node.remove());
            sessionStorage.setItem(panel.dataset.supportKey, copy.innerHTML);
        } catch {
            // Chat remains usable when browser storage is unavailable.
        }
    }

    try {
        const saved = sessionStorage.getItem(panel.dataset.supportKey);
        if (saved) log.innerHTML = saved;
    } catch {
        // An unavailable transcript must not prevent a new question.
    }

    launcher.addEventListener('click', () => toggle(panel.hidden));
    close.addEventListener('click', () => toggle(false));
    panel.addEventListener('keydown', event => {
        if (event.key === 'Escape') toggle(false);
    });
    for (const starter of form.querySelectorAll('[data-support-prompt]')) {
        starter.addEventListener('click', () => {
            if (message.value.trim()) {
                message.focus();
                return;
            }
            message.value = starter.dataset.supportPrompt;
            form.requestSubmit();
        });
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.busy === 'true') return;

        const data = new FormData(form);
        const question = String(data.get('message') || '').trim();
        if (!question) return;
        const isTicket = event.submitter?.value === 'ticket';
        if (isTicket) {
            if (pendingTicket?.question !== question) {
                const id = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
                pendingTicket = { question, id };
            }
            data.set('intent', 'ticket');
            data.set('request_id', pendingTicket.id);
        }

        form.dataset.busy = 'true';
        for (const control of controls) control.disabled = true;
        const turn = document.createElement('div');
        if (isTicket || /\btickets?\b/i.test(question)) turn.dataset.supportPrivate = '';
        log.append(turn);
        appendMessage(turn, 'You', question);
        form.reset();
        const progress = window.AgentRequestStatus.start(turn);
        log.scrollTop = log.scrollHeight;

        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 35000);
        let failed = false;
        let openPath = null;
        let traceId = null;
        try {
            const response = await fetch('/support/ask', { method: 'POST', body: data, signal: abort.signal });
            traceId = response.headers.get('X-Agent-Trace');
            const result = await response.json();
            failed = !response.ok || !!result.error;
            const actions = Array.isArray(result.actions)
                ? result.actions.filter(action => action?.type === 'navigate' && isActionPath(action.path))
                : [];
            appendMessage(turn, 'Support', result.reply || result.error || 'Unable to answer right now.');
            appendActions(turn, actions);
            if (!failed && isTicket) pendingTicket = null;
            if (!failed && actions.some(action => action.path === result.open)) openPath = result.open;
        } catch {
            failed = true;
            const guidance = isTicket
                ? 'We could not confirm receipt. Use Send to clinic again with the same message to check or retry.'
                : abort.signal.aborted
                    ? 'Support took too long to respond. Please try again.'
                    : 'Unable to answer right now. Please try again.';
            appendMessage(turn, 'Support', guidance);
        } finally {
            clearTimeout(timeout);
            progress.finish(failed);
            if (failed && /^[a-f0-9]{16}$/.test(traceId || '')) {
                const reference = document.createElement('small');
                reference.textContent = 'Trace ' + traceId;
                turn.append(reference);
            }
            form.dataset.busy = 'false';
            for (const control of controls) control.disabled = false;
            if (failed) message.value = question;
            saveLog();
            log.scrollTop = log.scrollHeight;
            if (openPath) location.assign(openPath);
        }
    });
})();

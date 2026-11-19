(() => {
    const launcher = document.querySelector('.support-fab');
    const panel = document.getElementById('support-panel');
    const form = document.getElementById('support-form');
    const log = document.getElementById('support-log');
    if (!launcher || !panel || !form || !log) return;

    const close = panel.querySelector('.support-panel__close');
    const message = form.elements.message;
    const controls = form.querySelectorAll('textarea, button');
    const transcriptKey = panel.dataset.supportKey + ':turns-v2';
    const ticketKey = panel.dataset.supportKey + ':pending-ticket';
    let pendingTicket = readStorage(ticketKey);
    const transcript = [];

    function readStorage(key) {
        try {
            return JSON.parse(sessionStorage.getItem(key));
        } catch {
            return null;
        }
    }

    function writeStorage(key, value) {
        try {
            if (value === null) sessionStorage.removeItem(key);
            else sessionStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Storage can be disabled. The active page retains its in-memory state.
        }
    }

    async function prepareTicket(question) {
        const bytes = new TextEncoder().encode(question);
        const digest = await crypto.subtle.digest('SHA-256', bytes);
        const fingerprint = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('');
        if (pendingTicket?.fingerprint !== fingerprint || !/^[a-f0-9]{32}$/.test(pendingTicket.id)) {
            const id = Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
            pendingTicket = { fingerprint, id };
            writeStorage(ticketKey, pendingTicket);
        }
        return pendingTicket.id;
    }

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

    function validActions(actions) {
        return Array.isArray(actions)
            ? actions.filter(action => action?.type === 'navigate'
                && typeof action.label === 'string' && isActionPath(action.path))
            : [];
    }

    writeStorage(panel.dataset.supportKey, null);
    const saved = readStorage(transcriptKey);
    if (Array.isArray(saved)) {
        for (const entry of saved.slice(-20)) {
            if (typeof entry?.question !== 'string' || typeof entry.reply !== 'string') continue;
            const actions = validActions(entry.actions);
            const turn = document.createElement('div');
            appendMessage(turn, 'You', entry.question);
            appendMessage(turn, 'Support', entry.reply);
            appendActions(turn, actions);
            log.append(turn);
            transcript.push({ question: entry.question, reply: entry.reply, actions });
        }
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
        form.dataset.busy = 'true';
        for (const control of controls) control.disabled = true;
        const turn = document.createElement('div');
        const privateTurn = isTicket || /\btickets?\b/i.test(question);
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
        let reply = '';
        let actions = [];
        try {
            if (isTicket) {
                data.set('intent', 'ticket');
                data.set('request_id', await prepareTicket(question));
            }
            const response = await fetch('/support/ask', { method: 'POST', body: data, signal: abort.signal });
            traceId = response.headers.get('X-Agent-Trace');
            const result = await response.json();
            failed = !response.ok || !!result.error;
            actions = validActions(result.actions);
            reply = String(result.reply || result.error || 'Unable to answer right now.');
            appendMessage(turn, 'Support', reply);
            appendActions(turn, actions);
            if (!failed && isTicket) {
                pendingTicket = null;
                writeStorage(ticketKey, null);
            }
            if (!failed && actions.some(action => action.path === result.open)) openPath = result.open;
        } catch {
            failed = true;
            const guidance = isTicket
                ? 'We could not confirm receipt. Use Send to clinic again with the same message to check or retry.'
                : abort.signal.aborted
                    ? 'Support took too long to respond. Please try again.'
                    : 'Unable to answer right now. Please try again.';
            reply = guidance;
            appendMessage(turn, 'Support', reply);
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
            if (!privateTurn) {
                transcript.push({ question, reply, actions });
                if (transcript.length > 20) transcript.shift();
                writeStorage(transcriptKey, transcript);
            }
            log.scrollTop = log.scrollHeight;
            if (openPath) location.assign(openPath);
        }
    });
})();

(() => {
    const launcher = document.querySelector('.support-fab');
    const panel = document.getElementById('support-panel');
    const form = document.getElementById('support-form');
    const log = document.getElementById('support-log');
    if (!launcher || !panel || !form || !log) return;

    const close = panel.querySelector('.support-panel__close');
    const message = form.elements.message;
    const controls = form.querySelectorAll('textarea, button, input[type=checkbox]');
    const viewport = window.visualViewport;
    const chat = new window.AgentConversation(panel, '/support/ask', form.elements._csrf.value);
    const clear = document.getElementById('support-clear');
    const intro = log.firstElementChild;
    const introduction = intro.textContent;
    const prompts = document.getElementById('support-prompts');
    const pageLauncher = document.getElementById('support-page-launch');
    let returnFocus = launcher;
    let loading = null;
    let retry = null;
    const ticketKey = panel.dataset.supportKey + ':pending-ticket';
    let pendingTicket = readStorage(ticketKey);


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
        line.className = speaker === 'You' ? 'support-message support-message--customer' : 'support-message';
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

    function fitViewport() {
        if (!viewport || panel.hidden) return;
        const inset = document.documentElement.clientHeight - viewport.height - viewport.offsetTop;
        panel.style.setProperty('--support-keyboard-inset', Math.max(0, inset) + 'px');
    }

    function toggle(open, source = launcher) {
        if (open) returnFocus = source;
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        pageLauncher?.setAttribute('aria-expanded', String(open));
        (open ? close : returnFocus).focus();
        if (open) {
            restore().catch(historyError);
            fitViewport();
            log.scrollTop = log.scrollHeight;
        }
    }

    function validActions(actions) {
        return Array.isArray(actions)
            ? actions.filter(action => typeof action?.label === 'string' && ((action.type === 'navigate' && isActionPath(action.path)) || (action.type === 'whatsapp' && typeof action.path === 'string' && /^https:\/\/wa\.me\/[1-9]\d{7,14}\?text=[^#]+$/.test(action.path))))
            : [];
    }

    // Clear earlier browser transcripts, including those left behind by a signed-out account.
    try {
        for (let i = sessionStorage.length - 1; i >= 0; i--) {
            const key = sessionStorage.key(i);
            if (/^nebo-support-chat-(?:guest|[a-f0-9]{64})(?::turns-v2)?$/.test(key)) sessionStorage.removeItem(key);
        }
    } catch {
        // The current conversation lives in the PHP session when local storage is blocked.
    }

    async function restore() {
        if (!chat.hasTurns) return;
        if (loading) return loading;
        loading = chat.request('history').then(turns => {
            intro.textContent = introduction;
            for (const entry of turns) {
                const turn = document.createElement('div');
                turn.className = 'support-turn';
                appendMessage(turn, 'You', entry.question);
                appendMessage(turn, entry.response.response_mode === 'application_action' ? 'Clinic action' : 'Support AI', entry.response.reply);
                appendActions(turn, validActions(entry.response.actions));
                if (entry.response.response_mode==='model_with_retrieval') {
                    appendActions(turn,validActions((entry.response.sources||[]).slice(0,5).map(path=>({type:'navigate',path,label:'Source: '+path}))));
                }

                log.append(turn);
            }
            log.scrollTop = log.scrollHeight;
            chat.hasTurns = false;
            clear.hidden = turns.length === 0;
            prompts.open = turns.length === 0;
        }).finally(() => { loading = null; });
        return loading;
    }

    function historyError() {
        intro.textContent = 'Could not load your conversation. Reload this page to reconnect.';
    }

    clear.addEventListener('click', async () => {
        if (form.dataset.busy === 'true' || loading) return;
        form.dataset.busy = 'true';
        clear.disabled = true;
        try {
            await chat.request('clear');
            intro.textContent = introduction;
            log.replaceChildren(intro);
            clear.hidden = true;
            prompts.open = true;
            retry = null;
            if (form.elements.archive_session) form.elements.archive_session.checked = false;
            message.value = '';
            message.focus();
        } catch {
            historyError();
        } finally {
            clear.disabled = false;
            form.dataset.busy = 'false';
        }
    });

    launcher.addEventListener('click', () => toggle(panel.hidden));
    if (pageLauncher) {
        pageLauncher.hidden = false;
        pageLauncher.addEventListener('click', () => toggle(panel.hidden, pageLauncher));
    }
    close.addEventListener('click', () => toggle(false));
    viewport?.addEventListener('resize', fitViewport);
    viewport?.addEventListener('scroll', fitViewport);
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
        const archiveConsent = form.elements.archive_session?.checked === true;
        form.dataset.busy = 'true';
        try {
            await restore();
        } catch {
            form.dataset.busy = 'false';
            historyError();
            return;
        }
        if (form.contains(document.activeElement)) log.focus({ preventScroll: true });
        clear.disabled = true;
        for (const control of controls) control.disabled = true;
        const sameRequest = retry?.question === question && retry.isTicket === isTicket;
        const id = sameRequest ? retry.id : chat.turnId();
        const turn = sameRequest ? retry.turn : document.createElement('div');
        turn.className = 'support-turn';
        if (sameRequest) turn.replaceChildren();
        else log.append(turn);
        appendMessage(turn, 'You', question);
        data.set('conversation', chat.id);
        data.set('turn_id', id);
        form.reset();
        if (form.elements.archive_session) form.elements.archive_session.checked = archiveConsent;
        const progress = window.AgentRequestStatus.start(turn);
        log.scrollTop = log.scrollHeight;

        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 35000);
        let failed = false;
        let openPath = null;
        let traceId = null;
        let reply = '';
        clear.hidden = false;
        prompts.open = false;
        let actions = [];
        try {
            if (isTicket) {
                data.set('intent', 'ticket');
                data.set('request_id', await prepareTicket(question));
            }
            const response = await fetch('/support/ask', { method: 'POST', headers: { Accept: 'application/json' }, body: data, signal: abort.signal });
            traceId = response.headers.get('X-Agent-Trace');
            const result = await response.json();
            failed = !response.ok || !!result.error;
            actions = validActions(result.actions);
            reply = String(result.reply || result.error || 'Unable to answer right now.');
            appendMessage(turn, failed ? 'Support unavailable' : result.response_mode === 'application_action' ? 'Clinic action' : 'Support AI', reply);
            appendActions(turn, actions);
            if (result.response_mode === 'model_with_retrieval') {
                if (!failed) window.BapxA2ui?.append(turn, result.a2ui);
                appendActions(turn,validActions((Array.isArray(result.sources)?result.sources:[]).slice(0,5).map(path=>({type:'navigate',path,label:'Source: '+path}))));

                const label=document.createElement('small'); label.className='support-source-note';
                label.textContent='AI response using retrieved records'; turn.append(label);
            }
            if (!failed && isTicket) {
                pendingTicket = null;
                writeStorage(ticketKey, null);
            }
            if (result.archive_status === 'unavailable') { const note=document.createElement('small'); note.textContent='The clinic conversation archive is temporarily unavailable.'; turn.append(note); }
            if (!failed && actions.some(action => action.type === 'navigate' && action.path === result.open)) openPath = result.open;
        } catch {
            failed = true;
            const guidance = isTicket
                ? 'We could not confirm receipt. Use Send to clinic again with the same message to check or retry.'
                : abort.signal.aborted
                    ? 'Support took too long to respond. Please try again.'
                    : 'Unable to answer right now. Please try again.';
            reply = guidance;
            appendMessage(turn, 'Support unavailable', reply);
        } finally {
            clearTimeout(timeout);
            progress.finish(failed);
            if (failed && /^[a-f0-9]{16}$/.test(traceId || '')) {
                const reference = document.createElement('small');
                reference.textContent = 'Trace ' + traceId;
                turn.append(reference);
            }
            form.dataset.busy = 'false';
            clear.disabled = false;
            for (const control of controls) control.disabled = false;
            if (failed) message.value = question;
            retry = failed ? { question, id, isTicket, turn } : null;
            log.scrollTop = log.scrollHeight;
            if (openPath) location.assign(openPath);
        }
    });
})();

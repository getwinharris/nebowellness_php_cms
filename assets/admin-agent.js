(function () {
    'use strict';
    const input = document.getElementById('agent-input');
    const form = document.getElementById('agent-form');
    const messages = document.getElementById('agent-messages');
    const submit = document.getElementById('agent-submit');
    const suggestions = document.getElementById('agent-suggestions');
    const suggestionGroup = document.getElementById('agent-suggestion-group');
    const composer = document.getElementById('agent-composer-wrap');
    const status = document.getElementById('agent-composer-status');
    const uploadBtn = document.getElementById('agent-upload-btn');
    const clear = document.getElementById('agent-clear');
    const welcome = messages.firstElementChild.cloneNode(true);
    const csrfToken = form.elements.namedItem('_csrf').value;
    const chat = new window.AgentConversation(form, form.action, csrfToken);
    composer.hidden = false;
    suggestionGroup.hidden = false;
    clear.hidden = false;
    clear.addEventListener('click', async function () {
        if (submit.disabled) return;
        submit.disabled = true;
        clear.disabled = true;
        input.disabled = true;
        try {
            await chat.request('clear');
        } catch {
            status.textContent = 'Could not start a new chat. Reload this page to reconnect.';
            return;
        } finally {
            submit.disabled = false;
            clear.disabled = false;
            input.disabled = false;
        }
        messages.replaceChildren(welcome.cloneNode(true));
        input.value = '';
        autoGrow();
        suggestionGroup.hidden = false;
        status.textContent = '';
        closeMentions();
        input.focus();
    });

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Minimal Markdown: escape first, then re-introduce a known-safe subset, so model
    // output can never inject HTML.
    function renderMarkdown(src) {
        let t = escapeHtml(src);
        t = t.replace(/```([\s\S]*?)```/g, (_, c) => '<pre><code>' + c.trim() + '</code></pre>');
        t = t.replace(/`([^`]+)`/g, '<code>$1</code>');
        t = t.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/(^|[^*])\*([^*\n]+)\*/g, '$1<em>$2</em>');
        const lines = t.split('\n');
        let out = '', list = null;
        for (const raw of lines) {
            const line = raw.trimEnd();
            const ul = line.match(/^\s*[-*]\s+(.*)$/);
            const ol = line.match(/^\s*\d+\.\s+(.*)$/);
            if (ul) { if (list !== 'ul') { if (list) out += '</' + list + '>'; out += '<ul>'; list = 'ul'; } out += '<li>' + ul[1] + '</li>'; continue; }
            if (ol) { if (list !== 'ol') { if (list) out += '</' + list + '>'; out += '<ol>'; list = 'ol'; } out += '<li>' + ol[1] + '</li>'; continue; }
            if (list) { out += '</' + list + '>'; list = null; }
            if (line.trim() === '') { out += ''; continue; }
            out += '<p>' + line + '</p>';
        }
        if (list) out += '</' + list + '>';
        return out;
    }

    function addRow(who, html, before = null) {
        const row = document.createElement('div');
        row.className = 'agent-row agent-row--' + who;
        const avatar = document.createElement('div');
        avatar.className = 'agent-avatar agent-avatar--' + (who === 'user' ? 'user' : 'bot');
        avatar.setAttribute('aria-hidden', 'true');
        avatar.textContent = who === 'user' ? 'You' : 'AI';
        const bubble = document.createElement('div');
        bubble.className = 'agent-bubble agent-bubble--' + who;
        bubble.innerHTML = html;
        row.appendChild(avatar); row.appendChild(bubble);
        messages.insertBefore(row, before);
        messages.scrollTop = messages.scrollHeight;
        return bubble;
    }

    function appendTrace(target, traceId) {
        if (!/^[a-f0-9]{16}$/.test(traceId || '')) return;
        const reference = document.createElement('small');
        reference.textContent = 'Trace ' + traceId;
        reference.className = 'agent-trace-reference';
        target.append(reference);
    }

    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    }
    input.addEventListener('input', autoGrow);

    // Enter sends, Shift+Enter makes a new line — the convention users expect.
    suggestions.addEventListener('click', function (e) {
        const btn = e.target.closest('.agent-suggestion');
        if (!btn || submit.disabled) return;
        if (input.value === '') {
            input.value = btn.dataset.q;
            autoGrow();
        }
        input.focus();
    });

    // ── Draft forms ────────────────────────────────────────────────────────
    // The agent hands back a filled-in form, never a saved record. It posts to the
    // same route the ordinary admin screens use, so nothing reaches the site until
    // the owner has read the draft and pressed Save.
    function buildDraftForm(draft) {
        const wrap = document.createElement('div');
        wrap.className = 'agent-draft';

        const note = document.createElement('p');
        note.className = 'agent-draft__note';
        note.textContent = draft.note || '';
        wrap.appendChild(note);

        const form = document.createElement('form');
        form.method = 'post';
        form.action = draft.action;
        form.className = 'agent-draft__form';

        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_csrf';
        csrf.value = csrfToken;
        form.appendChild(csrf);

        (draft.fields || []).forEach(function (field) {
            const row = document.createElement('label');
            row.className = 'agent-draft__row';

            const label = document.createElement('span');
            label.className = 'agent-draft__label';
            label.textContent = field.label + (field.required ? ' *' : '');
            row.appendChild(label);

            const big = field.type === 'textarea' || field.type === 'markdown';
            const input = document.createElement(big ? 'textarea' : 'input');
            if (big) { input.rows = field.type === 'markdown' ? 12 : 3; } else { input.type = 'text'; }
            input.name = field.name;
            input.value = field.value || '';
            if (field.required) input.required = true;
            row.appendChild(input);

            if (field.hint) {
                const hint = document.createElement('span');
                hint.className = 'agent-draft__hint';
                hint.textContent = field.hint;
                row.appendChild(hint);
            }
            form.appendChild(row);
        });

        const actions = document.createElement('div');
        actions.className = 'agent-draft__actions';
        const save = document.createElement('button');
        save.type = 'submit';
        save.className = 'btn btn-primary btn-sm';
        save.textContent = draft.kind === 'blog' ? 'Save blog post' : 'Save product';
        actions.appendChild(save);
        form.appendChild(actions);

        wrap.appendChild(form);
        return wrap;
    }

    // ── @ attachments ──────────────────────────────────────────────────────
    const attachables = JSON.parse(document.getElementById('agent-attachables')?.textContent || '[]');
    const mentionBox = document.getElementById('agent-mentions');

    function closeMentions() {
        mentionBox.hidden = true;
    }

    function openMentions(query) {
        const q = query.toLowerCase();
        const hits = attachables.filter(a => a.name.toLowerCase().includes(q)).slice(0, 8);
        if (!hits.length) { closeMentions(); return; }
        mentionBox.innerHTML = '';
        hits.forEach(function (hit) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'agent-mention';
            item.innerHTML = '<span class="agent-mention__name">@' + escapeHtml(hit.name) + '</span>'
                + '<span class="agent-mention__kind">' + escapeHtml(hit.kind) + '</span>';
            item.addEventListener('mousedown', event => event.preventDefault());
            item.addEventListener('click', function () {
                const end = input.selectionStart;
                input.setRangeText('@' + hit.name + ' ', end - query.length - 1, end, 'end');
                closeMentions();
                input.focus();
                autoGrow();
            });
            mentionBox.appendChild(item);
        });
        mentionBox.hidden = false;
    }

    input.addEventListener('input', function () {
        const match = input.value.slice(0, input.selectionStart).match(/@([A-Za-z0-9._-]*)$/);
        if (match) { openMentions(match[1]); } else { closeMentions(); }
    });
    input.addEventListener('keydown', function (event) {
        if (event.isComposing) return;
        if (event.key === 'ArrowDown' && !mentionBox.hidden) {
            event.preventDefault();
            mentionBox.firstElementChild.focus();
        } else if (event.key === 'Escape') {
            closeMentions();
        } else if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (!submit.disabled) form.requestSubmit();
        }
    });
    mentionBox.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeMentions();
            input.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const next = event.key === 'ArrowDown' ? event.target.nextElementSibling : event.target.previousElementSibling;
            (next || input).focus();
        }
    });
    form.addEventListener('focusout', function (event) {
        if (event.relatedTarget !== input && !mentionBox.contains(event.relatedTarget)) closeMentions();
    });

    // ── Upload ─────────────────────────────────────────────────────────────
    const uploadInput = document.getElementById('agent-upload-input');
    if (uploadBtn && uploadInput) {
        uploadBtn.addEventListener('click', function () { uploadInput.click(); });
        uploadInput.addEventListener('change', function () {
            if (uploadInput.files && uploadInput.files.length) {
                document.getElementById('agent-upload-form').submit();
            }
        });
    }

    async function askAgent(e, retryMessage = null, retryRow = null, id = chat.turnId()) {
        e.preventDefault();
        const msg = retryMessage ?? input.value.trim();
        if (!msg || submit.disabled) return false;

        if (retryMessage === null) {
            addRow('user', escapeHtml(msg).replace(/\n/g, '<br>'));
            input.value = ''; autoGrow();
        }
        input.focus({ preventScroll: true });
        submit.disabled = true;
        clear.disabled = true;
        uploadBtn.disabled = true;
        suggestionGroup.hidden = true;
        closeMentions();
        status.textContent = 'Waiting for a response…';

        const thinking = addRow('bot', '', retryRow);
        if (retryRow) retryRow.remove();
        const progress = window.AgentRequestStatus.start(thinking);
        const response = document.createElement('div');
        thinking.appendChild(response);
        let failed = false, retry = false, mode, traceId;
        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 35000);

        try {
            const body = new URLSearchParams({ message: msg, _csrf: csrfToken, conversation: chat.id, turn_id: id });
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
                body: body,
                credentials: 'same-origin',
                signal: abort.signal
            });
            const data = await resp.json().catch(() => ({ error: 'The server returned an unreadable response.' }));
            traceId = resp.headers.get('X-Agent-Trace');
            if (!resp.ok || data.error) {
                failed = true;
                retry = resp.status >= 500;
                thinking.className = 'agent-bubble agent-bubble--error';
                response.textContent = data.error || 'The request failed. Please try again.';
            } else if (data.draft) {
                mode = data.draft.mode;
                response.appendChild(buildDraftForm(data.draft));
            } else {
                response.innerHTML = renderMarkdown(data.answer || 'No response.');
                if (data.response_mode === 'model_with_retrieval') window.BapxA2ui?.append(response, data.a2ui);
            }
            if (data.archive_status || data.response_mode === 'model_with_retrieval') {
                const info = document.createElement('small');
                info.className = 'crm-muted';
                info.textContent = [data.response_mode === 'model_with_retrieval' ? 'AI response using retrieved records' : '', data.archive_status === 'saved' ? 'Session archived' : data.archive_status === 'unavailable' ? 'Session archive unavailable' : ''].filter(Boolean).join(' · ');
                response.append(info);
                if (Array.isArray(data.sources)) for (const path of data.sources.slice(0,5)) {
                    if (typeof path!=='string'||!/^\/(?!\/)[a-z0-9/#-]*$/i.test(path)) continue;
                    const source=document.createElement('a');
                    source.href=path; source.textContent='Source: '+path; source.className='btn btn-outline btn-sm';
                    response.append(source);
                }

            }
            if (data.missing && data.missing.length) {
                addRow('bot', 'I could not find ' + data.missing.map(m => '<code>@' + escapeHtml(m) + '</code>').join(', ')
                    + '. Type <code>@</code> to see what is available.');
            }
        } catch (err) {
            failed = true;
            retry = true;
            thinking.className = 'agent-bubble agent-bubble--error';
            response.textContent = abort.signal.aborted
                ? 'The request timed out. You can retry it.'
                : 'Could not reach the server. Check your connection and try again.';
        } finally {
            clearTimeout(timeout);
            progress.finish(failed, mode);
        }
        appendTrace(response, traceId);
        if (retry) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline btn-sm agent-retry';
            button.textContent = 'Retry request';
            button.addEventListener('click', event => askAgent(event, msg, thinking.parentElement, id));
            response.append(button);
        }

        submit.disabled = false;
        clear.disabled = false;
        uploadBtn.disabled = false;
        status.textContent = failed ? 'Request failed. See the conversation for details.' : (mode === 'manual'
            ? 'Manual form ready in the conversation.'
            : mode === 'unavailable' ? 'Draft unavailable. See the conversation for details.' : 'Response ready in the conversation.');
        messages.scrollTop = messages.scrollHeight;
        return false;
    }
    form.addEventListener('submit', askAgent);
    if (chat.hasTurns) {
        submit.disabled = true;
        clear.disabled = true;
        status.textContent = 'Loading your conversation…';
        chat.request('history').then(turns => {
            for (const entry of turns) {
                addRow('user', escapeHtml(entry.question).replace(/\n/g, '<br>'));
                const answer = addRow('bot', renderMarkdown(entry.response.answer));
                appendTrace(answer, entry.trace_id);
            }
            suggestionGroup.hidden = turns.length > 0;
            status.textContent = '';
        }).catch(() => {
            status.textContent = 'Could not load your conversation. Reload this page to reconnect.';
        }).finally(() => {
            submit.disabled = false;
            clear.disabled = false;
        });
    }
})();

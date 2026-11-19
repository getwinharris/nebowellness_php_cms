<?php $__campaignsEnabled = !empty($campaignsEnabled); ?>
<div class="admin-card">
    <div class="agent-head">
        <div>
            <h2 class="agent-head__title">Enterprise Agent</h2>
            <p class="agent-head__sub">
                <?= !empty($shopEnabled)
                    ? 'Ask about users, orders, revenue, top customers or products, or paste content to draft a blog post.'
                    : ($__campaignsEnabled
                        ? 'Review therapy requests, support enquiries, and clinic activity. Review drafts before saving changes.'
                        : 'Ask about users, clinic activity, support enquiries, or paste content to draft a blog post.') ?>
            </p>
        </div>
        <div class="agent-head__meta">
            <span class="agent-chip <?= !empty($modelConfig['configured']) ? 'agent-chip--ok' : 'agent-chip--warn' ?>">
                <?= !empty($modelConfig['configured']) ? 'Model configured' : 'No API key' ?>
            </span>
            <span class="agent-chip agent-chip--muted"><?= e($modelConfig['model'] ?? 'gemma-4-31b-it') ?></span>
        </div>
    </div>

    <?php if (empty($modelConfig['configured'])): ?>
        <div class="agent-notice">
            <strong>No API key configured.</strong>
            Set <code>ai_api_key</code> in <a href="/admin/integrations">Admin → Integrations</a> to enable the agent.
        </div>
    <?php endif; ?>
</div>

<div class="agent-workspace">
<div class="admin-card agent-shell">
    <div class="agent-conversation-tools">
        <h3>Conversation</h3>
        <button type="button" class="btn btn-outline btn-sm" id="agent-clear" hidden>New chat</button>
    </div>
    <div id="agent-messages" class="agent-messages" role="log" aria-live="polite" aria-label="Conversation">
        <div class="agent-row agent-row--bot">
            <div class="agent-avatar agent-avatar--bot" aria-hidden="true">AI</div>
            <div class="agent-bubble agent-bubble--bot">
                <?= !empty($shopEnabled)
                    ? "Ask me about users, revenue, top customers or products — or give me content and I'll draft a blog post."
                    : ($__campaignsEnabled
                        ? "Ask me about users, clinic activity, campaigns or support enquiries — or give me content and I'll draft a blog post."
                        : "Ask me about users, clinic activity or support enquiries — or give me content and I'll draft a blog post.") ?>
            </div>
        </div>
    </div>

    <div class="agent-suggestions" id="agent-suggestions">
        <?php $__suggestions = !empty($shopEnabled)
            ? ['Which products sell best?', 'What is the revenue this month?', '/create-blog benefits of gut health', '/add-product wellness guide']
            : array_values(array_filter([
                'How many support enquiries are open?',
                $__campaignsEnabled ? 'Summarise current campaigns' : null,
                '/create-blog benefits of gut health',
            ])); ?>
        <?php if ($__campaignsEnabled) array_unshift($__suggestions, \App\Services\AgentToolRegistry::THERAPY_SUMMARY_PROMPT); ?>
        <?php $__suggestions[] = \App\Services\AgentToolRegistry::APPOINTMENTS_PROMPT; ?>
        <?php foreach ($__suggestions as $__s): ?>
            <button type="button" class="agent-suggestion" data-q="<?= e($__s) ?>"><?= e($__s) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="agent-composer-wrap">
        <?php // Typing @ opens this. Without it the owner has to remember exact slugs. ?>
        <div id="agent-mentions" class="agent-mentions" role="listbox" aria-label="Attach a page, article or image"></div>
        <form id="agent-form" class="agent-composer" onsubmit="return askAgent(event)">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="button" class="agent-attach" id="agent-upload-btn" aria-label="Upload an image"
                    title="Upload an image — it is added to the media library">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <textarea id="agent-input" name="message" rows="1" required
                      placeholder="Ask anything, @attach a page or image, or type /create-blog…"
                      aria-label="Message"></textarea>
            <button type="submit" class="agent-send" id="agent-submit" aria-label="Send">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </form>
    </div>
    <p class="agent-hint">
        Enter to send · Shift+Enter for a new line · <code>@terms</code> or <code>@filename</code> to attach ·
        <code>/create-blog</code><?= !empty($shopEnabled) ? ' and <code>/add-product</code>' : '' ?> to draft
    </p>

    <?php // Uploads here land in the media library, the same as every other admin upload. ?>
    <form id="agent-upload-form" action="/admin/media/upload" method="post" enctype="multipart/form-data" hidden>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="context" value="blog">
        <input type="hidden" name="redirect" value="/admin/agent">
        <input type="file" id="agent-upload-input" name="media_files[]" accept="image/*" multiple>
    </form>
</div>
<aside class="agent-workspace__rail" aria-label="Operations and agent activity">
    <section class="admin-card agent-workspace__panel" aria-labelledby="agent-health-title">
        <h3 id="agent-health-title">Agent activity</h3>
        <p>Last 24 hours. P95 is the time within which 95% of recorded runs finished.</p>
        <?php if (!is_array($agentMonitoring ?? null)): ?>
            <p role="status">Activity is temporarily unavailable.</p>
        <?php else: ?>
            <?php foreach (['support' => 'Customer support', 'admin' => 'Owner agent'] as $surface => $label): $row = $agentMonitoring[$surface]; ?>
                <div class="agent-workspace__metric">
                    <h4><?= e($label) ?></h4>
                    <dl>
                        <div><dt>Runs</dt><dd><?= (int)$row['requests'] ?></dd></div>
                        <div><dt>Error rate</dt><dd><?= $row['error_percent'] === null ? '—' : e(number_format($row['error_percent'], 1) . '%') ?></dd></div>
                        <div><dt>P95 time</dt><dd><?= $row['p95_ms'] === null ? '—' : e(number_format($row['p95_ms'] / 1000, 2) . ' s') ?></dd></div>
                    </dl>
                </div>
            <?php endforeach; ?>
            <p>Owner drafts: <?= (int)$agentMonitoring['admin']['draft'] ?> generated · <?= (int)$agentMonitoring['admin']['manual'] ?> manual · <?= (int)$agentMonitoring['admin']['draft_unavailable'] ?> unavailable</p>
        <?php endif; ?>
    </section>
    <section class="admin-card agent-workspace__panel" aria-labelledby="agent-traces-title" data-agent-traces>
        <h3 id="agent-traces-title">Recent agent runs</h3>
        <p>Inspect the latest 12 traced runs. A failed provider attempt can still produce a response through retry or fallback.</p>
        <?php if (!is_array($agentMonitoring ?? null)): ?>
            <p role="status">Recent traces are temporarily unavailable.</p>
        <?php elseif (empty($agentTraces)): ?>
            <p>No traced runs yet.</p>
        <?php else: ?>
            <div class="agent-trace-filters" data-trace-filters hidden>
                <label>Agent
                    <select data-trace-surface>
                        <option value="">Both agents</option>
                        <option value="support">Customer support</option>
                        <option value="admin">Enterprise Agent</option>
                    </select>
                </label>
                <label>Show
                    <select data-trace-result>
                        <option value="">All recent runs</option>
                        <option value="provider">Runs with a failed provider attempt</option>
                        <option value="error">Failed runs</option>
                    </select>
                </label>
                <p role="status" data-trace-count></p>
            </div>
        <?php endif; ?>
        <?php foreach ($agentTraces ?? [] as $run):
            $meta = $run['meta'];
            $providerFailed = false;
            foreach ($meta['steps'] ?? [] as $step) {
                if (($step['kind'] ?? '') === 'provider' && in_array($step['outcome'] ?? '', ['http_error', 'transport_error'], true)) {
                    $providerFailed = true;
                    break;
                }
            }
            $recordedAt = strtotime((string)($run['created_at'] ?? ''));
            ?>
            <details class="agent-trace" data-surface="<?= e($meta['surface'] ?? '') ?>" data-outcome="<?= e($meta['outcome'] ?? '') ?>" data-provider-failed="<?= $providerFailed ? 'yes' : 'no' ?>">
                <summary>
                    <?= ($meta['surface'] ?? '') === 'support' ? 'Support' : 'Enterprise' ?> · <?= e(str_replace('_', ' ', $meta['outcome'] ?? '')) ?> · <?= (int)($meta['duration_ms'] ?? 0) ?> ms
                    <?php if ($recordedAt !== false || $providerFailed): ?>
                        <span class="agent-trace-summary">
                            <?php if ($recordedAt !== false): ?><time datetime="<?= e(gmdate('c', $recordedAt)) ?>"><?= e(gmdate('M j, H:i', $recordedAt)) ?> UTC</time><?php endif; ?>
                            <?php if ($providerFailed): ?><?= $recordedAt !== false ? ' · ' : '' ?>Provider attempt failed<?php endif; ?>
                        </span>
                    <?php endif; ?>
                </summary>
                <p>Trace <code><?= e($meta['trace_id']) ?></code></p>
                <?php if (empty($meta['steps'])): ?><p>No provider or tool call.</p><?php endif; ?>
                <?php foreach ($meta['steps'] ?? [] as $step): ?>
                    <p><?= e($step['kind']) ?>: <?= e($step['name']) ?>
                        <?= isset($step['model']) ? e($step['model']) : '' ?>
                        <?= !empty($step['status']) ? 'HTTP ' . (int)$step['status'] : '' ?>
                        · <?= e(str_replace('_', ' ', $step['outcome'])) ?> · <?= (int)$step['duration_ms'] ?> ms
                    </p>
                <?php endforeach; ?>
            </details>
        <?php endforeach; ?>
    </section>
    <nav class="admin-card agent-workspace__panel" aria-label="Operations links">
        <h3>Open a workflow</h3>
        <?php if ($__campaignsEnabled): ?><a href="/admin/campaigns">Manage therapy products <span aria-hidden="true">↗</span></a><?php endif; ?>
        <a href="/admin/contact-submissions">Review session requests and contacts <span aria-hidden="true">↗</span></a>
        <a href="/admin/support-tickets">Review support tickets <span aria-hidden="true">↗</span></a>
    </nav>
</aside>
</div>

<script src="/assets/agent-traces.js?v=<?= filemtime(__DIR__ . '/../../assets/agent-traces.js') ?>" defer></script>
<script id="agent-attachables" type="application/json"><?= json_encode($attachables ?? [], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?></script>


<script>
(function () {
    const input = document.getElementById('agent-input');
    const form = document.getElementById('agent-form');
    const messages = document.getElementById('agent-messages');
    const submit = document.getElementById('agent-submit');
    const suggestions = document.getElementById('agent-suggestions');
    const clear = document.getElementById('agent-clear');
    const welcome = messages.firstElementChild.cloneNode(true);
    clear.hidden = false;
    clear.addEventListener('click', function () {
        if (submit.disabled) return;
        messages.replaceChildren(welcome.cloneNode(true));
        input.value = '';
        autoGrow();
        suggestions.style.display = '';
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

    function autoGrow() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    }
    input.addEventListener('input', autoGrow);

    // Enter sends, Shift+Enter makes a new line — the convention users expect.
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });

    suggestions.addEventListener('click', function (e) {
        const btn = e.target.closest('.agent-suggestion');
        if (!btn || input.disabled) return;
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
        csrf.value = document.querySelector('input[name="_csrf"]')?.value || '';
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

    function closeMentions() { if (mentionBox) mentionBox.classList.remove('is-open'); }

    function openMentions(query) {
        if (!mentionBox) return;
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
            item.addEventListener('click', function () {
                input.value = input.value.replace(/@([A-Za-z0-9._-]*)$/, '@' + hit.name + ' ');
                closeMentions();
                input.focus();
                autoGrow();
            });
            mentionBox.appendChild(item);
        });
        mentionBox.classList.add('is-open');
    }

    input.addEventListener('input', function () {
        const match = input.value.slice(0, input.selectionStart).match(/@([A-Za-z0-9._-]*)$/);
        if (match) { openMentions(match[1]); } else { closeMentions(); }
    });
    input.addEventListener('blur', function () { setTimeout(closeMentions, 150); });

    // ── Upload ─────────────────────────────────────────────────────────────
    const uploadBtn = document.getElementById('agent-upload-btn');
    const uploadInput = document.getElementById('agent-upload-input');
    if (uploadBtn && uploadInput) {
        uploadBtn.addEventListener('click', function () { uploadInput.click(); });
        uploadInput.addEventListener('change', function () {
            if (uploadInput.files && uploadInput.files.length) {
                document.getElementById('agent-upload-form').submit();
            }
        });
    }

    window.askAgent = async function (e, retryMessage = null, retryRow = null) {
        e.preventDefault();
        const msg = retryMessage ?? input.value.trim();
        if (!msg || submit.disabled) return false;

        if (retryMessage === null) {
            addRow('user', escapeHtml(msg).replace(/\n/g, '<br>'));
            input.value = ''; autoGrow();
        }
        input.disabled = true; submit.disabled = true; clear.disabled = true;
        suggestions.style.display = 'none';

        const thinking = addRow('bot', '', retryRow);
        if (retryRow) retryRow.remove();
        const progress = window.AgentRequestStatus.start(thinking);
        const response = document.createElement('div');
        thinking.appendChild(response);
        let failed = false, retry = false, mode, traceId;
        const abort = new AbortController();
        const timeout = setTimeout(() => abort.abort(), 35000);

        try {
            const body = 'message=' + encodeURIComponent(msg)
                + '&_csrf=' + encodeURIComponent(document.querySelector('input[name="_csrf"]')?.value || '');
            const resp = await fetch('/admin/agent/ask', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
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
            }
            if (data.missing && data.missing.length) {
                addRow('bot', 'I could not find ' + data.missing.map(m => '<code>@' + escapeHtml(m) + '</code>').join(', ')
                    + '. Type <code>@</code> to see what is available.');
            }
        } catch (err) {
            failed = true;
            retry = true;
            thinking.className = 'agent-bubble agent-bubble--error';
            response.textContent = 'Could not reach the server. Check your connection and try again.';
        } finally {
            clearTimeout(timeout);
            progress.finish(failed, mode);
        }
        if (/^[a-f0-9]{16}$/.test(traceId || '')) {
            const reference = document.createElement('small');
            reference.textContent = 'Trace ' + traceId;
            reference.style.display = 'block';
            response.append(reference);
        }
        if (retry) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline btn-sm';
            button.textContent = 'Retry request';
            button.addEventListener('click', event => window.askAgent(event, msg, thinking.parentElement));
            response.append(button);
        }

        input.disabled = false; submit.disabled = false; clear.disabled = false;
        input.focus();
        messages.scrollTop = messages.scrollHeight;
        return false;
    };
})();
</script>

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
            Configure the agent under <a href="/admin/integrations">Admin → Integrations</a>.
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

    <noscript><p>Enable JavaScript to use chat. The operations links below open the corresponding admin pages.</p></noscript>
    <div class="agent-composer-wrap" id="agent-composer-wrap" hidden>
        <form id="agent-form" class="agent-composer" action="/admin/agent/ask" method="post">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <label for="agent-input">Message</label>
            <textarea id="agent-input" name="message" rows="1" required
                      placeholder="Ask a question"
                      aria-describedby="agent-hint" aria-controls="agent-mentions"></textarea>
            <div id="agent-mentions" class="agent-mentions" role="group" aria-label="Available attachments" hidden></div>
            <div class="agent-composer__actions">
                <button type="button" class="agent-attach" id="agent-upload-btn"
                        title="Uploads are added to the media library">Upload image</button>
                <button type="submit" class="agent-send" id="agent-submit">Send message</button>
            </div>
        </form>
        <p class="agent-hint" id="agent-hint">
            Type <code>@</code> to find a page or image. Shift+Enter starts a new line.
        </p>
        <p class="agent-composer__status" id="agent-composer-status" role="status"></p>
    </div>

    <div class="agent-suggestion-group" id="agent-suggestion-group" hidden>
        <h4>Suggested prompts</h4>
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
    </div>

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
                if (($step['kind'] ?? '') === 'provider' && \App\Services\AgentRunTrace::providerFailed((string)($step['outcome'] ?? ''))) {
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


<script src="/assets/admin-agent.js?v=<?= filemtime(__DIR__ . '/../../assets/admin-agent.js') ?>" defer></script>

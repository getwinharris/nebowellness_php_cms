const assert = require('node:assert/strict');
const fs = require('node:fs');
const test = require('node:test');
const vm = require('node:vm');
const { webcrypto } = require('node:crypto');

const conversationScript = fs.readFileSync(require.resolve('../assets/agent-conversation.js'), 'utf8');
const script = fs.readFileSync(require.resolve('../assets/support-chat.js'), 'utf8');

function element(tag) {
    return {
        tag, dataset: {}, children: [], events: {},
        addEventListener(type, listener) { this.events[type] = listener; },
        append(...children) { this.children.push(...children); },
        get childElementCount() { return this.children.length; },
        setAttribute() {},
        focus() { this.focused = true; },
        set innerHTML(value) { assert.fail('Unexpected HTML assignment: ' + value); },
    };
}

function boot({ storage = new Map(), fetch, hasTurns = false } = {}) {
    const launcher = element('button'), panel = element('section'), log = element('div');
    const clear = element('button'), close = element('button'), form = element('form'), starter = element('button');
    const message = element('textarea');
    message.value = '';
    panel.dataset.hasTurns = hasTurns ? '1' : '0';
    panel.dataset.conversation = '1'.repeat(32);
    panel.hidden = true;
    log.firstElementChild = element('p');
    panel.dataset.supportKey = 'support-test';
    panel.querySelector = () => close;
    starter.dataset.supportPrompt = 'Where is the clinic?';
    form.elements = { message, _csrf: { value: 'fixture-token' } };
    form.contains = node => node === message || node === starter;
    form.querySelectorAll = selector => selector === '[data-support-prompt]' ? [starter] : [message, starter];
    form.reset = () => { message.value = ''; };
    let submissions = 0;
    form.requestSubmit = () => { submissions++; };
    const document = {
        querySelector: () => launcher,
        getElementById: id => ({ 'support-panel': panel, 'support-form': form, 'support-log': log, 'support-clear': clear, 'support-prompts': element('details') })[id],
        createElement: element,
    };
    class FormData extends Map {
        constructor() { super([['message', message.value]]); }
    }
    vm.runInNewContext(conversationScript + '\n' + script, {
        document, FormData, URLSearchParams, AbortController, TextEncoder, setTimeout, clearTimeout, fetch,
        window: { AgentRequestStatus: { start: () => ({ finish() {} }) } },
        sessionStorage: {
            getItem: key => storage.get(key) ?? null,
            setItem: (key, value) => storage.set(key, value),
            removeItem: key => storage.delete(key),
        },
        crypto: webcrypto,
    });
    return {
        message, log, starter, storage,
        open: async () => { launcher.events.click(); await new Promise(resolve => setImmediate(resolve)); },
        submissions: () => submissions,
        submit: value => form.events.submit({ preventDefault() {}, submitter: { value } }),
    };
}

function response(reply, actions = []) {
    return { ok: true, headers: { get: () => null }, json: async () => ({ reply, actions }) };
}

test('a starter leaves the typed draft and submission count unchanged', () => {
    const chat = boot();
    chat.message.value = 'Keep my draft';
    chat.starter.events.click();
    assert.equal(chat.message.value, 'Keep my draft');
    assert.equal(chat.submissions(), 0);
    assert.equal(chat.message.focused, true);
});

test('live and restored reply markup remains text and only allowed links render', async () => {
    const reply = '<em>Clinic hours</em>';
    const chat = boot({ fetch: async () => response(reply, [
        { type: 'navigate', path: '/contact', label: 'Contact' },
        { type: 'navigate', path: '//example.invalid', label: 'External' },
    ]) });
    chat.message.value = 'Clinic hours?';
    await chat.submit();
    assert.doesNotMatch(JSON.stringify([...chat.storage]), /Clinic hours/);
    const restored = boot({ hasTurns: true, fetch: async (url, { body }) => {
        assert.equal(url, '/support/ask');
        assert.equal(body.get('conversation_action'), 'history');
        assert.equal(body.get('_csrf'), 'fixture-token');
        return { ok: true, json: async () => ({
            conversation: '1'.repeat(32), turns: [{ question: 'Clinic hours?', response: {
                reply, actions: [
                    { type: 'navigate', path: '/contact', label: 'Contact' },
                    { type: 'navigate', path: '//example.invalid', label: 'External' }
                ]
            } }]
        }) };
    }});
    await restored.open();
    for (const current of [chat, restored]) {
        const turn = current.log.children[0];
        assert.equal(turn.children[1].children[1], ' ' + reply);
        assert.deepEqual(turn.children[1].children.map(node => node.tag).filter(Boolean), ['strong']);
        assert.deepEqual(turn.children[2].children.map(node => node.href), ['/contact']);
    }
});

test('an uncertain ticket retries with the same identifier after reload', async () => {
    const ids = [];
    const question = 'Synthetic ticket';
    const first = boot({ fetch: async (_url, options) => {
        ids.push(options.body.get('request_id'));
        throw Error('response lost');
    } });
    first.message.value = question;
    await first.submit('ticket');
    assert.equal(first.message.value, question);
    assert.doesNotMatch(JSON.stringify([...first.storage]), /Synthetic ticket/);

    const second = boot({ storage: first.storage, fetch: async (_url, options) => {
        ids.push(options.body.get('request_id'));
        return response('Received');
    } });
    second.message.value = question;
    await second.submit('ticket');
    assert.match(ids[0], /^[a-f0-9]{32}$/);
    assert.equal(ids[1], ids[0]);
    assert.equal(second.storage.has('support-test:pending-ticket'), false);
});

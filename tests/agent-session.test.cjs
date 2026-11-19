const assert = require('node:assert/strict');
const { spawn } = require('node:child_process');
const { randomBytes } = require('node:crypto');
const { mkdtemp, rm } = require('node:fs/promises');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const test = require('node:test');

test('both chat routes resume their own JSON session, reuse accepted requests and clear old context', async t => {
    const dir = await mkdtemp(path.join(os.tmpdir(), 'nebo-session-'));
    t.after(() => rm(dir, { recursive: true, force: true }));
    const session = randomBytes(16).toString('hex');
    const providerPrompts = [];
    const provider = http.createServer(async (req, res) => {
        let raw = '';
        for await (const chunk of req) raw += chunk;
        providerPrompts.push(JSON.parse(raw).messages[0].content);
        res.end(JSON.stringify({ choices: [{ message: { content: 'The fixture clinic offers programmes.' } }] }));
    });
    await new Promise(resolve => provider.listen(0, '127.0.0.1', resolve));
    t.after(() => new Promise(resolve => provider.close(resolve)));

    async function request(surface, fields, identity) {
        const proc = spawn('php', [path.join(__dirname, 'fixtures/agent-session.php'), dir, session], {
            env: {
                ...process.env, BAPX_TEST_MODE: '1', AI_API_KEY: 'synthetic',
                BAPX_AI_ENDPOINT: 'http://127.0.0.1:' + provider.address().port, AGENT_MODEL: 'fixture',
            },
        });
        let body = '', errors = '';
        proc.stdout.on('data', chunk => { body += chunk; });
        proc.stderr.on('data', chunk => { errors += chunk; });
        const finished = new Promise((resolve, reject) => {
            proc.on('error', reject);
            proc.on('close', code => code === 0 ? resolve() : reject(Error(errors)));
        });
        proc.stdin.end(JSON.stringify({ surface, fields, identity }));
        await finished;
        return JSON.parse(body);
    }

    const support = await request('support', null, 'synthetic-owner');
    const owner = await request('admin');
    const sessions = { support, admin: owner };
    const questions = { support: 'Which public programmes are available?', admin: 'Summarise the clinic operations this week.' };
    assert.notEqual(support.conversation, owner.conversation);
    for (const surface of ['support', 'admin']) {
        const fields = { _csrf: 'fixture-token', conversation: sessions[surface].conversation };
        const turn = { ...fields, turn_id: randomBytes(16).toString('hex'), message: questions[surface] };
        const first = await request(surface, turn);
        assert.equal(first.status, 200);
        const key = surface === 'admin' ? 'answer' : 'reply';
        assert.equal(first.body[key], 'The fixture clinic offers programmes.');
        const calls = providerPrompts.length;
        const replay = await request(surface, turn);
        assert.equal(replay.body[key], first.body[key]);
        assert.equal(providerPrompts.length, calls);
        assert.equal((await request(surface, { ...turn, message: 'different message' })).status, 409);
        const resumed = await request(surface, { ...fields, conversation_action: 'history' });
        assert.match(resumed.body.turns[0].trace_id, /^[a-f0-9]{16}$/);
        assert.deepEqual(resumed.body.turns.map(each => each.question), [questions[surface]]);
        const next = await request(surface, { ...turn, turn_id: randomBytes(16).toString('hex'), message: 'Please explain in more detail.' });
        assert.equal(next.status, 200);
        const prompt = providerPrompts.at(-1);
        assert.ok(prompt.includes(questions[surface]));
        assert.ok(!prompt.includes(questions[surface === 'support' ? 'admin' : 'support']));
    }
    const fields = { _csrf: 'fixture-token', conversation: support.conversation };
    await request('support', { ...fields, message: 'My support tickets', turn_id: randomBytes(16).toString('hex') });
    assert.equal((await request('support', { ...fields, conversation_action: 'history' })).body.turns.length, 2);
    const invalid = await request('support', { ...fields, _csrf: 'invalid', conversation_action: 'clear' });
    assert.equal(invalid.status, 419);
    const cleared = await request('support', { ...fields, conversation_action: 'clear' });
    assert.deepEqual(cleared.body.turns, []);
    assert.notEqual(cleared.body.conversation, support.conversation);
    assert.equal((await request('support', { ...fields, conversation_action: 'history' })).status, 409);
    const oldOwner = { _csrf: 'fixture-token', conversation: owner.conversation, conversation_action: 'history' };
    assert.equal((await request('admin', oldOwner)).body.turns.length, 2);
    assert.equal((await request('admin', oldOwner, 'second-owner')).status, 409);
});

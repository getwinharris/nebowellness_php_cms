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
        const payload = JSON.parse(raw);
        providerPrompts.push(payload.messages[0].content);
        assert.ok(payload.tools.length > 0);
        const prompt=payload.messages[0].content;
        if (prompt.includes('Question: Summarise current clinic operations without a lookup')) {
            res.end(JSON.stringify({choices:[{finish_reason:'stop',message:{content:'There are 999 requests.'}}]}));
            return;
        }
        if (prompt.includes('Question: Draft a short fictional welcome sentence') || prompt.includes('question: Hello there, good morning') || prompt.includes('How many support enquiries are open?') || prompt.includes('What is the published initial consultation fee?')) {
            const value=prompt.includes('How many support enquiries')?'There are 999 support enquiries.':prompt.includes('published initial consultation fee')?'The fee is ₹999.':prompt.includes('fictional welcome')?'**Welcome** to a brighter day.':'Good morning! How can I help?';
            res.end(JSON.stringify({choices:[{finish_reason:'stop',message:{content:value}}]}));
            return;
        }
        const articleQuery=prompt.includes('Find published blog articles about sleep');
        const appointmentQuery=prompt.includes('Summarise historical appointments');
        const toolName=articleQuery?'search_articles':appointmentQuery?'appointment_summary':
            payload.tools.some(t => t.function.name === 'clinic_context') ? 'clinic_context' : 'owner_dashboard_summary';
        const args=articleQuery?JSON.stringify({query:'sleep'}):'{}';
        const answer=articleQuery?'See the retrieved published articles.':appointmentQuery?'Historical appointments: 0.':'The fixture clinic offers programmes.';
        if (!payload.messages.some(m => m.role === 'tool')) {
            res.end(JSON.stringify({ choices: [{ finish_reason: 'tool_calls', message: { tool_calls: [{ id: 'call_fixture', type: 'function', function: { name: toolName, arguments: args } }] } }] }));
        } else {
            assert.equal(payload.messages.at(-1).tool_call_id, 'call_fixture');
            assert.equal(payload.messages.at(-2).tool_calls[0].id,'call_fixture');
            assert.ok(!JSON.parse(payload.messages.at(-1).content).error);
            res.end(JSON.stringify({ choices: [{ finish_reason: 'stop', message: { content: answer } }] }));
        }
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
        assert.equal(first.body.response_mode, 'model_with_retrieval');
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
    // A greeting and creative task genuinely reach the model without an irrelevant
    // tool; a provider that guesses a current clinic fact must still fail closed.
    const hello=await request('support',{message:'Hello there, good morning'});
    assert.equal(hello.status,200);
    assert.equal(hello.body.reply,'Good morning! How can I help?');
    assert.equal(hello.body.response_mode,'model_direct');
    const creative=await request('admin',{_csrf:'fixture-token',message:'Draft a short fictional welcome sentence'});
    assert.equal(creative.status,200);
    assert.equal(creative.body.answer,'**Welcome** to a brighter day.');
    assert.equal(creative.body.response_mode,'model_direct');
    const guessedFee=await request('support',{message:'What is the published initial consultation fee?'});
    assert.equal(guessedFee.status,503);
    assert.ok(!guessedFee.body.reply);
    const guessedCount=await request('admin',{_csrf:'fixture-token',message:'How many support enquiries are open?'});
    assert.equal(guessedCount.status,502);
    assert.ok(!guessedCount.body.answer);
    const guessedReport=await request('admin',{_csrf:'fixture-token',message:'Summarise current clinic operations without a lookup'});
    assert.equal(guessedReport.status,502);
    assert.ok(!guessedReport.body.answer);
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
    // Actual provider-selected native reads generate fresh server-projected UI.
    const articleTurn=await request('support',{message:'Find published blog articles about sleep'});
    assert.equal(articleTurn.status,200);
    assert.equal(articleTurn.body.response_mode,'model_with_retrieval');
    assert.equal(articleTurn.body.a2ui.version,'1.0');
    assert.ok(articleTurn.body.a2ui.createSurface.components.some(c=>c.title==='Published article search'));
    const appointmentFields={_csrf:'fixture-token',conversation:owner.conversation,
        turn_id:randomBytes(16).toString('hex'),message:'Summarise historical appointments'};
    const appointmentTurn=await request('admin',appointmentFields);
    assert.equal(appointmentTurn.status,200);
    assert.ok(appointmentTurn.body.a2ui.createSurface.components.some(c=>c.label==='Total historical appointments'&&c.value==='0'));
    const restoredAppointment=await request('admin',appointmentFields);
    assert.equal(restoredAppointment.body.answer,appointmentTurn.body.answer);
    assert.ok(!('a2ui' in restoredAppointment.body),'No stale structured record replay');
    assert.ok(!(await request('admin',oldOwner)).body.turns.some(t=>'a2ui' in t.response));
    assert.ok(!('a2ui' in hello.body));
    assert.ok(!('a2ui' in guessedCount.body));

    assert.equal((await request('admin', oldOwner, 'second-owner')).status, 409);
});

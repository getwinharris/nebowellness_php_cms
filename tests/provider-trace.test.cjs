const assert = require('node:assert/strict');
const { execFile } = require('node:child_process');
const http = require('node:http');
const path = require('node:path');
const { test } = require('node:test');
const { promisify } = require('node:util');

const run = promisify(execFile);
const fixture = path.join(__dirname, 'fixtures/provider-trace.php');

test('native tool rounds for both Google models and support outage keep distinct provider measurements', async t => {
    const received = [];
    const server = http.createServer(async (req, res) => {
        let raw = '';
        for await (const chunk of req) raw += chunk;
        const payload = JSON.parse(raw);
        received.push(payload);
        if (req.url.includes('unavailable')) {
            res.writeHead(503).end(JSON.stringify({error: {message: 'Synthetic confidential diagnostic'}}));
            return;
        }
        let part = {text: '<final_answer>Synthetic confidential answer</final_answer>'};
        if (req.url.includes('thought-only')) {
            part = {thought: true, text: 'Synthetic confidential reasoning'};
        } else if (payload.tools && !payload.contents.some(turn => turn.parts.some(part => part.functionResponse))) {
            part = {functionCall: {id: 'gemma-call-fixture', name: payload.tools[0].functionDeclarations.some(f=>f.name==='clinic_context')?'clinic_context':'appointment_summary', args: {}}, thoughtSignature: 'opaque-fixture-signature'};
        }
        res.end(JSON.stringify({candidates: [{content: {parts: [part]}}]}));
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    t.after(() => new Promise(resolve => server.close(resolve)));

    async function exercise(surface, model, scenario = 'normal') {
        const { stdout } = await run('php', [fixture, surface, scenario], {
            env: {
                ...process.env, BAPX_TEST_MODE: '1', AGENT_MODEL: model,
                AI_API_KEY: 'synthetic-test-key', BAPX_AI_ENDPOINT: `http://127.0.0.1:${server.address().port}/googleapis`,
            },
            timeout: 10000,
        });
        assert.doesNotMatch(stdout, /Synthetic confidential|synthetic-test-key/);
        return JSON.parse(stdout);
    }

    const owner = await exercise('admin', 'fixture-primary');
    assert.ok(received[0].tools[0].functionDeclarations.length > 0);
    assert.equal(received[1].contents.at(-1).parts[0].functionResponse.name, 'appointment_summary');
    assert.equal(received[1].contents.at(-1).parts[0].functionResponse.id,'gemma-call-fixture');
    assert.deepEqual(received[1].contents.at(-2).parts[0],{functionCall:{id:'gemma-call-fixture',name:'appointment_summary',args:{}},thoughtSignature:'opaque-fixture-signature'});
    assert.ok(received[0].systemInstruction.parts[0].text);
    assert.ok(!received[0].tools[0].functionDeclarations.find(f=>f.name==='appointment_summary').parameters);
    assert.equal(received[0].tools[0].functionDeclarations.find(f=>f.name==='search_clinic_knowledge').parameters.properties.query.type,'STRING');
    assert.equal(received[0].tools[0].functionDeclarations.find(f=>f.name==='search_clinic_knowledge').parameters.type,'OBJECT');
    assert.ok(!received[0].tools[0].functionDeclarations.some(f=>f.strict));
    assert.deepEqual(owner.trace.steps.map(step => step.kind), ['provider', 'tool', 'provider']);
    const tool = owner.report.provider_details[0];
    assert.equal(tool.mode, 'tools');
    assert.equal(tool.surface, 'admin');
    assert.equal(tool.attempts, 2);
    assert.equal(tool.failures, 0);
    assert.ok(Number.isInteger(tool.p95_ms));

    const fallback = await exercise('admin', 'gemma-4-26b-a4b-it');
    assert.ok(received.at(-1).tools);
    assert.equal(fallback.report.provider_details[0].mode, 'tools');
    assert.deepEqual(fallback.trace.steps.map(step => step.kind), ['provider','tool','provider']);

    const publicGemma=await exercise('support','gemma-4-31b-it');
    assert.equal(received.at(-1).contents.at(-1).parts[0].functionResponse.name,'clinic_context');
    assert.deepEqual(publicGemma.trace.steps.map(step=>step.kind),['provider','tool','provider']);
    assert.ok(!received.at(-1).tools[0].functionDeclarations.some(f=>f.name==='owner_dashboard_summary'));
    const support = await exercise('support', 'fixture-unavailable');
    const failed = support.report.provider_details[0];
    assert.equal(failed.model, 'fixture-unavailable');
    assert.equal(failed.surface, 'support');
    assert.equal(failed.mode, 'tools');
    assert.deepEqual(failed.failure_types, {'HTTP 503': 1});
    assert.equal(support.report.surfaces.support.fallback, 0);
    assert.equal(support.report.surfaces.support.error, 1);
    const onlyThoughts = await exercise('support', 'fixture-thought-only');
    assert.equal(onlyThoughts.trace.steps[0].outcome, 'unusable_response');
    assert.equal(onlyThoughts.report.surfaces.support.error, 1);

    const finalRound = await exercise('admin', 'fixture-last-round', 'last-round');
    assert.deepEqual(finalRound.trace.steps.map(step => step.mode || step.kind), ['tools']);
    assert.equal(finalRound.trace.steps[0].outcome,'unusable_response');

});

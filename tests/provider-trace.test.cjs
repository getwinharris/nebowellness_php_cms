const assert = require('node:assert/strict');
const { execFile } = require('node:child_process');
const http = require('node:http');
const path = require('node:path');
const { test } = require('node:test');
const { promisify } = require('node:util');

const run = promisify(execFile);
const fixture = path.join(__dirname, 'fixtures/provider-trace.php');

test('a tool round, plain fallback model and support outage keep distinct provider measurements', async t => {
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
        } else if (received.length === 1 || (req.url.includes('last-round') && payload.tools)) {
            part = {functionCall: {name: 'appointment_summary', args: {}}};
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
    assert.deepEqual(owner.trace.steps.map(step => step.kind), ['provider', 'tool', 'provider']);
    const tool = owner.report.provider_details[0];
    assert.equal(tool.mode, 'tools');
    assert.equal(tool.surface, 'admin');
    assert.equal(tool.attempts, 2);
    assert.equal(tool.failures, 0);
    assert.ok(Number.isInteger(tool.p95_ms));

    const fallback = await exercise('admin', 'gemma-4-26b-a4b-it');
    assert.equal(received.at(-1).tools, undefined);
    assert.equal(fallback.report.provider_details[0].mode, 'plain');

    const support = await exercise('support', 'fixture-unavailable');
    const failed = support.report.provider_details[0];
    assert.equal(failed.model, 'fixture-unavailable');
    assert.equal(failed.surface, 'support');
    assert.equal(failed.mode, 'plain');
    assert.deepEqual(failed.failure_types, {'HTTP 503': 1});
    assert.equal(support.report.surfaces.support.fallback, 1);
    assert.equal(support.report.surfaces.support.error, 0);
    const onlyThoughts = await exercise('support', 'fixture-thought-only');
    assert.equal(onlyThoughts.trace.steps[0].outcome, 'unusable_response');
    assert.equal(onlyThoughts.report.surfaces.support.fallback, 1);

    const finalRound = await exercise('admin', 'fixture-last-round', 'last-round');
    assert.deepEqual(finalRound.trace.steps.map(step => step.mode || step.kind), ['tools', 'tool', 'plain']);
    assert.match(received.at(-1).contents[0].parts[0].text, /"functionResponse":\{"name":"appointment_summary","response":\{/);

});

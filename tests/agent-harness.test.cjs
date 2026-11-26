const assert = require('node:assert/strict');
const {execFile} = require('node:child_process');
const {promisify} = require('node:util');
const http = require('node:http');
const path = require('node:path');
const {test} = require('node:test');
const run = promisify(execFile);

test('completion harness correlates native calls, rejects non-tool answers and enforces permission and budgets', async t => {
    const calls=[];
    const service=http.createServer(async(req,res)=>{
        let raw=''; for await (const part of req) raw+=part;
        const body=JSON.parse(raw);
        calls.push(body);
        const sent=body.messages.filter(m=>m.role==='tool');
        const mode=body.model;
        const send=message=>res.end(JSON.stringify({choices:[{finish_reason:message.tool_calls?'tool_calls':'stop',message}],usage:{prompt_tokens:41,completion_tokens:7,total_tokens:48}}));
        const call=(id,name,args)=>({id,type:'function',function:{name,arguments:args}});
        if(mode==='outage'){res.writeHead(503).end(JSON.stringify({error:{message:'fixture only'}}));return;}
        if(mode==='no-tool'){send({content:'QA-ORCHID-731'});return;}
        if(mode==='cycle'){send({tool_calls:[call('loop-'+sent.length,'clinic_context','{}')]});return;}
        if(mode==='duplicate'){send({tool_calls:[call('same','clinic_context','{}'),call('same','clinic_context','{}')]});return;}
        if(!sent.length){
            if(mode==='rejected'){send({tool_calls:[call('call_denied','owner_dashboard_summary','{}')]});return;}
            if(mode==='invalid'){send({tool_calls:[call('call_invalid','clinic_context','{"email":"someone@example.test"}')]});return;}
            if(mode==='bad-json'){send({tool_calls:[call('call_invalid','clinic_context','[]')]});return;}
            send({tool_calls:[call('call_real','clinic_context','{}')]});return;
        }
        if(mode==='rejected'||mode==='invalid'||mode==='bad-json'){
            assert.ok(JSON.parse(sent.at(-1).content).error);
            send({content:'QA-ORCHID-731'});return;
        }
        assert.equal(sent.at(-1).tool_call_id,'call_real');
        assert.equal(body.messages.at(-2).tool_calls[0].id,'call_real');
        assert.equal(JSON.parse(sent.at(-1).content).fixture_value,'QA-ORCHID-731');
        if(mode==='echo'){send({content:'I am the Nebo Wellness support bot designed to assist you with wellness programs and clinic information. You can find more information about our services at /blog.'});return;}
        send({content:'<final_answer>QA-ORCHID-731</final_answer>'});
    });
    await new Promise(resolve=>service.listen(0,'127.0.0.1',resolve));
    t.after(()=>new Promise(resolve=>service.close(resolve)));
    async function exercise(model){
        const {stdout}=await run('php',[path.join(__dirname,'fixtures/agent-harness.php')],{env:{...process.env,BAPX_TEST_MODE:'1',AI_API_KEY:'fixture-only',BAPX_AI_ENDPOINT:'http://127.0.0.1:'+service.address().port,AGENT_MODEL:model},timeout:10000});
        return JSON.parse(stdout);
    }
    const ok=await exercise('success');
    assert.equal(ok.usable,true);
    assert.equal(ok.answer,'QA-ORCHID-731');
    assert.deepEqual(ok.trace.steps.map(s=>s.kind),['provider','tool','provider']);
    assert.equal(ok.trace.steps[1].origin,'model');
    assert.equal(ok.trace.steps[2].total_tokens,48);
    assert.match(ok.trace.steps[1].call_ref,/^[0-9a-f]{16}$/);
    assert.doesNotMatch(JSON.stringify(ok.trace),/QA-ORCHID-731|fixture-only/);
    assert.equal(calls[0].tool_choice,'required');
    for(const {function:f} of calls[0].tools){
        assert.equal(f.strict,true);
        assert.equal(f.parameters.additionalProperties,false);
        assert.deepEqual(Object.keys(f.parameters.properties),f.parameters.required);
    }
    assert.ok(!calls[0].tools.some(t=>t.function.name==='owner_dashboard_summary'));
    for(const model of ['outage','no-tool','cycle','duplicate','rejected','invalid','bad-json','echo']){
        const fail=await exercise(model);
        assert.equal(fail.usable,false,model);
        assert.equal(fail.answer,null,model);
    }
});

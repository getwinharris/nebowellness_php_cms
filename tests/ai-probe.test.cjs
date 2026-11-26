const assert=require('node:assert/strict');
const {spawn}=require('node:child_process');
const {test}=require('node:test');
const http=require('node:http'),path=require('node:path'),os=require('node:os');
const {mkdtemp,rm}=require('node:fs/promises');
test('diagnostics reject a successful HTTP response that does not complete the challenge',async t=>{
 const calls=[];
 let scenario='wrong';
 const provider=http.createServer(async(req,res)=>{
  let raw=''; for await(const part of req)raw+=part;
  const b=JSON.parse(raw);calls.push({url:req.url,body:b});
  const google=req.url.includes(':generateContent');
  const sent=google?b.contents.flatMap(m=>m.parts).filter(p=>p.functionResponse):b.messages.filter(m=>m.role==='tool');
  const text=google?b.contents[0].parts[0].text:b.messages[0].content;
  const final=(value,finish=true)=>google?{candidates:[{...(finish?{finishReason:'STOP'}:{}),content:{parts:[{text:value}]}}]}:{choices:[{...(finish?{finish_reason:'stop'}:{}),message:{content:value}}]};
  const call=(name='connection_probe',args={})=>google?{candidates:[{finishReason:'STOP',content:{parts:[{functionCall:{name,args,id:'probe-call'}}]}}]}:{choices:[{finish_reason:'tool_calls',message:{tool_calls:[{type:'function',id:'probe-call',function:{name,arguments:JSON.stringify(args)}}]}}]};
  if(scenario==='outage'){res.writeHead(503).end(JSON.stringify({error:{message:'private upstream marker'}}));return;}
  let response;
  if(scenario==='wrong')response=final('unrelated nonempty text');
  else if(scenario==='bad-json'){res.end('not JSON');return;}
  else if(scenario==='missing-finish')response=final(text.match(/probe-[0-9a-f]{16}/)?.[0]||'unknown',false);
  else if(scenario==='text')response=final(text.match(/probe-[0-9a-f]{16}/)?.[0]||'unknown');
  else if(scenario==='no-tool')response=final('connection ok');
  else if(!sent.length)response=scenario==='wrong-tool'?call('owner_dashboard_summary'):scenario==='bad-args'?call('connection_probe',{x:1}):call();
  else {
   const result=google?sent[0].functionResponse.response:JSON.parse(sent[0].content);
   assert.equal(google?sent[0].functionResponse.id:sent[0].tool_call_id,'probe-call');
   if(['bad-args','wrong-tool'].includes(scenario))assert.ok(result.error);
   response=final(scenario==='wrong-final'?'wrong':result.challenge||'no challenge',scenario!=='tool-no-finish');
  }
  res.end(JSON.stringify(response));
 });
 await new Promise(resolve=>provider.listen(0,'127.0.0.1',resolve));
 t.after(()=>new Promise(resolve=>provider.close(resolve)));
 async function probe(mode='text',target='',google=false,model='fixture'){
  const args=['ai:probe',mode];if(target)args.push(target);
  const proc=spawn('bash',[path.join(__dirname,'..','cli','bapXcms'),...args],{env:{...process.env,BAPX_TEST_MODE:'1',AI_API_KEY:'synthetic-only',BAPX_AI_ENDPOINT:'http://127.0.0.1:'+provider.address().port+(google?'/googleapis':''),AGENT_MODEL:model}});
  let stdout='',stderr='';proc.stdout.on('data',c=>stdout+=c);proc.stderr.on('data',c=>stderr+=c);
  const code=await new Promise((resolve,reject)=>{proc.on('error',reject);proc.on('close',resolve)});
  return {code,stdout,stderr,result:()=>JSON.parse(stdout)};
 }
 let r=await probe();
 assert.notEqual(r.code,0,'An unrelated nonempty HTTP 200 reply must not pass');
 assert.equal(r.result().reason,'unexpected_answer');
 scenario='text';
 for(const google of [false,true]){
  r=await probe('text','',google);assert.equal(r.code,0,r.stdout);assert.equal(r.result().ok,true);
  assert.equal(r.result().mode,'text');
  assert.doesNotMatch(r.stdout,/probe-[0-9a-f]{16}|synthetic-only/);
 }
 for(const bad of ['bad-json','missing-finish']){
  scenario=bad;r=await probe();assert.notEqual(r.code,0,bad);assert.equal(r.result().ok,false);
 }
 scenario='tools';
 for(const google of [false,true]){
  const n=calls.length;r=await probe('tools','',google);
  assert.equal(r.code,0,r.stdout);const v=r.result();assert.equal(v.ok,true);assert.equal(v.tool_calls,1);
  assert.deepEqual(v.steps.map(s=>s.kind),['provider','tool','provider']);
  const first=calls[n].body;
  const names=google?first.tools[0].functionDeclarations.map(f=>f.name):first.tools.map(t=>t.function.name);
  assert.deepEqual(names,['connection_probe']);
  assert.doesNotMatch(JSON.stringify(first),/probe-[0-9a-f]{16}/);
  assert.doesNotMatch(r.stdout,/probe-[0-9a-f]{16}|synthetic-only/);
 }
 for(const bad of ['no-tool','wrong-tool','bad-args','wrong-final','tool-no-finish','outage']){
  scenario=bad;r=await probe('tools');assert.notEqual(r.code,0,bad);assert.equal(r.result().ok,false);
  assert.doesNotMatch(r.stdout,/private upstream marker/);
 }
 scenario='text';
 const n=calls.length;
 r=await probe('text','not-allowed');assert.notEqual(r.code,0);assert.equal(calls.length,n);
 r=await probe('text','gemma-4-26b-a4b-it',true,'gemma-4-31b-it');assert.equal(r.code,0,r.stdout);
 assert.match(calls.at(-1).url,/gemma-4-26b-a4b-it:generateContent$/);
 const dir=await mkdtemp(path.join(os.tmpdir(),'nebo-probe-route-'));
 t.after(()=>rm(dir,{recursive:true,force:true}));
 async function route(fields,role='admin'){
  const proc=spawn('php',[path.join(__dirname,'fixtures/ai-probe-route.php'),dir],{env:{...process.env,BAPX_TEST_MODE:'1',AI_API_KEY:'synthetic-only',BAPX_AI_ENDPOINT:'http://127.0.0.1:'+provider.address().port,AGENT_MODEL:'fixture'}});
  let out='',err='';proc.stdout.on('data',c=>out+=c);proc.stderr.on('data',c=>err+=c);
  const done=new Promise((resolve,reject)=>proc.on('close',code=>code===0?resolve():reject(Error(err))));
  proc.stdin.end(JSON.stringify({fields,role}));await done;return JSON.parse(out);
 }
 scenario='tools';
 let post=await route({probe_mode:'tools',probe_model:'fixture'});
 assert.equal(post.status,302);assert.equal(post.result.ok,true);assert.equal(post.result.tool_calls,1);
 assert.doesNotMatch(JSON.stringify(post),/probe-[0-9a-f]{16}|synthetic-only/);
 const beforeDenials=calls.length;
 for(const [fields,role] of [[{probe_mode:'tools'},'guest'],[{probe_mode:'tools'},'customer'],[{probe_mode:'tools',_csrf:'bad'},'admin']]){
  post=await route(fields,role);assert.equal(post.result,null);assert.equal(calls.length,beforeDenials);
 }
 post=await route({probe_mode:['tools']});assert.equal(post.result.reason,'invalid_target');assert.equal(calls.length,beforeDenials);

});

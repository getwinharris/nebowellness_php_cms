const assert=require('node:assert/strict');
const {spawn}=require('node:child_process');
const {mkdtemp,rm}=require('node:fs/promises');
const os=require('node:os'),path=require('node:path'),http=require('node:http');
const {test}=require('node:test');
test('blog drafting and enhancement use completed inference and never replace media with a failed suggestion',async t=>{
 const dir=await mkdtemp(path.join(os.tmpdir(),'nebo-editorial-'));
 t.after(()=>rm(dir,{recursive:true,force:true}));
 let reply={choices:[{finish_reason:'stop',message:{content:'## A practical routine\n\nA fresh, fictional editorial draft for review.'}}]};
 let status=200,calls=[];
 const server=http.createServer(async(req,res)=>{
  let body='';for await(const chunk of req)body+=chunk;
  calls.push(JSON.parse(body));
  res.writeHead(status,{'Content-Type':'application/json'}).end(JSON.stringify(reply));
 });
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 t.after(()=>new Promise(resolve=>server.close(resolve)));
 async function request(route,fields,google=false,role='admin'){
  const proc=spawn('php',[path.join(__dirname,'fixtures/editorial-route.php'),dir],{env:{...process.env,BAPX_TEST_MODE:'1',AI_API_KEY:'synthetic',BAPX_AI_ENDPOINT:'http://127.0.0.1:'+server.address().port+(google?'/googleapis':''),AGENT_MODEL:google?'gemma-4-fixture':'fixture'}});
  let out='',err='';proc.stdout.on('data',c=>out+=c);proc.stderr.on('data',c=>err+=c);
  const done=new Promise((resolve,reject)=>proc.on('close',code=>code===0?resolve():reject(Error(err))));
  proc.stdin.end(JSON.stringify({path:route.startsWith('/')?route:'/admin/blog/'+route,fields,role}));await done;return JSON.parse(out);
 }
 const before=calls.length;
 let denied=await request('ai-draft',{title:'Denied',_csrf:'invalid'});
 assert.equal(denied.status,419);assert.equal(calls.length,before);
 for(const role of ['guest','customer']){
  denied=await request('ai-draft',{title:'Denied'},false,role);
  assert.equal(denied.status,302);assert.equal(calls.length,before);
 }
 let r=await request('ai-draft',{title:'Fictional wellness habits',template:'editorial',content:''});
 assert.equal(calls.length,before+1,'Draft endpoint must reach real provider transport');
 assert.equal(r.status,200);assert.equal(r.body.source,'provider');assert.match(r.body.content,/fresh, fictional/);
 const original='A useful habit.\n\n![Keep this](/assets/images/example.webp)\n\n[Contact](/contact)';
 const originalFields={content:original};
 reply={choices:[{finish_reason:'length',message:{content:'Partial replacement.'}}]};
 r=await request('enhance-content',originalFields);assert.equal(r.status,502);assert.ok(!r.body.content);
 reply={choices:[{finish_reason:'stop',message:{content:'Lost the original image.'}}]};
 r=await request('enhance-content',originalFields);assert.equal(r.status,502);assert.ok(!r.body.content);
 reply={choices:[{finish_reason:'stop',message:{content:original.replace('/contact','/other')}}]};
 r=await request('enhance-content',originalFields);assert.equal(r.status,502);
 reply={choices:[{finish_reason:'stop',message:{content:original.replace('A useful habit.','A clearer everyday habit.')}}]};
 r=await request('enhance-content',originalFields);assert.equal(r.status,200);assert.equal(r.body.source,'provider');assert.match(r.body.content,/clearer/);
 reply={choices:[{finish_reason:'stop',message:{content:'Headline one\nHeadline two'}}]};
 r=await request('enhance-title',{title:'Original'});assert.equal(r.status,502);
 status=503;reply={error:{message:'fixture upstream private diagnostic'}};
 r=await request('enhance-title',{title:'Original'});assert.equal(r.status,503);assert.ok(!r.body.title);assert.doesNotMatch(r.body.error,/No AI API key|private diagnostic/);
 status=200;
 reply={candidates:[{finishReason:'STOP',content:{parts:[{text:'Private reasoning fixture',thought:true},{text:'A completed headline'}]}}]};
 r=await request('enhance-title',{title:'Original'},true);
 assert.equal(r.status,200);assert.equal(r.body.title,'A completed headline');assert.equal(calls.at(-1).generationConfig.thinkingConfig.thinkingLevel,'minimal');
 reply={candidates:[{finishReason:'MAX_TOKENS',content:{parts:[{text:'Incomplete headline'}]}}]};
 r=await request('enhance-title',{title:'Original'},true);assert.equal(r.status,502);assert.ok(!r.body.title);
 reply={choices:[{finish_reason:'stop',message:{content:JSON.stringify({title:'A useful fictional guide',slug:'fixture-guide',category:'wellness',content:'## A fictional topic\\n\\nThis is a complete reviewable editorial proposal for the clinic owner.',og_image:'/invented.webp'})}}]};
 r=await request('/admin/agent/ask',{message:'/create-blog A fictional guide'});
 assert.equal(r.status,200);assert.equal(r.body.draft.mode,'generated');
 assert.equal(r.body.draft.fields.find(f=>f.name==='og_image').value,'');
 assert.match(r.body.draft.fields.find(f=>f.name==='content').value,/complete reviewable/);
 reply={choices:[{finish_reason:'length',message:{content:JSON.stringify({title:'Partial',content:'A truncated draft that looks valid but must not be used.'})}}]};
 r=await request('/admin/agent/ask',{message:'/create-blog A fictional guide'});assert.equal(r.body.draft.mode,'unavailable');
 reply={choices:[{finish_reason:'stop',message:{content:'[Unsafe](javascript:alert(1))'}}]};
 r=await request('enhance-content',{content:'Original article'});assert.equal(r.status,502);
 const n=calls.length;
 r=await request('ai-draft',{title:'Keep my article',content:original});assert.equal(r.status,409);assert.equal(calls.length,n);
 r=await request('enhance-title',{title:['bad']});assert.equal(r.status,422);assert.equal(calls.length,n);
});
test('late suggestions cannot replace changed editor fields or another record',()=>{
 const {sameSnapshot}=require('../assets/blog-editorial.js');
 const a={epoch:1,slug:'fixture',title:'Original',content:'Original text',template:'editorial',sourceUrl:''};
 assert.equal(sameSnapshot(a,{...a}),true);
 for(const key of Object.keys(a))assert.equal(sameSnapshot(a,{...a,[key]:key==='epoch'?2:'changed'}),false,key);
 assert.equal(sameSnapshot(a,null),false);
});

const assert=require('node:assert/strict');
const {test}=require('node:test');
const {spawn,execFileSync}=require('node:child_process');
const {mkdtemp,rm,readFile,writeFile,stat,chmod,symlink,utimes,mkdir,readdir}=require('node:fs/promises');
const os=require('node:os'),path=require('node:path');
const root=path.resolve(__dirname,'..');
const env={...process.env,BAPX_TEST_MODE:'1',APP_URL:'https://journal-fixture.test'};
function php(code,args=[]){
    return JSON.parse(execFileSync('php',['-r','require "app/bootstrap.php"; '+code,...args],{cwd:root,env,encoding:'utf8'}));
}
function asyncPhp(code,args=[]){
    return new Promise((resolve,reject)=>{
        const proc=spawn('php',['-r','require "app/bootstrap.php"; '+code,...args],{cwd:root,env});
        let out='',err='';proc.stdout.on('data',c=>out+=c);proc.stderr.on('data',c=>err+=c);
        proc.once('error',reject);proc.once('close',code=>{if(code!==0)return reject(Error(err));try{resolve(JSON.parse(out));}catch(e){reject(e);}});
    });
}
const id='a'.repeat(32),turn='b'.repeat(32);
const record=(overrides={})=>({id,schema_version:2,surface:'support',actor_type:'signed_in',customer_email:'fixture@example.test',
    created_at:new Date().toISOString().replace(/\.\d+Z$/,'Z'),events:[],omitted_turns:0,prior_history_unavailable:false,...overrides});
const event=(turnId=turn,trace='c'.repeat(16),overrides={})=>({turn_id:turnId,at:new Date().toISOString().replace(/\.\d+Z$/,'Z'),
    question:'api_key=synthetic-secret',reply:'Visible answer',http_status:200,outcome:'model',response_mode:'model_with_retrieval',ticket_id:null,
    trace:{trace_id:trace,steps:[{kind:'tool',name:'published_site_identity',outcome:'ok',duration_ms:3,origin:'model',private_payload:'not retained'}],omitted_steps:0,sources:['/contact'],raw:'not retained'},...overrides});
const persist='$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo json_encode($s->readRecord($argv[3]),JSON_THROW_ON_ERROR);';
test('private JSON journals are atomic, bounded, identity-bound, idempotent and outside the web root',async()=>{
 const parent=await mkdtemp(path.join(os.tmpdir(),'bapxcms-journal-'));const dir=path.join(parent,'archive');
 try{
  const base=record({events:[event()],arbitrary_secret:'not retained'});
  const write=value=>php(persist,[dir,JSON.stringify(value),id]);
  const first=write(base);
  assert.equal(first.events.length,1);assert.equal(first.events[0].question,'api_key: [redacted]');
  assert.doesNotMatch(JSON.stringify(first),/synthetic-secret|not retained|arbitrary_secret|private_payload/);
  assert.equal((await stat(dir)).mode&0o777,0o700);
  const file=path.join(dir,id+'.json');
  assert.equal((await stat(file)).mode&0o777,0o600);
  assert.deepEqual(write(base),first);
  const extra=event('d'.repeat(32),'e'.repeat(16));
  const next=write({...base,events:[extra]});assert.equal(next.events.length,2,'An incomplete snapshot cannot truncate the archive');
  const attempt='$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);try{$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo "false";}catch(Throwable){echo "true";}';
  for(const bad of [{...base,customer_email:'other@example.test'},{...base,events:[{...base.events[0],reply:'Modified recorded answer'}]},{...base,events:Array(101).fill(event())}]){
      assert.equal(php(attempt,[dir,JSON.stringify(bad)]),true);
      assert.deepEqual(JSON.parse(await readFile(file,'utf8')),next);
  }
  // Two independent processes can extend the same prefix without replacing each other's event.
  await Promise.all([asyncPhp(persist,[dir,JSON.stringify({...base,events:[event('1'.repeat(32),'2'.repeat(16))]}),id]),
                     asyncPhp(persist,[dir,JSON.stringify({...base,events:[event('3'.repeat(32),'4'.repeat(16))]}),id])]);
  const merged=JSON.parse(await readFile(file,'utf8'));assert.equal(merged.events.length,4);
  assert.equal(php('$s=new App\\Services\\AgentSessionService(privateStore:new App\\Services\\PrivateAgentJournalStore($argv[1]));echo json_encode($s->get($argv[2]),JSON_THROW_ON_ERROR);',[dir,id]).events.length,4);
  const listing=php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);echo json_encode($s->recent());',[dir]);
  assert.equal(listing[0].turns,4);assert.doesNotMatch(JSON.stringify(listing),/fixture@example|Visible answer/);
  // Interrupted staging never replaces the committed JSON.
  await writeFile(path.join(dir,'.journal-interrupted'),'{"unfinished":',{mode:0o600});
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);echo json_encode($s->readRecord($argv[2])["events"]);',[dir,id]).length,4);
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);echo json_encode($s->readRecord("../private"));',[dir]),null);
  assert.equal(php('try{new App\\Services\\PrivateAgentJournalStore(app_path("storage/private"));echo "false";}catch(Throwable){echo "true";}'),true);
  await chmod(file,0o644);
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);try{$s->readRecord($argv[2]);echo "false";}catch(Throwable){echo "true";}',[dir,id]),true);
  await chmod(file,0o600);
  const other='f'.repeat(32);
  await symlink(file,path.join(dir,other+'.json'));
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);try{$s->readRecord($argv[2]);echo "false";}catch(Throwable){echo "true";}',[dir,other]),true);
  await rm(path.join(dir,other+'.json'));
  const route=role=>execFileSync('php',['tests/fixtures/private-journal-route.php',parent,'/admin/agent-sessions/'+id+'/json',role],{cwd:root,env:{...env,BAPX_AGENT_JOURNAL_TEST_DIR:dir},encoding:'utf8'});
  for(const role of ['guest','customer']){
      const denied=route(role);assert.match(denied,/STATUS:302$/);assert.doesNotMatch(denied,/Visible answer|fixture@example.test/);
  }
  const exported=route('admin');assert.match(exported,/STATUS:200$/);
  assert.deepEqual(JSON.parse(exported.replace(/\nSTATUS:200$/,'')),merged);
  assert.ok((await readdir(dir)).includes(id+'.json'));
 }finally{await rm(parent,{recursive:true,force:true});}
});
test('expiry is explicit, does not revive a stale archive, and cleanup deletes only expired private files',async()=>{
 const parent=await mkdtemp(path.join(os.tmpdir(),'bapxcms-journal-expiry-'));const dir=path.join(parent,'archive');
 try{
  const time=Math.floor(Date.now()/1000)-31*86400;
  const old=record({created_at:new Date(time*1000).toISOString().replace(/\.\d+Z$/,'Z'),events:[event(turn,'c'.repeat(16),{at:new Date(time*1000).toISOString().replace(/\.\d+Z$/,'Z')})]});
  php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1],static fn()=>(int)$argv[3]);$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo "true";',[dir,JSON.stringify(old),String(time)]);
  // Simulate backup restoration changing an expired record's filesystem timestamp.
  await utimes(path.join(dir,id+'.json'),new Date(),new Date());
  const current='9'.repeat(32);
  php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo "true";',[dir,JSON.stringify(record({id:current,events:[] }))]);
  // A current record copied with an old mtime must remain visible and retained.
  await utimes(path.join(dir,current+'.json'),time,time);
  const before=php('$s=new App\\Services\\AgentSessionService(privateStore:new App\\Services\\PrivateAgentJournalStore($argv[1]));echo json_encode(["expired"=>$s->get($argv[2]),"report"=>$s->recentReport()]);',[dir,id]);
  assert.equal(before.expired,null);assert.deepEqual(before.report.items.map(r=>r.id),[current]);
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);try{$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo "false";}catch(DomainException){echo "true";}',[dir,JSON.stringify(old)]),true);
  assert.equal(php('$s=new App\\Services\\AgentSessionService(privateStore:new App\\Services\\PrivateAgentJournalStore($argv[1]));echo json_encode($s->purgeExpired());',[dir]),1);
  assert.ok((await readdir(dir)).includes(current+'.json'));assert.ok(!(await readdir(dir)).includes(id+'.json'));
  assert.equal(php('$s=new App\\Services\\PrivateAgentJournalStore($argv[1]);try{$s->persist(json_decode($argv[2],true,flags:JSON_THROW_ON_ERROR));echo "false";}catch(DomainException){echo "true";}',[dir,JSON.stringify(old)]),true);
 }finally{await rm(parent,{recursive:true,force:true});}
});
